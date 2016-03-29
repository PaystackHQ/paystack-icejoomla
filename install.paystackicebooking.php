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

error_reporting(E_ALL);

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.filesystem.file');
//create folder at administrator/components/com_icebooking/gateways/paystack
$path = JPATH_BASE.DS.'components'.DS.'com_icebooking'.DS;
$src = JPATH_BASE.DS.'components'.DS.'com_paystackicebooking'.DS.'paystack'.DS;
chmod($path.'gateways'.DS, 0755);
if(!is_dir($path.'gateways'.DS.'paystack')) mkdir($path.'gateways'.DS.'paystack', 0755);
//copy files
if(!copy($src.'paystack.php',$path.'gateways'.DS.'paystack'.DS.'paystack.php')) echo 'Unable to copy file<br />';
if(!copy($src.'paystack.cfg.php',$path.'gateways'.DS.'paystack'.DS.'paystack.cfg.php')) echo 'Unable to copy file<br />';
chmod($path.'models'.DS.'gateway.php', 0755);
$config = file_get_contents($path.'models'.DS.'gateway.php');
if(strpos($config,"gateways['paystack']") === false) {
  $config = str_ireplace("\$gateways['paypal']","\$gateways['paystack'] = 'paystack'; \n\$gateways['paypal']",$config);
  file_put_contents($path.'models'.DS.'gateway.php',$config);
}

echo '<table bgcolor="#E0FFE0" width ="100%">
  <tr style="height:30px">
    <td width="50px"><img src="/images/tick.png" height="20px" width="20px"></td>
    <td><font size="2"><b>Installation completed for Paystack Icebooking integration</b></font></td>
  </tr>
  <tr style="height:30px">
    <td width="50px"><img src="images/tick.png" height="20px" width="20px"></td>
    <td><font size="2"><b>You may uninstall this component now. All necessary files have been copied to the icebooking directory.</b></font></td>
  </tr>
  <tr style="height:30px">
    <td width="50px"><img src="images/tick.png" height="20px" width="20px"></td>
    <td><font size="2"><b>Thank you for choosing paystack.</b></font></td>
  </tr>
  </table>';
