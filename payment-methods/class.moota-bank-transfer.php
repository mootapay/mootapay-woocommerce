<?php

use Moota\Moota\Config\Moota;

class WC_Moota_Bank_Transfer extends WC_Payment_Gateway {
	private $banks = [];
	private static $payment_id = 'moota-bank-transfer';

	public function __construct() {
		$this->id                 = self::$payment_id;
		$this->has_fields         = true;
		$this->method_title       = 'Bank Transfer';
		$this->method_description = 'Terima Pembayaran langsung ke masuk kerekening tanpa biaya per-transaksi. Mendukung Banyak Bank Nasional';

		$this->init_form_fields();

		// fetch bank each Saving data
		$this->fetch_bank_posts();

        // fetch bank list each reload bank transfer page, delay 5 seconds
        $this->update_bank_lists();

		$this->init_settings();

		// Populate Values settings
		$this->enabled     = $this->get_option( 'enabled' );
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [
			$this,
			'process_admin_options'
		] );

	}

	private function fetch_bank_posts() {
		if (
			isset( $_POST[ "woocommerce_" . self::$payment_id . "_access_token" ] ) &&
			empty( get_transient( "update_moota_payment" ) )
		) {
			$banks = Moota_Api::run( $this->settings['access_token'] )->getBank();
			$this->set_bank_lists( $banks );
			set_transient( "update_moota_payment", time(), 3 );
		}
	}

	private function update_bank_lists() {
		$settings = self::get_fields();
		if ( ! get_transient( "moota_update_payment_method" ) ) {
			$banks = Moota_Api::run( $settings['access_token'] )->getBank();
			$this->set_bank_lists( $banks );
			set_transient( 'moota_update_payment_method', 5 );
		}
	}

	private function set_bank_lists( $banks ) {
		if ( ! empty( $banks ) ) {
			foreach ( $banks->data as $bank ) {
				$this->banks[] = $bank;
			}
		}

		$this->update_option( 'moota-bank-lists', $this->banks );
	}

	public function init_form_fields() {
        $banks = [];
        if ( $bank_lists = $this->get_option( 'moota-bank-lists' ) ) {
            foreach ($bank_lists as $bank) {
                $banks[$bank->bank_id] = $bank->label . ' ( ' . $bank->atas_nama . ' )';
            }
        }

		$this->form_fields = array(
			'enabled'        => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-moota' ),
				'type'    => 'checkbox',
				'label'   => __( 'Aktifkan Moota Transaksi', 'woocommerce-gateway-moota' ),
				'default' => 'yes'
			),
			'title'          => array(
				'title'       => __( 'Title', 'woocommerce-gateway-moota' ),
				'type'        => 'text',
				'description' => __( 'Nama Yang Muncul Di halaman Checkout', 'woocommerce-gateway-moota' ),
				'default'     => __( 'Moota Bank Transfer', 'woocommerce-gateway-moota' ),
				'desc_tip'    => true,
			),
			'description'    => array(
				'title'       => __( 'Deskripsi', 'woocommerce-gateway-moota' ),
				'type'        => 'textarea',
				'description' => 'Penjelasan akan muncul di halaman checkout',
				'default'     => '',
				'desc_tip'    => true,
			),
			'section_title'  => array(
				'title'       => __( 'Pengaturan Umum', 'woocommerce-gateway-moota' ),
				'type'        => 'title',
				'description' => 'Semua Pembayaran Moota Transaksi Menggunakan Bagian Pengaturan Ini',
			),
			'access_token'   => array(
				'title'       => __( 'Access Token', 'woocommerce-gateway-moota' ),
				'type'        => 'password',
				'description' => __( 'Moota Access Token, <a href="https://app.moota.co/integrations/personal" target="_blank">Ambil Token Disini</a>', 'woocommerce-gateway-moota' ),
				'default'     => null,
				'desc_tip'    => false,
			),
			'success_status' => array(
				'title'       => __( 'Status Berhasil', 'woocommerce-gateway-moota' ),
				'type'        => 'select',
				'description' => __( 'Status setelah berhasil menemukan order yang telah dibayar', 'woocommerce-gateway-moota' ),
				'default'     => 'processing',
				'desc_tip'    => true,
				'options'     => array(
					'completed'  => 'Completed',
					'on-hold'    => 'On Hold',
					'processing' => 'Processing'
				),
			),

			'bank_account_title' => array(
				'title'       => __( 'Pengaturan Pembayaran', 'woocommerce-gateway-moota' ),
				'type'        => 'title',
				'description' => 'Pilih Akun Bank Yang akan ditampilkan halaman pembayaran',
			),
			'toggle_status'      => array(
				'title'       => __( 'Nomor Unik?', 'woocommerce-gateway-moota' ),
				'type'        => 'checkbox',
				'description' => __( 'Centang, untuk aktifkan fitur penambahan 3 angka unik di setiap akhir pesanan / order. Sebagai pembeda dari order satu dengan yang lainnya.', 'woocommerce-gateway-moota' ),
				'desc_tip'    => true,
			),
			'type_append'        => array(
				'title'       => __( 'Tipe Tambahan', 'woocommerce-gateway-moota' ),
				'type'        => 'select',
				'description' => __( 'Increase = Menambah unik number ke total harga, Decrease = Mengurangi total harga dengan unik number', 'woocommerce-gateway-moota' ),
				'default'     => 'increase',
				'desc_tip'    => true,
				'options'     => array(
					'increase' => 'Tambahkan',
					'decrease' => 'Kurangi'
				),
				'id'          => 'woomoota_type_append'
			),
			'unique_start'       => array(
				'title'             => __( 'Batas Awal Angka Unik', 'woocommerce-gateway-moota' ),
				'type'              => 'number',
				'description'       => __( 'Masukan batas awal angka unik', 'woocommerce-gateway-moota' ),
				'id'                => 'woomoota_start_unique_number',
				'default'           => 1,
				'custom_attributes' => array(
					'min' => 0,
					'max' => 99999
				),
				'desc_tip'          => true,
			),
			'unique_end'         => array(
				'title'             => __( 'Batas Akhir Angka Unik', 'woocommerce-gateway-moota' ),
				'type'              => 'number',
				'description'       => __( 'Masukan batas akhir angka unik', 'woocommerce-gateway-moota' ),
				'id'                => 'woomoota_end_unique_number',
				'default'           => 999,
				'custom_attributes' => array(
					'min' => 0,
					'max' => 99999
				),
				'desc_tip'          => true,
			),
			'bank_list'          => array(
				'title'       => __( 'Daftar Bank', 'woocommerce-gateway-moota' ),
				'type'        => 'multiselect',
				'description' => __( 'Daftar Bank', 'woocommerce-gateway-moota' ),
				'id'          => 'woomoota_bank_list',
				'options'     => $banks,
				'desc_tip'    => true,
			),
		);
	}

	public function init_settings() {
		parent::init_settings(); // TODO: Change the autogenerated stub
	}

    private function bank_selection( $bank_id ) {
	    $bank_selection = [];
	    $bank_lists = $this->get_option( 'moota-bank-lists' );
        if ( $bank_lists ) {
            foreach ($bank_lists as $bank) {
                if ( $bank_id == $bank->bank_id ) {
	                $bank_selection = $bank;
                    break;
                }
            }
        }

        return $bank_selection;
    }

	public function payment_fields() {
		$banks = $this->settings['bank_list'];
		?>
        <ul>
			<?php if ( ! empty( $banks ) ) :
				foreach ( $banks as $item ) :
                    $bank_selection = $this->bank_selection($item);
                    ?>
                    <li>
                        <label for="bank-transfer-<?php echo $bank_selection->bank_type; ?> bank-id-<?php echo $item; ?>">
                            <input id="bank-transfer-bank-id-<?php echo $item; ?>" name="channels" type="radio"
                                   value="<?php echo $item; ?>">
							<span><img src="<?php echo $bank_selection->icon;?>" alt="<?php echo $bank_selection->bank_type; ?>"></span>
                            <span class="moota-bank-account"><?php echo $bank_selection->label; ?> <?php echo $bank_selection->account_number; ?> An. (<?php echo $bank_selection->atas_nama; ?>)</span>
                        </label>
                    </li>
				<?php endforeach;
			endif; ?>
        </ul>
		<?php
		$description = $this->get_description();
		if ( $description ) {
			echo wpautop( wptexturize( $description ) ); // @codingStandardsIgnoreLine.
		}
	}

	public function validate_fields() {
		if ( empty( $_POST['channels'] ) ) {
			wc_add_notice( '<strong>Channel Pembayaran</strong> Pilih Channel Pembayaran', 'error' );

			return false;
		}

		return true;
	}

	public function process_payment( $order_id ) {
//		Moota::$ACCESS_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJucWllNHN3OGxsdyIsImp0aSI6ImVkOWMxMmExYjE4MjA1YWEzZWI0ZDRiYjBhNWUzNjIyMjRkM2NkZjIxMDA1YmFlMjJlNDk5M2I5MjA3MTQwNDM5YzZiMWZiNTllOTAxZDNmIiwiaWF0IjoxNjU1MTg1NzE4Ljg4MDU0MSwibmJmIjoxNjU1MTg1NzE4Ljg4MDU0MywiZXhwIjoxNjg2NzIxNzE4Ljg3ODQ1Niwic3ViIjoiMyIsInNjb3BlcyI6WyJhcGkiXX0.nmgHoQEnmfIBaKA0raNwXx0d1NxhPzRiEvkFbWSTxoqoLFuWgVgFw0kaL3uvE_ZcMXQnmBWuvTZXQAsurzZhlH6rTsytPS-lYlJgdSs9pdtsBy0eFZ3dCtmul4tccMwBXAiWQLrOJeuNe8gPQNtJgJ5sTDzXg7SMcc5qKNeIurB9jeBnqUMelt-nKBBJngBifUlBOVIAyHg5iXaH-BzHFBGxDFoxc2QBXy-T9UpVaCFzhkjBcj5u3B-QJqTtAbPHyXsArl1h46kerJEtoJusYifqQI6QsPpJuK4BkF_HAkXIs1jkuSB5YZ7zeVEamg7OHFs51EBSb1oIjNnVfdv9qYI11kq7Ar5v7eS2gBQJR6fuJ_HeHtNKq0ovBJ_UNjXFqTY5V2pnBEv5LO7fS2kZgk92nzX3RTipOKrmY1aBBMFVid2c3NJs_jFVF8Wlld-yXiW9yPkx6_6ITIqZmL5NTyk9VVyWGakf2OgfDeMo3rFzZ4Qo2N1H7s09JmOfmPRzVDjwZ8R6wEiDjqyBxeJ_qESw-IzY0dVlQzy1F0C4hNIKlrVpK5QKo7lxbf-Zkv-BQ8pppcF5g95tQVqiHjS6ur7wlpqh8SEj0aQ0WPmZpmkjkdpjL76HVjpayJOPSErYdN1AIr8uJOSdT039Xmds_1aWiC3BnHFjjMyK5lSh888';

		global $woocommerce;
		$order = new WC_Order( $order_id );
		$order->update_meta_data( 'moota_channels', $_POST['channels'] );

		$args = [
			'invoice_number'                  => $order->get_order_number(),
			'amount'                          => $order->get_total(),
			'payment_method_id'               => '',
			'payment_method_type'             => '',
			'callback_url'                    => '',
			'increase_total_from_unique_code' => 1,
			'expired_date'                    => '',
			'customer'                        => [
				'name'  => $order->get_billing_first_name() . ' class.moota-gateway-sandbox.php' . $order->get_billing_last_name(),
				'email' => $order->get_billing_email(),
				'phone' => $order->get_billing_phone()
			],
			'items'                           => collect( $order->get_items() )->flatMap( function ( $item ) {
				return [
					'name'      => $item->get_name(),
					'qty'       => $item->get_quantity(),
					'price'     => $item->get_price(),
					'sku'       => $item->get_sku(),
					'image_url' => ''
				];
			} )->toArray()
		];

		die();
		$response = wp_remote_post( '{payment processor endpoint}', $args );

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status( 'on-hold', __( 'Awaiting Payment', 'woocommerce-gateway-moota' ) );

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}

	public static function get_fields() {
		return get_option( 'woocommerce_' . self::$payment_id . '_settings', '' );
	}

}