<?php

namespace SpeedUpEssentials\Model;

class HtmlHeaders {

    private static $instance;
    private $css;
    private $js;

    private function __construct() {
        
    }

    function setCss($css) {
        $this->css = $css;
    }

    function setJs($js) {
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
