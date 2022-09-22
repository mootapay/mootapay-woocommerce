<?php
/**
 * Plugin Name: WooMootaPay
 * Plugin URI: https://wordpress.org/plugins/woomootapay/
 * Description: Platform penerima pembayaran otomatis untuk produk, jasa dan apapun.
 * Author: Fattah
 * Author URI: https://fattah.id/
 * Version: 1.0.0
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Tested up to: 5.9
 * WC requires at least: 5.7
 * WC tested up to: 6.2
 * Text Domain: woomootapay
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const MOOTA_BASE_DIR = __DIR__;
const MOOTA_FULL_PATH = __FILE__;
const MOOTA_INCLUDES = MOOTA_BASE_DIR . '/includes';
const MOOTA_PAYMENT_METHOD = MOOTA_BASE_DIR . '/payment-methods';

require __DIR__ . '/vendor/autoload.php';
require MOOTA_INCLUDES . '/class.loader.php';


Moota_Loader::init();