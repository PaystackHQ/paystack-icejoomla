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

defined( '_JEXEC' ) or die( 'Restricted access' );
require_once( JApplicationHelper::getPath( 'toolbar_html' ) );
switch($task)
{
  default:
    TOOLBAR_paystackicebooking::_DEFAULT();
    break;
}

