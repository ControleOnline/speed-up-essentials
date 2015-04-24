<?php

namespace SpeedUpEssentials\Model;

class HtmlHeaders {

    private static $instance;
    private $mainJsScript;
    private $css = array();
    private $js = array();

    private function __construct() {
        
    }

    public function getMainJsScript() {
        return $this->mainJsScript;
    }

    public function setMainJsScript($mainJsScript) {
        $this->mainJsScript = $mainJsScript;
        return $this;
    }

    public function setCss($css) {
        $this->css = $css;
        return $this;
    }

    public function setJs($js) {
        $this->js = $js;
        return $this;
    }

    public static function getInstance() {
        if (!isset(self::$instance)) {
            $class = __CLASS__;
            self::$instance = new $class();
        }
        return self::$instance;
    }

    public function getCss() {
        return $this->css;
    }

    public function getJs() {
        return $this->js;
    }

    public function addJs($js) {
        $this->js[] = $js;
        return $this;
    }

    public function addCss($css) {        
        $css['media'] = isset($css['media']) ? $css['media'] : 'all';
        $this->css[$css['media']][] = $css;
        return $this;
    }

}
