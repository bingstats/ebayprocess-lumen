<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/4/7
 * Time: 15:09
 */

namespace App\EbayApi\Behavior;


use App\EbayApi\BaseItem;
use App\EbayApi\Component\ItemImage;
use App\selfLog\Log;

class AddBehavior extends EbayBehavior implements EbayInterface
{
    public $rules = array(
        array('quantity' => 0), // balance
        array('quantity' => 999,'arrival|than' => 2),
        array('preview_pic' => 'http://img1.com/images/notavailable.jpg'),
        array('catid' => array(582,585,586,588,590,596,597,602,607,608,609,610,612,613,614,621,627,628,629,666,673,719,767)),
        array('catid' => 683,'maker' => 'Samsung'),
        array('maker' => array('Belkin','Linksys')),
        array('part' => array('part1','part2','part3','part3','part4','part5','part6')),
        array('price' => 10000),
        array('price|lthan' => 0.99),
        array('ship_cost' => 0),
        array('title|regex' => '/plantronics/i'),
        array('title|regex' => '/Ceedo/i'),
    );
    public function run($part)
    {
        // TODO: Implement run() method.
      $item = new BaseItem($this->store,$part);
      if (!$item->isExistInEwiz()){
          Log::info('can not get item on company.com,so can not be added');
          return false;
      }
      $data = $item->getEwiz();
      $data['preview_pic'] = ItemImage::create()->getPreviewImg($part, '_LG', true);
      $data['price'] = $item->ebayPrice->getPrice();
      $data['ship_cost'] = $item->ebayPrice->getShipCost();
      if (!$this->checkRule($data)){
          Log::info('the item fit the add rules,so can not be added');
          return false;
      }
      return $item->addItem();
    }
    public function checkRule($data)
    {
        if(!$this->flexibleFilter($data)){
            Log::info("item:{$data['part']} fit the flexible filter rules");
            return false;
        }
        return true;
    }
}
