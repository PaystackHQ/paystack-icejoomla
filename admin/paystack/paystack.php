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

    function redir_meta($url) {
        return "<meta http-equiv=\"refresh\" content=\"0;url=" . addslashes($url) . "\" />";
    }

    public function __construct()
    {
        parent::__construct('paystack');
        $this->callbackForward = 0;
        $this->configs = $this->_getConfigurationsArray();
    }
    
    public function getSecretKey(){
        return (strtolower($this->configs['MODE'])==='live')?
            $this->configs['LIVE_SECRET_KEY'] :
            $this->configs['TEST_SECRET_KEY'] ;
    }

    public function generatePayForm(&$data)
    {
        $lg = JFactory::getLanguage();
        $activeLanguage = $lg->getTag();
        if(!strlen($activeLanguage))
        {
            $activeLanguage = 'en-GB';
        }

        // initialize a transaction
        $transactionInit = new stdClass();
        $myref=$data['bookingID'].'---'.rand(10000,99999);
        $fields = array(
          'reference'     => $myref,
          'callback_url'  => $this->getCallbackUrl().
                            ((strpos($this->getCallbackUrl(), '?')===false) ? '?' : '&').
                            'ref='.urlencode($myref).'&iscallback',
            'email'       => $data['email'],
            'amount'      => $data['gateway_amount'] * 100
        );

        // build query from the array
        $fields_string = http_build_query($fields);

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize');

        //set the headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Authorization: Bearer ' . $this->getSecretKey(),
          "Content-Type: application/x-www-form-urlencoded"));

        //set number of POST vars, POST data
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

        // exec the cURL
        $response = curl_exec($ch);
        
        // should be 0
        if (curl_errno($ch)) {   
            // curl ended with an error
            $transactionInit->error = "cURL said: " . curl_error($ch);
            curl_close($ch);
        } else {

            //close connection
            curl_close($ch);

            // Then, after your curl_exec call:
            $body = json_decode($response);
            if(!$body->status){
                // paystack has an error message for us
                $transactionInit->error = "Paystack API said: " . $body->message;
            } else {
                // get body returned by Paystack API
                $transactionInit = $body->data;
            }
        }
        
        if($transactionInit->error)
        {
            die("Error while attempting to initialize transaction: " . 
                "\n<br>" . $transactionStatus->error); 
        }
        else
        {
            // use meta to redirect to authorization url
            return $this->redir_meta($transactionInit->authorization_url);
        }
    }

    public function createhash($data)
    {
        $str = round($data["gateway_amount"]).$data["bookingID"].$this->configs['MERCHANT_ID'].dirname(__FILE__);
        return md5($str);
    }

    public function getConfigurationForm()
    {
        $configs  =& $this->_getConfigurationsArray();
        $iceLang = $this->getInstance('languages', 'icebookingModel');
        $languages = $iceLang->getFrontendLanguages();
        $firstRun = true;
        $languageClickList = $buttonTextFields = '';

        if (is_array($languages)) {
            foreach ($languages as $key => $value) {
                if ($firstRun) {
                    $languageClickList .= '| <a href="#" class="langSelected" title="'.
                    $key.'">'.$value.'</a>'.' | ';
                    $buttonTextFields .= '<input type="text" class="'.
                    $key.'" size="40" value="'.$configs['BUTTON_TEXT_'.$key].
                    '" name="BUTTON_TEXT_'.$key.'" id="u_'.$key.'"/>';
                } else {
                    $languageClickList .= '<a href="#" title="'.$key.'">'.
                    $value.'</a>'.' | ';
                    $buttonTextFields .= '<input style="display:none;" type="text" class="'.
                    $key.'" size="40" value="'.$configs['BUTTON_TEXT_'.$key].
                    '" name="BUTTON_TEXT_'.$key.'" id="u_'.$key.'"/>';
                }
                $firstRun=false;
            }
        }

        $form = '';
        $form .= '<div class="gatewayData">';

        $form .=
            '<div class="gatewayForm">
                <label>LIVE SECRET KEY</label>
                    <div>
                        <input type="text" name="LIVE_SECRET_KEY" value="'.$configs['LIVE_SECRET_KEY'].'"/>
                    </div>
            </div>';

        $form .=
            '<div class="gatewayForm">
                <label>TEST SECRET KEY</label>
                    <div>
                        <input type="text" name="TEST_SECRET_KEY" value="'.$configs['TEST_SECRET_KEY'].'"/>
                    </div>
            </div>';

        $form .=
            '<div class="gatewayForm">
                <label>MODE</label>
                    <div>
                        <select name="MODE">
                            <option value="test" '.
                                ($configs['MODE']=='test'?"selected='selected'":'').'
                                >Test</option>
                            <option value="live" '.
                                ($configs['MODE']=='live'?"selected='selected'":'').'
                                >Live</option>
                        </select>
                    </div>
            </div>';

        return $form;
    }

    public function storeConfigurationData()
    {
        $data['LIVE_SECRET_KEY'] = JRequest::getString('LIVE_SECRET_KEY', 0, 'post');
        $data['TEST_SECRET_KEY'] = JRequest::getString('TEST_SECRET_KEY', 0, 'post');
        $data['MODE'] = JRequest::getString('MODE', 0, 'post');
        
        $iceLang = $this->getInstance('languages', 'icebookingModel');
        $languages = $iceLang->getFrontendLanguages();
        foreach ($languages as $key => $value) {
            $data['BUTTON_TEXT_'.$key] = JRequest::getString('BUTTON_TEXT_'.$key, '', 'post');
        }
        return $this->_writeToFile($data);
    }

    public function callback()
    {
        ob_end_clean();
        ob_end_clean();
        ob_end_clean();
        ob_end_clean();
        
        $ch = curl_init();
        $transactionStatus = new stdClass();
        $trxref = JRequest::getVar('trxref', '', 'GET');

        // set url
        curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . rawurlencode($trxref));

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer '. $this->getSecretKey()
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        // cURL must be able to use TLSv1.2 to connect
        // to Paystack servers
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        
        // exec the cURL
        $response = curl_exec($ch);
        
        // should be 0
        if (curl_errno($ch)) {   
            // curl ended with an error
            $transactionStatus->error = "cURL said:" . curl_error($ch);
            curl_close($ch);
        } else {

            //close connection
            curl_close($ch);

            // Then, after your curl_exec call:
            $body = json_decode($response);
            if(!$body->status){
                // paystack has an error message for us
                $transactionStatus->error = "Paystack API said: " . $body->message;
            } else {
                // get body returned by Paystack API
                $transactionStatus = $body->data;
            }
        }

        if($transactionStatus->status == 'success')
        {
            //Success insert into gateway table and update pay amount
            $insert = array();
            $merchant_ref = explode('---', $trxref);
            $insert['status'] = $transactionStatus->status;
            $insert['bookingID'] = $merchant_ref[0];
            $insert['transactionID'] = $trxref;
            $insert['memo'] = "Paid with: " . $transactionStatus->authorization->card_type .
                              " ending with " . $transactionStatus->authorization->last4;
            $insert['amount'] = round($transactionStatus->amount / 100);
            $insert['date'] = date('d. M Y H:i:s');
            $this->insertCallback($insert) ?
                $this->bookingModel->setAsPaid($insert['bookingID'], $insert['amount']) : '';
            die('Transaction Successful');
        }
        else
        {
            die("Error while attempting to verify transaction: trxref: " . $trxref . 
                "\n<br>" . $transactionStatus->error);
        }
    }

}
