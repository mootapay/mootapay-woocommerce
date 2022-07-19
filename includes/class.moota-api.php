<?php
class Moota_Api {
	private static $base_api = 'https://app.moota.co/api/v2';
	private static $run;
	private $api_token;

	public function __construct($api_token) {
		$this->api_token = $api_token;
	}

	public static function run($api_token) {
		if ( ! self::$run instanceof  self ) {
			self::$run = new self($api_token);
		}

		return self::$run;
	}

	public function postApi($endpoint, $args = []) {
		$default_args = [
			'method'      => 'POST',
			'headers'     => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_token,
			],
		];

		$args = wp_parse_args($args, $default_args);

		$response = wp_remote_post( self::$base_api . $endpoint, $args);

		if ( is_wp_error( $response ) ) {
			return ['error' => $response->get_error_message()];
		} else {
			return $response;
		}
	}

	public function getApi( $endpoint, $headers = [] ) {
		$default_header = [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_token,
		];

		$headers['headers'] = wp_parse_args($headers, $default_header);
		$response = wp_remote_get(self::$base_api . $endpoint, $headers);

		if ( ( !is_wp_error($response)) && (200 === wp_remote_retrieve_response_code( $response ) ) ) {
			$responseBody = json_decode($response['body']);
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $responseBody;
			}
		}

		return [];
	}

	public function getBank() {
		return $this->getApi('/bank', [
			'page' => 1,
			'per_page' => 50
		]);
	}

	public function getPaymentMethod() {
		return $this->getApi('/payment-method?page=1&per_page=100');
	}

	public function postTransaction($data = []) {
		return $this->getApi('contract', [
			'body' => json_encode($data)
		]);
	}
}