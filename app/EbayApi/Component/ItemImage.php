<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/25
 * Time: 14:24
 */

namespace App\EbayApi\Component;


use App\selfLog\Log;

class ItemImage extends EbayComponent
{
    public $check_type = 'file';
    public $img_server_domain = array(
        'http://img1.img.com',
        'http://img2.img.com',
        'http://img3.img.com',
    );
    public $maxIndex = 3;
    public $domainIndex = 0;
    public static function create($config=array(), $className=__CLASS__)
    {
        return parent::create($config, $className);
    }
    /**
     * get the list of item's pic by part
     * @access public
     * @param  string $part item part
     * @return array item's pics
     */
    public function getImages($part){
        $ary_images = $ary_gif_images = $ary_jpg_images = array();
        $ary_str = array();
        $image_gif_type = false;
        $dirname = ($this->check_type == 'file') ? "/web" : config('constant.APP_IMG_DOMAIN');
        for($ci = 1;$ci <=13; $ci ++) {
            $ary_str[] = '.'.$ci;
        }
        foreach($ary_str as $av) {
            $base_url = $dirname . config('constant.APP_IMG_DOMAIN_FOLDER') . '/' . $part{0}. '/' . $part{1} . '/' . $part{2} . '/' . $part . '/' . $part;
            $ary_gif_images[] = $base_url.sprintf('%s',$av).'_LG.GIF';
            $ary_jpg_images[] = $base_url.sprintf('%s',$av).'_LG.JPG';
        }
        for($cj = 0 ; $cj <=12 ; $cj++) {
            if($image_gif_type ) {
                $fp = $this->checkImageExist($ary_gif_images[$cj]);
                if(!$fp) {
                    if($cj == 0) {
                        $image_gif_type = false;
                        $cj --;
                    }else {
                        break;
                    }
                }else {
                    $ary_images[] = $ary_gif_images[$cj];
                }
            }else {
                $fp = $this->checkImageExist($ary_jpg_images[$cj]);
                if(!$fp) {
                    break;
                }else {
                    $ary_images[] = $ary_jpg_images[$cj];
                }
            }
        }
        foreach( $ary_images as $k=>$img ) {
            //$ary_images[$k] = str_replace( '/web' , APP_IMG_DOMAIN , $img );
            if($this->domainIndex == $this->maxIndex){
                $this->domainIndex = 0;
            }
            $ary_images[$k] = str_replace( '/web' , $this->img_server_domain[$this->domainIndex] , $img );
            $this->domainIndex++;
        }

        return $ary_images;
    }

    /**
     * get item's preview image path by part
     * @access public
     * @param  string $part item part
     * @param  string $imgSize item image size
     * @param  boolean $checkSize if need to check image size
     * @return string image path
     */
    public function getPreviewImg($part, $imgSize='', $checkSize=false){
        $part = strtoupper($part);
        $imgDir = config('constant.APP_IMG_DOMAIN_FOLDER') . "/".substr($part, 0, 1) . "/" . substr($part, 1, 1) . "/" . substr($part, 2, 1);
        $imgDir = strtr($imgDir, '.', '_') . "/" . $part . "/";;
        $dirname = ($this->check_type == 'file') ? "/web" : config('constant.APP_IMG_DOMAIN');

        if($this->checkImageExist($dirname . $imgDir . $part . $imgSize . '.JPG')){
            $imgPath = $imgDir . $part . $imgSize . '.JPG';
        }elseif($this->checkImageExist($dirname . $imgDir . $part . '.JPG')){
            $imgPath = $imgDir . $part . '.JPG';
        }elseif($this->checkImageExist($dirname . $imgDir . $part . $imgSize . '.jpg')){
            $imgPath = $imgDir . $part . $imgSize . '.jpg';
        }elseif($this->checkImageExist($dirname . $imgDir . $part . '.jpg')){
            $imgPath = $imgDir . $part . '.jpg';
        }elseif($this->checkImageExist($dirname . $imgDir . $part . $imgSize . '.GIF')){
            $imgPath = $imgDir . $part . $imgSize . '.GIF';
        }elseif($this->checkImageExist($dirname . $imgDir . $part . '.GIF')){
            $imgPath = $imgDir . $part . '.GIF';
        }elseif($this->checkImageExist($dirname . $imgDir . $part . $imgSize . '.gif')){
            $imgPath = $imgDir . $part . $imgSize . '.gif';
        }elseif($this->checkImageExist($dirname . $imgDir . $part . '.gif')){
            $imgPath = $imgDir . $part . '.gif';
        }else{
            Log::info("Part : $part previewImg is not exist.");
            $imgPath = config('constant.APP_IMG_DEFAULT');
        }

        // check image size
        if($checkSize && $imgPath != config('constant.APP_IMG_DEFAULT') && !$this->checkImageSize($dirname . $imgPath)){
            $imgPath = config('constant.APP_IMG_DEFAULT');
        }

        // if the format of image is gif, write logs
        $format = substr($imgPath, -3);
        if($format == 'GIF' || $format == 'gif'){
            Log::info("Part: $part $imgPath is GIF/gif");
        }

        $domain = $this->img_server_domain[0];

        return $domain . $imgPath;
    }

    public function checkImageExist($url){
        switch($this->check_type){
            case 'file':
                $rs = file_exists($url);
                break;
            case 'header':
                $header = @get_headers($url);
                if(is_array($header) && !preg_match("/404 Not Found/", $header[0])){
                    $rs = true;
                }else{
                    $rs = false;
                }
                break;
            case 'curl':
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                $contents = curl_exec($ch);
                if(preg_match("/404 Not Found/", $contents)){
                    $rs = false;
                }else{
                    $rs = true;
                }
                break;
        }

        return $rs;
    }

    /**
     * check image size, image must be at lieast 500 pixels on the longest side.
     * @param $imgPath
     * @return bool
     */
    public function checkImageSize($imgPath){
        list($weight, $height) = getimagesize($imgPath);
        if($weight < 500 && $height < 500 ){
            Log::info("Image: $imgPath smaller than 500 pixels on longest side.");
            return false;
        }
        return true;
    }

}
