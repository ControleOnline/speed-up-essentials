<?php

namespace SpeedUpEssentials\Model;

class HtmlHeaders {

    private static $instance;
    private $css;
    private $js;
    private $mainJsScript;

    private function __construct() {
        
    }

    public function getMainJsScript() {
        return $this->mainJsScript;
    }

    public function setMainJsScript($mainJsScript) {
        $this->mainJsScript = $mainJsScript;
    }

    public function setCss($css) {
        $this->css = $css;
    }

    public function setJs($js) {
        $this->js = $js;
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
    }

    public function addCss($css) {
        $this->css[] = $css;
    }

}
