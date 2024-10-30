<?php

class WC_Kaypay_Payment_Gateway extends WC_Kaypay_Payment_Api {
	public function __construct() {
		parent::__construct();

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			[ $this, 'process_admin_options' ]
		);
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'       => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Kaypay payment method', 'kaypay-text' ),
				'default' => 'yes'
			),
			'title'         => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'safe_text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Kaypay', 'kaypay-text' ),
				'desc_tip'    => true,
			),
			'description'   => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Buy Now Pay Later with Kaypay', 'kaypay-text' ),
				'desc_tip'    => true,
			),
			'sandbox'       => array(
				'title'   => __( 'Test mode', 'kaypay-text' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable payment testing with Kaypay Sandbox.', 'kaypay-text' ),
				'default' => 'no'
			),
			'merchant_code' => array(
				'title'       => __( 'Merchant Code', 'kaypay-text' ),
				'type'        => 'text',
				'description' => __( 'Unique merchant code provided by Kaypay account manager.', 'kaypay-text' ),
				'desc_tip'    => true,
			),
			'secret_key'    => array(
				'title'       => __( 'Secret Key', 'kaypay-text' ),
				'type'        => 'password',
				'description' => __( 'Secret authentication key provided by Kaypay account manager.', 'kaypay-text' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	public function payment_fields() {
		wc_get_template(
			'kaypay/checkout/payment-method.php',
			[],
			'',
			KAYPAY_PLUGIN_DIR . '/templates/'
		);
	}

	function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// https://woocommerce.com/document/payment-gateway-api/
		$order->update_status( 'on-hold', __( 'Kaypay awaiting payment', 'kaypay-text' ) );
		WC()->cart->empty_cart();

		$return_url = $this->get_return_url( $order );

		require_once KAYPAY_PLUGIN_DIR . '/sdk/order_create.php';
		$sandbox      = $this->sandbox === 'yes';
		$order_create = new WC_Kaypay_Sdk_OrderCreate( $order, $sandbox, $this->signer );
		$response     = $order_create->post();
		if ( is_wp_error( $response ) ) {
			$error_messages = $response->get_error_messages();
			$this->logger->error( compact( 'order_id', 'error_messages' ) );

			return [ 'result' => 'failure', 'redirect' => $return_url ];
		}

		if ( ! is_array( $response )
		     || ! isset( $response['code'] )
		     || $response['code'] !== 0
		     || ! isset( $response['data'] )
		) {
			$this->logger->error( compact( 'response' ) );

			return [ 'result' => 'failure', 'redirect' => $return_url ];
		}

		$data = $response['data'];
		if ( ! is_array( $data ) || empty( $data['redirectUrl'] ) ) {
			$this->logger->error( compact( 'data' ) );

			return [ 'result' => 'failure', 'redirect' => $return_url ];
		}

		$redirectUrl = $data['redirectUrl'];
		$order->add_meta_data( __( 'Kaypay Redirect URL', 'kaypay-text' ), $redirectUrl );
		$order->save_meta_data();

		return [
			'result'   => 'success',
			'redirect' => $redirectUrl
		];
	}
}