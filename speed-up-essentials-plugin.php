<?php

/**
 * Plugin Name: Speed Up Essentials
 * Plugin URI: http://www.controleonline.com
 * Description: Minify and Merge HTML,CSS,JS. LazyLoad Images,Spritify CSS Images,Remove (Unify) CSS Imports,Static files on cookieless domain
 * Version: 1.0.0
 * Author: Controle Online
 * Author URI: http://www.controleonline.com
 * License: GPL2
 */
ob_start();
chdir(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../../');
require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
$WPSpeedUpEssentials = \SpeedUpEssentials\WPSpeedUpEssentials::init();