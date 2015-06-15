<?php

namespace SpeedUpEssentials;

//require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'tubalmartin' . DIRECTORY_SEPARATOR . 'cssmin' . DIRECTORY_SEPARATOR . 'cssmin.php');
use SpeedUpEssentials\SpeedUpEssentials,
    Zend\View\Model\ViewModel,
    Zend\View\Renderer\PhpRenderer,
    Zend\View\Resolver\AggregateResolver,
    Zend\View\Resolver\TemplateMapResolver,
    Zend\View\Resolver\RelativeFallbackResolver,
    Zend\View\Resolver\TemplatePathStack;

class WPSpeedUpEssentials {

    protected static $render;
    protected static $myOptions = array(
        'OptimizeAdmin',
        'APP_ENV',
        'charset',
        'RemoveMetaCharset',
        'URIBasePath',
        'BasePath',
        'PublicCacheDir',
        'LazyLoadBasePath',
        'LazyLoadPlaceHolder',
        'JavascriptOnFooter',
        'JavascriptIntegrate',
        'JavascriptIntegrateInline',
        'JsAllAsync',
        'CssMinify',
        'CssIntegrateInline',
        'CssIntegrate',
        'CssSpritify'
    );
    protected static $mySiteOptions = array(
        'CookieLessDomain'
    );

    public static function init() {
        if (filter_input(INPUT_POST, 'update_options')) {
            self::update_options();
        }
        self::$render = new PhpRenderer();
        self::getResolver(self::$render);
        if (get_option('OptimizeAdmin') || !is_admin()) {
            add_action('shutdown', array('\SpeedUpEssentials\WPSpeedUpEssentials', 'shutdown'), 0);
            add_filter('final_output', array('\SpeedUpEssentials\WPSpeedUpEssentials', 'final_output'));
        }
        if (is_admin()) {
            add_action('admin_menu', array('\SpeedUpEssentials\WPSpeedUpEssentials', 'menu'));
        }
    }

    private static function update_options() {
        $options = filter_input_array(INPUT_POST)? : array();
        foreach ($options as $key => $option) {
            if (in_array($key, self::$myOptions)) {
                $o = get_option($key);
                ($o || $o === '0') ? update_option($key, $option) : add_option($key, $option, '', 'yes');
            }
            if (in_array($key, self::$mySiteOptions)) {
                $o = get_site_option($key);
                ($o || $o === '0') ? update_site_option($key, $option) : add_site_option($key, $option, '', 'yes');
            }
        }
    }

    public static function menu() {
        add_options_page('Speed Up Essentials', 'Speed Up Essentials', 'manage_options', 'SpeedUpEssentials', array('\SpeedUpEssentials\WPSpeedUpEssentials', 'plugin_options'));
    }

    private static function getResolver($renderer) {
        $resolver = new AggregateResolver();
        $renderer->setResolver($resolver);
        $map = new TemplateMapResolver(array(
            'layout' => __DIR__ . '/view/layout.phtml'
        ));
        $stack = new TemplatePathStack(array(
            'script_paths' => array(
                dirname(__FILE__) . '/View/'
            )
        ));

        $resolver->attach($map)->attach($stack)->attach(new RelativeFallbackResolver($map))->attach(new RelativeFallbackResolver($stack));
    }

    public static function plugin_options() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $viewModel = new ViewModel(array('foo' => 'bar'));
        $viewModel->setTerminal(true);
        echo self::$render->partial('plugin/options.phtml', $viewModel);
    }

    public static function shutdown() {
        $final = '';
        $levels = count(ob_get_level());
        for ($i = 0; $i < $levels; $i++) {
            $final .= ob_get_clean();
        }
        echo apply_filters('final_output', $final);
    }

    public static function deactivateSpeedUpEssentials() {
        delete_option('OptimizeAdmin');
        delete_option('APP_ENV');
        delete_option('charset');
        delete_option('RemoveMetaCharset');
        delete_option('URIBasePath');
        delete_option('BasePath');
        delete_option('PublicCacheDir');
        delete_option('JsAllAsync');
        delete_option('JavascriptIntegrateInline');
        delete_option('JavascriptOnFooter');
        delete_option('JavascriptIntegrate');
        delete_option('LazyLoadBasePath');
        delete_option('LazyLoadPlaceHolder');
        delete_option('CssSpritify');
        delete_option('CssMinify');
        delete_option('CssIntegrateInline');
        delete_option('CssIntegrate');
        delete_site_option('CookieLessDomain');
    }

    public static function activateSpeedUpEssentials() {
        /*
         * @todo Deactivate plugin if php version is < 5.4
         */
//        if (check_version(PHP_VERSION, '5.4', '>=') >= 0) {
//            deactivate_plugins(plugin_basename(__FILE__));
//            wp_die('This plugin requires PHP Version , >= 5.4.  Sorry about that.');
//        }
        add_option('OptimizeAdmin', 0, '', 'yes');
        add_option('APP_ENV', 'production', '', 'yes');
        add_option('charset', 'utf-8', '', 'yes');
        add_option('RemoveMetaCharset', 1, '', 'yes');
        add_option('URIBasePath', '/', '', 'yes');
        add_option('BasePath', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, '', 'yes');
        add_option('PublicCacheDir', 'wp-content/cache/', '', 'yes');
        add_option('LazyLoadBasePath', 'wp-content/cache/', '', 'yes');
        add_option('LazyLoadPlaceHolder', '/wp-content/plugins/speedupessentials/public/img/blank.png', '', 'yes');
        add_option('JavascriptOnFooter', 1, '', 'yes');
        add_option('JavascriptIntegrate', 0, '', 'yes');
        add_option('JsAllAsync', 0, '', 'yes');
        add_option('JavascriptIntegrateInline', 0, '', 'yes');
        add_option('CssMinify', 1, '', 'yes');
        add_option('CssSpritify', false, '', 'yes');
        add_option('CssIntegrateInline', 1, '', 'yes');
        add_option('CssIntegrate', 1, '', 'yes');
        add_site_option('CookieLessDomain', $_SERVER['HTTP_HOST']);
    }

    public static function final_output($output) {
        $config = wp_load_alloptions();
        $config['CookieLessDomain'] = get_site_option('CookieLessDomain');
        $SpeedUpEssentials = new SpeedUpEssentials($config, $config['URIBasePath']);
        return $SpeedUpEssentials->render($output);
    }

}
