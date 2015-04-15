<?php

namespace SpeedUpEssentials\Helper;

use SpeedUpEssentials\Model\HtmlHeaders,
    SpeedUpEssentials\Helper\Url;

class CSSIntegrate {

    protected $config;
    protected $filename;
    protected $content;
    protected $completeFilePath;
    protected $htmlHeaders;
    protected $csss;
    protected $cssImported;

    public function __construct($config) {
        $this->config = $config;
        $this->htmlHeaders = HtmlHeaders::getInstance();
        $this->csss = $this->htmlHeaders->getCss();
    }

    private function setCssFileName() {
        $css = '';
        foreach ($this->csss as $item) {
            $css .= Url::normalizeUrl($item['href']);
        }
        $this->filename = md5($css) . '.css';
    }

    public function integrate() {
        if ($this->csss) {
            if ($this->config['CssIntegrate']) {
                $this->integrateAllCss();
            } else {
                foreach ($this->csss as $key => $css) {
                    $j[$key]['type'] = 'text/css';
                    $j[$key]['rel'] = 'stylesheet';
                    $j[$key]['media'] = 'screen';
                    if ($this->config['CssMinify'] && is_file(realpath($this->config['PublicBasePath']) . '/' . $css['href'])) {
                        $this->filename = $this->config['PublicBasePath'] . $this->config['PublicCacheDir'] . $this->config['cacheId'] . $css['href'];
                        if (!is_file($this->filename)) {
                            $this->content = $this->get_data(realpath($this->config['PublicBasePath']) . '/' . $css['href']);
                            $this->writeCssFile();
                        }
                        $j[$key]['href'] = Url::normalizeUrl($this->config['URIBasePath'] . $this->config['PublicCacheDir'] . $this->config['cacheId'] . $css['href']);
                    } elseif ($css['href']) {
                        $j[$key]['href'] = Url::normalizeUrl($css['href']);
                    }
                }
                $this->htmlHeaders->setCss($j);
            }
        }
    }

    protected function integrateAllCss() {
        $this->setCssFileName();
        $this->htmlHeaders->setCss(
                array(
                    array(
                        'href' =>
                        Url::normalizeUrl($this->config['URIBasePath'] .
                                $this->config['PublicCacheDir'] . $this->config['cacheId'] .
                                $this->config['CssMinifiedFilePath'] .
                                $this->filename),
                        'type' => 'text/css',
                        'rel' => 'stylesheet',
                        'media' => 'screen'
                    )
                )
        );
        $this->filename = $this->config['PublicBasePath'] .
                $this->config['PublicCacheDir'] . '/' . $this->config['cacheId'] .
                $this->config['CssMinifiedFilePath'] . $this->filename;
        $this->makeFilePath($this->filename);
        if (!file_exists($this->completeFilePath)) {
            foreach ($this->csss as $item) {
                $this->content .= $this->get_data($item['href']);
            }
            $this->writeCssFile();
        }
    }

    protected function writeCssFile() {
        $this->makeFilePath($this->filename);
        if (!file_exists($this->completeFilePath)) {
            if (!is_dir(dirname($this->completeFilePath))) {
                mkdir(dirname($this->completeFilePath), 0777, true);
            }
            if ($this->config['CssMinify']) {
                $cssmin = new \CSSmin();
                $this->content = $cssmin->run($this->content);
            }
            if ($this->config['CssSpritify']) {
                $spritify = new Spritify($this->config);
                $this->content = $spritify->run($this->content);
            }
            file_put_contents($this->completeFilePath, $this->content);
        }
    }

    protected function get_data($url) {
        $cssUrl = $url;
        if (is_file($this->config['PublicBasePath'] . Url::normalizeUrl($url))) {
            $url = $this->config['PublicBasePath'] . Url::normalizeUrl($url);
            try {
                $data = Url::get_content($url);
            } catch (Exception $ex) {
                
            }
        } else {
            if (is_file($this->config['PublicBasePath'] . $url)) {
                $data = Url::get_content($this->config['PublicBasePath'] . $url);
            } else {
                $data = Url::get_content($url);
            }
        }
        if (!$data) {
            $data = '/*File: (' . $url . ') not found*/';
        } else {
            $data = $this->removeImports($this->fixUrl($data, $cssUrl), $cssUrl);
        }
        return $data;
    }

    protected function removeImports($data, $cssUrl) {
        $sBaseUrl = dirname($cssUrl) . '/';
        return preg_replace_callback(
                '/@import url\(([^)]+)\)(;?)/', function($aMatches) use ($sBaseUrl) {
            $url = str_replace(array('"', '\''), '', trim($aMatches[1]));
            if (is_file($this->config['PublicBasePath'] . $url)) {
                $newUrl = $this->config['PublicBasePath'] . $url;
                if (!isset($this->cssImported[md5($newUrl)])) {
                    $content = Url::get_content($newUrl);
                    $this->cssImported[md5($newUrl)] = $newUrl;
                }
                return $content;
            } else {
                return '@import url("' . $url . '")';
            }
        }, $data
        );
    }

    protected function fixUrl($data, $cssUrl) {
        $sBaseUrl = dirname($cssUrl) . '/';
        return preg_replace_callback(
                '|url\s*\(\s*[\'"]?([^\'"\)]+)[\'"]\s*\)|', function($aMatches) use ($sBaseUrl) {
            $url = trim($aMatches[1]);
            if ($url['0'] != '/' && !preg_match('#^https?://#', $url)) {
                $newUrl = $sBaseUrl . $url;
            } else {
                $newUrl = $url;
            }
            return 'url("' . $newUrl . '")';
        }, $data
        );
    }

    protected function makeFilePath($filename) {
        $this->completeFilePath = $filename;
    }

}
