<?php
/**
 * Plugin Name: MootaPay For WooCommerce
 * Plugin URI: https://wordpress.org/plugins/mootapay-for-woocommerce/
 * Description: Platform penerima pembayaran otomatis untuk produk, jasa dan apapun.
 * Author: Moota Pay
 * Author URI: https://mootapay.com/
 * Version: 1.0.0
 * Requires at least: 6.0.0
 * Requires PHP: 7.4
 * Tested up to: 6.0.2
 * WC requires at least: 6.9.0
 * WC tested up to: 6.9.3
 * Text Domain: mootapay-woocommerce
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