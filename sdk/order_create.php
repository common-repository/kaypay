<?php

class WC_Kaypay_Sdk_OrderCreate {
	/**
	 * @var WC_Order
	 */
	private $order;

	/**
	 * @var bool
	 */
	private $sandbox;

	/**
	 * @var WC_Kaypay_Sdk_Signer
	 */
	private $signer;

	/**
	 * @param $order WC_Order
	 * @param $sandbox bool
	 * @param $signer WC_Kaypay_Sdk_Signer
	 */
	public function __construct( $order, $sandbox, $signer ) {
		$this->order   = $order;
		$this->sandbox = $sandbox;
		$this->signer  = $signer;
	}

	public function post() {
		$order    = $this->order;
		$signer   = $this->signer;
		$order_id = strval( $order->get_id() );

		$request_body         = [
			'callbackUrl'       => $this->prepare_callback_url(),
			'currency'          => $order->get_currency(),
			'description'       => $this->prepare_description(),
			'merchantCode'      => $signer->get_merchant_code(),
			'merchantDisplayId' => "#$order_id",
			'merchantRefId'     => $order_id,
			'orderItems'        => $this->prepare_order_items(),
			'shippingDetails'   => $this->prepare_shipping_details(),
			'returnUrl'         => $order->get_checkout_order_received_url(),
			'shippingFee'       => intval( $order->get_shipping_total() ),
			'totalAmount'       => intval( $order->get_total() ),
		];
		$request_body_encoded = json_encode( $request_body );

		$url = 'https://payment-api.kaypay.net/v1/orders';
		if ( $this->sandbox ) {
			$url = 'https://payment-api.sandbox.kaypay.io/v1/orders';
		}

		$args         = [
			'body'        => $request_body_encoded,
			'data_format' => 'body',
			'headers'     => [
				'Content-Type'       => 'application/json',
				'X-Kaypay-Signature' => $signer->sign( $request_body_encoded ),
			],
			'timeout'     => 30,
		];
		$raw_response = wp_remote_post( $url, $args );
		if ( is_wp_error( $raw_response ) ) {
			return $raw_response;
		}

		return json_decode( wp_remote_retrieve_body( $raw_response ), true );
	}

	private function prepare_callback_url() {
		if ( defined( 'KAYPAY_CALLBACK_URL' ) ) {
			return KAYPAY_CALLBACK_URL;
		}

		return WC()->api_request_url( 'WC_Kaypay_Payment_Gateway' );
	}

	private function prepare_description() {
		$order_id  = $this->order->get_id();
		$shop_name = get_bloginfo( 'name' );

		/* translators: 1: order id 2: shop name. */
		$description = sprintf( __( 'BNPL for order #%1$s at %2$s', 'kaypay-text' ), $order_id, $shop_name );

		return $description;
	}

	private function prepare_order_items() {
		$wc_order_items     = $this->order->get_items();
		$kaypay_order_items = [];
		/** @var WC_Order_Item_Product $wc_order_item */
		foreach ( $wc_order_items as $wc_order_item ) {
			$product = $wc_order_item->get_product();

			$kaypay_order_item = [
				'price'       => $product->get_price(),
				'productName' => $product->get_name(),
				'quantity'    => $wc_order_item->get_quantity(),
			];

			$sku = $product->get_sku();
			if ( ! empty( $sku ) ) {
				$kaypay_order_item += [
					'sku'     => $product->get_sku(),
					'skuName' => $product->get_name(),
				];
			}

			$image_url = wp_get_attachment_url( $product->get_image_id() );
			if ( is_string( $image_url ) ) {
				$kaypay_order_item['productImage'] = $image_url;

				if ( ! empty( $sku ) ) {
					$kaypay_order_item['skuImage'] = $image_url;
				}
			}

			$kaypay_order_items[] = $kaypay_order_item;
		}

		return $kaypay_order_items;
	}

	private function prepare_shipping_details() {
		$order        = $this->order;
		$phone_number = $order->get_billing_phone();
		if ( empty( $phone_number ) ) {
			$get_shipping_phone = [ $order, 'get_shipping_phone' ];
			if ( is_callable( $get_shipping_phone ) ) {
				$phone_number = call_user_func( $get_shipping_phone );
			}
		}

		return [
			'address1'  => $order->get_billing_address_1(),
			'address2'  => $order->get_billing_address_2(),
			'city'      => $order->get_billing_city(),
			'country'   => $order->get_billing_country(),
			'email'     => $order->get_billing_email(),
			'firstName' => $order->get_billing_first_name(),
			'lastName'  => $order->get_billing_last_name(),
			'phone'     => $phone_number,
			'province'  => $order->get_billing_state(),
			'zip'       => $order->get_billing_postcode(),
		];
	}
}