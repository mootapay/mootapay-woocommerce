<?php

class Moota_Transaction {
	public static function request($order_id, $channel_id, $payment_method_type = 'bank_transfer') {
		global $woocommerce;
		$order = new WC_Order( $order_id );

		$items = [];
		/**
		 * @var $item WC_Order_Item_Product
		 */
		foreach ($order->get_items() as $item) {
			$product = wc_get_product($item->get_product_id());
			$items[] = [
				'name'      => $item->get_name(),
				'qty'       => $item->get_quantity(),
				'price'     => $product->get_price() * $item->get_quantity(),
				'sku'       => $product->get_sku(),
				'image_url' => get_the_post_thumbnail_url($item->get_product_id())
			];

			if ( empty($product->get_sku()) ) {
				wc_add_notice( '<strong>SKU salah</strong> Hubungi Adamin', 'error' );
				return false;
			}
		}

		$args = [
			'invoice_number'                  => $order->get_order_number(),
			'amount'                          => $order->get_total(),
			'payment_method_id'               => $channel_id,
			'payment_method_type'             => $payment_method_type,
			'callback_url'                    => home_url('moota-callback'),
			'increase_total_from_unique_code' => true,
			'expired_date'                    => date('Y-m-d H:i:s', strtotime('+1 day')),
			'customer'                        => [
				'name'  => $order->get_billing_first_name() . ' class.moota-gateway-sandbox.php' . $order->get_billing_last_name(),
				'email' => $order->get_billing_email(),
				'phone' => $order->get_billing_phone()
			],
			'items'                           => $items
		];

		$payment_link = self::get_return_url( $order );

		$response = Moota_Api::run()->postTransaction( $args );

		if ( $response && ! empty($response->data) ) {

			if ( get_option('payment_mode', 'direct') == 'redirect' ) {
				$payment_link = $response->data->payment_link;
			}

			$order->update_meta_data("trx_id", $response->data->trx_id);
			$order->update_meta_data("unique_code", $response->data->unique_code);
			$order->update_meta_data("total", $response->data->total);
			$order->update_meta_data("payment_link", $response->data->payment_link);

		} else {
			wc_add_notice( '<strong>Terjadi Masalah Server</strong> Coba beberapa saat lagi', 'error' );
			return false;
		}

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status( 'on-hold', __( 'Awaiting Payment', 'woocommerce-gateway-moota' ) );

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result'   => 'success',
			'redirect' => $payment_link
		);
	}

	public static function get_return_url($order) {
		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() );
		}

		return apply_filters( 'woocommerce_get_return_url', $return_url, $order );
	}
}