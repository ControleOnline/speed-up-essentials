<?php

namespace SpeedUpEssentials;

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
        'LazyLoadImages',
        'LazyLoadPlaceHolder',
        'JavascriptOnFooter',
        'JavascriptIntegrate',
        'JavascriptIntegrateInline',
        'JsAllAsync',
        'CssMinify',
        'CssIntegrateInline',
        'CssIntegrate',
        'CssSpritify',
        'CSSSeparateInline',
        'StaticCache',
        'StaticCacheDir'
    );
    protected static $mySiteOptions = array(
        'CookieLessDomain'
    );

    public static function thisPluginLast() {
        $wp_path_to_this_file = preg_replace('/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR . "/$2", __FILE__ . '/../../');
        $this_plugin = plugin_basename(trim($wp_path_to_this_file));
        $active_plugins = get_option('active_plugins');
        $this_plugin_key = array_search($this_plugin, $active_plugins);
        array_splice($active_plugins, $this_plugin_key, 1);
        array_push($active_plugins, $this_plugin);
        update_option('active_plugins', $active_plugins);
    }

    public static function init() {
        if (filter_input(INPUT_POST, 'update_options')) {
            self::update_options();
        }
        if (!get_option('BasePath')) {
            self::activateSpeedUpEssentials();
        }
        self::$render = new PhpRenderer();
        self::getResolver(self::$render);
        if (get_option('OptimizeAdmin') || !is_admin()) {
            add_action('shutdown', array('\SpeedUpEssentials\WPSpeedUpEssentials', 'shutdown'), -999999);
        }
        if (is_admin()) {
            add_action('admin_menu', array('\SpeedUpEssentials\WPSpeedUpEssentials', 'menu'));
            if (get_option('StaticCache')) {
                add_action('save_post', array('\SpeedUpEssentials\Helper\Cache', 'clearPostCache'));
                add_action('edit_terms', array('\SpeedUpEssentials\Helper\Cache', 'clearTermCache'));
            }
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
        echo self::final_output($final);        
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
        delete_option('CSSSeparateInline');
        delete_option('LazyLoadImages');
        delete_option('StaticCache');
        delete_option('StaticCacheDir');
        delete_site_option('CookieLessDomain');
    }

    public static function activateSpeedUpEssentials() {
        $base_path = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $public_cache_dir = 'wp-content/cache/';
        add_option('OptimizeAdmin', 0, '', 'yes');
        add_option('APP_ENV', 'production', '', 'yes');
        add_option('charset', 'utf-8', '', 'yes');
        add_option('RemoveMetaCharset', 1, '', 'yes');
        add_option('URIBasePath', '/', '', 'yes');
        add_option('BasePath', $base_path, '', 'yes');
        add_option('PublicCacheDir', $public_cache_dir, '', 'yes');
        add_option('LazyLoadImages', 0, '', 'yes');
        add_option('LazyLoadBasePath', 'wp-content/cache/', '', 'yes');
        add_option('LazyLoadPlaceHolder', '/wp-content/plugins/speedupessentials/public/img/blank.png', '', 'yes');
        add_option('JavascriptOnFooter', 1, '', 'yes');
        add_option('JavascriptIntegrate', 0, '', 'yes');
        add_option('JsAllAsync', 0, '', 'yes');
        add_option('JavascriptIntegrateInline', 0, '', 'yes');
        add_option('CssMinify', 0, '', 'yes');
        add_option('CssSpritify', 0, '', 'yes');
        add_option('CssIntegrateInline', 0, '', 'yes');
        add_option('CssIntegrate', 0, '', 'yes');
        add_option('CSSSeparateInline', 0, '', 'yes');
        add_option('StaticCache', 0, '', 'yes');
        add_option('StaticCacheDir', $base_path . $public_cache_dir . 'html', '', 'yes');
        add_site_option('CookieLessDomain', $_SERVER['HTTP_HOST']);
    }

    public static function final_output($output) {
        $config = wp_load_alloptions();
        $config['CookieLessDomain'] = get_site_option('CookieLessDomain');
        $SpeedUpEssentials = new SpeedUpEssentials($config, $config['URIBasePath']);
        return $SpeedUpEssentials->render($output, is_user_logged_in());
    }

}
