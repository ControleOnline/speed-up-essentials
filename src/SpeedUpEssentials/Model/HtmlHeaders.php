<?php

namespace SpeedUpEssentials\Model;

class HtmlHeaders {

    private static $instance;
    private $mainJsScript;
    private $css = array();
    private $js = array();
    private $js_inline = array();
    private $css_inline = array();

    private function __construct() {
        
    }

    function getJsInline() {
        return $this->js_inline;
    }

    function getCssInline() {
        return $this->css_inline;
    }

    function addJsInline($js_inline) {
        $this->js_inline[] = $js_inline;
        return $this;
    }

    function addCssInline($css_inline) {
        $this->css_inline[] = $css_inline;
        return $this;
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
        $this->css[] = $css;
        return $this;
    }

}
