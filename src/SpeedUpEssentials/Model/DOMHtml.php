<?php

namespace SpeedUpEssentials\Model;

class DOMHtml {

    private static $content;
    private static $instance;
    private static $charset;

    private function __construct($charset = 'utf-8') {
        self::$charset = $charset;
    }

    public function getContent() {
        return self::$content;
    }

    public function setContent($content) {
        self::$content = $content;
        return self::$instance;
    }

    public static function render() {
        return self::$content;
    }

    public static function getInstance($charset = 'utf-8') {
        if (!isset(self::$instance)) {
            $class = __CLASS__;
            self::$instance = new $class($charset);
        }
        return self::$instance;
    }

}
