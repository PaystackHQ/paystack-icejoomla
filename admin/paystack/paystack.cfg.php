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

// no direct access
defined('_JEXEC') or die ( 'Restricted access' );
$configs = array();
$configs["TEST_SECRET_KEY"] = 'sk_test_demo';
$configs["LIVE_SECRET_KEY"] = 'sk_live_demo';
$configs["MODE"] = 'test';
$configs["BUTTON_TEXT_en-GB"] = 'Pay now';
