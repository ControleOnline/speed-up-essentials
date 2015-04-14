<?php

namespace SpeedUpEssentials;

use SpeedUpEssentials\Model\DOMHtml,
    SpeedUpEssentials\Helper\HtmlFormating,
    SpeedUpEssentials\Helper\Url,
    SpeedUpEssentials\Helper\JSMin;

class SpeedUpEssentials {

    protected $config;

    public function getConfig($config, $baseUri) {
        $env = isset($config['APP_ENV']) ? $config['APP_ENV'] : (getenv('APP_ENV') ? : 'production');

        /*
         * CookielessDomain
         */
        $config['CookieLessDomain'] = (isset($config['CookieLessDomain']) ? $config['CookieLessDomain'] : 'static.' . $_SERVER['HTTP_HOST']);

        /*
         * Encoding
         */
        $config['charset'] = (isset($config['charset']) ? $config['charset'] : 'utf-8');
        $config['RemoveMetaCharset'] = (isset($config['RemoveMetaCharset']) ? $config['RemoveMetaCharset'] : true);

        /*
         * Url Configs
         */

        $config['URIBasePath'] = (isset($config['URIBasePath']) ? $config['URIBasePath'] : $baseUri);
        $config['PublicBasePath'] = realpath(isset($config['PublicBasePath']) ? $config['PublicBasePath'] : 'public/') . DIRECTORY_SEPARATOR;
        $config['PublicCacheDir'] = (isset($config['PublicCacheDir']) ? $config['PublicCacheDir'] : 'cache/');

        /*
         * Lazy Load Configs
         */
        $config['LazyLoadImages'] = (isset($config['LazyLoadImages']) ? $config['LazyLoadImages'] : true);
        $config['LazyLoadClass'] = (isset($config['LazyLoadClass']) ? $config['LazyLoadClass'] : 'lazy-load');
        $config['LazyLoadPlaceHolder'] = (isset($config['LazyLoadPlaceHolder']) ? $config['LazyLoadPlaceHolder'] : 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
        $config['LazyLoadFadeIn'] = (isset($config['LazyLoadFadeIn']) ? $config['LazyLoadFadeIn'] : true);
        $config['LazyLoadJsFile'] = (isset($config['LazyLoadJsFile']) ? $config['LazyLoadJsFile'] : true);
        $config['LazyLoadJsFilePath'] = (isset($config['LazyLoadJsFilePath']) ? $config['LazyLoadJsFilePath'] : 'js/vendor/ControleOnline/');
        $config['LazyLoadCssFilePath'] = (isset($config['LazyLoadCssFilePath']) ? $config['LazyLoadCssFilePath'] : 'css/vendor/ControleOnline/');

        /*
         * Html Formatter Config
         */
        $config['HtmlRemoveComments'] = (isset($config['HtmlRemoveComments']) ? $config['HtmlRemoveComments'] : ($env == 'development' ? false : true));
        $config['HtmlIndentation'] = (isset($config['HtmlIndentation']) ? $config['HtmlIndentation'] : ($env == 'development' ? true : false));
        $config['HtmlMinify'] = (isset($config['HtmlMinify']) ? $config['HtmlMinify'] : ($env != 'development' ? true : false));

        /*
         * Javascript Minify
         */
        $config['JavascriptIntegrate'] = (isset($config['JavascriptIntegrate']) ? $config['JavascriptIntegrate'] : ($env == 'development' ? false : true));
        $config['JavascriptCDNIntegrate'] = (isset($config['JavascriptIntegrate']) ? $config['JavascriptIntegrate'] : true);
        $config['JavascriptMinify'] = (isset($config['JavascriptMinify']) ? $config['JavascriptMinify'] : ($env == 'development' ? false : true));
        $config['JsMinifiedFilePath'] = (isset($config['JsMinifiedFilePath']) ? $config['JsMinifiedFilePath'] : 'js/vendor/ControleOnline/');
        $config['JsAllAsync'] = (isset($config['JsAllAsync']) ? $config['JsAllAsync'] : false);

        /*
         * Css Minify
         */
        $config['CssIntegrate'] = (isset($config['CssIntegrate']) ? $config['CssIntegrate'] : ($env == 'development' ? false : true));
        $config['CssMinify'] = (isset($config['CssMinify']) ? $config['CssMinify'] : ($env == 'development' ? false : true));
        $config['CssMinifiedFilePath'] = (isset($config['CssMinifiedFilePath']) ? $config['CssMinifiedFilePath'] : 'css/vendor/ControleOnline/');
        $config['CssRemoveImports'] = (isset($config['CssRemoveImports']) ? $config['CssRemoveImports'] : true);
        $config['CssSpritify'] = (isset($config['CssSpritify']) ? $config['CssSpritify'] : true);

        /*
         * Cache
         */
        if (!isset($config['cacheId'])) {
            if (is_file('.version')) {
                $contents = Url::get_content('.version');
                if ($contents) {
                    $content = array_values(preg_split('/\r\n|\r|\n/', $contents, 2));
                    $version = trim(array_shift($content));
                    if (empty($version)) {
                        $config['cacheId'] = date('Y/m/d/H/');
                    } else {
                        $config['cacheId'] = $version . '/';
                    }
                } else {
                    $config['cacheId'] = date('Y/m/d/H/');
                }
            } else {
                $config['cacheId'] = date('Y/m/d/H/');
            }
        }
        return $config;
    }

    public function __construct($config, $baseUri) {
        $this->env = getenv('APP_ENV') ? : 'production';

        $this->config = $this->getConfig(
                (isset($config) ? $config : array()), $baseUri
        );
        DOMHtml::getInstance($this->config['charset']);
        Url::setStaticDomain($this->config['CookieLessDomain']);
        Url::setBaseUri($this->config['URIBasePath']);
    }

    private function addJsHeaders() {
        $htmlHeaders = Model\HtmlHeaders::getInstance();
        $jss = $htmlHeaders->getJs();
        if ($jss) {
            $DOMHtml = DOMHtml::getInstance();
            $dom = $DOMHtml->getDom();
            foreach ($jss as $js) {
                $script = $dom->createElement('script');
                krsort($js);
                foreach ($js as $key => $value) {
                    $script->setAttribute($key, $value);
                }
                $head = $dom->getElementsByTagName('head')->item(0);
                if ($head) {
                    $head->appendChild($script);
                }
            }
        }
    }

    private function addCssHeaders() {
        $htmlHeaders = Model\HtmlHeaders::getInstance();
        $csss = $htmlHeaders->getCss();
        if ($csss) {
            $DOMHtml = DOMHtml::getInstance();
            $dom = $DOMHtml->getDom();
            foreach ($csss as $css) {
                $link = $dom->createElement('link');
                krsort($css);
                foreach ($css as $key => $value) {
                    $link->setAttribute($key, $value);
                }
                $head = $dom->getElementsByTagName('head')->item(0);
                if ($head) {
                    $head->appendChild($link);
                }
            }
        }
    }

    public function addHtmlHeaders() {
        $this->addCssHeaders();
        $this->addJsHeaders();
    }

    public function render(&$html) {
        $HtmlFormating = new HtmlFormating($this->config);
        $HtmlFormating->prepareHtml($html);
        $DOMHtml = DOMHtml::getInstance();
        $DOMHtml->setContent($html);
        $HtmlFormating->format();
        $this->addHtmlHeaders();
        return $HtmlFormating->render($html);
    }

}
