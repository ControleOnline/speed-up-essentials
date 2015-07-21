<?php

namespace SpeedUpEssentials\Helper;

class Cache {

    protected static $config;

    private function __construct() {
        
    }

    public static function init($config = false) {
        self::$config = $config? : array(
            'StaticCache' => get_option('StaticCache'),
            'StaticCacheDir' => get_option('StaticCacheDir')
        );
    }

    public static function clearTermCache() {
        self::init();
        $post = $_REQUEST;
        $home_url = home_url();
        $uri = Url::getUri(get_term_link((int) $post['tag_ID'], $post['taxonomy']), $home_url);
        $file_path = self::getFilePath($uri);
        self::clearCache($file_path);
    }

    public static function clearPostCache() {

        self::init();
        $post = $_REQUEST;
        $id = $post['post_ID'];
        $categories = $post['post_category']? : array();
        $home_url = home_url();
        if ($id) {
            if (wp_is_post_revision($id)) {
                return;
            } else {
                $uri = Url::getUri(get_permalink($id), $home_url);
                $file_path = self::getFilePath($uri);
                self::clearCache($file_path);
            }
            foreach ($categories as $cat) {
                $uri = Url::getUri(get_category_link($cat), $home_url);
                $file_path = self::getFilePath($uri);
                self::clearCache($file_path);
            }
        }
    }

    public static function clearCache($file_path) {
        if (is_file($file_path)) {
            return unlink($file_path);
        }
    }

    private static function checkPath($path) {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
    }

    private static function getFilePath($uri = false) {
        $uri = $uri? : $_SERVER['REQUEST_URI'];
        $file_path = self::$config['StaticCacheDir'] . DIRECTORY_SEPARATOR . $_SERVER['HTTP_HOST'] . $uri;
        $path_parts = pathinfo($file_path);
        if (!$path_parts['extension'] || $uri == '/') {
            $file_path = $file_path . DIRECTORY_SEPARATOR . 'index.html';
        }
        return $file_path;
    }

    public static function generate($output) {
        $file_path = self::getFilePath();        
        if (!is_file($file_path) && strlen(trim($output)) > 0) {
            self::checkPath($file_path);
            File::put_content($file_path, $output);
        }
    }

}
