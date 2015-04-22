<?php

namespace SpeedUpEssentials\Helper;

class File {

    function encode_url($url) {
        $reserved = array(
            ":" => '!%3A!ui',
            "/" => '!%2F!ui',
            "?" => '!%3F!ui',
            "#" => '!%23!ui',
            "[" => '!%5B!ui',
            "]" => '!%5D!ui',
            "@" => '!%40!ui',
            "!" => '!%21!ui',
            "$" => '!%24!ui',
            "&" => '!%26!ui',
            "'" => '!%27!ui',
            "(" => '!%28!ui',
            ")" => '!%29!ui',
            "*" => '!%2A!ui',
            "+" => '!%2B!ui',
            "," => '!%2C!ui',
            ";" => '!%3B!ui',
            "=" => '!%3D!ui',
            "%" => '!%25!ui',
        );
        return preg_replace(array_values($reserved), array_keys($reserved), rawurlencode($url));
    }

    public static function url_decode($url) {
        return htmlspecialchars_decode(urldecode($url));
    }

    public static function get_content($URL) {

        if (substr($URL, 0, 2) == '//') {
            $URL = 'http:' . $URL;
        }
        $url_exec = self::url_decode($URL);
        if (preg_match('#^https?://#', $url_exec)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_URL, $url_exec);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code != 200) {
                $data = '/*Content of ' . $url_exec . ': <Empty>*/';
            }
            curl_close($ch);
        } else {
            $data = file_get_contents($url_exec);
        }
        return $data;
    }

    public static function put_content($filename, $data) {

        $fp = fopen($filename, 'w');
        $return = fwrite($fp, $data);
        fclose($fp);
        return $return;
        //return file_put_contents($filename, stripslashes($data));
    }

}
