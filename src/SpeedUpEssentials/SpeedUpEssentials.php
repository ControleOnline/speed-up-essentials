<?php

namespace SpeedUpEssentials;

use SpeedUpEssentials\Model\DOMHtml,
    SpeedUpEssentials\Helper\HtmlFormating,
    SpeedUpEssentials\Helper\Url;

class SpeedUpEssentials {

    protected $config;
    protected $is_html;

    public function getConfig($config, $baseUri) {

        $env = isset($config['APP_ENV']) ? $config['APP_ENV'] : (getenv('APP_ENV') ? : 'production');
        /*
         * CookielessDomain
         */
        $config['CookieLessDomain'] = (isset($config['CookieLessDomain']) ? $config['CookieLessDomain'] : filter_input(INPUT_SERVER, 'HTTP_HOST'));
        $config['Protocol'] = isset($config['Protocol']) ? $config['Protocol'] : (filter_input(INPUT_SERVER, 'HTTPS') == 'on' ? 'https:' : 'http:');
        defined('PROTOCOL')? : define('PROTOCOL', $config['Protocol']);

        /*
         * Encoding
         */
        $config['charset'] = (isset($config['charset']) ? $config['charset'] : 'utf-8');

        /*
         * Url Configs
         */

        $config['URIBasePath'] = (isset($config['URIBasePath']) ? $config['URIBasePath'] : $baseUri);
        $config['BasePath'] = realpath(isset($config['BasePath']) ? $config['BasePath'] : 'public/') . DIRECTORY_SEPARATOR;
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
        $config['LazyLoadBasePath'] = (isset($config['LazyLoadBasePath']) ? $config['LazyLoadBasePath'] : '/');
        $config['LazyLoadOnlyOnNoScript'] = (isset($config['LazyLoadOnlyOnNoScript']) ? $config['LazyLoadOnlyOnNoScript'] : array('itemprop'));
        $config['LazyLoadExcludeTags'] = (isset($config['LazyLoadExcludeTags']) ? $config['LazyLoadExcludeTags'] : array('script', 'noscript', 'textarea'));

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
        $config['JsAllAsync'] = (isset($config['JsAllAsync']) ? $config['JsAllAsync'] : true);
        $config['JavascriptOnFooter'] = (isset($config['JavascriptOnFooter']) ? $config['JavascriptOnFooter'] : false);
        $config['JavascriptIntegrateInline'] = (isset($config['JavascriptIntegrateInline']) ? $config['JavascriptIntegrateInline'] : true);

        /*
         * Css Minify
         */
        $config['CssIntegrate'] = (isset($config['CssIntegrate']) ? $config['CssIntegrate'] : ($env == 'development' ? false : true));
        $config['CssMinify'] = (isset($config['CssMinify']) ? $config['CssMinify'] : ($env == 'development' ? false : true));
        $config['CssMinifiedFilePath'] = (isset($config['CssMinifiedFilePath']) ? $config['CssMinifiedFilePath'] : 'css/vendor/ControleOnline/');
        $config['CssRemoveImports'] = (isset($config['CssRemoveImports']) ? $config['CssRemoveImports'] : true);
        $config['CssSpritify'] = (isset($config['CssSpritify']) ? $config['CssSpritify'] : true);
        $config['CssIntegrateInline'] = (isset($config['CssIntegrateInline']) ? $config['CssIntegrateInline'] : true);

        /*
         * Cache
         */
        if (!isset($config['cacheId'])) {
            if (is_file($config['BasePath'] . '.version')) {
                $contents = file_get_contents($config['BasePath'] . '.version');
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

    protected function is_html() {
        $headers = headers_list();
        $is_html = $headers ? false : true;
        if ($headers) {
            foreach ($headers as $key => $header) {
                if (strpos($header, 'text/html') !== false) {
                    $is_html = true;
                }
            }
        }
        return $is_html;
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
            foreach ($jss as $js) {
                krsort($js);
                $c = '<script';
                foreach ($js as $key => $value) {
                    $c .= ' ' . $key . '="' . $value . '"';
                }
                $c .='></script>';
                if ($this->config['JavascriptOnFooter']) {
                    $child = 'body';
                } else {
                    $child = 'head';
                }
                $new_html = preg_replace('~<(?:!DOCTYPE|/?(?:\?xml|html|head|body))[^>]*>\s*~i', '', $c);
                $DOMHtml->setContent(str_replace('</' . $child . '>', $new_html . '</' . $child . '>', $DOMHtml->getContent()));
            }
        }
    }

    private function addCssHeaders() {
        $htmlHeaders = Model\HtmlHeaders::getInstance();
        $css_m = $htmlHeaders->getCss();
        if ($css_m) {
            $DOMHtml = DOMHtml::getInstance();
            foreach ($css_m as $csss) {
                foreach ($csss as $css) {
                    krsort($css);
                    $c = '<link';
                    foreach ($css as $key => $value) {
                        $c .= ' ' . $key . '="' . $value . '"';
                    }
                    $c .='>';
                    $child = 'head';
                    $new_html = preg_replace('~<(?:!DOCTYPE|/?(?:\?xml|html|head|body))[^>]*>\s*~i', '', $c);
                    $DOMHtml->setContent(str_replace('</' . $child . '>', $new_html . '</' . $child . '>', $DOMHtml->getContent()));
                }
            }
        }
    }

    public function addHtmlHeaders() {
        $this->addCssHeaders();
        $this->addJsHeaders();
    }

    public function render($html) {
        if (!$this->is_html()) {
            return $html;
        }
        $HtmlFormating = new HtmlFormating($this->config);
        $HtmlFormating->prepareHtml($html);
        $DOMHtml = DOMHtml::getInstance();
        $DOMHtml->setContent($html);
        $HtmlFormating->format();
        $this->addHtmlHeaders();
        return $HtmlFormating->render();
    }

}
