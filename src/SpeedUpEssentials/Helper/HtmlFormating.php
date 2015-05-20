<?php

namespace SpeedUpEssentials\Helper;

use SpeedUpEssentials\Model\DOMHtml,
    SpeedUpEssentials\Model\HtmlHeaders,
    SpeedUpEssentials\Helper\JSIntegrate,
    SpeedUpEssentials\Helper\Url;

class HtmlFormating {

    protected $config;

    /**
     * @var \SpeedUpEssentials\Model\DOMHtml
     */
    protected $DOMHtml;

    public function __construct($config = null) {
        $this->config = $config;
        $this->DOMHtml = DOMHtml::getInstance();
    }

    private function organizeHeaderOrder() {
        $htmlHeaders = HtmlHeaders::getInstance();
        if ($this->config['CssIntegrate']) {
            $this->organizeCSS($htmlHeaders);
        }
        if ($this->config['JavascriptIntegrate']) {
            $this->organizeJS($htmlHeaders);
        }
    }

    private function removeConditionals() {
        $regex = '/\]><link(.*?)<\!/smix';
        $htmlContent = $this->DOMHtml->getContent();
        $content = preg_replace_callback($regex, function($script) {
            return str_replace('<link', '<replace_conditional', $script[0]);
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content? : $htmlContent);
    }

    private function returnConditionals() {
        $regex = '/\]><replace_conditional(.*?)<\!/smix';
        $htmlContent = $this->DOMHtml->getContent();
        $content = preg_replace_callback($regex, function($script) {
            return str_replace('<replace_conditional', '<link', $script[0]);
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content? : $htmlContent);
    }

    private function organizeCSS($htmlHeaders) {
        $reg = array(
            '/<link((?:.)*?)>(.*?)<\/link>/smix',
            '/<link((?:.)*?)\/>/smix',
            '/<style((?:.)*?)>(.*?)<\/style>/smix'
        );
        $config = $this->config;
        $self = $this;
        foreach ($reg AS $regex) {
            $htmlContent = $this->DOMHtml->getContent();
            $content = preg_replace_callback($regex, function($script) use ($htmlHeaders, $config, $self) {
                $regex_tribb = '/(\S+)=["\']((?:.(?!["\']\s+(?:\S+)=|[>"\']))+.)["\']/';
                preg_match_all($regex_tribb, $script[1], $matches);
                if (isset($matches[1]) && isset($matches[2])) {
                    foreach ($matches[1] AS $k => $key) {
                        if (trim($key) == 'href') {
                            $v = File::url_decode(trim($matches[2][$k]));
                        } else {
                            $v = trim($matches[2][$k]);
                        }
                        $attributes[trim($key)] = $v;
                    }
                }
                if ($attributes['type'] == 'text/css') {
                    if ($attributes['href']) {
                        $htmlHeaders->addCss($attributes);
                        return;
                    } elseif ($config['CssIntegrateInline']) {
                        $attributes['value'] = isset($script[2]) ? $script[2] : '';
                        $self->addCssInline($htmlHeaders, $attributes);
                        return;
                    } else {
                        return $script[0];
                    }
                } else {
                    return $script[0];
                }
            }, $htmlContent
            );
            $this->DOMHtml->setContent($content? : $htmlContent);
        }
    }

    public function jsAwaysInline($content) {
        return strpos($content, 'document.write');
    }

    public function addJsInline($htmlHeaders, $attributes) {

        $file = 'inline' . DIRECTORY_SEPARATOR . md5($attributes['value']) . '.js';
        $completeFilePath = $this->config['PublicBasePath'] . $this->config['PublicCacheDir'] . $file;

        if (!file_exists($completeFilePath)) {
            if (!is_dir(dirname($completeFilePath))) {
                mkdir(dirname($completeFilePath), 0777, true);
            }
            if ($this->config['JavascriptMinify']) {
                $attributes['value'] = JSMin::minify($attributes['value']);
            }
            File::put_content($completeFilePath, $attributes['value']);
        }

        $attributes['src'] = Url::normalizeUrl($this->config['URIBasePath'] . $this->config['PublicCacheDir'] . $file);
        unset($attributes['value']);
        $htmlHeaders->addJs($attributes);
    }

    public function addCssInline($htmlHeaders, $attributes) {

        $file = 'inline' . DIRECTORY_SEPARATOR . md5($attributes['value']) . '.css';
        $completeFilePath = $this->config['PublicBasePath'] . $this->config['PublicCacheDir'] . $file;

        if (!file_exists($completeFilePath)) {
            if (!is_dir(dirname($completeFilePath))) {
                mkdir(dirname($completeFilePath), 0777, true);
            }
            if ($this->config['CssMinify']) {
                $cssmin = new \CSSmin();
                $attributes['value'] = $cssmin->run($attributes['value']);
            }
            if ($this->config['CssSpritify']) {
                $spritify = new Spritify($this->config);
                $attributes['value'] = $spritify->run($attributes['value']);
            }
            File::put_content($completeFilePath, $attributes['value']);
        }
        $attributes['href'] = Url::normalizeUrl($this->config['URIBasePath'] . $this->config['PublicCacheDir'] . $file);
        unset($attributes['value']);
        $htmlHeaders->addCss($attributes);
    }

    /**
     * @param HtmlHeaders $htmlHeaders
     */
    private function organizeJS($htmlHeaders) {
        $htmlContent = $this->DOMHtml->getContent();
        $regex = '/<script((?:.)*?)>(.*?)<\/script>/smix';
        $config = $this->config;
        $self = $this;
        $content = preg_replace_callback($regex, function($script) use ($htmlHeaders, $config, $self) {
            $regex_tribb = '/(\S+)=["\']((?:.(?!["\']\s+(?:\S+)=|[>"\']))+.)["\']/';
            preg_match_all($regex_tribb, $script[1], $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                foreach ($matches[1] AS $k => $key) {
                    if (trim($key) == 'src') {
                        $v = File::url_decode(trim($matches[2][$k]));
                    } else {
                        $v = trim($matches[2][$k]);
                    }
                    $attributes[trim($key)] = $v;
                }
            }
            if ($attributes['type'] == 'text/javascript' || !$attributes['type']) {
                if ($attributes['src']) {
                    $htmlHeaders->addJs($attributes);
                    return;
                } elseif ($config['JavascriptIntegrateInline']) {
                    $attributes['value'] = isset($script[2]) ? $script[2] : '';
                    if (!$self->jsAwaysInline($attributes['value'])) {
                        $self->addJsInline($htmlHeaders, $attributes);
                        return;
                    } else {
                        return $script[0];
                        /**
                         * @todo Adjust to work fine with document.write
                         */
                        /*
                          $id = md5($script[0]);
                          $attributes['value'] = str_replace('document.write(', 'replace_text("' . $id . '",', $attributes['value']);
                          $self->addJsInline($htmlHeaders, $attributes);
                          $replace = '<script type="text/javascript" id="' . $id . '">';
                          $replace .= 'var elem = document.getElementById("' . $id . '");';
                          $replace .= 'elem.addEventListener("' . $id . '", function (event) {';
                          $replace .= 'document.write(event.detail);';
                          $replace .= '});';
                          $replace .= '</script>';
                          return $replace;
                         */
                    }
                } else {
                    return $script[0];
                }
            } else {
                return $script[0];
            }
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content? : $htmlContent);
    }

    public function normalizeImgUrl($content) {
        return $content;
    }

    public function addDataMain($url) {
        
    }

    public function prepareHtml(&$html) {
        if ($this->config['HtmlRemoveComments']) {
            $this->removeHtmlComments($html);
        }
        if ($this->config['HtmlMinify']) {
            $this->htmlCompress($html);
        }
        return $html;
    }

    public function removeHtmlComments(&$html) {        
        $html = preg_replace('/<!--(?!<!)[^\[>](.|\n)*?-->/', '', $html);
        return $html;
    }

    public function render(&$html) {
        $DOMHtml = DOMHtml::getInstance();
        $html = $DOMHtml->render();
        if ($this->config['HtmlMinify']) {
            $html = $this->htmlCompress($html);
        } elseif ($this->config['HtmlIndentation']) {
            $html = $this->htmlIndentation($html);
        }
        return $html;
    }

    public function htmlIndentation(&$html) {
        if (class_exists('tidy')) {

            $config = array(
                "char-encoding" => "utf8",
                'vertical-space' => false,
                'indent' => true,
                'wrap' => 0,
                'word-2000' => 1,
                'break-before-br' => true,
                'indent-cdata' => true
            );

            $tidy = new \Tidy();
            $tidy->parseString($html, $config);
            return str_replace('>' . PHP_EOL . '</', '></', tidy_get_output($tidy));
        } else {
            return $html;
        }
    }

    public function htmlCompress(&$html) {

        $search = array(
            '/\>[^\S]+/s', //strip whitespaces after tags, except space
            '/[^\S]+\</s', //strip whitespaces before tags, except space
                //'/(\s)+/s'  // shorten multiple whitespace sequences (Broken <pre></pre>)
        );
        $replace = array(
            '>',
            '<',
                //'\\1'
        );
        $html = str_replace('> <', '><', preg_replace($search, $replace, $html));
        return $html;
    }

    private function sentHeaders() {
        headers_sent() ? : header('Content-Type: text/html; charset=' . $this->config['charset']);
    }

    public function format() {
        $this->sentHeaders();
        $this->removeConditionals();
        $this->imgLazyLoad();
        $this->organizeHeaderOrder();
        $this->removeMetaCharset();
        if ($this->config['CssIntegrate']) {
            $this->cssIntegrate();
        }
        if ($this->config['JavascriptIntegrate']) {
            $this->javascriptIntegrate();
        }
        $this->returnConditionals();
    }

    private function cssIntegrate() {
        $CSSIntegrate = new CSSIntegrate($this->config);
        $CSSIntegrate->integrate();
    }

    private function javascriptIntegrate() {
        $JSIntegrate = new JSIntegrate($this->config);
        $JSIntegrate->integrate();
    }

    private function removeMetaCharset() {

//        if ($this->config['RemoveMetaCharset']) {
//            $dom = new \DOMDocument();
//            libxml_use_internal_errors(true);
//            $htmlContent = $this->DOMHtml->getContent();
//            $dom->loadHTML($htmlContent);
//            libxml_use_internal_errors(false);
//            $x = new \DOMXPath($dom);
//            if ($x) {
//                foreach ($x->query("//meta") as $item) {
//                    if ($item->getAttribute('charset')) {
//                        $item->parentNode->removeChild($item);
//                    }
//                }
//            }
//            $this->DOMHtml->setContent($dom->saveHTML());
//        }
    }

    private function lazyLoadHead() {
        if ($this->config['LazyLoadJsFile'] || $this->config['LazyLoadFadeIn']) {
            $base = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../../public/';
            $path = $this->config['URIBasePath'] . $this->config['LazyLoadBasePath'] . $this->config['cacheId'] . DIRECTORY_SEPARATOR;
            if ($this->config['LazyLoadJsFile']) {
                $file = $this->config['PublicBasePath'] . $this->config['LazyLoadBasePath'] . $this->config['cacheId'] . DIRECTORY_SEPARATOR . $this->config['LazyLoadJsFilePath'] . 'Lazyload.js';
                if (!file_exists($file)) {
                    try {
                        mkdir(dirname($file), 0777, true);
                        copy($base . $this->config['LazyLoadJsFilePath'] . 'LazyLoad.js', $file);
                    } catch (Exception $ex) {
                        
                    }
                }
                $htmlHeaders = HtmlHeaders::getInstance();
                $htmlHeaders->addJs(
                        array(
                            'src' => $path . $this->config['LazyLoadJsFilePath'] . 'Lazyload.js',
                            'type' => 'text/javascript',
                            'async' => 'async'
                        )
                );
            }

            if ($this->config['LazyLoadFadeIn']) {
                $file = $this->config['PublicBasePath'] . $this->config['LazyLoadBasePath'] . $this->config['cacheId'] . DIRECTORY_SEPARATOR . $this->config['LazyLoadCssFilePath'] . 'LazyLoad.css';
                if (!file_exists($file)) {
                    try {
                        mkdir(dirname($file), 0777, true);
                        copy($base . $this->config['LazyLoadCssFilePath'] . 'LazyLoad.css', $file);
                    } catch (Exception $ex) {
                        
                    }
                }
                $htmlHeaders = HtmlHeaders::getInstance();
                $htmlHeaders->addCss(
                        array(
                            'href' => $path . $this->config['LazyLoadCssFilePath'] . 'LazyLoad.css',
                            'rel' => 'stylesheet',
                            'type' => 'text/css',
                            'media' => 'screen'
                        )
                );
            }
        }
    }

    private function imgLazyLoad() {
        if ($this->config['LazyLoadImages']) {
            $htmlContent = $this->DOMHtml->getContent();
            $regex = '/<img((?:.)*?)>/smix';
            $config = $this->config;
            $self = $this;
            $content = preg_replace_callback($regex, function($script) use ($htmlHeaders, $config, $self) {

                $regex_img = '/(\S+)=["\']((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']/';
                preg_match_all($regex_img, $script[1], $matches);

                if (isset($matches[1]) && isset($matches[2])) {
                    foreach ($matches[1] AS $k => $key) {
                        $attributes[trim($key)] = trim($matches[2][$k]);
                    }
                }
                $img = '<img';
                $lazy_img = '<img';
                if ($attributes) {
                    foreach ($attributes AS $key => $att) {
                        if (strtolower($key) == 'class') {
                            $att = $att . ' ' . $config['LazyLoadClass'];
                        }
                        if (strtolower($key) == 'src') {
                            $att = Url::normalizeUrl($att);
                            $img .= ' ' . $key . '="' . $att . '"';
                            $lazy_img .= ' ' . $key . '="' . $config['LazyLoadPlaceHolder'] . '"';
                            $key = 'data-src';
                        } else {
                            $img .= ' ' . $key . '="' . $att . '"';
                        }
                        $lazy_img .= ' ' . $key . '="' . $att . '"';
                    }
                    if (!array_key_exists('class', $attributes)) {
                        $img .= ' class="' . $config['LazyLoadClass'] . '"';
                    }
                }
                $img .= '>';
                $lazy_img .= '>';
                $content_img = $lazy_img;
                $content_img .= '<noscript>';
                $content_img .= $img;
                $content_img .= '</noscript>';
                return $content_img;
            }, $htmlContent);
            $this->DOMHtml->setContent($content? : $htmlContent);
            $this->lazyLoadHead();
        }
    }

}
