<?php

class WC_Moota_Advanced {
	public function __construct() {
		add_filter( 'woocommerce_get_sections_advanced', [ $this, 'general' ], 20 );
		add_filter( 'woocommerce_get_settings_advanced', [ $this, 'get_settings_for_moota_section' ], 10, 2 );

		add_action('woocommerce_update_options_advanced_moota', [ $this, 'save']);
	}


	public function general( $sections ) {
		$sections['moota'] = 'Setting Moota';

		return $sections;
	}

	public function get_settings_for_moota_section( $settings, $current_screen ) {
		if ( 'moota' !== $current_screen ) {
			return $settings;
		}

		return array(
			'section_title'  => array(
				'title'       => __( 'Pengaturan Umum Moota', 'woocommerce-gateway-moota' ),
				'type'        => 'title',
				'description' => 'Semua Pembayaran Moota Transaksi Menggunakan Bagian Pengaturan Ini',
			),
			'access_token'   => array(
				'id' => 'access_token',
				'title'       => __( 'Access Token', 'woocommerce-gateway-moota' ),
				'type'        => 'password',
				'description' => __( 'Moota Access Token, <a href="https://app.moota.co/integrations/personal" target="_blank">Ambil Token Disini</a>', 'woocommerce-gateway-moota' ),
				'default'     => null,
				'desc_tip'    => false,
			),
			'plugin_token'   => array(
				'id' => 'plugin_token',
				'title'       => __( 'Plugins Token', 'woocommerce-gateway-moota' ),
				'type'        => 'password',
				'description' => __( 'Moota Plugin Token, <a href="https://app.moota.co/integrations/plugin" target="_blank">Ambil Token Disini</a>', 'woocommerce-gateway-moota' ),
				'default'     => null,
				'desc_tip'    => false,
			),
			'success_status' => array(
				'id' => 'success_status',
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
			array(
				'type' => 'sectionend',
				'id'   => 'moota_options',
			),

		);
	}

	public function save() {
		$this->fetch_bank();
		$this->fetch_escrow();
		$this->get_plugin_token();
	}

	private function fetch_bank() {
		$lists_bank = Moota_Api::run()->getBank();
		if ( ! empty($lists_bank) ) {
			update_option('_user_bank_lists', $lists_bank);
		}
	}

	private function get_plugin_token() {
		$plugin_token = Moota_Api::run()->getPluginToken();
		if ( ! empty($plugin_token) ) {
			update_option('plugin_token', $plugin_token);
		}
	}

	private function fetch_escrow() {
		$lists_escrow = Moota_Api::run()->getEscrow();
		if ( $lists_escrow ) {
			update_option('_user_escrow_lists', $lists_escrow);
		}
	}

}

new WC_Moota_Advanced();