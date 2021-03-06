<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/1
 * Time: 14:54
 */

namespace App\EbayApi\Component;


use App\EbayApi\Lib\Encryption;
use App\selfLog\Log;

class EbayTemplate extends EbayComponent
{
    private $_storeId;
    private $_storeName;
    private $_htmlName = 'default';
    private $_isSony;
    private $_filePath;
    private $_condition;
    private $_templateVar = array();

    public function init()
    {
        if($this->_isSony)
            $this->_htmlName = 'defaultSony';
        $this->_filePath = storage_path('html_template') . $this->_storeName . '_' . $this->_storeId . DIRECTORY_SEPARATOR . $this->_htmlName . '.php';
    }
    public static function create($config = array(), $className = __CLASS__)
    {
        return parent::create($config, $className); // TODO: Change the autogenerated stub
    }
    public function get($part, $item, $desc, $weight, $price, $cost, $ship_type, $ship_cost, $isArrival, $returnType, $manual_desc=false, $editContent=false)
    {
        $imageUrl = ItemImage::create()->getPreviewImg($part);
        $imageList = ItemImage::create()->getImages($part);
        if(empty($imageList))
            $imageList = array($imageUrl);

        $this->_templateVar['item']        = $item;
        $this->_templateVar['desc']        = $desc;
        $this->_templateVar['part']        = $part;
        $this->_templateVar['cost']        = $cost;
        $this->_templateVar['ship_cost']   = $ship_cost;
        $this->_templateVar['ship_type']   = $ship_type;
        $this->_templateVar['price']       = $price;
        $this->_templateVar['weight']      = $weight;
        $this->_templateVar['picurl']      = $imageUrl;
        $this->_templateVar['images']      = $imageList;
        $this->_templateVar['isArrival']   = $isArrival;
        $this->_templateVar['returnType']  = $returnType;
        $this->_templateVar['manual_desc'] = $manual_desc;
        $this->_templateVar['editContent'] = $editContent;
        $this->_templateVar['vict']        = $this->generalEncryptString($this->_templateVar);
        $this->_templateVar['condition'] = $this->_condition;

        return $this->getFileContent();
    }

    public function getFileContent()
    {
        if(file_exists($this->_filePath))
        {
            ob_start();
            extract($this->_templateVar);
            include($this->_filePath);
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }
        else
            Log::info('Template file not exists!');
    }

    public function generalEncryptString($param)
    {
        //$vict = $aryProduct['part'].chr(233).$aryProduct['price'].chr(233).$aryProduct['ship_type'].chr(233).$aryProduct['ship_cost'].chr(233).$aryProduct['ship_cost'].chr(233)."on".chr(233).$aryProduct['cost'].chr(233).$aryProduct['weight'];
        $data = array();
        $data[] = isset($param['part']) ? $param['part'] : '';
        $data[] = isset($param['price']) ? $param['price'] : '';
        $data[] = isset($param['ship_type']) ? $param['ship_type'] : '';
        $data[] = isset($param['ship_cost']) ? $param['ship_cost'] : '';
        $data[] = isset($param['ship_cost']) ? $param['ship_cost'] : '';
        $data[] = 'on';
        $data[] = isset($param['cost']) ? $param['cost'] : '';
        $data[] = isset($param['weight']) ? $param['weight'] : '';
        $vict   = implode(chr(233), $data);
        return Encryption::McryptEncrypt($vict);
    }

    public function setStoreId($value)
    {
        $this->_storeId = $value;
    }

    public function setStoreName($value)
    {
        $this->_storeName = $value;
    }

    public function setIsSony($value)
    {
        $this->_isSony = $value;
    }

    public function setCondition($value)
    {
        $this->_condition = $value;
    }

}