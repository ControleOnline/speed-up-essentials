<?php

namespace SpeedUpEssentials\Helper;

use SpeedUpEssentials\Model\DOMHtml,
    SpeedUpEssentials\Model\HtmlHeaders,
    SpeedUpEssentials\Helper\JSIntegrate,
    SpeedUpEssentials\Helper\Url;

class HtmlFormating {

    protected $config;
    protected $no_ll = array(
        'script',
        'noscript',
        'textarea'
    );

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

    private function removeElements($type, $regex = false) {
        $regex = $regex? : '/<' . $type . '(.*?)[/>|</' . $type . '>]/smix';
        $htmlContent = $this->DOMHtml->getContent();
        $content = preg_replace_callback($regex, function($script) use ($type) {
            return str_replace('<' . $type, '<c_' . $type, $script[0]);
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content? : $htmlContent);
    }

    private function returnElements($type, $regex = false) {
        $regex = $regex? : '/<c_' . $type . '(.*?)[/>|</' . $type . '>]/smix';
        $htmlContent = $this->DOMHtml->getContent();
        $content = preg_replace_callback($regex, function($script)use ($type) {
            return str_replace('<c_' . $type, '<' . $type, $script[0]);
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content? : $htmlContent);
    }

    private function removeConditionals($type) {
        $regex = '/\]><' . $type . '(.*?)<\!/smix';
        $this->removeElements($type, $regex);
    }

    private function returnConditionals($type) {
        $regex = '/\]><c_' . $type . '(.*?)<\!/smix';
        $this->returnElements($type, $regex);
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
                    } elseif ($config['CssIntegrateInline'] && $config['CssIntegrate']) {
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
                } elseif ($config['JavascriptIntegrateInline'] && $config['JavascriptIntegrate']) {
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

    public function removeHtmlComments($html) {
        $regex = '/<script((?:.)*?)>(.*?)<\/script>/smix';
        $reg = '/<!--((?!<!)[^\[>](.|\n)*?)-->/';
        $content = preg_replace_callback($regex, function($script) use ($reg) {
            return preg_replace_callback($reg, function($s) {
                return $s[1];
            }, $script[0]);
        }, $html);
        $html = preg_replace('/<!--(?!<!)[^\[>](.|\n)*?-->/', '', $content);
        return $html;
    }

    public function render($html) {
        $DOMHtml = DOMHtml::getInstance();
        $html = $DOMHtml->render();
        if ($this->config['HtmlMinify']) {
            $html = $this->htmlCompress($html);
        } elseif ($this->config['HtmlIndentation']) {
            $html = $this->htmlIndentation($html);
        }
        return $html;
    }

    public function htmlIndentation($html) {
        if (class_exists('tidy')) {
            $config = array(
                'char-encoding' => 'utf8',
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

    public function htmlCompress($html) {

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
        $this->imgLazyLoad();
        $this->organizeHeaderOrder();
        $this->removeMetaCharset();
        $this->cssIntegrate();
        $this->javascriptIntegrate();
    }

    private function cssIntegrate() {
        if ($this->config['CssIntegrate']) {
            $this->removeConditionals('link');
            $CSSIntegrate = new CSSIntegrate($this->config);
            $CSSIntegrate->integrate();
            $this->returnConditionals('link');
        }
    }

    private function javascriptIntegrate() {
        if ($this->config['JavascriptIntegrate']) {
            $this->removeConditionals('script');
            $JSIntegrate = new JSIntegrate($this->config);
            $JSIntegrate->integrate();
            $this->returnConditionals('script');
        }
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
                    mkdir(dirname($file), 0777, true);
                    copy($base . $this->config['LazyLoadJsFilePath'] . 'LazyLoad.js', $file);
                }
                $htmlHeaders = HtmlHeaders::getInstance();
                $htmlHeaders->addJs(
                        array(
                            'src' => $path . $this->config['LazyLoadJsFilePath'] . 'Lazyload.js',
                            'type' => 'text/javascript'
                        )
                );
            }

            if ($this->config['LazyLoadFadeIn']) {
                $file = $this->config['PublicBasePath'] . $this->config['LazyLoadBasePath'] . $this->config['cacheId'] . DIRECTORY_SEPARATOR . $this->config['LazyLoadCssFilePath'] . 'LazyLoad.css';
                if (!file_exists($file)) {
                    mkdir(dirname($file), 0777, true);
                    copy($base . $this->config['LazyLoadCssFilePath'] . 'LazyLoad.css', $file);
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

    /**
     *  Adjust this regex to not get images inside a script tag
     * Example:
     * <script>var a = '<img src="test.png">';</script>            
     */
    private function removeImagesFromScripts() {

        foreach ($this->no_ll as $no) {
            $htmlContent = $this->DOMHtml->getContent();
            $regex = '/<' . $no . '((?:.)*?)>((?:.)*?)<\/' . $no . '>/smix';
            $config = $this->config;
            $self = $this;
            $content = preg_replace_callback($regex, function($script) use ($htmlHeaders, $config, $self, $no) {
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
            $this->DOMHtml->setContent($content? : $htmlContent);
        }
    }

    private function returnImagesFromScripts() {
        foreach ($this->no_ll as $no) {
            $htmlContent = $this->DOMHtml->getContent();
            $regex = '/<' . $no . '((?:.)*?)>((?:.)*?)<\/' . $no . '>/smix';
            $config = $this->config;
            $self = $this;
            $content = preg_replace_callback($regex, function($script) use ($htmlHeaders, $config, $self, $no) {
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
            $this->DOMHtml->setContent($content? : $htmlContent);
        }
    }

    private function imgLazyLoad() {
        if ($this->config['LazyLoadImages']) {
            $this->removeImagesFromScripts();
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
                            $key = 'data-ll';
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
                $content_img .= '<noscript class="ns-ll">';
                $content_img .= $img;
                $content_img .= '</noscript>';
                return $content_img;
            }, $htmlContent);
            $this->DOMHtml->setContent($content? : $htmlContent);
            $this->returnImagesFromScripts();
            $this->lazyLoadHead();
        }
    }

}
