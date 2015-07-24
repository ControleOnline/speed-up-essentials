<?php

namespace SpeedUpEssentials\Helper;

class Cache {

    protected static $config;

    private function __construct() {
        
    }

    private static function wpSettings() {
        return array(
            'StaticCache' => get_option('StaticCache'),
            'StaticCacheDir' => get_option('StaticCacheDir')
        );
    }

    public static function init($config) {
        self::$config = $config;
    }

    public static function checkHtaccess() {
        $htaccess = self::$config['BasePath'] . DIRECTORY_SEPARATOR . '.htaccess';
        $htaccessSample = self::$config['AppDir'] . 'public' . DIRECTORY_SEPARATOR . '.htaccess';
        try {
            if (!is_file($htaccess)) {
                copy($htaccessSample, $htaccess);
            } else {
                $contentSample = File::get_content($htaccessSample, false);
                $content = File::get_content($htaccess, false);
                if (self::$config['StaticCache']) {
                    if (strpos($content, 'BEGIN SpeedUpEssentials') == 0) {
                        file::put_content($htaccess, $contentSample . PHP_EOL . $content);
                    }
                } elseif (strpos($content, 'BEGIN SpeedUpEssentials') != 0) {
                    $newContent = str_replace($contentSample, '', $content);
                    file::put_content($htaccess, $newContent);
                }
            }
        } catch (Exception $exc) {
            
        }        
    }

    public static function clearTermCache() {
        self::init(self::wpSettings());
        $post = $_REQUEST;
        $home_url = home_url();
        $uri = Url::getUri(get_term_link((int) $post['tag_ID'], $post['taxonomy']), $home_url);
        $file_path = self::getFilePath($uri);
        self::clearCache($file_path);
    }

    public static function clearPostCache() {
        self::init(self::wpSettings());
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
