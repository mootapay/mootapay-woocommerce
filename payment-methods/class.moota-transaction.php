<?php

class Moota_Transaction {
	public static function request( $order_id, $channel_id, $with_unique_code, $start_unique_code, $end_unique_code, $payment_method_type = 'bank_transfer',) {
		global $woocommerce;
		$order = new WC_Order( $order_id );

		$items = [];
		/**
		 * @var $item WC_Order_Item_Product
		 */
		foreach ( $order->get_items() as $item ) {
			$product = wc_get_product( $item->get_product_id() );
			$items[] = [
				'name'      => $item->get_name(),
				'qty'       => $item->get_quantity(),
				'price'     => $product->get_price() * $item->get_quantity(),
				'sku'       => $product->get_sku(),
				'image_url' => get_the_post_thumbnail_url( $item->get_product_id() )
			];

			if ( empty( $product->get_sku() ) ) {
				wc_add_notice( '<strong>SKU salah</strong> Hubungi Admin', 'error' );

				return false;
			}
		}

		if ( $order->get_shipping_total() ) {
			$items[] = [
				'name'      => 'Ongkos Kirim',
				'qty'       => 1,
				'price'     => $order->get_shipping_total(),
				'sku'       => 'shipping-cost',
				'image_url' => ''
			];
		}

		if ( $order->get_tax_totals() ) {
			$items[] = [
				'name'      => 'Pajak',
				'qty'       => 1,
				'price'     => $order->get_tax_totals(),
				'sku'       => 'taxes-cost',
				'image_url' => ''
			];
		}

		if ( strlen($start_unique_code) < 2 ) {
			$start_unique_code = sprintf('%02d', $start_unique_code);
		}

		if ( $start_unique_code > $end_unique_code ) {
			$end_unique_code += 10;
		}

		$minutes = get_option('woocommerce_hold_stock_minutes');
		if ( empty($minutes) ) {
			$minutes = 3600;
		}

		$args = [
			'invoice_number'                  => $order->get_order_number(),
			'amount'                          => $order->get_total(),
			'payment_method_id'               => $channel_id,
			'payment_method_type'             => $payment_method_type,
			'with_unique_code'                => $with_unique_code == "no" ? 0 : 1,
			'callback_url'                    => home_url( 'moota-callback' ),
			'increase_total_from_unique_code' => 1,
			'start_unique_code'               => $start_unique_code,
			'end_unique_code'                 => $end_unique_code,
			'expired_date'                    => date( 'Y-m-d H:i:s', strtotime( "+{$minutes} minutes" ) ),
			'customer'                        => [
				'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'email' => $order->get_billing_email(),
				'phone' => $order->get_billing_phone()
			],
			'items'                           => $items
		];

		$payment_link = self::get_return_url( $order );

		$response = Moota_Api::run()->postTransaction( $args );

		if ( $response && ! empty( $response->data ) ) {

			if ( get_option( 'payment_mode', 'direct' ) == 'redirect' ) {
				$payment_link = $response->data->payment_link;
			}

			$order->update_meta_data( "trx_id", $response->data->trx_id );
			$order->update_meta_data( "unique_code", $response->data->unique_code );
			$order->update_meta_data( "total", $response->data->total );
			$order->update_meta_data( "payment_link", $response->data->payment_link );

		} else {
			if ( isset($response->errors) ) {
				foreach ($response->errors as $error => $msg ) {
					wc_add_notice( '<strong>'.$error.'</strong> ' . $msg, 'error' );
				}
			} else {
				wc_add_notice( '<strong>Terjadi Masalah Server</strong> Coba beberapa saat lagi', 'error' );
			}


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

	public static function get_return_url( $order ) {
		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() );
		}

		return apply_filters( 'woocommerce_get_return_url', $return_url, $order );
	}
}