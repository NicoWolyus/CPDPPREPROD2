<?php
/**
* 2013-2019 2N Technologies
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to contact@2n-tech.com so we can send you a copy immediately.
*
* @author    2N Technologies <contact@2n-tech.com>
* @copyright 2013-2019 2N Technologies
* @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class WebdavLib
{
    /**
     * @var int The maximal size of a file to upload
     */
    const MAX_FILE_UPLOAD_SIZE = 62914560; // 60Mo (60 * 1024 * 1024 = 62 914 560)

    // The current server
    private $server;
    // The current user
    private $user;
    // The current password
    private $pass;
    // The sdk uri
    private $sdk_uri;
    // The physic sdk uri
    private $physic_sdk_uri;
    // Instance of NtbrCore
    private $ntbr;


    public function __construct($ntbr, $server, $user, $pass, $sdk_uri, $physic_sdk_uri)
    {
        if (Tools::substr($server, -1) != '/') {
            $server .= '/';
        }

        $this->server = $server;
        $this->user = $user;
        $this->pass = $pass;
        $this->sdk_uri = $sdk_uri;
        $this->physic_sdk_uri = $physic_sdk_uri;
        $this->ntbr = $ntbr;
    }

    /**
     * Create a curl with default options and any other given options
     *
     * @param   array       $curl_more_options  Further curl options to set. Default array().
     * @param   array       $more_header        Further curl headers to set. Default array().
     *
     * @return  resource    The curl
     */
    private function createCurl($curl_more_options = array(), $more_header = array())
    {
        $add_depth = true;

        if (is_array($more_header) && count($more_header)) {
            foreach ($more_header as $m_header) {
                if (strpos($m_header, 'Depth') !== false) {
                    $add_depth = false;
                }
            }
        }

        if ($add_depth) {
            $more_header[] = 'Depth: 1';
        }

        $header = array(

        );

        if (is_array($more_header) && count($more_header)) {
            $header = array_merge($header, $more_header);
        }

        $curl_default_options = array(
            // Default option (http://php.net/manual/fr/function.curl-setopt.php)
            CURLOPT_USERPWD         => $this->user.':'.$this->pass,
            CURLOPT_HTTPAUTH        => CURLAUTH_ANY,
            CURLOPT_HTTPHEADER      => $header,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYPEER  => false,
        );

        $curl = curl_init();

        curl_setopt_array($curl, $curl_default_options);

        if (count($curl_more_options)) {
            curl_setopt_array($curl, $curl_more_options);
        }

        return $curl;
    }

    /**
     * Execute a curl and return it's result
     *
     * @param   resource    $curl       The curl to execute.
     *
     * @return  array       The result of the execution of the curl.
     */
    private function execCurl($curl)
    {
        return $this->ntbr->execCurl($curl);
    }

    /**
     * Performs a call to the WebDAV API using the POST method.
     *
     * @param   string          $url            The url of the API call.
     * @param   String          $data           The data to pass in the body of the request.
     * @param   array           $more_options   The options of the curl.
     * @param   array           $header         The data to pass in the header of the request.
     *
     * @return  array           The result of the execution of the curl.
     */
    public function apiPost($url, $data = '', $more_options = array(), $header = array())
    {
        $options = array(
            CURLOPT_URL => $url
        );

        $header[] = 'content-length: '.strlen($data);

        $curl = $this->createCurl($options, $header);


        if (false != $data && '' != $data) {
            $more_options[CURLOPT_POSTFIELDS] = $data;
        }

        if (!isset($more_options[CURLOPT_CUSTOMREQUEST]) || !$more_options[CURLOPT_CUSTOMREQUEST] || $more_options[CURLOPT_CUSTOMREQUEST] == '') {
            $more_options[CURLOPT_CUSTOMREQUEST] = 'POST';
        }

        curl_setopt_array($curl, $more_options);

        return $this->execCurl($curl);
    }

    /**
     * Performs a call to the WebDAV API using the GET method.
     *
     * @param   string  $url        The url of the API call.
     * @param   array   $options    The options of the curl.
     * @param   array           $header         The data to pass in the header of the request.
     *
     * @return  array   The response of the execution of the curl.
     */
    public function apiGet($url, $options = array(), $header = array())
    {
        $curl = $this->createCurl($options, $header);

        curl_setopt($curl, CURLOPT_URL, $url);
        return $this->execCurl($curl);
    }

    /**
     * Performs a call to the WebDAV API using the PUT method.
     *
     * @param   string      $url                The path of the API call.
     * @param   ressource   $stream             The data to upload.
     * @param   array       $header             The data to pass in the header of the request.
     * @param   array       $other_options      Other options to use in the request.
     * @param   float       $filesize           The size of the stream
     *
     * @return  array       The result of the execution of the curl.
     */
    public function apiPut($url, $stream, $header = array(), $other_options = array(), $filesize = 0)
    {
        //$header[] = 'Content-Type: application/octet-stream';

        $curl = $this->createCurl(array(), $header);

        if (!(float)$filesize) {
            $stats      = fstat($stream);
            $filesize   = $stats[7];
        }

        $options = array(
            CURLOPT_URL         => $url,
            CURLOPT_HTTPHEADER  => $header,
            CURLOPT_PUT         => true,
            CURLOPT_INFILE      => $stream,
            CURLOPT_INFILESIZE  => $filesize,
        );

        if (is_array($other_options) && count($other_options)) {
            $options = array_merge($options, $other_options);
        }

        curl_setopt_array($curl, $options);
        return $this->execCurl($curl);
    }

    /**
     * Get the available quota of the current WebDAV account.
     *
     * @return  int     Available quota
     */
    public function getAvailableQuota()
    {
        $quota_available = 0;

        $header = array(
            'Depth: 0',
            'Content-type: text/xml;',
        );

        $options = array(
            CURLOPT_CUSTOMREQUEST => 'PROPFIND'
        );

        $data = '<?xml version="1.0" ?><d:propfind xmlns:d="DAV:"><d:prop><d:quota-available-bytes/><d:quota-used-bytes/></d:prop></d:propfind>';

        $result = $this->apiPost($this->server, $data, $options, $header);

        if ($result['success'] && !empty($result['result'])) {
            if (isset($result['result']['D_response']['D_propstat']['D_prop']['g0_quota-available-bytes'])) {
                $quota_available = $result['result']['D_response']['D_propstat']['D_prop']['g0_quota-available-bytes'];

                if (!is_numeric($quota_available)) {
                    $this->log($this->ntbr->l('Sending to WebDAV account: No limit', 'webdav'));
                    return  -1;
                }
            }
        }

        $this->log($this->ntbr->l('Sending to WebDAV server:', 'webdav').' '.$this->ntbr->l('Available quota:', 'webdav').' '.$quota_available);

        return $quota_available;
    }

    /**
     * Test the connection
     *
     * @return  bool    Connection result
     */
    public function testConnection()
    {
        $options = array(
            CURLOPT_CUSTOMREQUEST => 'PROPFIND'
        );

        $result = $this->apiGet($this->server, $options);

        return $result['success'];
    }

    /**
     * Upload a file on the WebDAV account
     *
     * @param   string  $file_path          The path of the file.
     * @param   string  $file_destination   The destination of the file.
     * @param   string  $name               The name of the file.
     * @param   int     $position           Position in the file.
     * @param   int     $nb_part            Current part number.
     * @param   int     $nb_part_total      Total parts to be sent.
     *
     * @return  bool    The success or failure of the action.
     */
    public function uploadFile($file_path, $file_destination = '', $name = '', $position = 0, $nb_part = 1, $nb_part_total = 1)
    {
        if (!$name || $name == '') {
            $name = basename($file_path);
        }

        $url                = $this->server.$file_destination.$name;
        $filesize           = (float)$this->ntbr->getFileSize($file_path);
        $total_file_size    = $filesize;
        $rest_to_upload     = $total_file_size - $position;
        $start_file_part    = $position;
        $chunk_count        = round($total_file_size / self::MAX_FILE_UPLOAD_SIZE);

        $file = fopen($file_path, 'r');

        // Go to the last position in the file
        $max_seek = $position;

        // If the file is really big
        if ($position > NtbrCore::MAX_SEEK_SIZE) {
            $max_seek = NtbrCore::MAX_SEEK_SIZE;
        }

        // Set where we were in the file
        if (fseek($file, $max_seek) == -1) {
            $this->log('ERR'.$this->ntbr->l('The file is no longer seekable'));
            return false;
        }

        $position -= $max_seek;

        $max_read = NtbrCore::MAX_READ_SIZE;
        while ($position > 0) {
            if ($position >= $max_read) {
                $size_to_read = $max_read;
            } else {
                $size_to_read = $position;
            }

            if (fread($file, $size_to_read) === false) {
                $this->log('ERR'.$this->ntbr->l('The file is no longer readable.'));
                return false;
            }

            $position -= $size_to_read;
        }

        if ($rest_to_upload > self::MAX_FILE_UPLOAD_SIZE) {
            $file_part_size = self::MAX_FILE_UPLOAD_SIZE;
            $end_file_part  = $start_file_part + $file_part_size - 1;// Size minus 1 cause we start from 0 in the size parts.
        } else {
            $file_part_size = $rest_to_upload;
            $end_file_part  = $start_file_part + $rest_to_upload - 1;// Size minus 1 cause we start from 0 in the size parts.
        }

        while ($rest_to_upload > 0) {
            $options = array();

            if ($chunk_count > 1) {
                $headers  = array(
                    'Content-Length: '.$file_part_size,
                    /*'OC-Chunked: 1 ',
                    'OC-Total-Length: '.$total_file_size,
                    'OC-Chunk-Size: '.$file_part_size,*/
                    'Content-Range: bytes '.$start_file_part.'-'.$end_file_part.'/'.$total_file_size
                );

                $chunk_url = $url;
                //$chunk_url = $url.'-chunking-'.$this->ntbr->webdav_session.'-'.$chunk_count.'-'.$this->ntbr->webdav_nb_chunk;
            } else {
                $headers  = array(
                    'Content-Length: '.$file_part_size,
                );

                $chunk_url = $url;
            }

            if ($chunk_count > 0) {
                $percent = ($this->ntbr->webdav_nb_chunk/$chunk_count) * 100;
            } else {
                $percent = 0;
            }

            if ($nb_part_total > 1) {
                $this->log($this->ntbr->l('Sending to WebDAV account:', 'webdav').' '.$nb_part.'/'.$nb_part_total.$this->ntbr->l(':', 'webdav').' '.(int)$percent.'%');
            } else {
                $this->log($this->ntbr->l('Sending to WebDAV account:', 'webdav').' '.(int)$percent.'%');
            }

            $part_file = fread($file, $file_part_size);

            $stream = fopen('php://temp/maxmemory:'.self::MAX_FILE_UPLOAD_SIZE, 'rw');

            if (false === $stream) {
                $this->log('WAR'.$this->ntbr->l('Error while creating your file: the temporary file cannot be opened.', 'webdav').' ('.$file_path.')');
                return false;
            }

            if (false === fwrite($stream, $part_file)) {
                fclose($stream);
                $this->log('WAR'.$this->ntbr->l('Error while creating your file: the temporary file cannot be written.', 'webdav').' ('.$file_path.')');
                return false;
            }

            if (!rewind($stream)) {
                fclose($stream);
                $this->log('WAR'.$this->ntbr->l('Error while creating your file: the temporary file cannot be rewound.', 'webdav').' ('.$file_path.')');
                return false;
            }

            $result_upload = $this->apiPut($chunk_url, $stream, $headers, $options, $file_part_size);

            fclose($stream);

            if (!$result_upload['success']) {
                return false;
            }

            $start_file_part = ($end_file_part + 1);
            $rest_to_upload -= $file_part_size;

            if ($rest_to_upload > self::MAX_FILE_UPLOAD_SIZE) {
                $file_part_size = self::MAX_FILE_UPLOAD_SIZE;
                $end_file_part = ($start_file_part + $file_part_size - 1);
            } else {
                $file_part_size = $rest_to_upload;
                $end_file_part = ($start_file_part + $rest_to_upload - 1);
            }

            $this->ntbr->webdav_nb_chunk++;
            $this->ntbr->webdav_position = $start_file_part;

            //refresh
            $this->ntbr->refreshBackup(true);
        }


        //$result = $this->apiPut($url, $file, array(), array(), $filesize);

        fclose($file);

        return true;
    }

    /**
     * Delete a file on the WebDAV account
     *
     * @param   string  $file_path          The path of the file on WebDAV.
     *
     * @return  bool    The success or failure of the action.
     */
    public function deleteFile($file_path)
    {
        $header = array(
            'Depth: 0',
        );

        $options = array(
           CURLOPT_CUSTOMREQUEST => 'DELETE'
        );

        $result = $this->apiGet($this->server.$file_path, $options, $header);

        return $result['success'];
    }

    /**
     * Create a folder in the WebDAV account
     *
     * @param   string  $folder_path          The path of the folder on WebDAV.
     *
     * @return  bool    The success or failure of the action.
     */
    public function createFolder($folder_path)
    {
        $options = array(
           CURLOPT_CUSTOMREQUEST => 'MKCOL'
        );

        $result = $this->apiGet($this->server.$folder_path, $options);

        return $result['success'];
    }

    /**
     * Check if the given folder exists
     *
     * @return  bool    If the folder exists
     */
    public function folderExists($folder_path)
    {
        $options = array(
            CURLOPT_CUSTOMREQUEST => 'PROPFIND'
        );

        $result = $this->apiGet($this->server.$folder_path.'/', $options);

        if ($result['success'] && !empty($result['result']) && $result['code_http'] != '404') {
            if (isset($result['result']['D_response'][0])) {
                $response = $result['result']['D_response'][0];
            } elseif ($result['result']['D_response']) {
                $response = $result['result']['D_response'];
            }

            if (isset($response['D_propstat']['D_prop']['lp1_creationdate'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the given file exists
     *
     * @return  bool    If the file exists
     */
    public function fileExists($file_path)
    {
        $options = array(
            CURLOPT_CUSTOMREQUEST => 'PROPFIND'
        );

        $result = $this->apiGet($this->server.$file_path, $options);

        if ($result['success'] && !empty($result['result'])) {
            if (isset($result['result']['D_response'][0])) {
                $response = $result['result']['D_response'][0];
            } elseif ($result['result']['D_response']) {
                $response = $result['result']['D_response'];
            }

            if (isset($response['D_propstat']['D_prop']['lp1_getcontentlength'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all of the files children of the given folder
     *
     * @return  array   List of the files
     */
    public function getFileChildren($folder_path = '')
    {
        $list_files = array();

        $options = array(
            CURLOPT_CUSTOMREQUEST => 'PROPFIND'
        );

        $result = $this->apiGet($this->server.$folder_path, $options);

        if ($result['success'] && !empty($result['result'])) {
            if (isset($result['result']['D_response'][0])) {
                $list_nodes = $result['result']['D_response'];
            } else {
                $list_nodes = $result['result'];
            }

            foreach ($list_nodes as $node) {
                if (!isset($node['D_propstat']['D_prop']['lp1_resourcetype']['D_collection']) && isset($node['D_href'])) {
                    $list_files[] = substr($node['D_href'], (strrpos($node['D_href'], '/') + 1));
                }
            }
        }

        return $list_files;
    }

    /**
     * Log()
     *
     * Log message to file
     *
     * @param   string  $message    Message to log
     *
     * @return void
     *
     */
    public function log($message)
    {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        if ($this->ntbr->getConfig('ACTIVATE_LOG')) {
            $this->ntbr->log($message);
        }
    }
}
