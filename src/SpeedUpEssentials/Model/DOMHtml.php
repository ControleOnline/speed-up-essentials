<?php

namespace SpeedUpEssentials\Model;

class DOMHtml {

    /**
     * @var \DOMDocument
     */
    private static $dom;
    private static $content;
    private static $instance;
    private static $as_html;

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
            self::$content = self::$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $compair = strtolower(mb_substr(trim($content), 0, 5));
            self::$as_html = ($compair == '<html' || $compair == '<!doc') ? true : false;
            libxml_use_internal_errors(false);
        }
    }

    public static function render() {
        if (!self::$as_html) {
            $content = self::$dom->firstChild->firstChild->childNodes;
            for ($i = 1; $i < $content->length; $i++) {
                self::$dom->appendChild($content->item($i));
            }
            self::$dom->removeChild(self::$dom->firstChild);
        }
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
