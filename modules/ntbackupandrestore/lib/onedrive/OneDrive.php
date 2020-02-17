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

require_once 'onedrive-config.php';

class OnedriveLib
{
    /**
     * @var string The base URL for API requests.
     */
    const API_URL = 'https://api.onedrive.com/v1.0/drive/';

    /**
     * @var string The base URL for authorization requests.
     */
    const AUTH_URL = 'https://login.live.com/oauth20_authorize.srf';

    /**
     * @var string The base URL for token requests.
     */
    const TOKEN_URL = 'https://login.live.com/oauth20_token.srf';

    /**
     * @var int The maximal size of a file to upload
     */
    const MAX_FILE_UPLOAD_SIZE = 10485760; // 10Mo (10 * 1024 * 1024 = 10 485 760)

    /**
     * @var int The maximal size of the log file
     */
    const MAX_FILE_LOG_SIZE = 200000000; // environs 200Mo (200 000 000)

    /**
     * @var int The maximal number of try for a request
     */
    const MAX_TRY_REQUEST = 3;

    /**
     * @var string The name of the current log file
     */
    const CURRENT_FILE_LOG_NAME = 'current_log_onedrive.txt';

    /**
     * @var string The name of old log file
     */
    const OLD_FILE_LOG_NAME = '_log_onedrive.txt';

    // The current token
    private $token;
    // The client ID
    private $client_id;
    // The client secret
    private $client_secret;
    // The redirect URI
    private $redirect_uri;
    // The sdk uri
    private $sdk_uri;
    // The physic sdk uri
    private $physic_sdk_uri;
    // Instance of NtbrCore
    private $ntbr;


    public function __construct($ntbr, $sdk_uri, $physic_sdk_uri, $token = '')
    {
        $this->client_id        = ONEDRIVE_CLIENT_ID;
        $this->client_secret    = ONEDRIVE_CLIENT_SECRET;
        $this->redirect_uri     = ONEDRIVE_CALLBACK_URI;
        $this->sdk_uri          = $sdk_uri;
        $this->physic_sdk_uri   = $physic_sdk_uri;
        $this->ntbr             = $ntbr;


        if (!empty($token)) {
            $this->token = $token;
        }
    }

    /**
     * Create a curl with default options and any other given options
     *
     * @param   array       $curl_more_options  Further curl options to set. Default array().
     *
     * @return  resource    The curl
     */
    private function createCurl($curl_more_options = array())
    {
        $curl_default_options = array(
            // Default option (http://php.net/manual/fr/function.curl-setopt.php)
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CAINFO => $this->physic_sdk_uri.'certificat.crt'
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
        /*$result = array(
            'success' => true,
            'result' => ''
        );

        $result_curl = curl_exec($curl);

        if ($result_curl === false) {
            $result['success'] = false;
            $this->log($this->ntbr->l('Error while executing the curl:', 'onedrive').' '.curl_error($curl));
        } else {
            if (!is_string($result_curl)) {
                $string_result = print_r($result_curl, true);
                $this->log($string_result);
                $result_curl = $string_result;
            }

            $decoded = Tools::jsonDecode($result_curl);

            if ((empty($decoded) || $decoded == false) && !empty($result_curl)) {
                $decoded = $result_curl;
            }

            if (isset($decoded->error)) {
                if (isset($decoded->error->message)) {
                    $this->log($decoded->error->message);
                } else {
                    $this->log($decoded->error);
                }
                $result['success'] = false;
            } else {
                $result['result'] = $decoded;
            }
        }

        curl_close($curl);

        return $result;*/

        return $this->ntbr->execCurl($curl);
    }

     /**
     * Gets the URL of the log in form. After login, the browser is redirected to
     * the redirect URL, and a code is passed as a GET parameter to this URL.
     *
     * The browser is also redirected to this URL if the user is already logged
     * in.
     *
     * @return  string|bool   The login URL or false.
     *
     */
    public function getLogInUrl()
    {
        if (null === $this->client_id) {
            $this->log('The client ID must be set to call getLoginUrl()');
            return false;
        }

        $scopes = 'onedrive.readwrite offline_access';

        // When using this URL, the browser will eventually be redirected to the
        // callback URL with a code passed in the URL query string (the name of the
        // variable is "code"). This is suitable for PHP.
        $url = self::AUTH_URL
            . '?client_id=' . urlencode($this->client_id)
            . '&scope=' . urlencode($scopes)
            . '&response_type=code'
            . '&redirect_uri=' . urlencode($this->redirect_uri)
            . '&display=popup';

        return $url;
    }

    /**
     * Performs a call to the OneDrive API using the GET method.
     *
     * @param   string  $path   The path of the API call.
     *
     * @return  array   The response of the execution of the curl.
     */
    public function apiGet($path)
    {
        $url = self::API_URL.$path.'?access_token='.urlencode($this->token);
        $curl = $this->createCurl();

        curl_setopt($curl, CURLOPT_URL, $url);
        return $this->execCurl($curl);
    }

    /**
     * Performs a call to the OneDrive API using the DELETE method.
     *
     * @param   string  $path   The path of the API call.
     *
     * @return  bool    The success or failure of the action.
     */
    public function apiDelete($path)
    {
        $url = self::API_URL.$path.'?access_token='.urlencode($this->token);

        $curl = $this->createCurl();

        $options = array(
            CURLOPT_URL           => $url,
            CURLOPT_CUSTOMREQUEST => 'DELETE'
        );

        curl_setopt_array($curl, $options);

        $result = $this->execCurl($curl);

        return $result['success'];
    }

    /**
     * Performs a call to the OneDrive API using the PUT method.
     *
     * @param   string      $path           The path of the API call.
     * @param   resource    $stream         The data stream to upload.
     * @param   array       $headers        Optional infos for the header. Default: null.
     * @param   string      $contentType    The MIME type of the data stream, or null if unknown. Default: null.
     * @param   float       $filesize           The size of the stream
     *
     * @return  array       The result of the execution of the curl.
     */
    public function apiPut($path, $stream, $headers = array(), $contentType = null, $filesize = 0)
    {
        $curl = $this->createCurl();
        //$stats = fstat($stream);

        $headers[] = 'Authorization: Bearer '.$this->token ;

        if (null !== $contentType) {
            $headers[] = 'Content-Type: '.$contentType;
        }

        if (!(float)$filesize) {
            $stats      = fstat($stream);
            $filesize   = $stats[7];
        }

        $options = array(
            CURLOPT_URL         => $path,
            CURLOPT_HTTPHEADER  => $headers,
            CURLOPT_PUT         => true,
            CURLOPT_INFILE      => $stream,
            CURLOPT_INFILESIZE  => $filesize,
        );

        curl_setopt_array($curl, $options);
        return $this->execCurl($curl);
    }

    /**
     * Performs a call to the OneDrive API using the POST method.
     *
     * @param   string      $url    The url of the API call.
     * @param   array       $data   The data to pass in the body of the request.
     *
     * @return  array           The result of the execution of the curl.
     */
    public function apiPost($url, $data = array(), $curl_header = array())
    {
        $post_field = '';
        foreach ($data as $nom => $valeur) {
            $post_field .= $nom.'='.$valeur.'&';
        }

        rtrim($post_field, '&');


        $curl = $this->createCurl();

        if (!empty($this->token)) {
            $curl_header[] = 'Authorization: Bearer '.$this->token;
        }

        $options = array(
            CURLOPT_URL        => $url,
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER  =>  $curl_header,
            CURLOPT_POSTFIELDS => $post_field
        );

        curl_setopt_array($curl, $options);

        return $this->execCurl($curl);
    }

    /**
     * Gets the access token
     *
     * @return  array|bool   The access token or false.
     *
     */
    public function getToken($code)
    {
        $datas = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'client_secret' => $this->client_secret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        );

        $result = $this->apiPost(self::TOKEN_URL, $datas);

        if ($result['success'] && !empty($result['result'])) {
            $result['result']['created'] = time();

        } else {
            return false;
        }

        return $result['result'];
    }

    /**
     * Fetches a fresh access token with the given refresh token.
     *
     * @param   string  $refreshToken
     *
     * @return string|bool   The refresh access token or false.
   */
    public function getRefreshToken($refreshToken)
    {
        $datas = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'client_secret' => $this->client_secret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        );

        $result = $this->apiPost(self::TOKEN_URL, $datas);

        if ($result['success'] && !empty($result['result'])) {
            $this->token = $result['result']['access_token'];
            $result['result']['created'] = time();
        } else {
            return false;
        }

        return $result['result'];
    }

    /**
     * Fetches the quota of the current OneDrive account.
     *
     * @return  int     Available quota
     */
    public function fetchQuota()
    {
        $result = $this->apiGet('');
        $quota_available = 0;
        $quota_total = 0;

        if ($result['success'] && !empty($result['result'])) {
            if (isset($result['result']['quota']['remaining'])) {
                $quota_available = $result['result']['quota']['remaining'];
            }
            if (isset($result['result']['quota']['total'])) {
                $quota_total = $result['result']['quota']['total'];
            }
        }

        $this->log($this->ntbr->l('Sending to OneDrive account:', 'onedrive').' '.$this->ntbr->l('Available quota:', 'onedrive').' '.$quota_available.'/'.$quota_total);


        return $quota_available;
    }

    /**
     * Test the connection
     *
     * @return  bool    Connection result
     */
    public function testConnection()
    {
        $result = $this->apiGet('');
        return $result['success'];
    }

    /**
     * Fetches the root directory ID
     *
     * @return  int|bool    ID of the root directory or false
     */
    public function getRootID()
    {
        $result = $this->apiGet('items/root');

        if ($result['success'] && !empty($result['result'])) {
            if (isset($result['result']['id'])) {
                return $result['result']['id'];
            }
        }

        return false;
    }

    /**
     * Fetches the item metadatas
     *
     * @param   int             $id_item    The item id
     *
     * @return  Object|bool     Metadatas or false
     */
    public function getMetadatas($id_item)
    {
        $result = $this->apiGet('/items/'.$id_item);

        if ($result['success'] && !empty($result['result'])) {
            return $result['result'];
        }

        return false;
    }

    /**
     * Transforme the informations so than they can be read and use more easily
     *
     * @param   Object          $infos      The datas to transform
     *
     * @return  array|bool      The informations in a forme more readable or false
     */
    public function transformInfos($infos)
    {
        $readable_infos = array();

        if (!isset($infos['id'])
            || !isset($infos['name'])
            || !isset($infos['size'])
            || !isset($infos['createdDateTime'])
            || !isset($infos['lastModifiedDateTime'])
            || !isset($infos['webUrl'])
        ) {
            return false;
        }

        $readable_infos['id'] = $infos['id'];
        $readable_infos['name'] = $infos['name'];
        $readable_infos['size'] = $infos['size'];
        $readable_infos['date_add'] = $infos['createdDateTime'];
        $readable_infos['date_upd'] = $infos['lastModifiedDateTime'];
        $readable_infos['url'] = $infos['webUrl'];

        // If it's a folder
        if (isset($infos['folder'])) {
            $readable_infos['is_folder'] = 1;
            if (isset($infos['folder']['childCount'])) {
                $readable_infos['nb_child'] = (int)$infos['folder']['childCount'];
            } else {
                $readable_infos['nb_child'] = 0;
            }
        } else {
            $readable_infos['is_folder'] = 0;
            if (isset($infos['file']['mimeType'])) {
                $readable_infos['mime_type'] = $infos['file']['mimeType'];
            } else {
                $readable_infos['mime_type'] = '';
            }
        }

        return $readable_infos;
    }

    /**
     * Fetches the item children list
     *
     * @param   int         $id_item            The item id.
     * @param   bool        $all_children_tree  If a tree of all children must be list. Default false.
     *
     * @return  array|bool  Children or false.
     */
    public function getListChildren($id_item, $all_children_tree = false)
    {
        if ($id_item == '') {
            $id_item = 'root';
        }

        $result = $this->apiGet('/items/'.$id_item.'/children');

        if ($result['success'] && !empty($result['result'])) {
            if (isset($result['result']['value'])) {
                $children = array();

                foreach ($result['result']['value'] as $child) {
                    $infos = $this->transformInfos($child);

                    if ($infos === false) {
                        return false;
                    }

                    if ($all_children_tree && isset($infos['nb_child'])) {
                        if ($infos['nb_child'] > 0) {
                            $list_children = $this->getListChildren($infos['id'], $all_children_tree);

                            if ($list_children !== false && count($list_children)) {
                                $infos['children'] = $list_children;
                            }
                        }
                    }

                    $children[] = $infos;
                }

                return $children;
            }
        }

        return false;
    }

    /**
     * Fetches the item children list
     *
     * @param   String      $file_name      The name of the file
     * @param   String      $id_parent      Id of the folder of the file
     *
     * @return  array|bool  Children or false.
     */
    public function checkExists($file_name, $id_parent)
    {
        if ($id_parent == '') {
            $id_parent = 'root';
        }

        $result = $this->apiGet('/items/'.$id_parent.'/children');

        if ($result['success'] && !empty($result['result'])) {
            if (isset($result['result']['value'])) {
                foreach ($result['result']['value'] as $child) {
                    $infos = $this->transformInfos($child);

                    if ($infos === false) {
                        return false;
                    }

                    if (isset($infos['name']) && isset($infos['id'])) {
                        if (stripos($infos['name'], $file_name) !== false) {
                            return $infos['id'];
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Fetches the item informations
     *
     * @param   int             $id_item            The item id
     * @param   bool            $children           If the children must be list (default false)
     * @param   bool            $all_childre_tree   If a tree of all children must be list (default false)
     *
     * @return  array|bool      The infos or false.
     */
    public function getInfos($id_item, $children = false, $all_childre_tree = false)
    {
        $metadatas = $this->getMetadatas($id_item);

        if ($metadatas !== false) {
            $infos = $this->transformInfos($metadatas);

            if ($infos === false) {
                return false;
            }

            if ($children && isset($infos['nb_child'])) {
                if ($infos['nb_child'] > 0) {
                    $list_children = $this->getListChildren($id_item, $all_childre_tree);

                    if ($list_children !== false && count($list_children)) {
                        $infos['children'] = $list_children;
                    }
                }
            }

            return $infos;
        }

        return false;
    }

    /**
     * Creates a file in the current OneDrive account.
     *
     * @param   string      $name           The name of the OneDrive file to be created.
     * @param   string      $file_path      The path to the file.
     * @param   string      $id_parent      The ID of the OneDrive folder into which to create the OneDrive file, or empty to
     *                                      create it in the OneDrive root folder. Default: ''.
     * @param   int         $nb_part        Current part number.
     * @param   int         $nb_part_total  Total parts to be sent.
     *
     * @return  bool        The result of the file creation
     */
    public function createFile($name, $file_path, $id_parent = '', $nb_part = 1, $nb_part_total = 1)
    {
        if (empty($id_parent)) {
            $id_parent = $this->getRootID();
        }

        if ($id_parent === false) {
            $this->log('WAR'.$this->ntbr->l('Error while creating your file: directory unknow.', 'onedrive').' ('.$file_path.')');
            return false;
        }

        $total_file_size = filesize($file_path);

        if (($total_file_size - self::MAX_FILE_UPLOAD_SIZE) > 0) {
            //Create upload session to upload big file
            $result_create_session = $this->apiPost(self::API_URL.'items/'.$id_parent.':/'.urlencode($name).':/upload.createSession');

            if (!($result_create_session['success'])) {
                return false;
            }

            $session = $result_create_session['result'];
            $this->ntbr->onedrive_session = $session;

            return $this->resumeCreateFile($file_path, $session);
        } else {
            $filesize = $this->ntbr->getFileSize($file_path);
            $file = fopen($file_path, 'r+');

            if ($nb_part_total > 1) {
                $this->log($this->ntbr->l('Sending to OneDrive account:', 'onedrive').' '.$nb_part.'/'.$nb_part_total);
            }

            $result_upload = $this->apiPut(self::API_URL.'items/'.$id_parent.':/'.urlencode($name).':/content', $file, array(), null, $filesize);

            fclose($file);

            return $result_upload['success'];
        }

        return true;
    }

    /**
     * Resume creates a file in the current OneDrive account.
     *
     * @param   string      $file_path      The path to the file.
     * @param   array       $session        The session of the upload.
     * @param   int         $position       The position in the file.
     * @param   int         $nb_part        Current part number.
     * @param   int         $nb_part_total  Total parts to be sent.
     *
     * @return  bool        The result of the file creation
     */
    public function resumeCreateFile($file_path, $session, $position = 0, $nb_part = 1, $nb_part_total = 1)
    {
        $total_file_size    = $this->ntbr->getFileSize($file_path);
        $rest_to_upload     = $total_file_size - $position;
        $start_file_part    = $position;
        //$file_part_size     = self::MAX_FILE_UPLOAD_SIZE;
        //$end_file_part      = self::MAX_FILE_UPLOAD_SIZE - 1;// Size minus 1 cause we start from 0 in the size parts.

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

        if (!isset($session['uploadUrl']) || !$session['uploadUrl'] || $session['uploadUrl'] == '') {
            $this->log('ERR'.$this->ntbr->l('The session is not valid.'));
            return false;
        }

        $upload_url = $session['uploadUrl'];
        $nb_try     = 0;

        while ($rest_to_upload > 0) {
            $part_size_done = $total_file_size - $rest_to_upload;
            $percent        = ($part_size_done/$total_file_size) * 100;

            if ($nb_part_total > 1) {
                $this->log($this->ntbr->l('Sending to OneDrive account:', 'onedrive').' '.$nb_part.'/'.$nb_part_total.$this->ntbr->l(':', 'onedrive').' '.(int)$percent.'%');
            } else {
                $this->log($this->ntbr->l('Sending to OneDrive account:', 'onedrive').' '.(int)$percent.'%');
            }

            $headers  = array(
                'Content-Length: '.$file_part_size,
                'Content-Range: bytes '.$start_file_part.'-'.$end_file_part.'/'.$total_file_size
            );

            $part_file = fread($file, $file_part_size);

            $stream = fopen('php://temp/maxmemory:'.self::MAX_FILE_UPLOAD_SIZE, 'rw');

            if (false === $stream) {
                $this->log('WAR'.$this->ntbr->l('Error while creating your file: the temporary file cannot be opened.', 'onedrive').' ('.$file_path.')');
                return false;
            }

            if (false === fwrite($stream, $part_file)) {
                fclose($stream);
                $this->log('WAR'.$this->ntbr->l('Error while creating your file: the temporary file cannot be written.', 'onedrive').' ('.$file_path.')');
                return false;
            }

            if (!rewind($stream)) {
                fclose($stream);
                $this->log('WAR'.$this->ntbr->l('Error while creating your file: the temporary file cannot be rewound.', 'onedrive').' ('.$file_path.')');
                return false;
            }

            $result_upload = $this->apiPut($upload_url, $stream, $headers);

            /*$this->log($upload_url);
            $this->log($headers);
            $this->log($result_upload);*/

            fclose($stream);

            if (!$result_upload['success']) {
                $nb_try++;

                if ($nb_try >= self::MAX_TRY_REQUEST) {
                    $this->log('WAR'.$this->ntbr->l('OneDrive error while uploading.', 'onedrive'));
                    return false;
                } else {
                    continue;
                }
            }

            if (is_string($result_upload['result']) && strpos($result_upload['result'], 'Our services aren\'t available right now') !== false) {
                $this->log('WAR'.$this->ntbr->l('OneDrive services aren\'t available right now.', 'onedrive'));
                return false;
            }

            $nb_try                 = 0;
            $new_start_file_part    = 0;

            if (isset($result_upload['result']['nextExpectedRanges'][0])) {
                $infos = explode('-', $result_upload['result']['nextExpectedRanges'][0]);

                if (isset($infos[0])) {
                    $new_start_file_part = $infos[0];
                }
            }

            $start_file_part = $new_start_file_part?$new_start_file_part:($end_file_part + 1);
            $this->ntbr->onedrive_position = $start_file_part;
            $rest_to_upload -= $file_part_size;

            if ($rest_to_upload > self::MAX_FILE_UPLOAD_SIZE) {
                $file_part_size = self::MAX_FILE_UPLOAD_SIZE;
                $end_file_part = ($start_file_part + $file_part_size - 1);
            } else {
                $file_part_size = $rest_to_upload;
                $end_file_part = ($start_file_part + $rest_to_upload - 1);
            }

            //refresh
            $this->ntbr->refreshBackup(true);
        }

        fclose($file);

        return true;
    }

    /**
     * Deletes an item in the current OneDrive account.
     *
     * @param   string  $id_item    The unique ID of the item to delete.
     *
     * @return  bool    If the item was successfully deleted.
     */
    public function deleteItem($id_item)
    {
        $nb_try = 0;
        $res = false;

        if (!$id_item) {
            $this->log($this->ntbr->l('OneDrive cannot delete file.', 'onedrive'));
            return false;
        }

        while (!$res && $nb_try < self::MAX_TRY_REQUEST) {
            $nb_try++;
            $res = $this->apiDelete('items/'.$id_item);
        }

        return $res;
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
