<?php

namespace SpeedUpEssentials\Model;

class DOMHtml {

    private static $dom;
    private static $content;
    private static $instance;

    private function __construct($charset = 'utf-8') {
        self::$dom = new \DOMDocument('1.0', $charset);
        self::$dom->formatOutput = true;
        self::$dom->preserveWhiteSpace = false;
    }

    public function getDom() {
        return self::$dom;
    }

    public function setContent($content) {
        if (!isset(self::$content)) {
            libxml_use_internal_errors(true);
            self::$content = self::$dom->loadHTML($content);
            libxml_use_internal_errors(false);
        }
    }

    public static function render() {
        return self::$dom->saveHTML();
    }

    public static function getInstance($charset = 'utf-8') {
        if (!isset(self::$instance)) {
            $class = __CLASS__;
            self::$instance = new $class($charset);
        }
        return self::$instance;
    }

}
