<?php

namespace SpeedUpEssentials\Helper;

use SpeedUpEssentials\Helper\JSMin,
    SpeedUpEssentials\Model\HtmlHeaders,
    SpeedUpEssentials\Helper\Url;

class JSIntegrate {

    protected $config;
    protected $filename;
    protected $content;
    protected $completeFilePath;
    protected $htmlHeaders;
    protected $jss;

    public function __construct($config) {
        $this->config = $config;
        $this->htmlHeaders = HtmlHeaders::getInstance();
        $this->jss = $this->htmlHeaders->getJs();
    }

    private function setJsFileName() {
        $js = '';
        foreach ($this->jss as $item) {
            $js .= Url::normalizeUrl($item['src']);
        }
        $this->filename = md5($js) . '.js';
    }

    public function integrate() {
        $async = (isset($this->config['JsAllAsync']) ? array('async' => 'async') : false);
        if ($this->jss) {
            if ($this->config['JavascriptIntegrate']) {
                $this->integrateAllJs();
            } else {
                foreach ($this->jss as $key => $js) {
                    $j[$key] = $async;
                    $j[$key]['type'] = 'text/javascript';
                    if ($this->config['JavascriptMinify'] && is_file(realpath($this->config['PublicBasePath']) . '/' . $js['src'])) {
                        $this->filename = $this->config['PublicBasePath'] . $this->config['PublicCacheDir'] . $this->config['cacheId'] . $js['src'];
                        if (!is_file($this->filename)) {
                            $this->content = $this->get_data(realpath($this->config['PublicBasePath']) . '/' . $js['src']);
                            $this->writeJsFile();
                        }
                        $j[$key]['src'] = Url::normalizeUrl($this->config['URIBasePath'] . $this->config['PublicCacheDir'] . $this->config['cacheId'] . $js['src']);
                    } elseif ($js['src']) {
                        $j[$key]['src'] = Url::normalizeUrl($js['src']);
                    }
                }
                $this->htmlHeaders->setJs($j);
            }
        }
    }

    protected function integrateAllJs() {
        $this->setJsFileName();
        $element = ((isset($this->config['JsAllAsync']) && $this->config['JsAllAsync']) ? array('async' => 'async') : false);
        $element['src'] = Url::normalizeUrl($this->config['URIBasePath'] .
                        $this->config['PublicCacheDir'] . $this->config['cacheId'] .
                        $this->config['JsMinifiedFilePath'] .
                        $this->filename);
        $element['type'] = 'text/javascript';
        $mainJsScript = $this->htmlHeaders->getMainJsScript();
        if ($mainJsScript) {
            $element['data-main'] = $mainJsScript;
        }

        $this->htmlHeaders->setJs(array($element));
        $this->filename = $this->config['PublicBasePath'] .
                $this->config['PublicCacheDir'] . $this->config['cacheId'] .
                $this->config['JsMinifiedFilePath'] . $this->filename;
        $this->makeFilePath($this->filename);
        if (!file_exists($this->completeFilePath)) {
            foreach ($this->jss as $item) {
                $this->content .= '/*File: (' . $item['src'] . ')*/' . PHP_EOL;
                $this->content .= $this->get_data($item['src']) . PHP_EOL;
            }
            $this->writeJsFile();
        }
    }

    protected function writeJsFile() {
        $this->makeFilePath($this->filename);
        if (!file_exists($this->completeFilePath)) {
            if (!is_dir(dirname($this->completeFilePath))) {
                mkdir(dirname($this->completeFilePath), 0777, true);
            }
            if ($this->config['JavascriptMinify']) {
                $this->content = JSMin::minify($this->content);
            }
            file_put_contents($this->completeFilePath, $this->content);
        }
    }

    protected function get_data($url) {
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
            $data .= '/*File: (' . $url . ') not found*/';
        }
        return $data;
    }

    protected function makeFilePath($filename) {
        $this->completeFilePath = $filename;
    }

}
