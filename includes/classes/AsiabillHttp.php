<?php

namespace Asiabill\Classes;

use Exception;

class AsiabillHttp
{

    protected $ch;
    protected $headers = [
        "Content-type" => "application/json;charset=UTF-8",
    ];

    protected $sslVersion = false;
    protected $timeout    = 60;

    private $response_info;

    function __construct($url)
    {
        if ($_SERVER['SERVER_ADDR'] == '::1' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
            $this->sslVersion = false;
        }

        $this->ch = curl_init($url);
    }

    function addHeaders($arr)
    {
        foreach ($arr as $key => $value) {
            $this->headers[$key] = $value;
        }
    }

    function curlOption($name, $value)
    {
        curl_setopt($this->ch, $name, $value);
    }

    /**
     * @throws Exception
     */
    function request($parameters, $method): bool
    {

        if (isset($parameters['header'])) {
            $this->addHeaders($parameters['header']);
        }

        $this->curlOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS);

        if ($method == 'POST') {
            if (isset($parameters['body'])) {
                $body = json_encode($parameters['body'], JSON_UNESCAPED_UNICODE);
            }
            else {
                $body = '';
            }

            $this->curlOption(CURLOPT_POST, 1);
            $this->curlOption(CURLOPT_POSTFIELDS, $body);
        }
        elseif ($method == "GET") {
            $this->curlOption(CURLOPT_HTTPGET, 1);
        }
        else {
            $this->curlOption(CURLOPT_CUSTOMREQUEST, $method);
        }

        if (count($this->headers)) {
            $heads = array();
            foreach ($this->headers as $k => $v) {
                $heads[] = $k . ': ' . $v;
            }
            $this->curlOption(CURLOPT_HTTPHEADER, $heads);
        }

        if ($this->timeout) {
            $this->curlOption(CURLOPT_TIMEOUT, $this->timeout);
        }

        $this->curlOption(CURLOPT_RETURNTRANSFER, 1);

        if ($this->sslVersion !== null) {
            $this->curlOption(CURLOPT_SSLVERSION, $this->sslVersion);
            $this->curlOption(CURLOPT_SSL_VERIFYPEER, $this->sslVersion);
            $this->curlOption(CURLOPT_SSL_VERIFYHOST, $this->sslVersion);
        }

        $body = curl_exec($this->ch);
        $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        $err = curl_error($this->ch);
        if (curl_errno($this->ch)) {
            throw new Exception('cURL Error #: ' . $err);
        }

        $this->response_info = [
            'Request URL' => curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL),
            'Status code' => $code,
            'Response'    => $body
        ];

        curl_close($this->ch);

        if ($this->getCode() == '200') {
            return true;
        }

        return false;
    }

    function getCode()
    {
        return $this->response_info['Status code'];
    }

    function getResponseToArr()
    {
        return json_decode($this->response_info['Response'], true);
    }

    function getResponseInfo(string $key = '')
    {
        if (isset($this->response_info[$key])) {
            return $this->response_info[$key];
        }

        return $this->response_info;
    }


}
