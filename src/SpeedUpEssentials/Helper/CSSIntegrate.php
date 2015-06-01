<?php

namespace SpeedUpEssentials\Helper;

use SpeedUpEssentials\Model\HtmlHeaders,
    SpeedUpEssentials\Helper\Url,
    SpeedUpEssentials\Helper\File;

class CSSIntegrate {

    /**
     * @var HtmlHeaders
     */
    protected $htmlHeaders;
    protected $config;
    protected $filename;
    protected $content;
    protected $completeFilePath;
    protected $csss;
    public $cssImported;
    protected $font_extensions = array('eot', 'ttf', 'woff');

    public function __construct($config) {
        $this->config = $config;
        $this->htmlHeaders = HtmlHeaders::getInstance();
        $this->csss = $this->htmlHeaders->getCss();
    }

    private function setCssFileName() {
        $css = '';
        foreach ($this->csss AS $key => $csss) {
            foreach ($csss as $item) {
                $css .= Url::normalizeUrl($item['href']);
            }
            $this->filename[$key] = md5($css) . '.css';
        }
    }

    public function integrate() {
        if ($this->csss) {
            if ($this->config['CssIntegrate']) {
                $this->integrateAllCss();
            } else {
                foreach ($this->csss AS $k => $csss) {
                    foreach ($csss as $key => $css) {
                        $j[$k][$key]['type'] = 'text/css';
                        $j[$k][$key]['rel'] = 'stylesheet';
                        if ($this->config['CssMinify'] && is_file(realpath($this->config['PublicBasePath']) . '/' . $css['href'])) {
                            $this->filename[$k] = $this->config['PublicBasePath'] . $this->config['PublicCacheDir'] . $this->config['cacheId'] . $css['href'];
                            if (!is_file($this->filename[$k])) {
                                $this->content[$key] = $this->get_data(realpath($this->config['PublicBasePath']) . '/' . $css['href']);
                                $this->writeCssFile($k);
                            }
                            $j[$k][$key]['href'] = Url::normalizeUrl($this->config['URIBasePath'] . $this->config['PublicCacheDir'] . $this->config['cacheId'] . $css['href']);
                        } elseif ($css['href']) {
                            $j[$k][$key]['href'] = Url::normalizeUrl($css['href']);
                        }
                    }
                }
                $this->htmlHeaders->setCss($j);
            }
        }
    }

    protected function integrateAllCss() {
        $this->setCssFileName();
        foreach ($this->csss AS $key => $csss) {
            $set_css[$key][] = array(
                'href' => Url::normalizeUrl($this->config['URIBasePath'] . $this->config['PublicCacheDir'] . $this->config['cacheId'] . $this->config['CssMinifiedFilePath'] . $this->filename[$key]),
                'type' => 'text/css',
                'rel' => 'stylesheet',
                'media' => $key
            );
            $this->filename[$key] = $this->config['PublicBasePath'] . $this->config['PublicCacheDir'] . '/' . $this->config['cacheId'] . $this->config['CssMinifiedFilePath'] . $this->filename[$key];
            $this->makeFilePath($this->filename[$key], $key);
            if (!file_exists($this->completeFilePath[$key])) {
                foreach ($csss as $item) {
                    $this->content[$key] .= $this->fixUrl($this->get_data($item['href']), Url::normalizeUrl($item['href'])) . PHP_EOL;
                }
                $this->writeCssFile($key);
            }
        }
        $this->htmlHeaders->setCss($set_css);
    }

    public function fixUrl($cssContent, $url) {

        $regex = '/url\s*\(\s*[\'"]?([^\'"\)]+)[\'"]?\s*\)/';
        $css_dir = explode($this->config['CookieLessDomain'], $url);
        $options = $this->config;
        $options['relative_url'] = dirname($css_dir[1]? : $css_dir[0]) . '/';
        $options['font_extensions'] = $this->font_extensions;
        $options['css_domain'] = parse_url($url, PHP_URL_HOST);
        $options['protocol'] = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https:' : 'http:';
        return preg_replace_callback(
                $regex, function($img) use($options) {
            $relative_url = $options['relative_url'];
            $domain = $options['css_domain'];
            if (substr($img[1], 0, 5) != 'data:' && substr($img[1], 0, 2) != '//' && !preg_match('#^https?://#', $img[1])) {
                if ($domain == $_SERVER['HTTP_HOST'] || !$domain) {
                    $domain = $options['CookieLessDomain'];
                    $ext = pathinfo($img[1], PATHINFO_EXTENSION);
                    if (in_array($ext, $options['font_extensions'])) {
                        $domain = $_SERVER['HTTP_HOST'];
                    }
                }
                if (substr($img[1], 0, 1) == '/') {
                    $relative_url = $options['protocol'] . '//' . $domain;
                } else {
                    $relative_url = $options['protocol'] . '//' . $domain . $relative_url;
                }
                $url_img = $relative_url . $img[1];
                return 'url("' . Url::normalizeUrl($url_img) . '")';
            } else {
                return 'url("' . Url::normalizeUrl($img[1]) . '")';
            }
        }, $cssContent
        );
    }

    protected function writeCssFile($key) {
        $this->makeFilePath($this->filename[$key], $key);

        if (!file_exists($this->completeFilePath[$key])) {
            if (!is_dir(dirname($this->completeFilePath[$key]))) {
                mkdir(dirname($this->completeFilePath[$key]), 0777, true);
            }
            if ($this->config['CssMinify']) {
                $cssmin = new \CSSmin();
                $this->content[$key] = $cssmin->run($this->content[$key]);
            }
            if ($this->config['CssSpritify']) {
                $spritify = new Spritify($this->config);
                $this->content[$key] = $spritify->run($this->content[$key]);
            }
            File::put_content($this->completeFilePath[$key], $this->content[$key]);
        }
    }

    protected function get_data($url) {
        $cssUrl = $url;
        if (is_file($this->config['PublicBasePath'] . Url::normalizeUrl($url))) {
            $url = $this->config['PublicBasePath'] . Url::normalizeUrl($url);
            try {
                $data = File::get_content($url);
            } catch (Exception $ex) {
                
            }
        } else {
            if (is_file($this->config['PublicBasePath'] . $url)) {
                $data = File::get_content($this->config['PublicBasePath'] . $url);
            } else {

                if (substr($url, 0, 2) == '//') {
                    $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https:' : 'http:';
                    $url = $protocol . $url;
                }

                $data = File::get_content($url);
            }
        }
        if ($data) {
            $data = $this->removeImports($data, $cssUrl);
        }
        return $data;
    }

    public function removeImports($data, $cssUrl) {
        $sBaseUrl = dirname($cssUrl) . '/';
        $config = $this->config;
        $config['css_url'] = $cssUrl;
        $self = $this;
        return preg_replace_callback(
                '/@import url\(([^)]+)\)(;?)/', function($aMatches) use ($sBaseUrl, $config, $self) {
            $url = Url::normalizeUrl(str_replace(array('"', '\''), '', trim($aMatches[1])));
            return $self::removeImports($self->fixUrl(File::get_content($url), $url), $url);
        }, $data
        );
    }

    protected function makeFilePath($filename, $key) {
        $this->completeFilePath[$key] = $filename;
    }

}
