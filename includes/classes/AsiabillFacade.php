<?php

namespace Asiabill\Classes;

class AsiabillFacade
{

    /**
     * 是否移动端
     * @return string
     */
    static function isMobile(): string
    {
        $client_keywords = ['mobile', 'iphone', 'ipod', 'iPad', 'android', 'HarmonyOS', 'wap'];
        if (preg_match("/(" . implode('|', $client_keywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return '1';
        }
        return '0';
    }

    /**
     * 客户端IP
     * @return string
     */
    static function clientIP(): string
    {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) { //优先使用  HTTP_X_FORWARDED_FOR，此值是一个逗号分割的多个IP
            $ips = $_SERVER["HTTP_X_FORWARDED_FOR"];
            $ips = explode(',', $ips);
            $ip = array_shift($ips);
        }
        elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return $ip;
        }

        return $_SERVER["REMOTE_ADDR"];
    }

    /**
     * 获取异步消息体
     * @return array
     */
    static function getWebhookData(): array
    {
        return json_decode(file_get_contents('php://input'), true);
    }


    /**
     * 请求时间
     * @return string
     */
    static function requestTime(): string
    {
        list($t1, $t2) = explode(' ', microtime());
        return sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    /**
     * 唯一识别码
     * @param $millisecond
     * @return string
     */
    static function uniqueId($millisecond): string
    {
        mt_srand($millisecond);
        $char_id = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);
        return substr($char_id, 0, 8) . $hyphen
            . substr($char_id, 8, 4) . $hyphen
            . substr($char_id, 12, 4) . $hyphen
            . substr($char_id, 16, 4) . $hyphen
            . substr($char_id, 20, 12);
    }

    /**
     * 计算字符串的hash值
     * @param array $data
     * @param string $sign_key
     */
    static function signInfo(array $data, string $sign_key = '')
    {
        $sign_arr = [];
        if (isset($data['header'])) {
            $header_str = $data['header']['gateway-no'] . $data['header']['request-id'] . $data['header']['request-time'];
            if (isset($data['header']['version'])) {
                $header_str .= $data['header']['version'];
            }
            $sign_arr[] = $header_str;
        }

        if (isset($data['path'])) {
            sort($data['path']);
            $sign_arr[] = implode('', $data['path']);
        }

        if (isset($data['query'])) {
            ksort($data['query']);
            $sign_arr[] = implode('', $data['query']);
        }

        if (isset($data['body'])) {
            $sign_arr[] = json_encode($data['body'], JSON_UNESCAPED_UNICODE);
        }

        $sign_str = implode('.', array_filter($sign_arr));

        return hash_hmac('sha256', $sign_str, $sign_key);
    }

}