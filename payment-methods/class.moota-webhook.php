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
				$secret   = 'PIOkB0lM';

				$signature = hash_hmac( 'sha256', $response, $secret );
				if ( hash_equals( $http_signature, $signature ) ) {

					$responseArray = json_decode( $response, true );
//					if ( ! empty( $responseArray[0] ) ) {
//						$amount = $responseArray[0]['amount'];
//
//						global $wpdb;
//						$sql    = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'total_with_unique_code' AND meta_value = '{$amount}'";
//						$result = $wpdb->get_results( $sql, ARRAY_A );
//						if ( count( $result ) == 1 ) {
//							$row = $wpdb->get_row( $sql );
//							update_post_meta($row->post_id, 'status_order', 'pending');
//							update_post_meta($row->post_id, 'date_pending', date('d-m-Y H:i:s'));
//							Neon_Send_Email::paymentOrderPending( $row->post_id );
//
//						} else if ( count( $result ) > 1 ) {
//							$id = [];
//							foreach ( $result as $item ) {
//								$id[] = $item['post_id'];
//							}
//
//							$log = 'multiple order id : ' . implode( ', ', $id );
//						} else {
//							$log = 'Order Id Not Found';
//						}
//					} else {
//						$log = 'No response';
//					}
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