<?php

namespace SpeedUpEssentials;

use SpeedUpEssentials\Model\DOMHtml,
    SpeedUpEssentials\Helper\HtmlFormating;

class SpeedUpEssentials {

    protected $config;

    public function getConfig($config) {
        $env = isset($config['APP_ENV']) ? $config['APP_ENV'] : (getenv('APP_ENV') ? : 'production');
        /*
         * Encoding
         */
        $config['charset'] = (isset($config['charset']) ? $config['charset'] : 'utf-8');
        $config['RemoveMetaCharset'] = (isset($config['RemoveMetaCharset']) ? $config['RemoveMetaCharset'] : true);

        /*
         * Url Configs
         */
        $config['URIBasePath'] = (isset($config['URIBasePath']) ? $config['URIBasePath'] : '/');
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

        /*
         * Css Minify
         */
        $config['CssIntegrate'] = (isset($config['CssIntegrate']) ? $config['CssIntegrate'] : ($env == 'development' ? false : true));        
        $config['CssMinify'] = (isset($config['CssMinify']) ? $config['CssMinify'] : ($env == 'development' ? false : true));        
        $config['CssMinifiedFilePath'] = (isset($config['CssMinifiedFilePath']) ? $config['CssMinifiedFilePath'] : 'css/vendor/ControleOnline/');

        return $config;
    }

    public function __construct($config) {
        $this->env = getenv('APP_ENV') ? : 'production';

        $this->config = $this->getConfig(
                (isset($config) ? $config : array())
        );
        DOMHtml::getInstance($this->config['charset']);
    }

    public function addHtmlHeaders() {
        $htmlHeaders = Model\HtmlHeaders::getInstance();
        $csss = $htmlHeaders->getCss();
        $jss = $htmlHeaders->getJs();
        if ($csss || $jss) {
            $DOMHtml = DOMHtml::getInstance();
            $dom = $DOMHtml->getDom();
            if ($csss) {
                foreach ($csss as $css) {
                    $link = $dom->createElement('link');
                    krsort($css);
                    foreach ($css as $key => $value) {
                        $link->setAttribute($key, $value);
                    }
                    $dom->getElementsByTagName('head')->item(0)->appendChild($link);
                }
            }
            if ($jss) {
                foreach ($jss as $js) {
                    $script = $dom->createElement('script');
                    krsort($js);
                    foreach ($js as $key => $value) {
                        $script->setAttribute($key, $value);
                    }
                    $dom->getElementsByTagName('head')->item(0)->appendChild($script);
                }
            }
        }
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
