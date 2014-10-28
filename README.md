# SpeedUpEssentials #

This software aims to be engaged in any system and without any additional line programming is required, the final code is automatically optimized.

## Features ##
* Minify HTML
* Minify CSS
* Unify CSS
* Minify JavaScript
* Unify Javascript
* LazyLoad Images
* Spritify CSS Imags

## Installation ##
### Composer ###
Add these lines to your composer.json:

```
    "repositories": [
        {
            "type": "vcs",
            "url": "git@bitbucket.org:controleonline/speed-up-essentials.git"
        },
        {
            "type": "vcs",
            "url": "https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port"
        }
    ],
    "require": {
        "controleonline/speed-up-essentials": "*",
        "tubalmartin/cssmin": "*"
    }

```


### Settings ###

**Default settings**
```
#!php
<?php
$config = array(
        'APP_ENV' => 'production', //Default configs to production or development
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
        'CssMinifiedFilePath' => 'css/vendor/ControleOnline/'
);
```
## Using ##

```
#!php
<?php

$config = array(); // If you do not use any configuration, all will be enabled.

$SpeedUpEssentials = new \SpeedUpEssentials($config);
echo  $SpeedUpEssentials->render('<html>.....</html>');
```
## Taking the buffer ##
```
#!php
<?php
ob_start();

/*
* You code here (including echo)
*/

$config = array(); // If you do not use any configuration, all will be enabled.
$SpeedUpEssentials = new \SpeedUpEssentials($config);
echo  $SpeedUpEssentials->render(ob_get_contents());
```
### Zend 2 ###
In your config/application.config.php confiruração add the following:

```
#!php
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
#!php
<?php
namespace YourNameSpace;

return array(
        'SpeedUpEssentials' => array(
                //Configs of SpeedUpEssentials here
         )
);
```