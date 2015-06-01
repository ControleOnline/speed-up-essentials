<?php

namespace SpeedUpEssentials\Helper;

use \Patchwork\JSqueeze;

class JSMin {

    public static function Minify($jsCode) {
        return self::JSMinPHP($jsCode);
    }

    private static function JSMinPHP($buffer) {
        return self::simpleMinify($buffer);
    }

    public static function simpleMinify($buffer) {
        /**
         * @todo Remove Single Line Comments
         */
        /* remove single line comments */
        //$buffer = preg_replace("/(?:\/\/.*)/", "", $buffer);
        /* remove comments */
        $buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/))/", "", $buffer);
        /* remove multiple spaces ) */
        $buffer = trim(preg_replace('/ +/', ' ', $buffer));
        /* remove multiple break lines ) */
        $buffer = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", PHP_EOL, $buffer);
        /* remove tabs, newlines, etc. */
        //$buffer = str_replace(array("\r\n", "\r", "\n", PHP_EOL), '', $buffer);
        
//        $regex = '/[^\/\/]+/ismix';
//        $htmlContent = $this->DOMHtml->getContent();
//        $content = preg_replace_callback($regex, function($script) {
//            return str_replace('<link', '<replace_conditional', $script[0]);
//        }, $htmlContent
//        );
        
        
        /* remove other spaces before/after ) */
        //$buffer = preg_replace(array('(( )+\))', '(\)( )+)'), ')', $buffer);

        return $buffer;
    }

}
