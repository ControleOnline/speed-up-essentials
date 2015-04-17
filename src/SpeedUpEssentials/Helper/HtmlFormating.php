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
    }

    private function organizeHeaderOrder() {
        $htmlHeaders = HtmlHeaders::getInstance();
        $this->DOMHtml = DOMHtml::getInstance();
        $this->organizeCSS($htmlHeaders);
        $this->organizeJS($htmlHeaders);
    }

    private function organizeCSS($htmlHeaders) {
        $htmlContent = $this->DOMHtml->getContent();
        $regex = '/(<link((.|\s)+?)(\/>|<\/link>))(.*?)/';
        $config = $this->config;
        $self = $this;
        $content = preg_replace_callback($regex, function($link) use ($htmlHeaders, $config, $self) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($link[0]);
            $x = new \DOMXPath($dom);
            foreach ($x->query("//link") as $item) {
                if ($item->getAttribute('type') == 'text/css') {
                    $attributes = array();
                    foreach ($item->attributes as $attribute_name => $attribute_node) {
                        $attributes[$attribute_name] = $attribute_node->nodeValue;
                    }
                    if ($item->getAttribute('href')) {
                        $htmlHeaders->addCss($attributes);
                    } elseif ($config['CssIntegrateInline']) {
                        $attributes['value'] = $item->nodeValue;
                        $self->addCssInline($htmlHeaders, $attributes);
                    }
                }
            }
            return;
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content);
    }

    private function jsAwaysInline($content) {
        return strpos($content, 'document.write');
    }

    public function addJsInline($htmlHeaders, $attributes) {
        if (!$this->jsAwaysInline($attributes['value'])) {
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

    private function organizeJS($htmlHeaders) {
        $htmlContent = $this->DOMHtml->getContent();
        $regex = '/(<script((.|\s)+?)(\/>|<\/script>))(.*?)/';
        $config = $this->config;
        $self = $this;
        $content = preg_replace_callback($regex, function($script) use ($htmlHeaders, $config, $self) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($script[0]);
            $x = new \DOMXPath($dom);
            foreach ($x->query("//script") as $item) {
                if ($item->getAttribute('type') == 'text/javascript') {
                    $attributes = array();
                    foreach ($item->attributes as $attribute_name => $attribute_node) {
                        if ($attribute_name == 'data-main') {
                            $htmlHeaders->setMainJsScript($attribute_node->nodeValue);
                        }
                        $attributes[$attribute_name] = $attribute_node->nodeValue;
                    }
                    if ($item->getAttribute('src')) {
                        $htmlHeaders->addJs($attributes);
                    } elseif ($config['JavascriptIntegrateInline']) {
                        $attributes['value'] = $item->nodeValue;
                        $self->addJsInline($htmlHeaders, $attributes);
                    }
                }
            }            
            return;
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content);
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
        $html = preg_replace('/<!--[^\[].*-->/', '', $html);
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
        $this->organizeHeaderOrder();
        $this->removeMetaCharset();
        $this->imgLazyLoad();
        if ($this->config['JavascriptIntegrate']) {
            $this->javascriptIntegrate();
        }
        if ($this->config['CssIntegrate']) {
            $this->cssIntegrate();
        }
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
        /*
          if ($this->config['RemoveMetaCharset']) {
          $DOMHtml = DOMHtml::getInstance();
          //$dom = $DOMHtml->getDom();
          $x = new \DOMXPath($dom);
          if ($x) {
          foreach ($x->query("//meta") as $item) {
          if ($item->getAttribute('charset')) {
          $item->parentNode->removeChild($item);
          }
          }
          }
          }
         */
    }

    private function lazyLoadHead() {
        if ($this->config['LazyLoadJsFile'] || $this->config['LazyLoadFadeIn']) {
            $base = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../../public/';
            $path = $this->config['URIBasePath'];
            if ($this->config['LazyLoadJsFile']) {
                $file = $this->config['PublicBasePath'] . $this->config['LazyLoadJsFilePath'] . 'Lazyload.js';
                if (!file_exists($file)) {
                    try {
                        mkdir($this->config['PublicBasePath'] . $this->config['LazyLoadJsFilePath'], 0777, true);
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
                $file = $this->config['PublicBasePath'] . $this->config['LazyLoadCssFilePath'] . 'LazyLoad.css';
                if (!file_exists($file)) {
                    try {
                        mkdir($this->config['PublicBasePath'] . $this->config['LazyLoadCssFilePath'], 0777, true);
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
            $regex = '/(<img((.|\s)+?)(\/>|<\/img>))(.*?)/';
            $config = $this->config;
            $self = $this;
            $content = preg_replace_callback($regex, function($script) use ($htmlHeaders, $config, $self) {
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($script[0]);
                $x = new \DOMXPath($dom);
                foreach ($x->query("//img") as $node) {
                    $img_attrs = array(
                        'src' => Url::normalizeUrl($node->getAttribute('src')),
                        'class' => $node->getAttribute('class')
                    );
                    if ($img_attrs['src']) {
                        $img = $dom->createElement('img');
                        if ($node->hasAttributes()) {
                            foreach ($node->attributes as $attr) {
                                $img->setAttribute($attr->nodeName, $attr->nodeValue);
                            }
                            $img->setAttribute('src', $img_attrs['src']);
                        }
                        $node->setAttribute('class', rtrim(($config['LazyLoadClass']) . ' ' . $img_attrs['class']));
                        $node->setAttribute('data-src', $img_attrs['src']);
                        $node->setAttribute('src', $config['LazyLoadPlaceHolder']);

                        $noscript = $dom->createElement('noscript');
                        $noscript->appendChild($img);
                        $node->parentNode->insertBefore($noscript, $node);
                    }
                }
                $content = $dom->saveHTML();                
                return preg_replace('~<(?:!DOCTYPE|/?(?:\?xml|html|head|body))[^>]*>\s*~i', '', $content);
            }, $htmlContent
            );
            $this->DOMHtml->setContent($content);
            $this->lazyLoadHead();
        }
    }

}
