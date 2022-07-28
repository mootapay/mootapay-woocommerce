<?php

use Moota\Moota\Config\Moota;

class WC_Moota_Escrow extends WC_Payment_Gateway {

	private $all_escrow = [];
	private $escrow_selection = [];

	public function __construct() {
		$this->id                 = 'moota-escrow';
		$this->has_fields         = true;
		$this->method_title       = 'Virtual Account/Rekening Bersama';
		$this->method_description = 'Terima Pembayaran Melalui Virtual Account';

		$this->init_form_fields();

		$this->init_settings();

		// Populate Values settings
		$this->enabled     = $this->get_option( 'enabled' );
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [
			$this,
			'process_admin_options'
		] );

		// custom fields
		add_filter( 'woocommerce_generate_escrow_lists_html', [ $this, 'escrow_lists' ], 99, 4 );
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, function ( $settings ) {
			return $settings;
		} );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-moota' ),
				'type'    => 'checkbox',
				'label'   => __( 'Aktifkan Moota Transaksi', 'woocommerce-gateway-moota' ),
				'default' => 'yes'
			),

			'title'              => array(
				'title'       => __( 'Title', 'woocommerce-gateway-moota' ),
				'type'        => 'text',
				'description' => __( 'Nama Yang Muncul Di halaman Checkout', 'woocommerce-gateway-moota' ),
				'default'     => __( 'Moota Bank Transfer', 'woocommerce-gateway-moota' ),
				'desc_tip'    => true,
			),
			'description'        => array(
				'title'       => __( 'Deskripsi', 'woocommerce-gateway-moota' ),
				'type'        => 'textarea',
				'description' => 'Penjelasan akan muncul di halaman checkout',
				'default'     => '',
				'desc_tip'    => true,
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
			'escrow_lists'         => array(
				'title'       => __( 'Daftar Rekening Bersama (VA)', 'woocommerce-gateway-moota' ),
				'type'        => 'escrow_lists',
				'description' => __( 'Pilih Bank yang ingin digunakan', 'woocommerce-gateway-moota' ),
				'id'          => 'woomoota_bank_list',
			),
		);
	}

	public function init_settings() {
		parent::init_settings(); // TODO: Change the autogenerated stub
	}

	// Custom fields for check list bank
	public function escrow_lists( $html, $k, $v, $object ) {

		ob_start();
		$field_key       = $object->get_field_key( $k );
		$escrow          = moota_get_escrow();

		?>
		</table>
		<h3 class="wc-settings-sub-title "
		    id="woocommerce_moota-bank-transfer_bank_account_<?php echo $v['id']; ?>>"><?php echo $v['title']; ?></h3>
		<?php if ( ! empty( $v['description'] ) ) : ?>
			<p><?php echo $v['description']; ?></p>
		<?php endif; ?>
		<table class="form-table">
		<?php if ( is_array( $escrow ) ) : ?>
			<?php foreach ( $escrow as $item ) :
				$field_key_escrow = $item->payment_method_type;
				$checked = $this->escrow_lists_checked( $k, $field_key_escrow, $item->payment_method_id );
				?>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $item->name ); ?></label>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo wp_kses_post( $item->label ); ?></span>
							</legend>
							<input type="checkbox" name="<?php echo $field_key; ?>[<?php echo $field_key_escrow; ?>]"
							       id="<?php echo $field_key . '_' . $item->payment_method_id; ?>"
							       value="<?php echo $item->payment_method_id; ?>" <?php echo $checked ? "checked" : ""; ?>/>
						</fieldset>
					</td>
				</tr>

			<?php endforeach; ?>
		<?php endif; ?>

		<?php

		return ob_get_clean();
	}

	// Custom Validate
	public function validate_escrow_lists_field( $key, $value ) {
		return $value;
	}

	// handle selection bank
	private function escrow_lists_checked( $k, $field_key, $value ): bool {
		if ( empty( $this->escrow_selection ) ) {
			$this->escrow_selection = $this->get_option( $k );
		}

		return ! empty( $this->escrow_selection[ $field_key ] ) && $this->escrow_selection[ $field_key ] == $value;
	}


	/**
	 * Handle WooCommerce Checkout
	 */
	private function escrow_selection( $payment_id ) {
        if ( empty($this->all_escrow) ) {
            $this->all_escrow = moota_get_escrow();
        }

        if ( ! empty($this->all_escrow) ) {
            foreach ($this->all_escrow as $escrow) {
                if ( $payment_id == $escrow->payment_method_id ) {
                    return $escrow;
                }
            }
        }

		return [];
	}

	public function payment_fields() {

        $escrow = $this->settings['escrow_lists'];
		 ?>
		 <ul>
		 <?php if ( ! empty( $escrow ) ) :
             foreach ( $escrow as $item ) :
                    $escrow_selection = $this->escrow_selection( $item );
                 ?>
                 <li>
                     <label for="bank-transfer-<?php echo $escrow_selection->payment_method_type; ?> va-id-<?php echo $item; ?>">
                     <input id="bank-transfer-va-id-<?php echo $item; ?>" name="channels" type="radio"
                     value="<?php echo $item; ?>">
                     <span><img width="80" src="<?php echo $escrow_selection->icon;?>" alt="<?php echo $escrow_selection->payment_method_type; ?>"></span>
                     <span class="moota-bank-account"><?php echo $escrow_selection->name; ?></span>
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

}