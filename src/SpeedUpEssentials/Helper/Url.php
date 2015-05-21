<?php

namespace SpeedUpEssentials\Helper;

class Url {

    private static $staticDomain;
    private static $baseUri;

    public static function getStaticDomain() {
        return self::$staticDomain;
    }

    public static function getBaseUri() {
        return self::$baseUri;
    }

    public static function setStaticDomain($staticDomain) {
        self::$staticDomain = $staticDomain;
    }

    public static function setBaseUri($baseUri) {
        self::$baseUri = $baseUri;
    }

    public static function normalizeUrl($url, $remove_host = false) {
        $original_url = $url;
        $u = explode('?', $url);
        $ext = pathinfo($u[0], PATHINFO_EXTENSION);
        if ($ext == 'php') {
            return $original_url;
        }
        if (substr($url, 0, 5) != 'data:' && substr($url, 0, 2) != '//' && !preg_match('#^https?://#', $url)) {
            if ($url['0'] == '/') {
                $url = '//' . self::$staticDomain . $url;
            } else {
                $url = '//' . self::$staticDomain . self::$baseUri . $url;
            }
        } elseif (preg_match('#^https?://' . $_SERVER['HTTP_HOST'] . '#', $url)) {
            $url = preg_replace('#^https?://' . $_SERVER['HTTP_HOST'] . '#', '//' . self::$staticDomain, $url);
        }

        if (self::$staticDomain == $_SERVER['HTTP_HOST']) {
            $url = preg_replace('#^//' . $_SERVER['HTTP_HOST'] . '#', '', $url);
        }
        $return = preg_replace('#^https?://#', '//', $url);
        if ($remove_host) {
            $return = str_replace('//' . self::$staticDomain, '', $return);
        }
        return $return;
    }

}
