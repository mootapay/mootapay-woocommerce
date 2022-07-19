<?php

class Moota_Loader {
	private static $init;

	public function __construct() {

		$this->require_files();

		add_action( 'plugins_loaded', [ $this, 'onload' ] );

		register_activation_hook( MOOTA_FULL_PATH, [ $this, 'activation_plugins' ] );
		register_deactivation_hook( MOOTA_FULL_PATH, [ $this, 'deactivation_plugins' ] );

		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_moota_gateway_class' ] );
	}

	public static function init() {
		if ( ! self::$init instanceof self ) {
			self::$init = new self();
		}

		return self::$init;
	}


	private function require_files() {
		require_once MOOTA_INCLUDES . '/helpers.php';
		require_once MOOTA_INCLUDES . '/class.moota-api.php';
		require_once MOOTA_PAYMENT_METHOD . '/class.moota-webhook.php';
	}

	public function onload() {
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once MOOTA_PAYMENT_METHOD . '/class.moota-bank-transfer.php';
		} else {
			add_action( 'admin_notices', function () {
				?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html__( 'Error! class <b>WC_Payment_Gateway</b>not found', 'moota' ); ?></p>
                </div>
				<?php
			} );
		}

        add_action('wp_enqueue_scripts', [$this, 'front_end_scripts']);
	}

	/**
	 * @param $methods
	 * Register Payment Method Woocommerce
	 * @return mixed
	 */
	public function add_moota_gateway_class( $methods ) {
		$methods[] = 'WC_Moota_Bank_Transfer';
		return $methods;
	}

	public function activation_plugins() {
        Moota_Webhook::init();
	}

	public function deactivation_plugins() {

	}

    public function front_end_scripts() {
        $assets = plugin_dir_url( MOOTA_FULL_PATH ) . 'assets/';
        wp_enqueue_style( 'moota-payment-gateway',  $assets . 'style.css' );
    }
}