<?php

namespace SpeedUpEssentials\Helper;

/* * *********************************************************** 
 * This script is developed by Arturs Sosins aka ar2rsawseen, http://webcodingeasy.com 
 * Fee free to distribute and modify code, but keep reference to its creator 
 *
 * This class can generate CSS sprite image from multiple provided images
 * Generated image can be outputted to browser, saved to specified location, etc.
 * This class also generates CSS code for sprite image, using element ID's provided with image.
 * It facilitates CSS sprite implementations to existing website structures
 * 
 * For more information, examples and online documentation visit:  
 * http://webcodingeasy.com/PHP-classes/CSS-sprite-class-for-creating-sprite-image-and-CSS-code-generation
 * ************************************************************ */

class Spritify {

    private $config;
    //image type to save as (for possible future modifications)
    private $image_type = "png";
    //array to contain images and image informations
    private $images = array();
    //array for errors
    private $errors = array();
    private $spritePath;
    private $spriteFilename;

    public function __construct($config) {
        $this->config = $config;
        $this->spritePath = $this->config['BasePath'] . $this->config['PublicCacheDir'] . $this->config['cacheId'] . 'img/';
        if (!is_dir(realpath($this->spritePath))) {
            mkdir($this->spritePath, 0777, true);
        }
    }

    //gets errors
    public function get_errors() {
        return $this->errors;
    }

    public function run($cssContent) {
        $regex = '/(background|background-image):url\s*\(\s*[\'\"]?([^\'\"\)]+)[\'\"]\s*\)(.*?)(\;|\})/';

        preg_match_all($regex, $cssContent, $matches);
        if ($matches[2]) {
            foreach ($matches[2] as $key => $img) {
                $this->add_image(realpath($this->config['BasePath'] . $img), $this->getImgId($img));
            }
        }
        $this->setSpriteFilename();
        $this->makeSprite($this->spritePath, $this->spriteFilename);

        $cssContent = preg_replace_callback(
                $regex, function($img) {
            if (file_exists($this->config['BasePath'] . $img[2])) {
                $return = '';
                /*
                 * @todo Descobrir como resolver o problema de imagens com divs não exclusivas
                 */
                if (trim($img[3])) {
                    $return .= $img[0];
                } else {
                    $return .= 'background-image:url("' . $this->config['URIBasePath'] . $this->config['PublicCacheDir'] . $this->config['cacheId'] . 'img/' . $this->spriteFilename . '");';
                    $return .= $this->getCss($this->getImgId($img[2]));
                    if ($img[4]) {
                        $return.= $img[4];
                    }
                }
                return $return;
            } else {
                return $img[0];
            }
        }, $cssContent
        );
        return $cssContent;
    }

    private function getImgId($imgPath) {
        return md5($imgPath);
    }

    /*
     * adds new image
     * first parameter - path to image file like ./images/image.png
     * second parameter (optiona) - ID of element fro css code generation
     */

    public function add_image($image_path, $id) {
        if (file_exists($image_path)) {
            $info = getimagesize($image_path);
            if (is_array($info)) {
                //$new = sizeof($this->images);
                $this->images[$id]["path"] = $image_path;
                $this->images[$id]["width"] = $info[0];
                $this->images[$id]["height"] = $info[1];
                $this->images[$id]["mime"] = $info["mime"];
                $type = explode("/", $info['mime']);
                $this->images[$id]["type"] = $type[1];
                $this->images[$id]["id"] = $id;
            } else {
                $this->errors[] = "Provided file \"" . $image_path . "\" isn't correct image format";
            }
        } else {
            $this->errors[] = "Provided file \"" . $image_path . "\" doesn't exist";
        }
    }

    //calculates width and height needed for sprite image
    private function total_size() {
        $arr = array("width" => 0, "height" => 0);
        foreach ($this->images as $image) {
            if ($arr["width"] < $image["width"]) {
                $arr["width"] = $image["width"];
            }
            $arr["height"] += $image["height"];
        }
        return $arr;
    }

    private function setSpriteFilename() {
        $name = '';
        if ($this->images) {
            foreach ($this->images as $key => $img) {
                $name .= $img['path'];
            }
        }
        $this->spriteFilename = md5($name);
    }

    //creates sprite image resource
    private function create_image() {
        $total = $this->total_size();
        $sprite = imagecreatetruecolor($total["width"], $total["height"]);
        imagesavealpha($sprite, true);
        $transparent = imagecolorallocatealpha($sprite, 0, 0, 0, 127);
        imagefill($sprite, 0, 0, $transparent);
        $top = 0;
        foreach ($this->images as $image) {
            $func = "imagecreatefrom" . $image['type'];
            $img = $func($image["path"]);
            imagecopy($sprite, $img, ($total["width"] - $image["width"]), $top, 0, 0, $image["width"], $image["height"]);
            $top += $image["height"];
        }
        return $sprite;
    }

    /*
     * generates css code using ID provided when adding images or pseido ID "elem"
     * $path parameter (optional) - takes path to already generated css_sprite file or uses default file for pseudo code generation
     */

    public function getCss($id) {
        $total = $this->total_size();
        $top = $total["height"];
        $css = "";
        foreach ($this->images as $image) {
            if ($image["id"] == $id) {
                $css .= "background-position:" . ($image["width"] - $total["width"]) . "px " . ($top - $total["height"]) . "px;";
                $css .= "background-repeat:no-repeat;";
                //$css .= "width: " . $image['width'] . "px; ";
                //$css .= "height: " . $image['height'] . "px; ";
            }
            $top -= $image["height"];
        }
        return $css;
    }

    /*
     * saves image to path
     */

    public function makeSprite($path, $file) {
        $sprite = $this->create_image();
        $func = "image" . $this->image_type;
        $func($sprite, $path . $file . '.' . $this->image_type);
        $this->spriteFilename = $file . '.' . $this->image_type;
        ImageDestroy($sprite);
    }

}
