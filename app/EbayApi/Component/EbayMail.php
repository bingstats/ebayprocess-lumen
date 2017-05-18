<?php
/**
 * Created by PhpStorm.
 * User: chain.wu
 * Date: 2017/3/18
 * Time: 10:56
 */

namespace App\EbayApi\Component;


use App\selfLog\Log;

class EbayMail extends EbayComponent
{
    /**
     * @param The interFace address
     */
    private $mailInterface = SEND_MAIL_INTERFACE;

    /**
     * @param The address to be sent
     */
    private $to = MAIL_ALERT_MAILTO;

    /**
     * @param The mail subject
     */
    private $subject = MAIL_ALERT_FROM;

    /**
     * @param Mail header
     */
    private $header = MAIL_ALERT_MAILTITLE;

    /**
     * @param The password of mail host
     */
    private $password = MAIL_ALERT_PASSWORD;

    public function create($config=array(),$className=__CLASS__)
    {
        return parent::create($config,$className);
    }

    /**
     * @usage: send mail
     * @param: $body The content of mail
     * @return mixed|string
     */
    private function sendAlertMail($body)
    {
        $mailInterface = $this->mailInterface;
        $data = array(
            'password' => $this->password,
            'to' => $this->to,
            'subject' => $this->subject,
            'body' => $body,
            'header' => $this->header
        );
        $mailch = curl_init();
        curl_setopt($mailch,CURLOPT_URL,$mailInterface);
        curl_setopt($mailch,CURLOPT_POST,1);
        curl_setopt($mailch,CURLOPT_POSTFIELDS,$data);
        curl_setopt($mailch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($mailch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($mailch,CURLOPT_SSL_VERIFYHOST,0);
        $mailRst = curl_exec($mailch);
        $mailErr = curl_error($mailch);
        curl_close($mailch);
        if(($mailErr != '') || ($mailRst != 1)){
            $result = $mailRst;
            return $result;
        }else{
            $result = "000000";
            return $result;
        }
    }

    /**
     * @usage: function for sending mail
     * @param: $body The content will be sent
     * @return mixed|string
     */
    public function sendMail($body)
    {
        $result = $this->sendAlertMail($body);
        return $result;
    }
    /**
     * usage: function for fill mail body
     * @param: int $storeID and string $storeName
     */
    public function sendEmail($storeID,$storeName)
    {
        $dir = storage_path('runtime/');
        $path = $dir . $storeName . '_tmpEmail.txt';
        $pathBalance = $dir . $storeName . '_balanceTmpEmail.txt';
        $pathAuto = $dir . $storeName . 'halfAutoTmpEmail.txt';
        $pathDouble = $dir . $storeName . '_manualDoubleTmpEmail.txt';
        $pathPrice = $dir . $storeName . '_priceTrackTmpEmail.txt';
        if(file_exists($path) || file_exists($pathBalance) || file_exists($pathAuto) || file_exists($pathDouble) || file_exists($pathPrice)){
            Log::info("Send alert email to ebay manager");
            $content = '<style>table{background: #ddd;font: normal 12px/16px Verdana;} td{background: #fff;padding:5px}</style>';
            if(file_exists($path)){
                $content .= "<b>Alert the cost or sox change of ebay manual items--(Store Name:".$storeName." Store ID: ".$storeID.")</b><br><br>".
                    "<b>Ewiz_Category | Part | ItemID | EwizCost--EbayCost | EwizSox--EbaySox</b><br><br>";
                self::getFileContent($path,$content);
                $content .= "<br><br><br><br>";
            }
            if(file_exists($pathBalance)){
                Log::info("there are items out off balance");
                $content .= "<b>The alert for manual items which are out of balance or doesn't exist on ewiz.com--(Store Name: ".$storeName.
                    " Store ID: ".$storeID.")<br><br></b>";
                $content .= '<table border="1" cellspacing="0" cellpadding="0">';
                $content .= '<tr><td>No.</td><td>Part</td><td>ItemID</td><td>Category</td><td>memo</td></tr>';
                self::getFileContent($pathBalance, $content);
                $content .= "</table><br><br><br><br>";
            }
            if(file_exists($pathAuto)){
                Log::info("there are half_auto items out off balance but sold more than 5");
                $content .= "<b>The alert for half_auto items which are out of balance but sold more than 5--(Store Name: ".
                    $storeName ." Store ID: ". $storeID .")<br><br></b>";
                $content .= '<table border="1" cellspacing="0" cellpadding="0">';
                $content .= '<tr><td>No.</td><td>Part</td><td>ItemID</td><td>Category</td><td>memo</td></tr>';
                self::getFileContent($pathAuto, $content);
                $content .= "</table><br><br><br><br>";
            }
            if(file_exists($pathDouble)){
                Log::info("there are two more same items in the same ebay shop");
                $content .= "<b>Alert that there are two more same items in the same ebay shop--(Store Name: ".
                    $storeName ." Store ID: ". $storeID .")<br><br></b>";
                $content .= '<table border="1" cellspacing="0" cellpadding="0">';
                $content .= '<tr><td>No.</td><td>Part</td><td>ItemID</td></tr>';
                self::getFileContent($pathDouble, $content);
                $content .= "</table>";
            }
            if($content != null){
                $result = $this->sendAlertMail($content);
                if($result == '000000'){
                    Log::info('Succeed to send alert email');
                }else{
                    Log::info('Fail to send alert email');
                }
            }

        }
    }

    /**
     * @param $path
     * @param $content
     */
    public static function getFileContent($path, &$content)
    {
        $contents = array_values(array_unique(file($path)));
        foreach($contents as $k => $con){
            $data = explode('|',$con);
            $content .= '<tr><td>'.($k+1).'</td>>';
            foreach($data as $i => $v){
                if($i == 1)
                    $content .= '<td><a href="http://www.ebay.com/itm/'.trim($v).'">'.trim($v).'</a></td>';
                else
                    $content .= '<td>'.trim($v).'</td>';
            }
            $content .= '</tr>';
            unset($data);
        }
    }

    /**
     * @param $content
     * @param $storeName
     * @param $type
     */
    public static function writeTmpEmail($content, $storeName, $type)
    {
        //compare to storage path
        $relativePath = 'runtime/'.$storeName.'_'.$type.'TmpEmail.txt';
        //txt file storage real path
        $path = storage_path($relativePath);
        $handle = fopen($path,'a+');
        fwrite($handle,$content);
        fclose($handle);
    }

    public static function removeEmailTmp($storeName)
    {
        $tmpName = array(
            $storeName . '_tmpEmail.txt',
            $storeName . '_balanceTmpEmail.txt',
            $storeName . '_halfAutoTmpEmail.txt',
            $storeName . '_manualDoubleTmpEmail.txt',
            $storeName . '_priceTrackTmpEmail.txt',
        );
        foreach ($tmpName as $name) {
            $path = storage_path('runtime/'.$name);
            if(file_exists($path)){
                unlink($path);
            }
        }
    }
}
?>