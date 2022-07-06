<?php
/**
 * Plugin Name: WooCommerce Moota Gateway
 * Plugin URI: https://wordpress.org/plugins/woocommerce-gateway-moota/
 * Description: Payment Using Personal Bank Account Or Moota Escrow
 * Author: Moota
 * Author URI: https://moota.co/
 * Version: 1.0.0
 * Requires at least: 5.6
 * Tested up to: 5.9
 * WC requires at least: 5.7
 * WC tested up to: 6.2
 * Text Domain: woocommerce-gateway-moota
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

add_action( 'plugins_loaded', 'init_gateway_moota' );
function init_gateway_moota() {
    class WC_Gateway_Sandbox_Moota extends WC_Payment_Gateway {
        public function __construct()
        {
            $this->id = 'moota-gateway';
            $this->has_fields = true;
            $this->method_title = 'Bank Transfer';
            $this->method_description = 'Terima Pembayaran langsung ke masuk kerekening tanpa biaya per-transaksi. Mendukung Banyak Bank Nasional';


            $this->init_form_fields();


            $this->init_settings();

            // Populate Values settings
            $this->enabled = $this->get_option( 'enabled' );
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woocommerce-gateway-moota' ),
                    'type' => 'checkbox',
                    'label' => __( 'Aktifkan Moota Transaksi', 'woocommerce-gateway-moota' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __( 'Title', 'woocommerce-gateway-moota' ),
                    'type' => 'text',
                    'description' => __( 'Nama Yang Muncul Di halaman Checkout', 'woocommerce-gateway-moota' ),
                    'default' => __( 'Moota Bank Transfer', 'woocommerce-gateway-moota' ),
                    'desc_tip'      => true,
                ),
                'description' => array(
                    'title' => __( 'Deskripsi', 'woocommerce-gateway-moota' ),
                    'type' => 'textarea',
                    'description'   =>  'Penjelasan akan muncul di halaman checkout',
                    'default' => '',
                    'desc_tip'      => true,
                ),
                'section_title' => array(
                    'title'     => __( 'Pengaturan Umum', 'woocommerce-gateway-moota' ),
                    'type'     => 'title',
                    'description'     => 'Semua Pembayaran Moota Transaksi Menggunakan Bagian Pengaturan Ini',
                ),
                'access_token' => array(
                    'title'       => __( 'Access Token', 'woocommerce-gateway-moota' ),
                    'type'        => 'password',
                    'description' => __( 'Moota Access Token, <a href="https://app.moota.co/integrations/personal" target="_blank">Ambil Token Disini</a>', 'woocommerce-gateway-moota' ),
                    'default'     => null,
                    'desc_tip'    => false,
                ),
                'success_status' => array(
                    'title' => __( 'Status Berhasil', 'woocommerce-gateway-moota' ),
                    'type' => 'select',
                    'description' => __( 'Status setelah berhasil menemukan order yang telah dibayar', 'woocommerce-gateway-moota' ),
                    'default'   =>  'processing',
                    'desc_tip'      => true,
                    'options' => array(
                        'completed'     => 'Completed',
                        'on-hold'       => 'On Hold',
                        'processing'    => 'Processing'
                    ),
                ),

                'bank_account_title' => array(
                    'title'     => __( 'Pengaturan Pembayaran', 'woocommerce-gateway-moota' ),
                    'type'     => 'title',
                    'description'     => 'Pilih Akun Bank Yang akan ditampilkan halaman pembayaran',
                ),
                'toggle_status' => array(
                    'title' => __( 'Nomor Unik?', 'woocommerce-gateway-moota' ),
                    'type' => 'checkbox',
                    'description' => __( 'Centang, untuk aktifkan fitur penambahan 3 angka unik di setiap akhir pesanan / order. Sebagai pembeda dari order satu dengan yang lainnya.', 'woocommerce-gateway-moota' ),
                    'desc_tip'      => true,
                ),
                'type_append' => array(
                    'title' => __( 'Tipe Tambahan', 'woocommerce-gateway-moota' ),
                    'type' => 'select',
                    'description' => __( 'Increase = Menambah unik number ke total harga, Decrease = Mengurangi total harga dengan unik number', 'woocommerce-gateway-moota' ),
                    'default'   =>  'increase',
                    'desc_tip'      => true,
                    'options' => array(
                        'increase'      => 'Tambahkan',
                        'decrease'      => 'Kurangi'
                    ),
                    'id'   => 'woomoota_type_append'
                ),
                'unique_start' => array(
                    'title' => __( 'Batas Awal Angka Unik', 'woocommerce-gateway-moota' ),
                    'type' => 'number',
                    'description' => __( 'Masukan batas awal angka unik', 'woocommerce-gateway-moota' ),
                    'id'   => 'woomoota_start_unique_number',
                    'default' => 1,
                    'custom_attributes' => array(
                        'min'  => 0,
                        'max'  => 99999
                    ),
                    'desc_tip'      => true,
                ),
                'unique_end' => array(
                    'title' => __( 'Batas Akhir Angka Unik', 'woocommerce-gateway-moota' ),
                    'type' => 'number',
                    'description' => __( 'Masukan batas akhir angka unik', 'woocommerce-gateway-moota' ),
                    'id'   => 'woomoota_end_unique_number',
                    'default' => 999,
                    'custom_attributes' => array(
                        'min'  => 0,
                        'max'  => 99999
                    ),
                    'desc_tip'      => true,
                ),
                'bank_list' => array(
                    'title' => __( 'Daftar Bank', 'woocommerce-gateway-moota' ),
                    'type' => 'select',
                    'description' => __( 'Daftar Bank', 'woocommerce-gateway-moota' ),
                    'id'   => 'woomoota_end_unique_number',
                    'default' => 999,
                    'custom_attributes' => array(
                        'min'  => 0,
                        'max'  => 99999
                    ),
                    'desc_tip'      => true,
                ),
            );
        }

        public function init_settings()
        {
            parent::init_settings(); // TODO: Change the autogenerated stub
        }

        public function payment_fields()
        {
            ?>
            <ul>
                <li>
                    <label for="bank-transfer-bca">
                        <input id="bank-transfer-bca" name="channels" type="radio" value="bca">
                        BCA
                    </label>
                </li>
                <li>
                    <label for="bank-transfer-mandiri">
                        <input id="bank-transfer-mandiri" name="channels" type="radio" value="mandiri">
                        Mandiri
                    </label>
                </li>
            </ul>
            <?php
            $description = $this->get_description();
            if ( $description ) {
                echo wpautop( wptexturize( $description ) ); // @codingStandardsIgnoreLine.
            }
        }

        public function validate_fields()
        {
            if( empty( $_POST[ 'channels' ]) ) {
                wc_add_notice(  'Pilih Channel Pembayaran', 'error' );
                return false;
            }
            return true;
        }

        public function process_payment( $order_id )
        {
            \Moota\Moota\Config\Moota::$ACCESS_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJucWllNHN3OGxsdyIsImp0aSI6ImVkOWMxMmExYjE4MjA1YWEzZWI0ZDRiYjBhNWUzNjIyMjRkM2NkZjIxMDA1YmFlMjJlNDk5M2I5MjA3MTQwNDM5YzZiMWZiNTllOTAxZDNmIiwiaWF0IjoxNjU1MTg1NzE4Ljg4MDU0MSwibmJmIjoxNjU1MTg1NzE4Ljg4MDU0MywiZXhwIjoxNjg2NzIxNzE4Ljg3ODQ1Niwic3ViIjoiMyIsInNjb3BlcyI6WyJhcGkiXX0.nmgHoQEnmfIBaKA0raNwXx0d1NxhPzRiEvkFbWSTxoqoLFuWgVgFw0kaL3uvE_ZcMXQnmBWuvTZXQAsurzZhlH6rTsytPS-lYlJgdSs9pdtsBy0eFZ3dCtmul4tccMwBXAiWQLrOJeuNe8gPQNtJgJ5sTDzXg7SMcc5qKNeIurB9jeBnqUMelt-nKBBJngBifUlBOVIAyHg5iXaH-BzHFBGxDFoxc2QBXy-T9UpVaCFzhkjBcj5u3B-QJqTtAbPHyXsArl1h46kerJEtoJusYifqQI6QsPpJuK4BkF_HAkXIs1jkuSB5YZ7zeVEamg7OHFs51EBSb1oIjNnVfdv9qYI11kq7Ar5v7eS2gBQJR6fuJ_HeHtNKq0ovBJ_UNjXFqTY5V2pnBEv5LO7fS2kZgk92nzX3RTipOKrmY1aBBMFVid2c3NJs_jFVF8Wlld-yXiW9yPkx6_6ITIqZmL5NTyk9VVyWGakf2OgfDeMo3rFzZ4Qo2N1H7s09JmOfmPRzVDjwZ8R6wEiDjqyBxeJ_qESw-IzY0dVlQzy1F0C4hNIKlrVpK5QKo7lxbf-Zkv-BQ8pppcF5g95tQVqiHjS6ur7wlpqh8SEj0aQ0WPmZpmkjkdpjL76HVjpayJOPSErYdN1AIr8uJOSdT039Xmds_1aWiC3BnHFjjMyK5lSh888';

            global $woocommerce;
            $order = new WC_Order( $order_id );
            $order->update_meta_data('moota_channels', $_POST['channels']);

            var_dump($order->get_total()); die();

            $args = [
                'invoice_number'    =>  $order->get_order_number(),
                'amount'    =>  $order->get_total(),
                'payment_method_id' =>  '',
                'payment_method_type'   =>  '',
                'callback_url'  =>  '',
                'increase_total_from_unique_code'   =>  1,
                'expired_date'  =>  '',
                'customer'  =>  [
                    'name'  =>  $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'email' =>  $order->get_billing_email(),
                    'phone' =>  $order->get_billing_phone()
                ],
                'items' =>  collect($order->get_items())->flatMap(function($item) {
                    return [
                        'name'  =>  $item->get_name(),
                        'qty'   =>  $item->get_quantity(),
                        'price' =>  $item->get_price(),
                        'sku'   =>  $item->get_sku(),
                        'image_url' =>  ''
                    ];
                })->toArray()
            ];
            $response = wp_remote_post( '{payment processor endpoint}', $args );

            // Mark as on-hold (we're awaiting the cheque)
            $order->update_status('on-hold', __( 'Awaiting Payment', 'woocommerce-gateway-moota' ));

            // Remove cart
            $woocommerce->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order )
            );
        }

        public function webhook()
        {
            $secret = ''; // populate from config
            // validation signature
            $signature = hash_hmac('sha256', file_get_contents('php://input'), $secret);
        }
    }
}

function add_moota_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_Sandbox_Moota';
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_moota_gateway_class' );