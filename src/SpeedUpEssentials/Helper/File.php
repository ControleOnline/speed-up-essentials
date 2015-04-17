<?php

namespace SpeedUpEssentials\Helper;

class File {

    public static function get_content($URL) {

        if (substr($URL, 0, 2) == '//') {
            $URL = 'http:' . $URL;
        }
        if (preg_match('#^https?://#', $URL)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $URL);
            $data = curl_exec($ch);
            curl_close($ch);
        } else {
            $data = file_get_contents($URL);
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
