<?php

namespace SpeedUpEssentials\Helper;

use SpeedUpEssentials\Model\HtmlHeaders;

class CSSIntegrate {

    protected $config;
    protected $filename;
    protected $content;
    protected $completeFilePath;
    protected $htmlHeaders;
    protected $csss;

    public function __construct($config) {
        $this->config = $config;
        $this->htmlHeaders = HtmlHeaders::getInstance();
        $this->csss = $this->htmlHeaders->getCss();
    }

    private function setCssFileName() {
        $css = '';
        foreach ($this->csss as $item) {
            $css .= $this->config['PublicBasePath'] . $item['href'];
        }
        $this->filename = md5($css) . '.css';
    }

    public function integrate() {
        if ($this->csss) {
            if ($this->config['CssIntegrate']) {
                $this->integrateAllCss();
            } elseif ($this->config['CssMinify']) {
                foreach ($this->csss as $key => $css) {
                    $j[$key]['type'] = 'text/css';
                    $j[$key]['rel'] = 'stylesheet';
                    $j[$key]['media'] = 'screen';
                    if (is_file(realpath($this->config['PublicBasePath']) . '/' . $css['href'])) {
                        $this->filename = $this->config['PublicBasePath'] . $this->config['PublicCacheDir'] . $css['href'];
                        if (!is_file($this->filename)) {
                            $this->content = $this->get_data(realpath($this->config['PublicBasePath']) . '/' . $css['href']);
                            $this->writeCssFile();
                        }
                        $j[$key]['href'] = $this->config['URIBasePath'] . $this->config['PublicCacheDir'] . $css['href'];
                    } else {
                        $j[$key]['href'] = $css['href'];
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
                        $this->config['URIBasePath'] .
                        $this->config['PublicCacheDir'] .
                        $this->config['CssMinifiedFilePath'] .
                        $this->filename,
                        'type' => 'text/css',
                        'rel' => 'stylesheet',
                        'media' => 'screen'
                    )
                )
        );
        $this->filename = $this->config['PublicBasePath'] .
                $this->config['PublicCacheDir'] .
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
            file_put_contents($this->completeFilePath, $this->content);
        }
    }

    protected function get_data($url) {

        $cssUrl = $url;
        if (is_file($this->config['PublicBasePath'] . $url)) {
            $url = $this->config['PublicBasePath'] . $url;
        }

        try {
            $data = file_get_contents($url);
        } catch (Exception $ex) {
            
        }
        if (!$data) {
            $data = '/*File: (' . $url . ') not found*/';
        }
        $data = $this->makeUrl($data, $cssUrl);
        return $data;
    }

    protected function makeUrl($data, $cssUrl) {

        $sBaseUrl = dirname($cssUrl) . '/';
        return preg_replace_callback(
                '|url\s*\(\s*[\'"]?([^\'"\)]+)[\'"]\s*\)|', function($aMatches) use ($sBaseUrl) {
            $url = trim($aMatches[1]);
            if ($url['0'] != '/' && !preg_match("^http(s)?://", $url)) {
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
