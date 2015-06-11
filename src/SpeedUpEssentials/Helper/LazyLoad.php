<?php

namespace SpeedUpEssentials\Helper;

use SpeedUpEssentials\Model\DOMHtml,
    SpeedUpEssentials\Model\HtmlHeaders;

class LazyLoad {

    /**
     * @var HtmlHeaders
     */
    protected static $htmlHeaders;

    /**
     * @var \SpeedUpEssentials\Model\DOMHtml
     */
    protected static $DOMHtml;
    protected static $config;
    protected static $base;
    protected static $path;

    private function __construct() {
        
    }

    protected static function init($config) {
        self::$config = $config;
        self::$DOMHtml = DOMHtml::getInstance();
        self::$htmlHeaders = HtmlHeaders::getInstance();
        self::$base = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public') . DIRECTORY_SEPARATOR;
        self::$path = self::$config['URIBasePath'] . self::$config['LazyLoadBasePath'] . self::$config['cacheId'] . DIRECTORY_SEPARATOR;
    }

    protected static function lazyLoadHead() {
        self::addJsFile();
        self::fadeIn();
    }

    protected static function addJsFile() {
        if (self::$config['LazyLoadJsFile']) {
            $file = self::$config['BasePath'] . self::$config['LazyLoadBasePath'] . self::$config['cacheId'] . DIRECTORY_SEPARATOR . self::$config['LazyLoadJsFilePath'] . 'LazyLoad.js';
            if (!file_exists($file)) {
                @mkdir(dirname($file), 0777, true);
                copy(self::$base . self::$config['LazyLoadJsFilePath'] . 'LazyLoad.js', $file);
            }
            self::$htmlHeaders->addJs(
                    array(
                        'src' => self::$path . self::$config['LazyLoadJsFilePath'] . 'LazyLoad.js',
                        'type' => 'text/javascript'
                    )
            );
        }
    }

    protected static function fadeIn() {
        if (self::$config['LazyLoadFadeIn']) {
            $file = self::$config['BasePath'] . self::$config['LazyLoadBasePath'] . self::$config['cacheId'] . DIRECTORY_SEPARATOR . self::$config['LazyLoadCssFilePath'] . 'LazyLoad.css';
            if (!file_exists($file)) {
                @mkdir(dirname($file), 0777, true);
                copy(self::$base . self::$config['LazyLoadCssFilePath'] . 'LazyLoad.css', $file);
            }
            self::$htmlHeaders->addCss(
                    array(
                        'href' => self::$path . self::$config['LazyLoadCssFilePath'] . 'LazyLoad.css',
                        'rel' => 'stylesheet',
                        'type' => 'text/css',
                        'media' => 'screen'
                    )
            );
        }
    }

    /**
     *  Adjust this regex to not get images inside a script tag
     * Example:
     * <script>var a = '<img src="test.png">';</script>            
     */
    protected static function removeImagesFromScripts() {
        foreach (self::$config['LazyLoadExcludeTags'] as $no) {
            $htmlContent = self::$DOMHtml->getContent();
            $regex = '/<' . $no . '((?:.)*?)>((?:.)*?)<\/' . $no . '>/smix';
            $content = preg_replace_callback($regex, function($script) use ($no) {
                if ($script[2]) {
                    $regimg = '/<img((?:.)*?)>/smix';
                    $img = preg_replace_callback($regimg, function($i) {
                        return '<noimg' . $i[1] . '>';
                    }, $script[2]);
                    return $img ? '<' . $no . $script[1] . '>' . $img . '</' . $no . '>' : $script[0];
                } else {
                    return $script[0];
                }
            }, $htmlContent);
            self::$DOMHtml->setContent($content? : $htmlContent);
        }
    }

    protected static function returnImagesFromScripts() {
        foreach (self::$config['LazyLoadExcludeTags'] as $no) {
            $htmlContent = self::$DOMHtml->getContent();
            $regex = '/<' . $no . '((?:.)*?)>((?:.)*?)<\/' . $no . '>/smix';
            $content = preg_replace_callback($regex, function($script) use ($no) {
                if ($script[2]) {
                    $regimg = '/<noimg((?:.)*?)>/smix';
                    $img = preg_replace_callback($regimg, function($i) {
                        return '<img' . $i[1] . '>';
                    }, $script[2]);
                    return $img ? '<' . $no . $script[1] . '>' . $img . '</' . $no . '>' : $script[0];
                } else {
                    return $script[0];
                }
            }, $htmlContent);
            self::$DOMHtml->setContent($content? : $htmlContent);
        }
    }

    public static function normalizeAttributes($attributes = array()) {
        foreach ($attributes AS $key => $att) {
            if (strtolower($key) == 'class') {
                $att = $att . ' ' . self::$config['LazyLoadClass'];
            }
            if (strtolower($key) == 'src') {
                $att = Url::normalizeUrl($att);
                $return['img'] .= ' ' . $key . '="' . $att . '"';
                $return['lazy_img'] .= ' ' . $key . '="' . self::$config['LazyLoadPlaceHolder'] . '"';
                $key = 'data-ll';
            } else {
                $return['img'] .= ' ' . $key . '="' . $att . '"';
            }
            if (!in_array(strtolower($key), self::$config['LazyLoadOnlyOnNoScript'])) {
                $return['lazy_img'] .= ' ' . $key . '="' . $att . '"';
            }
        }
        if (!array_key_exists('class', $attributes)) {
            $return['img'] .= ' class="' . self::$config['LazyLoadClass'] . '"';
        }
        return $return;
    }

    public static function prepareImg($script) {
        $regex_img = '/(\S+)=["\']((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']/';
        preg_match_all($regex_img, $script[1], $matches);
        if (isset($matches[1]) && isset($matches[2])) {
            foreach ($matches[1] AS $k => $key) {
                $attributes[trim($key)] = trim($matches[2][$k]);
            }
        }
        $img = '<img';
        $lazy_img = '<img';
        $att = self::normalizeAttributes($attributes);
        $img .= $att['img'];
        $lazy_img .= $att['lazy_img'];
        $img .= '>';
        $lazy_img .= '>';
        $content_img = $lazy_img;
        $content_img .= '<noscript class="ns-ll">';
        $content_img .= $img;
        $content_img .= '</noscript>';
        return $content_img;
    }

    public static function imgLazyLoad($config) {
        self::init($config);
        if (self::$config['LazyLoadImages']) {
            self::removeImagesFromScripts();
            $htmlContent = self::$DOMHtml->getContent();
            $regex = '/<img((?:.)*?)>/smix';
            $content = preg_replace_callback($regex, function($script) {
                return LazyLoad::prepareImg($script);
            }, $htmlContent);
            self::$DOMHtml->setContent($content? : $htmlContent);
            self::returnImagesFromScripts();
            self::lazyLoadHead();
        }
    }

}
