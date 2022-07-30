<?php

class Moota_Webhook {
	private static $filename = 'mutasi-log';
	private static $callback_name = 'moota-callback';

	public function __construct() {
		self::init();
	}

	public static function init() {
		add_action( 'init', [ 'Moota_Webhook', 'endpoint' ] );
		add_action( 'template_redirect', [ 'Moota_Webhook', '_endpoint_handler' ] );
	}

	public function endpoint() {
		add_rewrite_endpoint( self::$callback_name, EP_ROOT );
	}

	public function _endpoint_handler() {
		global $wp_query;

		if (
			( isset( $wp_query->query['name'] ) && $wp_query->query['name'] == self::$callback_name )
			||
			( isset( $wp_query->query[self::$callback_name] ) )
		) {
			header("HTTP/1.1 200 OK");

			$http_signature = ! empty( $_SERVER['HTTP_SIGNATURE'] ) ? $_SERVER['HTTP_SIGNATURE'] : null;
			if ( $http_signature && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
				$log      = '';
				$response = file_get_contents( 'php://input' );
				$secret   = get_option('plugin_token');

				$signature = hash_hmac( 'sha256', $response, $secret );
				if ( hash_equals( $http_signature, $signature ) ) {

					$result = json_decode( $response );
					if ( $result && ! empty($result->data) ) {
						$trx_id = $result->trx_id;
						global $wpdb;
						$sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='trx_id' AND meta_value='{$trx_id}'";
						$meta = $wpdb->get_row($sql);

						$order = new WC_Order( $meta->post_id );

						if ( $order->has_status() ) {
							switch ($result->status) {
								case 'pending' :
									$order->update_status('on-hold');
									break;
								case 'success' :
									$order->update_status('processing');
									break;
								default:
									$order->update_status('cancelled');

							}
						}

					}

				} else {
					$log = 'Invalid Signature';
				}

				if ( ! empty( $log ) ) {
					Moota_Webhook::addLog( $log );
				}
				exit;
			}
			wp_redirect( home_url() );
		}
	}


	public static function clearLog() {
		$file = ABSPATH . '/' . self::$filename;
		if ( file_exists( $file ) ) {
			unlink( $file );
		}
	}

	public static function addLog( $message ) {
		$file = ABSPATH . '/' . self::$filename;
		if ( ! file_exists( $file ) ) {
			touch( $file );
		}

		$log = file_get_contents( $file );
		$log .= PHP_EOL . ' ' . date( 'Y-m-d H:i:s' ) . ' : ' . $message;
		file_put_contents( $file, $log );
	}

	public static function getLog() {
		$file = ABSPATH . '/' . self::$filename;
		if ( file_exists( $file ) ) {
			return file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		}

		return [];
	}
}