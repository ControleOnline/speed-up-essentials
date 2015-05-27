<?php

namespace SpeedUpEssentials;

//require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'tubalmartin' . DIRECTORY_SEPARATOR . 'cssmin' . DIRECTORY_SEPARATOR . 'cssmin.php');
use SpeedUpEssentials\SpeedUpEssentials;

class WPSpeedUpEssentials {

    public function __construct() {
        register_deactivation_hook(__FILE__, array($this, 'deactivateSpeedUpEssentials'));
        register_activation_hook(__FILE__, array($this, 'activateSpeedUpEssentials'));
        if (!is_admin()) {
            add_action('shutdown', array($this, 'shutdown'), 0);
            add_filter('final_output', array($this, 'final_output'));
        } else {
            add_action('admin_menu', array($this, 'menu'));
        }
    }

    public function menu() {
        add_options_page('Speed Up Essentials', 'Speed Up Essentials', 'manage_options', 'SpeedUpEssentials', array($this, 'plugin_options'));
    }

    public function plugin_options() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        echo '<div class="wrap">';
        echo '<p>Here is where the form would go if I actually had options.</p>';
        echo '</div>';
    }

    public function shutdown() {
        $final = '';
        $levels = count(ob_get_level());
        for ($i = 0; $i < $levels; $i++) {
            $final .= ob_get_clean();
        }
        echo apply_filters('final_output', $final);
    }

    public function deactivateSpeedUpEssentials() {
        delete_option('APP_ENV');
        delete_option('charset');
        delete_option('RemoveMetaCharset');
        delete_option('URIBasePath');
        delete_option('PublicBasePath');
        delete_option('PublicCacheDir');
        delete_option('JsAllAsync');
        delete_option('JavascriptIntegrateInline');
        delete_option('CssSpritify');
        delete_option('LazyLoadBasePath');
        delete_option('LazyLoadPlaceHolder');
        delete_option('JavascriptOnFooter');
        delete_option('JavascriptIntegrate');
        delete_option('CssMinify');
        delete_site_option('CookieLessDomain');
    }

    public function activateSpeedUpEssentials() {
        add_option('APP_ENV', 'production', '', 'yes');
        add_option('charset', 'utf-8', '', 'yes');
        add_option('RemoveMetaCharset', true, '', 'yes');
        add_option('URIBasePath', '/', '', 'yes');
        add_option('PublicBasePath', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR, '', 'yes');
        add_option('PublicCacheDir', 'wp-content/cache/', '', 'yes');
        add_option('JsAllAsync', true, '', 'yes');
        add_option('JavascriptIntegrateInline', true, '', 'yes');
        add_option('CssSpritify', false, '', 'yes');
        add_option('LazyLoadBasePath', 'wp-content/cache/', '', 'yes');
        add_option('LazyLoadPlaceHolder', '/wp-content/plugins/speed-up-essentials/public/img/blank.png', '', 'yes');
        add_option('JavascriptOnFooter', true, '', 'yes');
        add_option('JavascriptIntegrate', true, '', 'yes');
        add_option('CssMinify', true, '', 'yes');
        add_site_option('CookieLessDomain', $_SERVER['HTTP_HOST']);
    }

    public function final_output($output) {
        $config = wp_load_alloptions();
        $config['CookieLessDomain'] = get_site_option('CookieLessDomain');
        $SpeedUpEssentials = new SpeedUpEssentials($config, $config['URIBasePath']);
        return $SpeedUpEssentials->render($output);
    }

}
