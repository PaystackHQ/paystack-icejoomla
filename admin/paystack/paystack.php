<?php
/**
 * Paystack extension for Joomla Icebooking Reservation System (http://icejoomla.com)
 *
 * DISCLAIMER
 * This file will not be supported if it is modified.
 *
 * @category  Paystack
 * @package   Paystack_Icebooking
 * @author    Yabacon Valley <yabacon.valley@gmail.com>
 * @copyright 2016 Yabacon Valley. (https://github.com/yabacon/paystack-joomla-icebooking)
 * @license   MIT License (MIT)
 */

defined('_JEXEC') or die ( 'Restricted access' );
require_once(JPATH_COMPONENT_ADMINISTRATOR.DS.'gateways/gatewayInterface.php');
class IcebookingModelPaystack extends GatewayInterface
{

    public function __construct()
    {
        parent::__construct('paystack');
        $this->callbackForward = 0;
        $this->configs = $this->_getConfigurationsArray();
    }

    public function generatePayForm(&$data)
    {
        $lg = JFactory::getLanguage();
        $activeLanguage = $lg->getTag();
        if(!strlen($activeLanguage))
        {
            $activeLanguage = 'en-GB';
        }

        $form = '';
        $postUrl = 'https://paystack.com/pay';
        $data['orderDesc'] = JText::_($data['item_booked']).', '.JText::_('ARRIVAL').' '.$data['arrival'].' '.JText::_('DEPARTURE').' '.$data['departure'];
        $myref = $data['bookingID'].':::'.$this->createhash($data);
    $form .= '<form name="bookingCheckoutForm" id="bookingCheckoutForm" action="'.$postUrl.'" method="post">';
    $form .= '<input type="hidden" name="v_merchant_id" value="'.$this->configs['MERCHANT_ID'].'" />';
        $form .= '<input type="hidden" name="total" value="'.$data['gateway_amount'].'" />';
        $form .= '<input type="hidden" name="merchant_ref" value="'.$myref.'" />';
        $form .= '<input type="hidden" name="notify_url" value="'.$this->getCallbackUrl().'&ref='.urlencode($myref).'" />';
        $form .= '<input type="hidden" name="memo" value="'.$data['orderDesc'].'" />';
         $buttonText = $this->configs['BUTTON_TEXT_'.$activeLanguage];
        $form .= '<input type="image" style="border:none;" src="'.$this->configs['BUYNOW_BUTTON'].'" alt="'.$buttonText.'"/>';
        $form .= '</form>';
        return $form;
    }

    public function createhash($data)
    {
        $str = round($data["gateway_amount"]).$data["bookingID"].$this->configs['MERCHANT_ID'].dirname(__FILE__);
        return md5($str);
    }

    public function getConfigurationForm()
    {
        $configs =& $this->_getConfigurationsArray();
        $iceLang = $this->getInstance('languages','icebookingModel');
        $languages = $iceLang->getFrontendLanguages();
        $firstRun = true;
        $languageClickList = $buttonTextFields = '';
        if(is_array($languages))
        {
            foreach($languages as $key => $value)
            {
                if($firstRun)
                {
                    $languageClickList .= '| <a href="#" class="langSelected" title="'.$key.'">'.$value.'</a>'.' | ';
                    $buttonTextFields .= '<input type="text" class="'.$key.'" size="40" value="'.$configs['BUTTON_TEXT_'.$key].'" name="BUTTON_TEXT_'.$key.'" id="u_'.$key.'"/>';
                }

                else
                {
                    $languageClickList .= '<a href="#" title="'.$key.'">'.$value.'</a>'.' | ';
                    $buttonTextFields .= '<input style="display:none;" type="text" class="'.$key.'" size="40" value="'.$configs['BUTTON_TEXT_'.$key].'" name="BUTTON_TEXT_'.$key.'" id="u_'.$key.'"/>';
                }

                $firstRun=false;
            }

        }

        $form = '';
        $form .= '<div class="gatewayData">';
        $form .=
            '<div class="gatewayForm">
                <label>Merchant ID</label>
                    <div>
            <input type="text" name="MERCHANT_ID" value="'.$configs['MERCHANT_ID'].'"/>
                    </div>
            </div>';
        $form .=
            '<div class="gatewayForm">
                <label>Buy Now Button Image</label>
                    <div>
            <input type="text" name="BUYNOW_BUTTON" value="'.$configs['BUYNOW_BUTTON'].'"/>
                    </div>
            </div>';
        return $form;
    }

    public function storeConfigurationData()
    {
        $data['MERCHANT_ID'] = JRequest::getString('MERCHANT_ID',0,'post');
        $data['BUYNOW_BUTTON'] = JRequest::getString('BUYNOW_BUTTON','http://paystack.com/images/buttons/make_payment_blue.png','post');
        $iceLang = $this->getInstance('languages','icebookingModel');
        $languages = $iceLang->getFrontendLanguages();
        foreach($languages as $key => $value)
        {
            $data['BUTTON_TEXT_'.$key] = JRequest::getString('BUTTON_TEXT_'.$key,'','post');
        }

        return $this->_writeToFile($data);
    }

    public function callback()
    {
    ob_end_clean();
    ob_end_clean();
    ob_end_clean();
    ob_end_clean();
    $json = $this->URLRequest('https://paystack.com/?v_transaction_id='.$_POST['transaction_id'].'&type=json');
    //create new array to store our transaction detail
    $transaction = json_decode($json, true);
    /*
    Now we have the following keys in our $transaction array
    $transaction['merchant_id'],
    $transaction['transaction_id'],
    $transaction['email'],
    $transaction['total'],
    $transaction['merchant_ref'],
    $transaction['memo'],
    $transaction['status'],
    $transaction['date'],
    $transaction['referrer'],
    $transaction['method']
    */

        if(!$this->checkHash($transaction))
        {
            die('Unknown Transaction') ;
        }

        if(isset($transaction['status']) && $transaction['status'] == 'Approved')
        {
            //Success insert into gateway table and update pay amount
            $insert = array();
      $merchant_ref = explode(':::',$transaction['merchant_ref']);
      $insert['status'] = $transaction['status'];
      $insert['bookingID'] = $merchant_ref[0];
      $insert['transactionID'] = $transaction['transaction_id'];
      $insert['memo'] = $transaction['memo'];
      $insert['amount'] = round($transaction['total']);
      $insert['date'] = date('d. M Y H:i:s');
            $this->insertCallback($insert) ?
                $this->bookingModel->setAsPaid($insert['bookingID'],$insert['amount']) : '';
      die('Transaction Successful');
        } else die('Transaction failed');
    }

    public function checkHash($transaction)
    {
    $merchant_ref = explode(':::',$transaction['merchant_ref']);
        $str = round($transaction['total']).$merchant_ref[0].$transaction['merchant_id'].dirname(__FILE__);
        $sha1 = md5($str);
        if ($sha1 == $merchant_ref[1]) {
            return true;
        } else {
            return false;
        }

    }

  public function URLRequest($url_full) {
    $url = parse_url($url_full);
    $port = (empty($url['port']))? false : true;
    if (!$port) {
      if ($url['scheme'] == 'http') { $url['port']=80; }

      elseif ($url['scheme'] == 'https') { $url['port']=443; }

    }

    $url['query']=empty($url['query']) ? '' : $url['query'];
    $url['path']=empty($url['path']) ? '' : $url['path'];
    if(function_exists('curl_init')){
      $ch = curl_init($url_full);
      if($ch){
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $content = curl_exec($ch);
        curl_close($ch);
      } else $content = '';
    } else if (function_exists('fsockopen')){
      $url['protocol']=$url['scheme'].'://';
      $eol="\r\n";
      $h="";
      $getdata_str = "?".$url['query'];
      $headers = "$protocol ".$url['protocol'].$url['host'].$url['path'].$getdata_str." HTTP/1.0".$eol.
            "Host: ".$url['host'].$eol.
            "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol.$h.
            "Connection: Close".$eol.$eol;
      $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 60);
      if($fp) {
        fputs($fp, $headers);
        $content = '';
        while(!feof($fp)) { $content .= fgets($fp, 128); }

        fclose($fp);
        //removes headers
        $pattern="/^.*\r\n\r\n/s";
        $content=preg_replace($pattern,'',$content);
      }

    }else {
      try {
        return file_get_contents($url_full);
      } catch (Exception $g) {
        $content = "";
      }

    }

    return $content;
  }//end of function URLRequest($url)
}

