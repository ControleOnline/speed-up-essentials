[![Build Status](https://travis-ci.org/ControleOnline/speed-up-essentials.svg)](https://travis-ci.org/ControleOnline/speed-up-essentials)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ControleOnline/speed-up-essentials/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ControleOnline/speed-up-essentials/)
[![Code Coverage](https://scrutinizer-ci.com/g/ControleOnline/speed-up-essentials/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ControleOnline/speed-up-essentials/)
[![Build Status](https://scrutinizer-ci.com/g/ControleOnline/speed-up-essentials/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ControleOnline/speed-up-essentials/)
# SpeedUpEssentials #

This software aims to be engaged in any system and without any additional line programming is required, the final code is automatically optimized.

## Features ##
* Minify HTML
* Minify CSS
* Unify CSS
* Minify JavaScript
* Unify Javascript
* LazyLoad Images
* Spritify CSS Images
* Remove (Unify) CSS Imports
* Static files on cookieless domain

## Installation ##
### Composer ###
Add these lines to your composer.json:

```
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:ControleOnline/speed-up-essentials.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:tubalmartin/YUI-CSS-compressor-PHP-port.git"
        }
    ],
    "require": {
        "controleonline/speed-up-essentials": "*",
        "tubalmartin/cssmin": "*"
    },
    "scripts": {
        "post-update-cmd": [
            "git describe --abbrev=0 --tags > .version"
        ]
    }

```


### Settings ###

**Default settings**
```
<?php
$config = array(
        'APP_ENV' => 'production', //Default configs to production or development
        'CookieLessDomain' => 'static.'.$_SERVER['HTTP_HOST'],
        'charset' => 'utf-8',
        'RemoveMetaCharset' =>true,
        'URIBasePath' => '/',
        'PublicBasePath' => 'public/',
        'PublicCacheDir' => 'public/cache/',
        'LazyLoadImages' =>true,
        'LazyLoadClass' => 'lazy-load',
        'LazyLoadPlaceHolder' => 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==',
        'LazyLoadFadeIn' => true,
        'LazyLoadJsFile' => true,
        'LazyLoadJsFilePath' => 'js/vendor/ControleOnline/',
        'LazyLoadCssFilePath' => 'css/vendor/ControleOnline/',
        'HtmlRemoveComments' => true, //Only in Production
        'HtmlIndentation' => true, //Only in development
        'HtmlMinify' => true, //Only in Production
        'JavascriptIntegrate' => true, //Only in Production
        'JavascriptCDNIntegrate' => true,
        'JavascriptMinify' => true, //Only on Production
        'JsMinifiedFilePath' => 'js/vendor/ControleOnline/',
        'CssIntegrate' => true, //Only in Production
        'CssMinify' => true, //Only in Production
        'CssMinifiedFilePath' => 'css/vendor/ControleOnline/',
        'CssRemoveImports' => true,
        'CacheId' => (is_file('.version')) ? file_get_contents('.version') . '/' : date('Y/m/d/H/')
);
```
### Zend 2 ###
In your config/application.config.php confiruração add the following:

```
<?php
$modules = array(
    'SpeedUpEssentials'
);
return array(
    'modules' => $modules,
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            './vendor',
        ),
        'config_glob_paths' => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
    ),
);
```
In your module.config.php file:

```

<?php
namespace YourNameSpace;

return array(
        'speed_up_essentials' => array(
                //Configs of SpeedUpEssentials here
         )
);
```



## To use without Zend ##

** Send your HTML **
```
<?php

$config = array(); // If you do not use any configuration, all will be enabled.

$SpeedUpEssentials = new \SpeedUpEssentials($config);
echo  $SpeedUpEssentials->render('<html>.....</html>');
```

**OR**


** Taking the buffer **
```

<?php
ob_start();

/*
* You code here (including echo)
*/

$config = array(); // If you do not use any configuration, all will be enabled.
$SpeedUpEssentials = new \SpeedUpEssentials($config);
echo  $SpeedUpEssentials->render(ob_get_contents());
```

##Wordpress Plugin##

[Download plugin](https://wordpress.org/plugins/speedupessentials/)