<?php

class Moota_Api {
	private static $base_api = 'https://app.moota.co/api/v2';
	private static $run;
	private $api_token;

	public function __construct( $api_token = null ) {
		if ( empty($api_token) ) {
			$api_token = get_option('access_token');
		}

		$this->api_token = $api_token;
	}

	public static function run( $api_token = null ) {
		if ( ! self::$run instanceof self ) {
			self::$run = new self( $api_token );
		}

		return self::$run;
	}

	public function postApi( $endpoint, $args = [] ) {
		$default_args = [
			'method'  => 'POST',
			'headers' => [
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_token,
			],
		];

		$args = wp_parse_args( $args, $default_args );

		$response = wp_remote_post( self::$base_api . $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		} else {
			return json_decode( wp_remote_retrieve_body( $response ) );
		}
	}

	public function getApi( $endpoint, $headers = [] ) {
		$default_header = [
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_token,
		];

		$headers['headers'] = wp_parse_args( $headers, $default_header );
		$response           = wp_remote_get( self::$base_api . $endpoint, $headers );

		if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
			$responseBody = json_decode( $response['body'] );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $responseBody;
			}
		}

		return [];
	}

	public function getPaymentMethod(): array {
		$payment_method = [];
		$response = $this->getApi( '/payment-method', [
			'page'     => 1,
			'per_page' => 100
		] );

		if ( ! empty($response->data) ) {
			foreach ($response->data as $item) {
				$payment_method[$item->category][] = $item;
			}

		}

		return $payment_method;
	}

	public function getBank(): array {
		$response =  $this->getApi( '/bank', [
			'page'     => 1,
			'per_page' => 50
		] );

		if ( ! empty($response->data) ) {
			return $response->data;
		}

		return [];
	}
	public function getEscrow() {
		$escrow = [];
		$payment_method = $this->getPaymentMethod();
		if ( ! empty($payment_method['escrow']) ) {
			$escrow = $payment_method['escrow'];
		}

		return $escrow;
	}

	public function postTransaction( $data = [] ) {
		return $this->postApi( '/contract', [
			'body' => wp_json_encode( $data )
		] );
	}

	public function getPluginToken(): string {
		$response = $this->getApi( '/plugin/token' );
		if ( ! empty($response->token) ) {
			return $response->token;
		}

		return "";
	}
}