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
        } catch (Throwable $t) {
            $error = $t->getMessage();
        }
        return array($result,$error);
    }
}
