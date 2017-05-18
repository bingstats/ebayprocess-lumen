<?php

namespace App\EbayApi;

use App\EbayApi\Component\Item;
use DB;
use App\Models\InvoiceNumber;
use App\Models\InvoicePayment;
use App\Models\MasterProductList;
use App\Models\ProductList;
use App\Models\TckEbayTransactionId;
use App\Models\UserAccount;
use App\selfLog\Log;
use App\Models\EbayOrder;
use App\Models\EbayOrderInvoice;
use App\Models\EbayOrderItems;
use App\Models\InvoiceList;
use App\Models\InvoiceItems;
use App\Models\InvoiceBillShip;
use App\Models\InvoiceStatus;
use App\EbayApi\Component\Scoring;
use App\EbayApi\Component\RecycleFee;
use App\EbayApi\Component\PaypalPro;
use App\EbayApi\Component\eBayFeeAdvanced;

class EbayOrderProcess
{
	const MaxRetry = 7;
	const CRLF              = "\n";
    const Mail_To           = 'george.zhao@newbiiz.com,chain.wu@newbiiz.com';
    const Mail_From         = 'ebay_support@newbiiz.com';
    const Mail_Support      = 'support@superbiiz.com';
    const Mail_Dev          = 'george.zhao@newbiiz.com,chain.wu@newbiiz.com';
    const Mail_Ebay         = 'george.zhao@newbiiz.com,chain.wu@newbiiz.com';
    const Mail_Csr          = 'george.zhao@newbiiz.com,chain.wu@newbiiz.com';
    const Mail_Someone      = 'chain.wu@newbiiz.com,george.zhao@newbiiz.com,zachary.zheng@superbiiz.com,wendy.xiao@newbiiz.com';
    const InvPre            = 'E';
    const InvLen            = 7;
    const Channel           = 'ebay';
	const ShippingCostURL   = 'http://192.168.10.132/rest/shipping/channel';
    const CostRate          = 1.065;
    protected $log ;    
    protected $isNewUser    = false;
    protected $feedbackScore = 1;
    protected $gsp           = false;
    protected $store        = '';
    protected $item         = '';
    protected $forAuction   = '';
	protected $ctrl = 1;

    protected $shippingTypes = array(
        'uspsfirstclass'        =>  'USPS First Class',
        'uspspriority'          =>  'USPS Priority Mail',
        'upsground'             =>  'UPS Ground',
        'upsnextdayair'         =>  'UPS Next Day Air',
        #below is from old program
        'ups2ndday'             =>  'UPS 2nd Day Air',
        'upsnextday'            =>  'UPS Next Day Air',
        'upsworldwideexpress'   =>  'UPS Worldwide Express',
        'ups3rdday'             =>  'UPS 3 Day Select',
        'uspsexpressmail'       =>  'USPS Express Mail International',
        'upsworldwideexpedited' =>  'UPS Worldwide Expedited',
    );
	protected $order2inv = array(
		'payer_email' => 'email',
		'mc_shipping' => 'shipping',
		'tax'         => 'tax',
		'mc_gross'    => 'total',
		'insurance_amount' => 'shippingInsurence',
	);
	
	protected $order2item = array(
        'part'                  =>  'part',
        'qty'                   =>  'qty',
        'price'                 =>  'price',
        'cost'                  =>  'cost',
        'item_name'             =>  'itemName',
        'weight'                =>  'weight',
        'item_number'           =>  'ebayItemNum',
    );
	
	protected $orderStatus = array(
        'N'     =>  'New',
        'P'     =>  'this item deEncrpt error',
        'T'     =>  'The %d time to get information from web',
        'W'     =>  'From Web',
        'A'     =>  'From API',
        'Y'     =>  'create invoice successfully',
    );
//filter items in order
	protected $items_p = array(
			'321851990469',
			'201396918200',
			'201396934488',
			'191694757138',
			'191716012117',
			'201450147820',
			'201448983564',
			'201453764163',
			'201449042644',
			'321900369140',
			'321904607723',
			'321892733410',
			'191726803964',
			'191705900673',
	);

	public function index()
	{
		$this->getProcessOrders();
	}

	/**
	 * process ebay order
	 * @return array
	 */
	protected function getProcessOrders()
	{
		Log::info('start process at '.date('Y-m-d H:i:s'));
		$findOrders = EbayOrder::where('verify','N')->orderBy('orderid','asc')->take(60)->get();
		$orders = array();
		foreach($findOrders as $ov){
			Log::info('begin to process orderid '.$ov->orderid);
			$findInv = EbayOrderInvoice::where('paypalTransactionId',$ov->txn_id)->count();
			if($findInv > 0){
				Log::info('mark orderid:'.$ov->orderid.' as D');
				$ov->verify = 'D';
				$ov->save();
				Log::info('orderid :'.$ov->orderid.' pp_txn_id : '.$ov->txn_id);
				continue;
			}
			$findItems = EbayOrderItems::where('orderid',$ov->orderid)->get();
			if(count($findItems) > 0){
				Log::info('process orderid :'.$ov->orderid);
				$itemidAry = $findItems->toArray();
				if(isset($itemidAry[0])){
					foreach($itemidAry as $idAry){
						$item_number = $idAry['item_number'];
						if(in_array($item_number,$this->items_p)){
							Log::info('mark orderid:'.$ov->orderid.' as P');
							$ov->verify = 'P';
							$ov->save();
							Log::info('orderid :'.$ov->orderid.' pp_txn_id : '.$ov->txn_id);
							continue;
						}
					}
				}
				Log::info('begin update orderid '.$ov->orderid);
				//check the order's retry to confirm it is new or a retry order
				if(isset($itemidAry[0]['retry']) && $itemidAry[0]['retry'] >= 8){
					self::retryAdd($ov->orderid);
					$RetryNum = self::retryGet($ov->orderid);
					if($RetryNum > 0){
						if($RetryNum%8 != 0){
							Log::info($ov->orderid." retry > 8");
							continue;
						}
					}
				}
				/**
				 * change the verify in table of ebayOrderItem
				 * 1.N
				 * 2.W
				 * 3.R
				 */
				$status = $this->updateOrderStatus($ov->toArray(),$findItems);
				if($status){
					$findItems = EbayOrderItems::where('orderid',$ov->orderid)->get();
					foreach($findItems->toArray() as $fv){
						switch($this->ctrl){
							case '1' :
								$orderItems[$ov->shipping_method][] = $fv;
								break;
                        	case '2' :
                            	$orderItems[$fv['shipping_method']][] = $fv;
                            	break;
                        	case '3' :
                            	$orderItems[][] = $fv;
                            	break;

							}
					}
					foreach($orderItems as $otv){
						$this->CAnOrder($ov->toArray(),$otv);
					}
				}

			}else{
				Log::info('item is empty');
			}
			$hours = floor((strtotime('now') - strtotime($ov->mdate))/3600);
            $mintues =  ((strtotime('now') - strtotime($ov->mdate))%3600)/60;
			if( $hours == 8 && $mintues < 2 ){
                $one = EbayOrder::where('txn_id',$ov->txn_id)->get();
                if( $one->verify == 'N' ){
                    Log::info("$ov->txn_id is timeout");
                    self::MailAll($ov->txn_id,'paypal transaction is New on status last 8 hours or more');
                }
            }			
		}
		Log::info('end process at'.date('Y-m-d H:i:s'));
		return $orders;
	}
	public function updateOrderStatus($order,$itemAry)
	{
		$okNum = 0;
		$return = false;
		if($order['verify'] == 'N'){
			foreach($itemAry as $iv){
				$verify = substr($iv->verify,0,1);
				$act = substr($iv->verify,1);
				$retry =$iv->verify;
				$itemid = $iv->item_number;
				/**
				 * added for paypal return wrong ebay_txn_id in condition that order includes more than two parts.
				 */
				if(count($itemAry) == 1){
					$txn_id = $iv->ebay_txn_id;
				}else{
					$txn_id = '';
				}
				Log::info('update '.$itemid.' status');
				$this->getItem($itemid);
				$store = $this->item['Item']['Seller']['UserID'];
				$this->store = $store;
				$goFeedback = true;
				if(empty($txn_id)){
					$txnResult = self::EbayTxnid($itemid,$order['txn_id'],$store);
					if($txnResult){
						$txn_id = $txnResult['orderid'];
						$this->feedbackScore = $txnResult['feedbackScore'];
                        $this->gsp = $txnResult['multiLegShip'];
                        $goFeedback = false;
						echo 'txn_id:'.$txn_id;
						$record = EbayOrderItems::find($iv->id);
						$record->ebay_txn_id = $txn_id;
						$record->save();
					}
					
				}
				if($goFeedback)
                {
                    $txnResult2 = self::EbayTxnid($itemid,$order['txn_id'],$store);
					$this->feedbackScore = $txnResult2['feedbackScore'];
                    $this->gsp = $txnResult2['multiLegShip'];
                }
				if($verify == 'N' || $verify == 'A'){
					$api = self::ApiInfor($itemid,$txn_id);
					Log::info('dispatch ApiInfo');
					Log::info($api);
					if($api && $store){
						$record = EbayOrderItems::find($iv->id);
						$record->ebayApiAmt = $api['price'];
						$record->ebayApiUser = $api['userid'];
						$record->store = $store;
						$record->price = $api['listprice'];
						if(empty($this->gsp)){
							$record->shipping_method = $shipping_method = $this->getMapValue($api['shipping_method'],'S');
						}else{
							$shippingOptions = $this->item['Item']['ShippingDetails']['ShippingServiceOptions'];
							if(isset($shippingOptions[0])){
                                    $shipType = $shippingOptions[0]['ShippingService'];
                                }else{
                                    $shipType = $shippingOptions['ShippingService'];
                                }
								$record->shipping_method = $this->getMapValue($shipType,'S');
						}
						$record->verify = 'W';
						$verify = 'W';
						$record->save();
					}else{
						$record = EbayOrderItems::find($iv->id);
						$record->verify = 'AR';
						if($retry < self::MaxRetry){
							$retry++;
							$record->retry = $retry;
							$record->save();
						}else{
							$record->verify = 'AF';
							$record->save();
							self::appErr('orderid :'.$order->orderid.' item -> '.$itemid.' get information from api error');
						}
					}
				}
				if($verify == 'W') {
					$web = self::WebInfor($itemid);
					if ($web) {
						$record = EbayOrderItems::find($iv->id);
						$web['part'] = trim($web['part']);
						$record->part = $web['part'];
						if ($gcost = ProductList::find($web['part'])) {
							$record->cost = $gcost->COST;
						} elseif ($gcost = MasterProductList::find($web['part'])) {
							$record->cost = $gcost->COST;
						}
						if (empty($shipping_method)) $shipping_method = strtolower($record->shipping_method);
						$record->shipping_cost = $this->getShippingCost($web['part'], $record->qty, $shipping_method);
						$record->weight = self::weightCheck($web);
						Log::info('web infor');
						if (!empty($web['part'])) {
							$record->verify = 'R';
							$verify = 'R';
						} else {
							$record->verify = 'WR';
							$verify = 'WR';
							//send mail
							self::MailEbay('orderid :' . $order['orderid'] . ' item -> ' . $itemid . ' get information from api error WR');
						}
						$record->save();
						Log::info("$itemid save");
					} else {
						$record = EbayOrderItems::find($iv->id);
						$record->verify = 'WR';
						if ($retry <= self::MaxRetry) {
							$retry++;
							$record->retry = $retry;
							$record->save();
						} else {
							$record->verify = 'WF';
							$record->save();
							self::MailEbay('orderid :' . $order['orderid'] . ' item -> ' . $itemid . ' get information from api error WF');

						}
					}
				}
				if($verify == 'R' || $verify == 'Y') 
                {
                    $okNum ++;
                }
			}
			if($okNum == count($itemAry)){
                $return = true;
            }
		}
		Log::info($verify);
		Log::info('return '.var_export($return,true));
		return $return ;
	}

	/**
	 * @param $itemid
	 * @param $txn_id
	 * @return array|bool
     */
	public static function ApiInfor($itemid, $txn_id)
	{
		return Item::ApiInfor($itemid,$txn_id);
	}
	public static function WebInfor($itemid)
	{
		return Item::WebInfor($itemid);
	}
	public function getItem($itemid)
	{
		$this->item = Item::getItem($itemid);
		
	}
	protected function getMapValue($index,$type='L')
    {
        /**
         *  L 
         *  S
         *  T
         */
        $indexS = $index;
        $index = strtolower($index);
        if(!in_array($type,array('L','S','T')))
        {
            return $indexS;
        }

        $search = array();

        switch($type)
        {
            case 'L' :
                $search = $this->order2inv;
                break;
            case 'S' :
                $search = $this->shippingTypes;
                break;
            case 'T' :
                $search = $this->order2item;
                break;
        }

        if(isset($search[$index]))
        {
            return $search[$index];
        }

        if($type == 'L' || $type == 'T'){
            return false;
        }

        return $indexS;

    }
	public function getShippingCost($part,$qty,$shipping_method)
	{
		$querystring = 'part='.$part.':'.$qty.'&ship_type_name='.$shipping_method;
		$shipping_cost = 0;
		return self::WShippingCost($part,$qty,$this->getMapValue($shipping_method,'S'));

	}
	public static function WShippingCost($part,$qty,$shipping_method)
	{
		$part =trim($part);
		$shipping_method =strtolower(trim($shipping_method));

		//$url = self::ShippingCostURL.'?part='.urlencode($part).':'.$qty.'&ship_type_name='.urlencode($shipping_method).'&has_discount=1';
		$url = self::ShippingCostURL.'?part='.urlencode($part).':'.$qty.'&shipping_type='.urlencode($shipping_method).'&has_discount=1&source=ebay';
		Log::info('get shipping cost from '.$url);
		$rev = file_get_contents($url);
		Log::info($rev);
		$rev = json_decode($rev,true);
		$rev = array_change_key_case($rev);
		Log::info($rev);

		if(!empty($rev[$shipping_method]))
		{
			return $rev[$shipping_method];
		}

		return 0;

	}
	public static function EbayTxnid($itemid,$pp_txn_id,$store)
	{
		$txn = Item::geteBayTxnidInOwenAPI($itemid,$pp_txn_id,$store);
		if($txn){
			Log::info('get ebay itemid:'.$itemid.' pp_txn_id:'.$pp_txn_id.' ebay_txn_id:'.$txn['orderid'].'feedbackScore:'.$txn['feedbackScore']);
		}else{
			Log::info('fail to get ebay itemid:'.$itemid.' pp_txn_id:'.$pp_txn_id);
		}
		return $txn;
	}
	protected function CAnOrder($order,$items)
	{
		$addOrder = array(
            'channel'           =>  self::Channel,
            'totalcost'         =>  '',
            'itemCost'          =>  '',
            'shippingCost'      =>  '',
            'score'             =>  '',
            'date_time'         =>  date('Y-m-d H:i:s'),
            'uptime'            =>  date('Y-m-d H:i:s'),
            'CCardType'         =>  'PP',
            'name'              =>  $order['first_name'].' '.$order['last_name'],
        );
		
		//@extract($items);
		//@extract($order);
		$payment_status = strtolower($order['payment_status']);
        $for_auction = !empty($order['for_auction']) ? strtolower($order['for_auction']) : '';
       	$this->forAuction = $for_auction;
		if($payment_status != 'completed'){
			if($payment_status == 'refunded' || $payment_status == 'reversed'){
				Log::info('refund order');
			}else{
				//$logString = 'this invoice have not completed'."\n";
				Log::info($logString);
				//send email
				//self::MailCsr($order['txn_id'],$logString);
			}
			$CebayOrder = EbayOrder::find($order['orderid']);
			if(count($CebayOrder) > 0){
				$CebayOrder->verify = 'E';
				$CebayOrder->save();
			}
			return false;
		}
		foreach($order as $ovk => $ovv)
        {
            if($mpv = $this->getMapValue($ovk,'L'))
            {
                $addOrder[$mpv]    = $ovv;
            }
        }
		$addOrderItems = array();
        $eBayFee = 0;

        $totalCost = $itemCost = $shippingCost = $totalWeight = 0;
        $checkShippingType = array();
		foreach($items as $otvk => $otvv)
        {
            //if($ovtv['shipping_method']
            foreach($otvv as $otvvk => $otvvv){
                if($mpv = $this->getMapValue($otvvk,'T'))
                {
                    $addItems[$mpv]   = $otvvv;
                }

                if(in_array($otvvk,array('ebay_txn_id')))
                {
                    $addItems[$otvvk] = $otvvv;
                }
            }

            $itemCost += ($otvv['cost'])*($otvv['qty']);
            $shippingCost += $otvv['shipping_cost'];
            $totalWeight += ($otvv['weight'])* ($otvv['qty']);

            $addItems['txn_id'] = $order['txn_id'];
            $addOrderItems[] = $addItems;
            $checkShippingType[$otvv['shipping_method']] = $otvv['item_number'];
            $eBayFeeArray = eBayFeeAdvanced::getFeeWithItem($otvv);
            if($eBayFeeArray)
			$eBayFee += $eBayFeeArray['finalFee'];
        }
		if(empty($eBayFee)){
            $eBayFee = eBayFeeAdvanced::getFeeWithAmount($addOrder['total']);
        }
		$totalCost = sprintf("%.2f",$eBayFee + $itemCost + $shippingCost + $order['tax']);
		$addOrder['totalcost'] = $totalCost;
        $addOrder['itemCost'] = $itemCost;
        $addOrder['shippingCost'] = $shippingCost;
		if(count($checkShippingType) == 1)
        {
            $variable = array_keys($checkShippingType);
            $addOrder['shippingType'] = array_pop($variable);
        }else if(count($checkShippingType) == 2){
            $variable = array_keys($checkShippingType);
            if(in_array('UPS Ground',$variable)&&in_array('USPS First Class',$variable)){
                $addOrder['shippingType'] = 'UPS Ground';
            }else if(in_array('USPS Priority Mail',$variable)&&in_array('USPS First Class',$variable)){
                $addOrder['shippingType'] = 'USPS Priority Mail';
            }else{
                $alternative = true;
            }
        }else{
           $alternative = true;
        }
		$password   = $this->getRandPwd();
		$address_street = str_replace("\r",' ',$order['address_street']);
        list($address_street,$address_street_second) = array_merge(explode("\n",$address_street),array(''));
        $address_name   = preg_replace("/\s+/","||",$order['address_name']);
        $newName        = explode("||",$address_name);
		$newFirstName    = trim(array_shift($newName));
        $newLastName   = trim(join(' ',$newName));
		$this->isNewUser = false;
		if($uid = $this->CAnUser(array(
			'user'          =>  $order['payer_email'],
            'passwd_new'    =>  md5($password),
            'BFName'        =>  $order['first_name'],
            'BLName'        =>  $order['last_name'],
            'BCompany'      =>  '',
            'BAddrOne'      =>  $order['address_street'],
            'BAddrTwo'      =>  $address_street_second,
            'BCity'         =>  $order['address_city'],
            'BState'        =>  $order['address_state'],
            'BZip'          =>  $order['address_zip'],
            'BCountry'      =>  $order['address_country_code'],
            'BPhone'        =>  '',
            'SFName'        =>  $newFirstName,
            'SLName'        =>  $newLastName,
            'SCompany'      =>  '',
            'SAddrOne'      =>  $address_street,
            'SAddrTwo'      =>  $address_street_second,
            'SCity'         =>  $order['address_city'],
            'SState'        =>  $order['address_state'],
            'SZip'          =>  $order['address_zip'],
            'SCountry'      =>  $order['address_country_code'],
            'sBusiName'     =>  '',
            'regip'         =>  '0',
            'SPhone'        =>  '',
            'businessAddr'  =>  '',
            'AddrOnFile'    =>  '',
            'CCrdPhone'     =>  '',
            'referral'      =>  '',
            'referralid'    =>  '',
            'acctype'       =>  '',
			'newsletter'    =>  'N',
            'datetime'      =>  date('Y-m-d H:i:s'),
            'mtime'         =>  date('Y-m-d H:i:s'),
		))){
			$CebayOrder = EbayOrder::find($order['orderid']);
			if(count($CebayOrder) > 0){
				$CebayOrder->verify = 'Y';
				$CebayOrder->save();
			}
			$invoice    = $this->getInvId();
            $invoiceCnt = $this->getInvCount($uid);
			Log::info('pp_txn_id : '.$order['txn_id']);
			$addOrder           = array_merge(
                $addOrder,
                array(
                    'invoice'   =>  $invoice,
                    'uid'       =>  $uid,
                )
            );
			$this->CAnInvoice($addOrder);
			$this->CAnInvoiceItems($addOrderItems,
                                        array(
                                            'timestamp'         =>  date('Y-m-d H:i:s'),
                                            'orderDateTime'     =>  date('Y-m-d H:i:s'),
                                            'invoice'           =>  $invoice,
                                            'channel'           =>  self::Channel,
                                        )
                                    );
                $this->CInvPayment(array(
                                        'invoice'       =>  $invoice,
                                        'CCardType'     =>  'PP',
                                        'PPEmail'       =>  $order['payer_email'],
                ));
				$this->CInvBillShip(array(
                                        'invoice'       =>  $invoice,
                                        'BFName'        =>  $order['first_name'],
                                        'BLName'        =>  $order['last_name'],
                                        'BAddrOne'      =>  $address_street,
                                        'BAddrTwo'      =>  $address_street_second,
                                        'BCity'         =>  $order['address_city'],
                                        'BState'        =>  $order['address_state'],
                                        'BZip'          =>  $order['address_zip'],
                                        'BCountry'      =>  $order['address_country_code'],
                                        'BPhone'        =>  '',
                                        'SFName'        =>  $newFirstName,
                                        'SLName'        =>  $newLastName,
                                        'SAddrOne'      =>  $address_street,
                                        'SAddrTwo'      =>  $address_street_second,
                                        'SCity'         =>  $order['address_city'],
                                        'SState'        =>  $order['address_state'],
                                        'SZip'          =>  $order['address_zip'],
                                        'SCountry'      =>  $order['address_country_code'],
                                        'SPhone'        =>  '',
                                        'sBusiName'     =>  '',
                ));
				if(!empty($this->gsp)){
					$this->forGSP($invoice);
					$CebayOrder = EbayOrder::find($order['orderid']);
					if(count($CebayOrder) > 0){
						$CebayOrder->isGSP = '1';
						$CebayOrder->save();
						Log::info('Mark orderid:'.$order['orderid'].' as GSP');
					}else{
						Log::info( 'Failed mark orderid:'.$order['orderid'].' as GSP');
					}
				}
				$recycleFee = $this->CEbayRecycleFee(array(
                                        'invoice'       =>  $invoice,
                ));
				 $this->checkorder(array_merge(
                                    $addOrder,
                                    array(
                                        'SAddrOne'      =>  $address_street,
                                        'SAddrTwo'      =>  $address_street_second,
                                        'SState'        =>  $order['address_state'],
                                        'BFName'        =>  $order['first_name'],
                                        'BLName'        =>  $order['last_name'],
                                        'SFName'        =>  $newFirstName,
                                        'SLName'        =>  $newLastName,
                                        'SCity'         =>  $order['address_city'],
                                        'recycleFee'     =>  $recycleFee,
                                        'txn_id'        =>  $order['txn_id'],
                                        'memo'          =>  $order['memo'],
                                        'SCountry'      =>  $order['address_country_code'],
                                        'BCountry'      =>  $order['address_country_code'],
                                        //'totalWeight'   =>  $totalWeight,
                                    )
                                ),$items
                            );
                $scoring = new Scoring();
                $score = $scoring->dojob($invoice);
                $invlist    = InvoiceList::find($invoice);
                if(count($invlist) > 0)
                {
					$invlist->score = $score;
					$invlist->save();
                }
				if(!empty($alternative)){
                    //check multiple shipping ,add by Kewin in 2014-09-16
                    $note = 'diff shipping,UPS and USPS Priority Mail,pls check shipping method';
                    $this->addstatus($invoice,'Hold',$note);
                }else{
                    if(count($checkShippingType) > 1){
                        $note = implode(',',$checkShippingType). ' has diff shipping,check shipping method';
                        $this->addstatus($invoice,'Hold',$note);
                    }
                }
				$CebayOrderItems = EbayOrderItems::where('orderid',$order['orderid'])->get();
				if(count($CebayOrderItems) > 0){
					foreach($CebayOrderItems as $Cv)
					{
						$Cv->verify = 'Y';
						$Cv->save();
					}
				}
			dd($invoice);
				Log::info('create '.$invoice.' done');
		}		
		
	}
	protected function getInvCount($uid)
	{
		return InvoiceList::where('uid',$uid)->count();
	}
	protected function getRandPwd($l=6)
	{
		$rand   = rand(1,10000);
		$index  = $rand % (32 - $l);
		$md5    = md5($rand);
		return substr($md5,$index,$l);
	}
	protected function forGSP($invoice)
	{
		$billship = InvoiceBillShip::find($invoice);
		if(count($billship) > 0){

			$billship->SAddrOne = $this->gsp['GSP_street1'];
			$billship->SAddrTwo = $this->gsp['GSP_street2'];
			$billship->SCity    = $this->gsp['GSP_cityName'];
			$billship->SZip     = $this->gsp['GSP_postalCode'];
			$billship->SState   = $this->gsp['GSP_stateOrProvince'];
			$billship->SCountry = $this->gsp['GSP_country'];
			$billship->sBusiName = "Ref #".$this->gsp['GSP_referenceID'];
			$billship->save();
			Log::info("$invoice GSP update");

		}
	}
	protected function CInvBillShip($data)
	{
		Log::info('insert invoice bill ship');
		$addItem = new InvoiceBillShip;
		foreach($data as $dvk => $dvv)
		{
			$addItem->{$dvk} = $dvv;
		}
		$addItem->save();

	}
	protected function CAnUser($data)
	{
		if(!is_array($data) || empty($data)){
			return false;
		}
		Log::info('create user account');
		//print_r($data);
		$findUser = UserAccount::select('uid')->where('user',$data['user'])->first()->toArray();
		if(count($findUser) > 0){
			Log::info('uid = '.$findUser['uid']);
			return $findUser['uid'];
		}
		$user = new UserAccount;
		foreach($data as $dk => $dv){
			$user->{$dk} = $dv;
		}
		if(!$user->save()){
			return false;
		}
		$this->isNewUser = true;
		$uid = $user->uid;
		Log::info('insert id:'.$uid);
		return $uid;
	}
	protected function CAnInvoice($data)
    {
		$addItem = new InvoiceList;
		foreach($data as $dvk => $dvv){
			$addItem->{$dvk} = $dvv;
		}
		if(!$addItem->save()){
			Log::error('insert invoiceList fail');
			Log::error(var_dump($addItem->getErrors()));
		}

	}
	protected function CInvPayment($data)
	{
		$addItem = new InvoicePayment;
		foreach($data as $dvk => $dvv)
		{
			$addItem->{$dvk} = $dvv;
		}
		$addItem->save();
	}
	protected function checkOrder($order,$items)
	{
		$sigFee = 0;
		$maxQty = 0;
		$status = 'New';
		$part = '';
		$totalAmt = $totalWeight = $totalShippingCost = $totalItemCost = 0;
		$excludeState = array('PR', 'AK', 'HI', 'APO', 'VI', 'GU', 'MP');
		$isCameraNoteBook = false;
		$isCombo = false;
		$isVarious = false;
		$isOpenbox = false;
		$isLimited = false;
		$ssdLimited = false;
		$per_price = $totalItems = 0;
		@extract($order);
		$note = '';
		//get paypal email,add by chain 09/21/2016
		$username='ebay_api1.ewiz.com';
		$password='VME2Q65J4MBC6H6U';
		$signature='AyonNgsS767uocCUuzR4S.uNO7kgAqWZ5D9ozMl8XlS9NGVsR5IMrjaO';
		$paypalPro = new PaypalPro($username,$password,$signature, '', '', TRUE, FALSE);
		$pp_email = self::getTransactionDetail($order['txn_id'],$paypalPro);

		if(is_array($items[0])){
			foreach($items as $iv)
			{
				if($iv['price'] > $per_price){
					$per_price = $iv['price'];
				}
				$totalItems += $iv['qty'];
				if(empty($iv['weight'])){
					$weightNote = true;
				}else if($iv['weight'] > 70){
					$unitWeightNote = true;
				}
				$totalWeight += $iv['weight'] * $iv['qty'];
				$totalItemCost += $iv['cost'] * $iv['qty'];
				$totalShippingCost += $iv['shipping_cost'];
				$totalAmt += $iv['qty'] * $iv['price'];
				if($maxQty < $iv['qty'])
				{
					$maxQty = $iv['qty'];
					$part   .= $iv['part'].' ';
				}

				$cateObj = ProductList::select('COMPONENT')->where('PART',$iv['part'])->first()->toArray();
				if(!$isCameraNoteBook && $cateObj)
				{
					$cate = $cateObj['COMPONENT'];
					Log::info('part :'.$iv['part'].' cate:'.$cate);
					if(stristr(":57:121:186:577:539:214:215:744:759:686:717:",$cate))
					{
						$isCameraNoteBook = true;
					}
				}

				if(!$isCombo && preg_match('/(lot|combo)/i',$iv['item_name']))
				{
					$isCombo = true;
				}

				if(!$isVarious  && preg_match('/various/i',$iv['item_name']))
				{
					$isVarious  = true;
				}

				if(!$isOpenbox  && preg_match('/(open.*box|used)/i',$iv['item_name']))
				{
					$isOpenbox = true;
				}

				if(!$isLimited  && preg_match('/cpu limited/i',$iv['item_name']))
				{
					$isLimited = true;
				}

				if(!$ssdLimited  && preg_match('/ssd limited/i',$iv['item_name']))
				{
					$ssdLimited = true;
				}
			}
		}
		else
		{
			$per_price = $items['price'];
			$maxQty = $totalItems =$items['qty'];
			if(empty($items['weight'])){
				$weightNote = true;
			}else if($items['weight'] > 70){
				$unitWeightNote = true;
			}
			$totalWeight = $items['weight'] * $items['qty'];
			$totalItemCost = $items['cost'] * $items['qty'];
			$totalShippingCost = $items['shipping_cost'];
			$totalAmt = $items['qty'] * $items['price'];
		}
		$totalAmt += $shipping + $tax + $shippingInsurence;

		if(!isset($shippingType)){
			$shippingType = '';
		}
		//check shipping type and state,add by kewin.jin in 08/10/2013
		if('usps ' != strtolower(substr($shippingType,0,5)) && in_array($SState,$excludeState))
		{
			$status = 'Hold';
			$note .= 'not USPS and chk state';
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}

		//check string,prevent the unreadable character, add by Kewin.Jin in 2013-09-17
		if(self::checkStr($name) == 'C')
		{
			$status = 'Hold';
			$note .= "buyer's name may has issue";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}
		//validate the length of shipping name, add by Kewin.Jin in 2014-04-03
		if(strlen($SFName)>25 || strlen($SLName) >25 )
		{
			$status = 'Hold';
			$note .= "shipping name is too long";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}
		//check feedbackScore
		//added by kewin.jin in 2013-06-06
		if($this->feedbackScore < 2)
		{
			$status = 'Hold';
			$note .= !empty($this->feedbackScore) ? "buyer's feedback score is less than 2" : "buyer's feedback score may be private";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}
		//check GSP, add by Kewin 2014-02-17
		if(!empty($this->gsp))
		{
			$status = 'Hold';
			$note .= 'maybe GSP, '.$this->store." 's listing";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}
		//hold on invoice which country is ID, add by Kewin 2014-07-11
		if(!empty($this->gsp) && $BCountry == 'ID')
		{
			$status = 'Hold';
			$note .= 'GSP ship to ID, maybe fraud';
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}
		//check weight , add by Kewin 2014-05-08
		if(!empty($weightNote)){
			$status = 'Hold';
			$note .= "maybe some item's weight empty";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}
		//check weight of unit item , add by Kewin in 2014-09-16
		if(!empty($unitWeightNote)){
			$status = 'Hold';
			$note .= "unit weight > 70";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}
		//lack variable which is named for_auction ,add by Kewin in 2014-09-23
		if(empty($this->forAuction)){
			$status = 'Hold';
			$note .= "lack variable in IPN, double check please";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}

		if( trim($SState) == 'CA' && (empty($tax)||$tax == '0.00'))
		{
			$status = 'Hold';
			$note .= "CA order,no tax";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}

		if($maxQty > 5 && $status == 'New')
		{
			$status = 'ACCT Qty';
			$note .= $part." : exceed 5 qty <br /> \n";
		}

		if(stristr($shippingType,'overnight') || stristr($shippingType,'2day'))
		{
			$note .= "Possible fraud. Overnight or 2day shipping .<br />";
		}

		if(strlen($SAddrOne) > 30 || strlen($SAddrTwo) >30)
		{
			$status     = 'Hold';
			$note       .= 'Ship address is too long';
		}
		//payer_email different with bill_email,add by Chain.wu in 2016-09-17
		if(strtolower(trim($order['email'])) != strtolower(trim($pp_email)))
		{

			$status     = 'Hold';
			$note       .= 'payer_email is different with bill_email';
		}

		if(preg_match('/"/',$SAddrOne) || preg_match('/"/',$SAddrTwo))
		{
			$status     = 'Hold Addr';
			$note       .= 'pls del "';
		}

		if($checkPo = $this->checkPobox($shippingType,$SAddrOne,$SAddrTwo))
		{
			list($status , $noteExt ) = $checkPo;
			$note .= $noteExt;
			unset($noteExt);
		}

		if(preg_match('/A[AEP]/i',$SState) && 'usps ' != strtolower(substr($shippingType,0,5)))
		{
			$status = 'Hold';
			$note   .= "Not USPS for Military Addr <br /> \n";
		}

		if(preg_match("/17146 ne sandy blvd/i",$SAddrOne))
		{
			$status     = 'ACCT Verify 2';
			$note       .= "Fraud Addr Found. <br /> \n";
		}

		if (($BCountry!='US')||($SCountry!='US')){
			$status = "Hold Addr";
			$note .= "Non US address!! Do NOT process this order. Please contact song for shipping quote.<br>\n";

			$invlist    = InvoiceList::model()->findByPk($invoice);
			if($invlist)
			{
				$invlist->addrAlert = 'Y';
				$invlist->save();
			}
		}

		if($shippingType == 'USPS First Class' && $totalWeight*16 > 10)
		{
			$status     = 'Hold';
			$note       .= "USPS FC & > 10oz<br />";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}
		//hold when weight is more than 150 lbs ,add by kewin in 2014.09.13
		if($totalWeight > 150){
			$status = 'Hold';
			$note .= 'weight > 150 lbs';
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}

		if($total > 750)
		{
			$status         = 'Hold';
			$note          .= "Order total > $750.";
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}

		/*
         * per apple's request , need check gsp invoice
         * a.   The delivery address must be a residence or street address, not a P.O. Box, FPO or APO address. (Exception: P.O. Box addresses in Canada are fine.)
         * b.   The package must not weigh more than 66 lbs.
         * c.   The package must not exceed 66 inches in length.
         * d.   The package must not exceed the maximum dimensions of 118 inches.
         * Dimensions = length + girth (the length of a string wrapped around the 2 smaller sides)
         * e.   The package must not exceed the maximum dimensional (DIM) weight of 66 lbs. Dimensional weight = (length x width x height) / (dimensional factor) where the dimensional factor = 166.
         * f.   An item's sale price, excluding shipping and handling, must not be more than:
         *
         * $500 when shipping to the Philippines
         * $1,000 when shipping to Mexico
         * $1,350 when shipping to Russia
         * $2,500 when shipping to all other countries
         *
         *
         */
		if(!empty($this->gsp))
		{
			if($totalWeight > 66)
			{
				$status     = 'Hold';
				$note       .= "GSP exceed 66 lbs";
			}

			if($BCountry == 'PH' && $totalAmt > 500)
			{
				$status     = 'Hold';
				$note       .= "Philippines exceed 500(GSP).";
			}

			if($BCountry == 'MX' && $totalAmt > 1000)
			{
				$status     = 'Hold';
				$note       .= "Mexico exceed 1000(GSP).";
			}

			if($BCountry == 'RU' && $totalAmt > 1350)
			{
				$status     = 'Hold';
				$note       .= "Russia exceed 1350(GSP).";
			}

			if($totalAmt > 2500)
			{
				$status     = 'Hold';
				$note       .= "GSP exceed 2500";
			}
		}
		if(strlen($note) > 200){
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}
		// check black list
		$blackCheck     = $this->checkBlack(array(
			'uid'       => $uid,
			'email'     =>  $email,
			'SFName'    =>  $SFName,
			'SLName'    =>  $SLName,
			'BFName'    =>  $BFName,
			'BLName'    =>  $BLName,
			'SState'    =>  $SState,
			'SAddrOne'  =>  $SAddrOne,
			'SCity'     =>  $SCity,
		));

		if(false !== $blackCheck)
		{
			$status     = 'Hold BL';
			$note      .= 'Possible Black Listed customer , please verify. Invoice#: '.$blackCheck."<BR />";
			$invlist    = InvoiceList::find($invoice);
			if(count($invlist) > 0)
			{
				$invlist->inBlackList = 'Y';
				$invlist->save();
			}
		}

		/*
        if($shippingType == 'USPS First Class' && $totalWeight*16 > 10)
        {
            $status     = 'Hold';
            $note       .= 'USPS FC & > 10oZ';
        }
         */


		//$totalCost = $sigFee + $totalShippingCost + $totalItemCost * self::CostRate + $tax + $recycleFee;

		$totalCost = $sigFee + $totalShippingCost + $totalItemCost + eBayFeeAdvanced::getFeeWithAmount($total) + $tax + $recycleFee;

		if($totalCost > $total)
		{
			$status         = 'ACCT Verify';
			$note          .= 'Total Cost:'.$totalCost.' - Total Charged: '.$total." <br />\n";
		}

		Log::info('totalAmt = '.$totalAmt);
		$totalAmt = (string)$totalAmt;

		//check price > 499, add by kewin jin 16/12/2013
		if($per_price > 499 && $totalItems != 1)
		{
			$status         = 'Hold';
			$note           .= "item > $499<br />\n";
		}

		if($total != $totalAmt)
		{
			$status         = 'Hold';
			$note           .= 'Amt '.$total.' is not correct';
		}

		$dupInvoices        = $this->checkDupInvoice($invoice,$uid,$total,$txn_id);
		if(false != $dupInvoices)
		{
			$status         = 'Dup';
			$note           .= "Duplicate order as $dupInvoices <br /> \n";
		}

		if($total > 1500)
		{
			$status         = 'ACCT Verify';
			$note           .= "Order total > $1,500.00. <br /> \n";
		}

		if($total >= 399 && $maxQty == 1 && $totalWeight > 20)
		{
			$status         = 'ACCT Verify';
			$note           .= "Single item with order total >= $399.00 and weight > 20lbs<br /> \n";
		}

		if($total > 499 && $maxQty == 1)
		{
			$status         = 'ACCT Verify';
			$note           .= "Single item with order total > $499.00. <br /> \n";
		}

		if($total > 500 && preg_match('/usps/i',$shippingType))
		{
			$status         = 'Hold';
			$note           .= "Price > 500 ,use $shippingType <br>\n";
		}

		if($total > 100 && preg_match('/usps first class/i',$shippingType))
		{
			$status         = 'Hold';
			$note           .= "Price > 100, use $shippingType <br />\n";
		}

		if($isCameraNoteBook)
		{
			$status         = 'ACCT Verify';
			$note           .= "Digital cameras,notebooks,tablets,LCD projectors or cell phones <br />\n";
		}
		if(strlen($note) > 200){
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}

		if($isCombo)
		{
			$status         = 'Hold';
			$note           .= "Possible Combo <br />";
		}

		if($isVarious)
		{
			$status         = 'Hold';
			$note           .= "Possible Various<br />";
		}

		if($isOpenbox)
		{
			$status         = 'Hold';
			$note           .= "Possible Openbox<br />";
		}

		if($isLimited)
		{
			$status         = 'Hold';
			$note           .= "Possible CPU Limited<br />";
		}

		if($ssdLimited)
		{
			$status         = 'Hold';
			$note           .= "item is on julie's hand, pls check with her<br />";
		}

		if(!empty($memo))
		{
			$status         = 'Hold';
			$note           .= "ct's note : ".$memo.'<br />';
			$this->addStatus($invoice,$status,$note,'');
			$note = '';
		}

		if((float)$recycleFee > 0)
		{
			$status         = 'Hold';
			$note           .= 'CED item ,Recycling Fee $'.$recycleFee.'<br />';
		}

		if($BFName != $SFName || $BLName != $SLName){
			$status         = 'ACCT Verify 1';
			$note           .= 'Bill name is different to Ship Name';

		}

		$priceVerify = 0;
		$qtyVerify = 0;

		if($status == 'ACCT Verify' || $priceVerify)
		{

		}

		if($qtyVerify)
		{
			$status         = 'ACCT Qty';
		}

		if($priceVerify)
		{
			$status         = 'ACCT Verify';
		}

		$note .= "<br>ppTxnId:$txn_id";
		$note .= "<br>Gross paypal payment recieved:$total";

		$this->addStatus($invoice,$status,$note,'');
	}
	protected function addStatus($invoice,$status='New',$note='',$employee='')
	{
		$invList        = InvoiceList::find($invoice);
		if(!empty($invList))
		{
			$invList->status    = $status;
			$invList->employee  = $employee;
			$invList->save();

			$invStatus          = new InvoiceStatus();
			$invStatus->invoice = $invoice;
			$invStatus->status  = $status;
			$invStatus->note    = substr($note,0,255);
			$invStatus->name    = $employee;
			$invStatus->timestamp = date('Y-m-d H:i:s');
			$invStatus->save();
			Log::info('add '.$invoice.' '.$status.' ');
			//var_dump($invStatus->getErrors());
		}
	}
	protected function checkPobox($shippingType,$SAddrOne,$SAddrTwo)
	{

		$keywords = array(
			'box',
			'apo',
			'fpo',
			'dpo',
			'pob',
		);



		foreach($keywords as $kv)
		{
			//if(false == stristr($SAddrOne,'box') || false == stristr($SAddrTwo,'box'))
			if(preg_match("/$kv/i",$SAddrOne) || preg_match("/$kv/i",$SAddrTwo))
			{
				return array('POBOX',"Possible PO Box address. <br /> \n");
			}

		}

		return false;
	}
	protected function checkDupInvoice($invoice,$uid,$total,$txnid)
	{
		$fromDate = date('Y-m-d H:i:s',strtotime('-3 day'));
		$findDup = InvoiceList::where([

				['uid','=',$uid],
				['date_time','>',$fromDate],
				['invoice','<>',$invoice],

		])
		->get()
		->toArray();
		if(count($findDup) > 0)
		{
			$invoices = false;
			foreach($findDup as $fv)
			{
				if($fv['total'] == $total)
				{
					$invoices = $fv['invoice'].' ';
				}
			}

			return $invoices;

		}
		return false;
	}
	protected function checkBlack($data)
	{
		foreach($data as $dk => $dv)
		{
			$data[$dk] = addslashes(trim($dv));
		}
		@extract($data);
		/*
        $black = Yii::app()->db->createCommand()
                ->select('invoice')
                ->from('blacklist')
                ->where("(fname=:fname and lname = :lname ) or uid = :uid or email = :email
                        or (addr like :addr and city like :city and state like :state) ",
                        array(
                            ':fname'    =>  $SFName,
                            ':lname'    =>  $SLName,
                            ':uid'      =>  $uid,
                            ':email'    =>  $email,
                            ':addr'     =>  '%'.$SAddrOne.'%',
                            ':city'     =>  '%'.$SCity.'%',
                            ':state'    =>  '%'.$SState.'%',
                        )
                    )->queryAll();
         */
		$newAddr = trim(preg_replace('/[0-9]/','',$SAddrOne));
		$black = DB::table('blacklist')
			->select('invoice')
			->where([
				['fname','=',$SFName],
				['lname','=',$SLName],
			])
			->orWhere('uid','=',$uid)
			->orWhere('email','=',$email)
			->orWhere([
				['fname','=',$BFName],
				['lname','=',$BLName],
			])
			->orWhere([
				['addr','like','%$SAddrOne%'],
				['city','like','%$SCity%'],
				['state','like','%$SState%'],
			])
			->orWhere([
				['addr','like','%$newAddr%'],
				['city','like','%$SCity%'],
				['state','like','%$SState%'],
				])
			->orderBy('timestamp','desc')
			->limit(1)
			->get();
//			->setText("select invoice from blacklist where (fname = '$SFName' and lname = '$SLName') or uid = '$uid' or email = '$email'
//                    or (fname = '$BFName' and lname = '$BLName')
//                    or (addr like '%$SAddrOne%' and city like '%$SCity%' and state like '%$SState%')
//                    or (addr like '%$newAddr%' and city like '%$SCity%' and state like '%$SState%')
//                    order by timestamp desc limit 1")->queryAll();
		/*
        echo "select invoice from blacklist where (fname = '$SFName' and lname = '$SLName') or uid = '$uid' or email = '$email'
                    or (addr like '%$SAddrOne%' and city like '%$SCity%' and state like '%$SState%') ";
         */
		if(count($black) > 0)
		{
			$ids = '';
			foreach($black as $bl)
			{
				$ids .= $bl['invoice'];
			}

			return $ids;
		}
		else
			return false;

	}

	public static function getTransactionDetail($transactionID,$paypalObj)
	{
		$method = 'gettransactionDetails';
		$nvpStr = '&TRANSACTIONID='.$transactionID;
		$detailArray =  $paypalObj->hash_call($method,$nvpStr);
		$ack = strtoupper($detailArray["ACK"]);
		if($ack == "Success")
		{
			return $detailArray['EMAIL'];
		}
		else{
			return '';
		}
	}
	private static function checkStr($str)
	{
		$new = ord($str);
		return $new == 26 ? 'C' : 'E';
	}
	/**
	 * deal with ebay order's retry > 8
	 */
	public static function retryAdd($itemId)
	{
		$retry_arr = array();
		$itemId_arr = array();
		$file = fopen('runtime/retrylog/retry.log','r') or exit("Unable to open file!");
		while(!feof($file)){
			$retry = trim(fgets($file));
			$retry = explode('=',$retry);
			if($retry[0]){
				$retryitemId = $retry[0];
				$retrynum = $retry[1];
				$temp[0] = $retryitemId;
				$temp[1] = $retrynum;
				array_push($itemId_arr,$retryitemId);
				array_push($retry_arr,$temp);
			}
		}
		fclose($file);
		if(!in_array($itemId,$itemId_arr)){
			$temp[0] = $itemId;
			$temp[1] = 9;
			array_push($retry_arr,$temp);
		}
		$file = fopen('runtime/retrylog/retry.log','w') or exit('Unable to open file!');
		if($retry_arr){
			foreach($retry_arr as $key => $value){
				$item = $value[0];
				$num = $value[1];
				if(!in_array($item,$itemId_arr)){
					fwrite($file,"$item=9\r\n");
				}elseif($itemId == $item){
					$num++;
					fwrite($file,"$item=$num\r\n");
				}else{
					fwrite($file,"$item=$num\r\n");
				}
			}
		}
		fclose($file);
	}
	/**
	 * get retry from log
	 */
	public static function retryGet($itemId)
	{
		$num = 0;
		$file = fopen('runtime/retrylog/retry.log','r') or exit("Unable to open file!");
		while(!feof($file)){
			$retry = trim(fgets($file));
			$retry = explode('=',$retry);
			if($retry[0]){
				$retryitemId = $retry[0];
				if($itemId == $retryitemId){
					$num = $retry[1];
				}
			}
		}
		fclose($file);
		return $num;
	}
	public static function Mail($from,$to,$subject,$body)
	{
		Log::error($from);
		Log::error($to);
		Log::error($subject);
		if(empty($body)){
			$body = $subject;
		}
		$url = "http://server-km3.ewiz.com/ewiz/postman/postman4ebay.php";
		$data = array(
			'password' => 'eMailWiz',
			'from' => $from,
			'to' => $to,
			'subject' => $subject,
			'body' => $body,
			'type' => 'html',
		);
		$mailch    = curl_init();
		curl_setopt($mailch,CURLOPT_URL, $url);
		curl_setopt($mailch,CURLOPT_POST, 1);
		curl_setopt($mailch,CURLOPT_POSTFIELDS, $data);
		curl_setopt($mailch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($mailch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($mailch, CURLOPT_SSL_VERIFYHOST, 0);
		$mailRst    = curl_exec($mailch);
		$mailErr    = curl_error($mailch);
		if (($mailErr!="") || ($mailRst!=1)){
			return $mailRst;
		}else{
			return true;
		}
	}
	protected function getInvId()
	{
		$invAdd = new InvoiceNumber();
		$invAdd->save();
		$invId = $invAdd->invoice_num;
		$invId = self::InvPre.$this->gen_invoice_number($invId);
		Log::info('invoice number : '.$invId);
		return $invId;
	}
	protected function gen_invoice_number($invoice_number) {
		if (strlen($invoice_number) > 6) {
			// 5 bits in tail
			$invoice_number_tail = intval($invoice_number) % 100000;
			$invoice_number_tail = str_pad($invoice_number_tail, 5, '0', STR_PAD_LEFT);;
			// 6th bit
			$i  = floor(intval($invoice_number) / 100000) - 10;
			if (65 + $i <= 90) {
				$letter = chr(65 + $i);
			} else {
				throw new \Exception('Fail to generate invoice number.');
			}
			$invoice_number = $letter.$invoice_number_tail;
		}
		return $invoice_number;
	}
	protected function CAnInvoiceItems($data,$ext)
	{
		if(isset($data[0]) && is_array($data[0]))
		{
			foreach($data as $dvk => $dvv)
			{
				if(is_array($dvv))
				{
					$this->creAnItems($dvv,$ext);
				}

			}
		}
		else{
			$this->creAnItems($data,$ext);
		}

	}
	protected function creAnItems($data,$ext)
	{
		$addItem = new InvoiceItems;
		$data = array_merge($data,$ext);
		Log::info('create an item : '.$data['ebayItemNum']);
		$fields = $addItem->getTableColumns();
		foreach($data as $dvk => $dvv)
		{
			if(in_array($dvk,$fields)){
				$addItem->{$dvk} = $dvv;
			}
		}
		$this->creInvOrderMap(array(
			'paypalTransactionId'   =>  $data['txn_id'],
			'invoice'               =>  $data['invoice'],
			'ebay_txn_id'           =>  $data['ebay_txn_id'],
		));
		if($addItem->save()) {
			$itemid = $addItem->item_id;
			$tck = new TckEbayTransactionId;
			$tck->item_id = $itemid;
			$tck->ebayTransactionId = $data['ebay_txn_id'];
			$tck->invoice = $data['invoice'];
			$tck->ebayItemNum = $data['ebayItemNum'];
			$tck->save();
		}
	}
	protected function creInvOrderMap($data)
	{
		Log::info('create order map '.$data['paypalTransactionId'].' '.$data['ebay_txn_id'].$data['invoice']);
	    $addItem = new EbayOrderInvoice;
		foreach($data as $dvk => $dvv){
			$addItem->{$dvk} = $dvv;
		}
		$addItem->save();
	}
	public static function MailAll($subject,$body){
		self::Mail(self::Mail_From,self::Mail_Someone,'paypal '.$subject.' is timeout',$body);
	}

	public static function MailCsr($subject,$body){
		self::Mail(self::Mail_From,self::Mail_Csr,'paypal transaciotn is '.$subject,$body);
	}

	public static function MailUser($data)
	{

		$items = '';
		foreach($data['items'] as $dv){
			$items .= $dv['ebayItemNum'].' ';
		}
		$mail_subject   = 'SuperBiiz.com - Ebay order '.$items;
		$mail_body  = <<<EOT
Dear New Customer,<br/><br/>

Thank you for your recent Ebay order <b><br />
EOT;
		foreach($data['items'] as $dv){
			$mail_body .= '[&nbsp;<a href="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item='.$dv['ebayItemNum'].'">'.$dv['ebayItemNum'].'</a>&nbsp;'.$dv['itemName'].'&nbsp;]<br />';
		}
		$mail_body .= <<<EOT
</b>You can monitor the status of your order through our official SuperBiiz.com store Website. <br/><br/>
Here is your temporary password: <b>{$data['pass']}</b> <br/><br/>
Click <a href="https://www.superbiiz.com/signin.php">here</a> to log in and change your password. <br/><br/>
We will email you when your order ships out and you will be able to track your order shipment status from your account page. <br/><br/>

SuperBiiz specializes in desktop and server hardware and we ship from six distribution centers located across the United States. We stock a tremendous amount of inventory but we also carry a large selection of special order items not shown on the Website. <br/><br/>

If you are a government, resale, SMB or corporate buyer and would like to work with an account manager on a special order or a request for quotation, you can email us at <a href="mailto:sales@superbiiz.com">sales@superbiiz.com</a> or call us at 1-866-931-2075 ext. 3. Account managers can <b>reserve special order items, offer volume pricing, provide discount freight shipping (over 150 lbs.) for qualified orders, and arrange assembly and delivery for large server deployments</b>. <br/><br/>

If you have any questions, you can email us at <a href="mailto:support@superbiiz.com">support@superbiiz.com</a> or visit our <a href="http://www.superbiiz.com/contact.php">Contact Us</a> page. <br/><br/>

SuperBiiz Customer Service Team
EOT;
		self::Mail(self::Mail_Support,$data['to'],$mail_subject,$mail_body);
	}

	public static function MailEbay($str)
	{
		self::Mail(self::Mail_From,self::Mail_Ebay,'ebay process app is error',$str);
	}

	public static function appErr($str)
	{
		self::Mail(self::Mail_From,self::Mail_Dev,'ebay process app is error',$str);
	}
	private static function weightCheck($item)
	{
		$part = $item['part'];
		$weight = $item['weight'];
		if(empty($weight)){
			$product = MasterProductList::find($part);
			$weight = !empty($product) ?  $product->WEIGHT : '';
		}
		return $weight;
	}
	protected function CEbayRecycleFee($data)
	{
		$recycle    = new RecycleFee();
		$fee        = $recycle->getRecycleFee($data['invoice']);
		Log::info($fee);

		if(!empty($fee['fee']))
		{
			$addItem    = new InvoiceSpecial;
			$addItem->invoice = $data['invoice'];
			$addItem->type  = 'RYC';
			$addItem->note  = 'Recycling Fee';
			$addItem->code  = 'DISC-RYC';
			$addItem->price = $fee['fee'];
			$addItem->qty   = 1;
			$addItem->save();

			$addEbay  = new InvoiceEbayRycle;
			$addEbay->invoice   = $data['invoice'];
			$addEbay->fee       = $fee['fee'];
			$addEbay->note      = $fee['comment'];
			$addEbay->save();
		}

		return $fee['fee'];
	}
}