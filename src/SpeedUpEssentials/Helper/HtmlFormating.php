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

        if ($this->config['CssIntegrateInline']) {
            $this->replaceInline($htmlHeaders);
        }
        if ($this->config['CssIntegrate']) {
            $this->organizeCSS($htmlHeaders);
        }
        if ($this->config['JavascriptIntegrate']) {
            $this->organizeJS($htmlHeaders);
        }
    }

    private function removeElements($type, $regex = false) {
        $reg = $regex? : '/<' . $type . '(.*?)[\/>|<\/' . $type . '>]/smix';
        $htmlContent = $this->DOMHtml->getContent();
        $content = preg_replace_callback($reg, function($script) use ($type) {
            return str_replace('<' . $type, '<c_' . $type, $script[0]);
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content? : $htmlContent);
    }

    private function returnElements($type, $regex = false) {
        $reg = $regex? : '/<c_' . $type . '(.*?)[\/>|<\/' . $type . '>]/smix';
        $htmlContent = $this->DOMHtml->getContent();
        $content = preg_replace_callback($reg, function($script)use ($type) {
            return str_replace('<c_' . $type, '<' . $type, $script[0]);
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content? : $htmlContent);
    }

    private function removeConditionals($type) {
        $regex = '/]>(.*?)<!/smix';
        $this->removeElements($type, $regex);
    }

    private function returnConditionals($type) {
        $regex = '/]>(.*?)<!/smix';
        $this->returnElements($type, $regex);
    }

    private function replaceInline($htmlHeaders) {
        $htmlContent = $this->DOMHtml->getContent();
        $regex = '/<style((?:.)*?)>(.*?)<\/style>/smix';
        $config = $this->config;
        $self = $this;
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
            $attributes['value'] = isset($script[2]) ? $script[2] : '';
            $attributes['type'] = isset($attributes['type']) ? $attributes['type'] : 'text/css';
            $attributes = $self->getCssInline($htmlHeaders, $attributes);
            $attributes['data-type'] = 'inline';


            foreach ($attributes as $key => $a) {
                $att .= ' ' . $key . '="' . $a . '"';
            }
            return '<link' . $att . '/>';
        }, $htmlContent
        );
        $this->DOMHtml->setContent($content? : $htmlContent);
    }

    private function organizeCSS($htmlHeaders) {
        $htmlContent = $this->DOMHtml->getContent();
        $regex = '/<link((?:.)*?)(>(.*?)<\/link>|\/>)/smix';
        $config = $this->config;
        $self = $this;
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

    public function jsAwaysInline($content) {
        return strpos($content, 'document.write');
    }

    public function addJsInline($htmlHeaders, $attributes) {

        $file = 'inline' . DIRECTORY_SEPARATOR . md5($attributes['value']) . '.js';
        $completeFilePath = $this->config['BasePath'] . $this->config['PublicCacheDir'] . $file;

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

    public function getCssInline($htmlHeaders, $attributes) {

        $file = 'inline' . DIRECTORY_SEPARATOR . md5($attributes['value']) . '.css';
        $completeFilePath = $this->config['BasePath'] . $this->config['PublicCacheDir'] . $file;

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
        if (!$attributes['rel']) {
            $attributes['rel'] = 'stylesheet';
        }
        if ($this->config['CSSSeparateInline']) {
            $attributes['media'] = $attributes['media'] ? $attributes['media'] . '_inline' : 'all_inline';
        }
        return $attributes;
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
        return preg_replace('/<!--(?!<!)[^\[>](.|\n)*?-->/', '', $content);
    }

    public function render() {
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
        return str_replace('> <', '><', preg_replace($search, $replace, $html));
    }

    private function sentHeaders() {
        headers_sent() ? : header('Content-Type: text/html; charset=' . $this->config['charset']);
    }

    public function format() {
        $this->sentHeaders();
        $this->removeConditionals('link');
        $this->removeConditionals('style');
        $this->removeConditionals('script');
        LazyLoad::imgLazyLoad($this->config);
        $this->organizeHeaderOrder();
        $this->cssIntegrate();
        $this->javascriptIntegrate();
        $this->returnConditionals('link');
        $this->returnConditionals('style');
        $this->returnConditionals('script');
        $this->removeHttpProtocol();
    }

    private function removeHttpProtocol() {
        $scripts = array('img', 'link', 'script');
        $attr = array('src', 'href', 'data-ll');
        foreach ($scripts as $script) {
            $html = $this->DOMHtml->getContent();
            $regex = '/<' . $script . '((?:.)*?)>/smix';
            $content = preg_replace_callback($regex, function($srcpt) use ($attr, $script) {
                $regex_img = '/(\S+)=["\']((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']/';
                preg_match_all($regex_img, $srcpt[1], $matches);
                if (isset($matches[1]) && isset($matches[2])) {
                    foreach ($matches[1] AS $k => $key) {
                        $attributes[trim($key)] = trim($matches[2][$k]);
                    }
                }
                if (isset($attributes) && is_array($attributes)) {
                    foreach ($attr as $att) {
                        if (isset($attributes[$att]) && $attributes[$att]) {
                            $attributes[$att] = Url::normalizeUrl($attributes[$att]);
                        }
                    }
                    $return = '<' . $script;
                    foreach ($attributes as $key => $a) {
                        $return .= ' ' . $key . '="' . $a . '"';
                    }
                    $return .= '>';
                    return $return;
                } else {
                    return $srcpt[0];
                }
            }, $html);
            $this->DOMHtml->setContent($content? : $html);
        }
    }

    private function cssIntegrate() {
        if ($this->config['CssIntegrate']) {
            $CSSIntegrate = new CSSIntegrate($this->config);
            $CSSIntegrate->integrate();
        }
    }

    private function javascriptIntegrate() {
        if ($this->config['JavascriptIntegrate']) {
            $JSIntegrate = new JSIntegrate($this->config);
            $JSIntegrate->integrate();
        }
    }

}
