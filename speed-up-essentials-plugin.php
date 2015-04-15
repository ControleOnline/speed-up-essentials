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
        'CookieLessDomain' => 'estatico.'.str_replace('www.', '', $_SERVER['HTTP_HOST']),
        'charset' => 'utf-8',
        'RemoveMetaCharset' => true,
        'URIBasePath' => '/',
        'PublicBasePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../../',
        'PublicCacheDir' => 'wp-content/cache/',
        'LazyLoadImages' => true,
        'LazyLoadClass' => 'lazy-load',
        'LazyLoadPlaceHolder' => 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==',
        'LazyLoadFadeIn' => true,
        'LazyLoadJsFile' => true,
        'HtmlRemoveComments' => true, //Only in Production
        'HtmlIndentation' => true, //Only in development
        'HtmlMinify' => true, //Only in Production
        'JavascriptIntegrate' => true, //Only in Production
        'JavascriptCDNIntegrate' => true,
        'JavascriptMinify' => true, //Only on Production
        'JavascriptOnFooter' => true, 
        'CssIntegrate' => true, //Only in Production
        'CssMinify' => true, //Only in Production        
        'CssRemoveImports' => true,
        'CacheId' => (is_file('.version')) ? file_get_contents('.version') . '/' : date('Y/m/d/H/'),
        'CssSpritify' => false,
        'JsAllAsync' => true
    );


    $SpeedUpEssentials = new \SpeedUpEssentials\SpeedUpEssentials($config, $config['URIBasePath']);
    return $SpeedUpEssentials->render($output);
});
