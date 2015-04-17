<?php

namespace SpeedUpEssentials\Helper;

use \Patchwork\JSqueeze;

class JSMin {

    public static function Minify($jsCode) {

        return $jsCode;
        //return self::JSMinPHP($jsCode);
        //return self::simpleMinify($jsCode);
        //return self::a($jsCode);
        //return self::JSqueeze($jsCode);
    }

    public static function a($js) {
        return \JShrink\Minifier::minify($js);
    }

    public static function JSMinPHP($buffer) {
        return \Rgrove\JSMin::minify($buffer);
    }

    public static function JSqueeze($jsCode) {
        $JSqueeze = new JSqueeze();
        return $JSqueeze->squeeze($jsCode, true, false);
    }

    public static function simpleMinify($buffer) {
        /* remove comments */
        $buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", "", $buffer);
        /* remove tabs, spaces, newlines, etc. */
        $buffer = str_replace(array("\r\n", "\r", "\t", "\n", '  ', '    ', '     '), '', $buffer);
        /* remove other spaces before/after ) */
        $buffer = preg_replace(array('(( )+\))', '(\)( )+)'), ')', $buffer);
        return $buffer;
    }

}
