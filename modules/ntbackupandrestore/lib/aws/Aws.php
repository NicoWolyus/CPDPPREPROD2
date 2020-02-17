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

class AwsLib
{
    /**
     * @var int The maximal size of a file to upload
     */
    const MAX_FILE_UPLOAD_SIZE = 10485760; // 10Mo (10 * 1024 * 1024 = 10 485 760)

    // The current token
    private $token;
    // The client ID
    private $client_id;
    // The client secret
    private $client_secret;
    // The region
    private $region;
    // The bucket
    private $bucket;
    // The service
    private $service;
    // The date of the request
    public $current_date;
    // The sdk uri
    private $sdk_uri;
    // The physic sdk uri
    private $physic_sdk_uri;
    // Instance of NtbrCore
    private $ntbr;


    public function __construct($ntbr, $client_id, $client_secret, $region, $bucket, $sdk_uri, $physic_sdk_uri, $token = '')
    {
        $this->client_id        = $client_id;
        $this->client_secret    = $client_secret;
        $this->region           = $region;
        $this->bucket           = $bucket;
        $this->service          = 's3';
        $this->sdk_uri          = $sdk_uri;
        $this->physic_sdk_uri   = $physic_sdk_uri;
        $this->ntbr             = $ntbr;

        //$current_date = date('Y-m-d H:i:s', strtotime('2013-05-24 00:00:00'));
        $current_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - date('Z'));
        $this->current_date     = $current_date;

        if (!empty($token)) {
            $this->token = $token;
        }
    }

    /**
     * Get the scope.
     *
     * @return  String   The scope to use
     */
    public function getScope()
    {
        return date('Ymd', strtotime($this->current_date)).'/'.$this->region.'/'.$this->service.'/aws4_request';
    }

    /**
     * Get the date to a working ISO8601 format
     *
     * @return  String  Date ISO8601
     */
    public function getDateISO8601()
    {
        return date('Ymd', strtotime($this->current_date)).'T'.date('His', strtotime($this->current_date)).'Z';
    }

    /**
     * Get the canonical and signed headers.
     *
     * @param   array       $headers        Header (array('header1_name' => 'header1_value', 'header2_name' => 'header2_value')).
     *
     * @return  array   Clean canonical and signed header
     */
    public function getCleanHeader($headers)
    {
        $clean_headers  = array(
            'canonical' => '',
            'signed'    => '',
        );

        ksort($headers, SORT_STRING | SORT_FLAG_CASE);

        foreach ($headers as $h_name => $h_value) {
            if ($clean_headers['signed'] != '') {
                $clean_headers['signed'] .= ';';
            }

            $clean_headers['canonical']  .= strtolower($h_name).':'.trim($h_value)."\n";
            $clean_headers['signed']     .= strtolower($h_name);
        }

        return $clean_headers;
    }

    /**
     * Get the canonical request.
     *
     * @param   String      $http_verb      GET|POST|PUT|....
     * @param   String      $ressource      The path.
     * @param   array       $params         Params (array('param1_name' => 'param1_value', 'param2_name' => 'param2_value')).
     * @param   array       $headers        Canonical and signed.
     *
     * @return  String  Canonical
     */
    public function getCanonicalRequest($http_verb, $ressource, $params = array(), $headers = array(), $payload = 'UNSIGNED-PAYLOAD')
    {
        $canonical_query_string = '';
        $canonical_headers      = '';
        $signed_headers         = '';

        if (is_array($params) && count($params)) {
            ksort($params);

            foreach ($params as $p_name => $p_value) {
                if ($canonical_query_string != '') {
                    $canonical_query_string .= '&';
                }

                $canonical_query_string .= rawurlencode($p_name).'='.rawurlencode($p_value);
            }
        }

        if (isset($headers['canonical'])) {
            $canonical_headers = $headers['canonical'];
        }

        if (isset($headers['signed'])) {
            $signed_headers = $headers['signed'];
        }

        return $http_verb."\n".$ressource."\n".$canonical_query_string."\n".$canonical_headers."\n".$signed_headers."\n".$payload;
    }

    /**
     * Get the string to sign.
     *
     * @param   String      $canonical_request  Canonical request
     *
     * @return  String  String to sign
     */
    public function getStringToSign($canonical_request)
    {
        $scope          = $this->getScope();
        $date_time      = $this->getDateISO8601();
        $hash_string    = hash('sha256', $canonical_request);

        return 'AWS4-HMAC-SHA256'."\n".$date_time."\n".$scope."\n".$hash_string;
    }

    /**
     * Get the signing key.
     *
     * @return  String  Signing key
     */
    public function getSigningKey()
    {
        $date_key = hash_hmac('sha256', date('Ymd', strtotime($this->current_date)), 'AWS4'.$this->client_secret, true);
        $date_region_key = hash_hmac('sha256', $this->region, $date_key, true);
        $date_region_service_key = hash_hmac('sha256', $this->service, $date_region_key, true);
        return hash_hmac('sha256', 'aws4_request', $date_region_service_key, true);
    }

    /**
     * Get the signature.
     *
     * @return  String  Signature
     */
    public function getSignature($string_to_sign, $signing_key)
    {
        return hash_hmac('sha256', $string_to_sign, $signing_key);
    }

    /**
     * Get the authorization.
     *
     * @param   String  $url        The path of the API call.
     * @param   String  $action     The action of the API call.
     * @param   array   $params     Parameters of the API call.
     * @param   array   $headers    Headers of the API call.
     * @param   String  $payload    The payload if there is one.
     *
     * @return  String  Authorization
     */
    public function getAuthorization($url, $action, $params = array(), $headers = array(), $payload = '')
    {
        $host = $this->bucket.'.s3.amazonaws.com';
        $hash_payload = hash('sha256', $payload);
        $other_headers = array(
            'host'                  => $host,
            'x-amz-date'            => $this->getDateISO8601(),
            'x-amz-content-sha256'  => $hash_payload,
        );

        $all_headers        = array_merge($headers, $other_headers);
        $scope              = $this->getScope();
        $clean_headers      = $this->getCleanHeader($all_headers);
        $canonical_request  = $this->getCanonicalRequest($action, $url, $params, $clean_headers, $hash_payload);
        //p($canonical_request);
        $string_to_sign     = $this->getStringToSign($canonical_request);
        //p($string_to_sign);
        $signing_key        = $this->getSigningKey();
        $signature          = $this->getSignature($string_to_sign, $signing_key);
        //p($signature);
        $authorization      = 'AWS4-HMAC-SHA256 Credential='.$this->client_id.'/'.$scope.',SignedHeaders='.$clean_headers['signed'].',Signature='.$signature;
        //p($authorization);
        return $authorization;
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
            CURLOPT_CAINFO => $this->physic_sdk_uri.'cacert.pem'
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
        $result = $this->ntbr->execCurl($curl);

        if (!isset($result['result']['Code']) && is_string($result['result']) && strpos($result['result'], '<Error><Code>') !== false) {
            $result['result'] = $this->ntbr->decodeXml($result['result']);
        }

        if (isset($result['result']['Code'])) {
            $result['success'] = 0;

            if (isset($result['result']['Message'])) {
                $this->log($result['result']['Message']);
            }
        }

        return $result;
    }

    /**
     * Performs a call to the AWS API using the GET method.
     *
     * @param   String  $url        The path of the API call.
     * @param   array   $params     Parameters of the API call.
     * @param   array   $headers    Headers of the API call.
     *
     * @return  array   The response of the execution of the curl.
     */
    public function apiGet($url, $params = array(), $headers = array())
    {
        $host = $this->bucket.'.s3.amazonaws.com';
        $authorization  = $this->getAuthorization($url, 'GET', $params, $headers);

        $url = 'https://'.$host.$url;
        $list_params = '';

        if (is_array($params) && count($params)) {
            ksort($params);

            foreach ($params as $p_name => $p_value) {
                if ($list_params != '') {
                    $list_params .= '&';
                } else {
                    $list_params = '?';
                }

                $list_params .= rawurlencode($p_name).'='.rawurlencode($p_value);
            }
        }

        $url .= $list_params;

        $curl = $this->createCurl();

        $curl_header = array(
            'host: '.$host,
            'authorization: '.$authorization,
            'x-amz-date: '.$this->getDateISO8601(),
            'x-amz-content-sha256: '.hash('sha256', ''),
        );

        if (is_array($headers) && count($headers)) {
            foreach ($headers as $key => $value) {
                $curl_header[] = $key.': '.$value;
            }
        }

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER  => $curl_header,
        );

        curl_setopt_array($curl, $options);
        return $this->execCurl($curl);
    }

    /**
     * Performs a call to the AWS API using the POST method.
     *
     * @param   String  $url        The path of the API call.
     * @param   String  $data       The data to upload.
     * @param   array   $params     Parameters of the API call.
     * @param   array   $headers    Headers of the API call.
     * @param   float   $filesize   The size of the string
     *
     * @return  array           The result of the execution of the curl.
     */
    public function apiPost($url, $data = '', $params = array(), $headers = array(), $filesize = 0)
    {
        $host = $this->bucket.'.s3.amazonaws.com';

        $headers['content-md5'] = base64_encode(hash('md5', $data, true));
        $authorization  = $this->getAuthorization($url, 'POST', $params, $headers, $data);

        $url = 'https://'.$host.$url;

        $list_params = '';

        if (is_array($params) && count($params)) {
            ksort($params);

            foreach ($params as $p_name => $p_value) {
                if ($list_params != '') {
                    $list_params .= '&';
                } else {
                    $list_params = '?';
                }

                $list_params .= rawurlencode($p_name).'='.rawurlencode($p_value);
            }
        }

        $url .= $list_params;

        $curl = $this->createCurl();

        if (!(float)$filesize) {
            $filesize   = strlen($data);
        }

        $curl_header = array(
            'host: '.$host,
            'authorization: '.$authorization,
            'content-length: '.$filesize,
            'x-amz-date: '.$this->getDateISO8601(),
            'x-amz-content-sha256: '.hash('sha256', $data),
        );

        if (false != $data && '' != $data) {
            $curl_header[] = 'content-type: text/plain';
        }

        if (is_array($headers) && count($headers)) {
            foreach ($headers as $key => $value) {
                $curl_header[] = $key.': '.$value;
            }
        }

        $options = array(
            CURLOPT_URL             => $url,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLINFO_HEADER_OUT     => true,
            CURLOPT_HTTPHEADER      => $curl_header
        );

        if (false != $data && '' != $data) {
            $options[CURLOPT_POSTFIELDS] = $data;
        }

        curl_setopt_array($curl, $options);
        return $this->execCurl($curl);
    }

    /**
     * Performs a call to the AWS API using the PUT method.
     *
     * @param   String  $url            The path of the API call.
     * @param   String  $data           The data to upload.
     * @param   bool    $multipart      If the upload is in multipart or not.
     * @param   array   $params         Parameters of the API call.
     * @param   array   $headers        Headers of the API call.
     * @param   String  $content_type   The MIME type of the data stream, or null if unknown. Default: null.
     * @param   float   $filesize       The size of the stream
     *
     * @return  array       The result of the execution of the curl.
     */
    public function apiPut($url, $data, $multipart = false, $params = array(), $headers = array(), $content_type = null, $filesize = 0)
    {
        $host = $this->bucket.'.s3.amazonaws.com';

        $headers['content-md5'] = base64_encode(hash('md5', $data, true));
        $authorization  = $this->getAuthorization($url, 'PUT', $params, $headers, $data);

        $url = 'https://'.$host.$url;

        $list_params = '';

        if (is_array($params) && count($params)) {
            ksort($params);

            foreach ($params as $p_name => $p_value) {
                if ($list_params != '') {
                    $list_params .= '&';
                } else {
                    $list_params = '?';
                }

                $list_params .= rawurlencode($p_name).'='.rawurlencode($p_value);
            }
        }

        $url .= $list_params;

        $curl = $this->createCurl();

        if (!(float)$filesize) {
            $filesize   = strlen($data);
        }

        $curl_header = array(
            'host: '.$host,
            'authorization: '.$authorization,
            'content-length: '.$filesize,
            'x-amz-date: '.$this->getDateISO8601(),
            'x-amz-content-sha256: '.hash('sha256', $data),
        );

        if ($multipart && '' !== $multipart) {
            $curl_header[] = 'expect: 100-continue';
        }

        if (null !== $content_type && '' !== $content_type) {
            $curl_header[] = 'content-type: '.$content_type;
        }

        if (is_array($headers) && count($headers)) {
            foreach ($headers as $key => $value) {
                $curl_header[] = $key.': '.$value;
            }
        }

        $options = array(
            CURLOPT_URL             => $url,
            CURLOPT_CUSTOMREQUEST   => 'PUT',
            CURLOPT_HTTPHEADER      => $curl_header,
            CURLOPT_POSTFIELDS      => $data,
            CURLOPT_HEADER          => true,
        );

        curl_setopt_array($curl, $options);
        $result = $this->execCurl($curl);

        if (strpos($result['result'], 'ETag') !== false) {
            $cut_by_line = preg_split('/(\r\n|\n|\r)/', $result['result']);
            $data = array();

            foreach ($cut_by_line as $line) {
                if (strpos($line, ':') === false) {
                    continue;
                }

                $explode = explode(':', $line);

                if (!isset($explode[0])) {
                    continue;
                }

                $data[$explode[0]] = trim(substr(str_replace($explode[0], '', $line), 1));
            }

            $result['result'] = $data;
        }

        return $result;
    }

    /**
     * Performs a call to the AWS API using the DELETE method.
     *
     * @param   String  $url        The path of the API call.
     * @param   array   $params     Parameters of the API call.
     *
     * @return  bool    The success or failure of the action.
     */
    public function apiDelete($url, $params = array())
    {
        $host = $this->bucket.'.s3.amazonaws.com';
        $authorization  = $this->getAuthorization($url, 'DELETE', $params);

        $url = 'https://'.$host.$url;

        $list_params = '';

        if (is_array($params) && count($params)) {
            ksort($params);

            foreach ($params as $p_name => $p_value) {
                if ($list_params != '') {
                    $list_params .= '&';
                } else {
                    $list_params = '?';
                }

                $list_params .= rawurlencode($p_name).'='.rawurlencode($p_value);
            }
        }

        $url .= $list_params;

        $curl = $this->createCurl();

        $curl_header = array(
            'host: '.$host,
            'authorization: '.$authorization,
            'x-amz-date: '.$this->getDateISO8601(),
            'x-amz-content-sha256: '.hash('sha256', ''),
        );

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER  =>  $curl_header,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        );

        curl_setopt_array($curl, $options);
        return $this->execCurl($curl);
    }

    /**
     * Fetches the directories
     *
     * @param   String      $path       The path to look into.
     *
     * @return  array|bool  List of directories or false.
     */
    public function getListDirectories($path = '', $next_continuation_token = '')
    {
        $directories = array();

        $params = array(
            'list-type'             => '2',
            'delimiter'             => '/'
        );

        if ($path && $path != '') {
            $params['prefix'] = $path;
        }

        if ($next_continuation_token && $next_continuation_token != '') {
            $params['continuation-token'] = $next_continuation_token;
        }

        $result = $this->apiGet('/', $params);

        if (!$result['success'] || !isset($result['result']['Contents']) || !isset($result['result']['CommonPrefixes'])) {
            return false;
        }

        if (is_array($result['result']['CommonPrefixes'])) {
            // If there is only one result, we need to transform it so it work the same as multi result
            if (isset($result['result']['CommonPrefixes']['Prefix'])) {
                $result['result']['CommonPrefixes'] = array(0 => $result['result']['CommonPrefixes']);
            }

            foreach ($result['result']['CommonPrefixes'] as $dir) {
                if (!isset($dir['Prefix'])) {
                    continue;
                }

                $subdir = array();
                $children_directories = $this->getListDirectories($dir['Prefix']);

                if ($children_directories && is_array($children_directories)) {
                    $subdir = $children_directories;
                }

                $directories[] = array(
                    'name' => substr(str_replace($path, '', $dir['Prefix']), 0, -1), // Remove the path and the last character ("/") so we only keep the directory name
                    'key' => $dir['Prefix'],
                    'subdir' => $subdir
                );
            }
        }

        if (isset($result['result']['IsTruncated']) && $result['result']['IsTruncated'] == true) {
            if (isset($result['result']['NextContinuationToken']) && $result['result']['NextContinuationToken'] != '') {
                $next_directories = $this->getListDirectories($path, $result['result']['NextContinuationToken']);

                $directories = array_merge($directories, $next_directories);
            }
        }

        return $directories;
    }

    /**
     * Fetches the files
     *
     * @param   String      $path       The path to look into.
     *
     * @return  array|bool  List of files or false.
     */
    public function getListFiles($path = '', $next_continuation_token = '')
    {
        $files = array();

        $params = array(
            'list-type'             => '2',
            'delimiter'             => '/'
        );

        if ($path && $path != '') {
            $params['prefix'] = $path;
        }

        if ($next_continuation_token && $next_continuation_token != '') {
            $params['continuation-token'] = $next_continuation_token;
        }

        $result = $this->apiGet('/', $params);

        if (!$result['success'] || !isset($result['result']['Contents'])) {
            return false;
        }

        // If the result is an array and there is more than just data of the folder
        if (is_array($result['result']['Contents']) && !isset($result['result']['Contents']['Key'])) {
            foreach ($result['result']['Contents'] as $content) {
                // If it is a directory, we ignore it
                if (substr($content['Key'], -1) == '/') {
                    continue;
                }

                $files[] = array(
                    'name' => str_replace($path, '', $content['Key']) // Remove the path so we only keep the file name
                );
            }
        }

        if (isset($result['result']['IsTruncated']) && $result['result']['IsTruncated'] == true) {
            if (isset($result['result']['NextContinuationToken']) && $result['result']['NextContinuationToken'] != '') {
                $next_files = $this->getListFiles($path, $result['result']['NextContinuationToken']);

                $files = array_merge($files, $next_files);
            }
        }

        return $files;
    }

    /**
     * Test the connection
     *
     * @return  bool    Connection result
     */
    public function testConnection()
    {
        $params = array(
            'list-type'             => '2',
            'delimiter'             => '/'
        );

        $result = $this->apiGet('/', $params);

        return $result['success'];
    }

    /**
     * Check if a file exists
     *
     * @param   String  $file_name  The name of the file
     * @param   String  $path       The path to look into.
     *
     * @return  bool    If the file exists.
     */
    public function checkFileExists($file_name, $path)
    {
        $list_files = $this->getListFiles($path);

        if (is_array($list_files) && count($list_files)) {
            foreach ($list_files as $file) {
                if ($file['name'] == $file_name) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a directory exists
     *
     * @param   String  $dir_key   The name of the directory
     *
     * @return  bool    If the directory exists.
     */
    public function checkDirectoryExists($dir_key)
    {
        $clean_path = trim($dir_key, '/');

        if (strpos($clean_path, '/') !== false) {
            $parent_key = str_replace(strrchr($clean_path, '/'), '', $dir_key);
        } else {
            $parent_key = '';
        }

        $list_dirs = $this->getListDirectories($parent_key);
        if (is_array($list_dirs) && count($list_dirs)) {
            foreach ($list_dirs as $dir) {
                if ($dir['key'] == $dir_key) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Deletes a file in the current AWS account.
     *
     * @param   String  $path   The path to the file to delete.
     *
     * @return  bool    If the item was successfully deleted.
     */
    public function deleteFile($path)
    {
        return $this->apiDelete('/'.$path);
    }

    /**
     * Add a lifecycle to abort incomplete multipart upload
     *
     * @param   String  $prefix     The prefix of the files on which use this lifecycle.
     *
     * @return  bool    Result of the request.
     */
    public function addLifecycleAbortIncompleteMultipartUpload($prefix)
    {
        // Must be call every backup, in case the shop's name changed and so the prefix changed to
        $lifecycle = '
            <LifecycleConfiguration>
                <Rule>
                    <ID>ntbackup_abort</ID>
                    <Filter>
                        <Prefix>'.$prefix.'</Prefix>
                    </Filter>
                    <Status>Enabled</Status>
                    <AbortIncompleteMultipartUpload>
                        <DaysAfterInitiation>1</DaysAfterInitiation>
                    </AbortIncompleteMultipartUpload>
                </Rule>
            </LifecycleConfiguration>
        ';

        $params = array(
            'lifecycle' => ''
        );

        $result = $this->apiPut('/', $lifecycle, false, $params, array(), '', strlen($lifecycle));

        return $result['success'];
    }

    /**
     * List incomplete multipart upload
     *
     * @return  array    List all upload ID from incomplete multipart upload.
     */
    public function listMultipartUpload()
    {
        $list_multipart_upload = array();
        $url = '/';

        $params = array(
            'uploads' => ''
        );

        $result = $this->apiGet($url, $params);

        if (!$result['success'] || (!isset($result['result']['Upload']))) {
            return false;
        }

        if (isset($result['result']['Upload']['UploadId'])) {
            $list_multipart_upload[] = $result['result']['Upload']['UploadId'];
        } else {
            foreach ($result['result']['Upload'] as $upload) {
                $list_multipart_upload[] = $upload['UploadId'];
            }
        }

        return $list_multipart_upload;
    }

    /**
     * List all the uploaded parts of a specific multipart upload
     *
     * @param   String  $destination    The destination of the file.
     * @param   String  $upload_id      The ID of th multipart.
     *
     * @return  array    Part of the multipart upload.
     */
    public function listPartsMultipartUpload($destination, $upload_id)
    {
        $url = '/'.$destination;

        $params = array(
            'uploadId' => $upload_id
        );

        $result = $this->apiGet($url, $params);

        if (!$result['success'] || !isset($result['result']['Part'])) {
            return false;
        }

        return $result['result']['Part'];
    }

    /**
     * Init a multi part upload
     *
     * @param   String  $destination   The destination of the file.
     *
     * @return  String  ID of the multipart upload.
     */
    public function initMultipartUpload($destination)
    {
        $url = '/'.$destination;

        $params = array(
            'uploads' => ''
        );

        $result = $this->apiPost($url, '', $params);

        if (!$result['success'] || !isset($result['result']['UploadId'])) {
            return false;
        }


        return $result['result']['UploadId'];
    }

    /**
     * Upload a part of the file
     *
     * @param   String  $destination    The destination of the file.
     * @param   int     $part_num       The number of the part.
     * @param   String  $upload_id      The ID of the multipart upload.
     * @param   String  $data           The content to upload.
     * @param   float   $filesize       The size of the part.
     *
     * @return  Bool    Success or failure of the request.
     */
    public function uploadPart($destination, $part_num, $upload_id, $data, $filesize)
    {
        $url = '/'.$destination;

        $params = array(
            'partNumber' => (int)$part_num,
            'uploadId' => $upload_id,
        );

        if (!is_string($data)) {
            return false;
        }

        $result = $this->apiPut($url, $data, true, $params, array(), '', $filesize);

        if (!isset($result['result']['ETag'])) {
            //$this->log($result);
            $this->log('WAR'. sprintf($this->ntbr->l('The file %s cannot be created'), $destination));
            return false;
        }

        //Save ETag for later
        $this->ntbr->aws_etag[] = array(
            'part_number'   => $part_num,
            'etag'          => $result['result']['ETag'],
        );

        //$this->log($this->ntbr->aws_etag);

        return $result['success'];
    }

    /**
     * Finish a multipart upload
     *
     * @param   String  $destination   The destination of the file.
     * @param   String  $upload_id      The ID of the multipart upload.
     * @param   float   $filesize       The size of the file to upload.
     * @param   array   $list_parts     The list of the parts uploaded.
     *
     * @return  Bool    Success or failure of the request.
     */
    public function completeMultipartUpload($destination, $upload_id, $filesize, $list_parts)
    {
        $url = '/'.$destination;

        $complete_multipart_upload = '<CompleteMultipartUpload>';

        foreach ($list_parts as $part) {
            $complete_multipart_upload .= '<Part>';
                $complete_multipart_upload .= '<PartNumber>'.$part['part_number'].'</PartNumber>';
                $complete_multipart_upload .= '<ETag>'.$part['etag'].'</ETag>';
            $complete_multipart_upload .= '</Part>';
        }

        $complete_multipart_upload .= '</CompleteMultipartUpload>';

        $params = array(
            //$complete_multipart_upload => '',
            'uploadId' => $upload_id,
        );

        $headers = array(
            'content-length: '.$filesize
        );

        //$list_part_aws = $this->listPartsMultipartUpload($destination, $upload_id);
        //$this->log($list_part_aws);
        //$this->log($list_parts);

        $result = $this->apiPost($url, $complete_multipart_upload, $params, $headers);

        if ($result['success'] === false || $result['code_http'] != '200') {
            //$this->log($result);
            $this->log('WAR'.sprintf($this->ntbr->l('The AWS upload of the file %s cannot be completed'), $destination));
            return false;
        }

        return true;
    }

    /**
     * Abort a multipart upload
     *
     * @param   String  $destination   The destination of the file.
     * @param   String  $upload_id      The ID of the multipart upload.
     *
     * @return  Bool    Success or failure of the request.
     */
    public function abortMultipartUpload($destination, $upload_id)
    {
        $url = '/'.$destination;

        $params = array(
            'uploadId' => $upload_id
        );

        $result = $this->apiDelete($url, $params);

        if (!$result['success']) {
            $this->log($this->ntbr->l('Cannot abort the upload ID').' '.$upload_id);
        }

        return $result['success'];
    }

    /**
     * Creates a file in the current AWS account.
     *
     * @param   String      $file_name          The name of the AWS file to be created.
     * @param   String      $file_path          The path to the file to upload.
     * @param   String      $aws_directory_key  The directory where the file must be send.
     * @param   int         $upload_num         The number of the upload part to send.
     * @param   int         $part_num           The number of the file part.
     * @param   int         $total_part_num     The number total of the file part.
     * @param   String      $prefix             Optional. Common name between all files.
     *
     * @return  bool        The result of the file creation
     */
    public function uploadFile($file_name, $file_path, $aws_directory_key, $upload_num, $part_num = 1, $total_part_num = 1, $prefix = '')
    {
        if ($prefix && '' != $prefix) {
            // Add lifecycle abort incomplete multipart upload
            if (!$this->addLifecycleAbortIncompleteMultipartUpload($prefix)) {
                $this->log('WAR'.$this->ntbr->l('The life cycle could not be added'));
            }
        }

        $destination = $aws_directory_key.$file_name;

        // Init multipart upload
        $upload_id = $this->initMultipartUpload($destination);

        if ($upload_id === false) {
            $this->log('WAR'.$this->ntbr->l('The upload cannot be initialize'));
            return false;
        }

        $this->ntbr->aws_upload_id = $upload_id;

        // Resume upload
        return $this->resumeUploadFile($upload_id, $file_name, $aws_directory_key, $file_path, $upload_num, $part_num, $total_part_num, 0);
    }

    /**
     * Resume creates a file in the current AWS account.
     *
     * @param   String      $upload_id          The ID of the upload to continue.
     * @param   String      $file_name          The name of the AWS file to be created.
     * @param   String      $aws_directory_key  The directory where the file must be send.
     * @param   String      $file_path          The path to the file to upload.
     * @param   int         $upload_num         The number of the upload part to send.
     * @param   int         $part_num           The number of the file part.
     * @param   int         $total_part_num     The number total of the file part.
     * @param   float       $position           The position in the file.
     *
     * @return  bool        The result of the file creation
     */
    public function resumeUploadFile($upload_id, $file_name, $aws_directory_key, $file_path, $upload_num, $part_num, $total_part_num, $position)
    {
        $destination = $aws_directory_key.$file_name;

        // Upload part
        $total_file_size    = (float)$this->ntbr->getFileSize($file_path);
        $rest_to_upload     = $total_file_size - $position;
        $start_file_part    = $position;

        if ($total_file_size <= 0) {
            $this->log('WAR'.$this->ntbr->l('The file size is not valid'));
        }

        $file = fopen($file_path, 'r');

        // Go to the last position in the file
        $max_seek = $position;

        // If the file is really big
        if ($position > NtbrCore::MAX_SEEK_SIZE) {
            $max_seek = NtbrCore::MAX_SEEK_SIZE;
        }

        // Set where we were in the file
        if (fseek($file, $max_seek) == -1) {
            $this->log('WAR'.$this->ntbr->l('The file is no longer seekable'));
            // Abort multipart
            $this->abortMultipartUpload($destination, $upload_id);
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
                $this->log('WAR'.$this->ntbr->l('The file is no longer readable.'));
                // Abort multipart
                $this->abortMultipartUpload($destination, $upload_id);
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

        if (!$upload_id || $upload_id == '') {
            $this->log('WAR'.$this->ntbr->l('The upload ID is not valid.'));
            // Abort multipart
            $this->abortMultipartUpload($destination, $upload_id);
            return false;
        }

        while ($rest_to_upload > 0) {
            $part_size_done = $total_file_size - $rest_to_upload;
            $percent        = ($part_size_done/$total_file_size) * 100;

            if ($total_part_num > 1) {
                $this->log($this->ntbr->l('Sending to AWS account:', 'aws').' '.$part_num.'/'.$total_part_num.' : '.(int)$percent.'%');
            } else {
                $this->log($this->ntbr->l('Sending to AWS account:', 'aws').' '.(int)$percent.'%');
            }


            $part_file = fread($file, $file_part_size);

            if (!is_string($part_file)) {
                $this->log('WAR'.$this->ntbr->l('Error while creating your file: the file cannot be read correctly.', 'aws').' ('.$file_path.')');
                // Abort multipart
                $this->abortMultipartUpload($destination, $upload_id);
                return false;
            }

            $result_upload = $this->uploadPart($destination, $upload_num, $upload_id, $part_file, $file_part_size);

            if (!$result_upload) {
                // Abort multipart
                $this->log('WAR'. sprintf($this->ntbr->l('The upload of the file %s failed.'), $file_path));
                $this->abortMultipartUpload($destination, $upload_id);
                return false;
            }

            $start_file_part = $end_file_part + 1;
            $this->ntbr->aws_position = $start_file_part;
            $rest_to_upload -= $file_part_size;

            if ($rest_to_upload > self::MAX_FILE_UPLOAD_SIZE) {
                $file_part_size = self::MAX_FILE_UPLOAD_SIZE;
                $end_file_part = ($start_file_part + $file_part_size - 1);
            } else {
                $file_part_size = $rest_to_upload;
                $end_file_part = ($start_file_part + $rest_to_upload - 1);
            }

            $upload_num++;
            $this->ntbr->aws_upload_part = $upload_num;

            //refresh
            $this->ntbr->refreshBackup(true);
        }

        fclose($file);

        if (count($this->ntbr->aws_etag)) {
            // Complete multipart
            if (!$this->completeMultipartUpload($destination, $upload_id, $total_file_size, $this->ntbr->aws_etag)) {
                $this->abortMultipartUpload($destination, $upload_id);
                return false;
            }
        } else {
            // Abort multipart
            $this->log('WAR'.$this->ntbr->l('No upload was successful.'));
            $this->abortMultipartUpload($destination, $upload_id);
            return false;
        }

        return true;


    }

    /**
     * Fetches the quota of the current AWS account.
     *
     * @return  int     Available quota
     */
    /*public function fetchQuota()
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

        $this->log($this->ntbr->l('Sending to AWS account:', 'aws').' '.$this->ntbr->l('Available quota:', 'aws').' '.$quota_available.'/'.$quota_total);


        return $quota_available;
    }*/

    /**
     * Log()
     *
     * Log message to file
     *
     * @param   String  $message    Message to log
     *
     * @return void
     *
     */
    public function log($message)
    {
        $this->ntbr->log($message);
    }
}
