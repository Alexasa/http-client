<?php

class HttpClient
{
    /**
    * inner variables
    */
    private $endpoint;
    private $method;
    private $token;
    
    /**
     * Create new instance.
     *
     * @param  $endpoint    API address
     * @param  $method    method
     */
    public function __construct($endpoint,$method='POST')
    {
        $this->endpoint = $endpoint;
        $this->method = $method;
    }

    /**
     * Main gate for sending request
     *
     * @param $data        Send data, array
     * @return string
     */
    public function send($data)
    {
        if (!isset($this->endpoint)||!isset($data)) return false;
        list($token,$error) = $this->streamPost('OPTIONS');
        if ($token&&!$error) {
            $this->token = $token;
            try {
                $json_data = json_encode($data);
                list($ret,$error) = $this->streamPost($json_data);
            } catch (Throwable $t) {
                $error = $t->getMessage();
            }
        } else $ret = false;
        return $error ? $error : $ret;
    }
    /**
    * Send payload
    * @param $post Send data, json
    * @return array(result,error)
    */
    private function streamPost($post)
    {
        $result = $error = false;
        try {
            $options =
                array(
                    'http' => array(
                        'method' => ($post=='OPTIONS'?'OPTIONS':$this->method),
                    ),
                );
            if($post!='OPTIONS') { 
                $options['http']['header'] =
                    'Content-Type: application/json' . "\r\n"
                    . 'Content-Length: ' . strlen($post) . "\r\n"
                    . 'Authorization: Bearer ' . $this->token . "\r\n";
                $options['http']['content'] = $post;
            }
            $result = file_get_contents($this->endpoint, null, 
                stream_context_create($options)
            );
            if(!$result) {
                $headers = $this->parse_response_header($http_response_header);
                $result = $headers['STATUS'];
            }
        } catch (Throwable $t) {
            $error = $t->getMessage();
        }
        return array($result,$error);
    }
    /**
    * parse_response_header()
    *   Parse $http_response_header produced by file_get_contents().
    *
    * @param array $header
    *   Supposed $http_response_header or array alike.
    * @param array
    *   Assoc array of the parsed version.
    */
    private function parse_response_header($header)
    {
        if (empty($header)) return []; // return empty array
        // parse status line
        $status_line = array_shift($header);
        if (!preg_match('/^(\w+)\/(\d+\.\d+) (\d+) (.+?)$/', $status_line, $matches))
            throw new Exception("misformat status line: {$status_line}");
        return [
            'PROTOCOL' => $matches[1],
            'PROTOCOL_VERSION' => $matches[2],
            'STATUS_CODE' => $matches[3],
            'STATUS' => $matches[4],
        ] + array_reduce($header, function ($carry, $line) {
            // parse content line
            list($key, $value) = explode(':', $line, 2);
            if (!isset($carry[$key])) {
                $carry[$key] = trim($value);
            } else {
                $carry[$key] .= "\n" . trim($value);
            }
            return $carry;
        }, []);
    }
}
