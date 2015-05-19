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
require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'tubalmartin' . DIRECTORY_SEPARATOR . 'cssmin' . DIRECTORY_SEPARATOR . 'cssmin.php');


//add_option($name, $value, $deprecated, $autoload);
//get_option($option);
//update_option($option_name, $newvalue);
//add_action('speed_it', 'speed_page');
//
//function speed_page() {
//    echo 'I am in the head section';
//}
//
//add_action('admin_menu', 'my_plugin_menu');
//
//function my_plugin_menu() {
//    add_menu_page('My Plugin Settings', 'Plugin Settings', 'administrator', 'my-plugin-settings', 'my_plugin_settings_page', 'dashicons-admin-generic');
//}
//
//function my_plugin_settings_page() {
//    
//}
//
//add_action('admin_init', 'my_plugin_settings');
//
//function my_plugin_settings() {
//    register_setting('my-plugin-settings-group', 'accountant_name');
//    register_setting('my-plugin-settings-group', 'accountant_phone');
//    register_setting('my-plugin-settings-group', 'accountant_email');
//}

/*
 * @todo  
 * Arrumar os "throw" na classe JSMin
 * Arrumar HTML com scripts estranhos passando pelo DOM
 */

ob_start();

add_action('shutdown', function() {
    $final = '';
    $levels = count(ob_get_level());
    for ($i = 0; $i < $levels; $i++) {
        $final .= ob_get_clean();
    }
    echo apply_filters('final_output', $final);
}, 0);
add_filter('final_output', function($output) {
    $config = array(
        'APP_ENV' => 'production', //Default configs to production or development
        'CookieLessDomain' => str_replace('www.', '', $_SERVER['HTTP_HOST']),
        'charset' => 'utf-8',
        'RemoveMetaCharset' => true,
        'URIBasePath' => '/',
        'PublicBasePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../../',
        'PublicCacheDir' => 'wp-content/cache/',
        'JsAllAsync' => false,
        'JavascriptOnFooter' => true,
        'JavascriptIntegrateInline' => true,
        'CssIntegrateInline' => true,
        'CssSpritify' => false,
        'LazyLoadBasePath' => 'wp-content/cache/',
        'LazyLoadPlaceHolder' => '/wp-content/plugins/speed-up-essentials/public/img/blank.png'
    );


    $SpeedUpEssentials = new \SpeedUpEssentials\SpeedUpEssentials($config, $config['URIBasePath']);
    return $SpeedUpEssentials->render($output);
});
