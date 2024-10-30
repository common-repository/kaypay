<?php

class WC_Kaypay_Payment_Api extends WC_Payment_Gateway {
	/**
	 * @var WC_Kaypay_Sdk_Logger
	 */
	protected $logger;

	/**
	 * @var string
	 */
	protected $sandbox;

	/**
	 * @var WC_Kaypay_Sdk_Signer
	 */
	protected $signer;

	public function __construct() {
		$this->logger = WC_Kaypay_Sdk_Logger::getInstance();

		// Define the required variables
		$this->id                 = 'kaypay';
		$this->icon               = '';
		$this->has_fields         = true;
		$this->method_title       = __( 'Kaypay', 'kaypay-text' );
		$this->method_description = __( 'Buy Now Pay Later with Kaypay', 'kaypay-text' );

		// Define and load settings fields
		$this->init_form_fields();
		$this->init_settings();

		// Get the settings and load them into variables
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->sandbox     = $this->get_option( 'sandbox' );

		$merchant_code = $this->get_option( 'merchant_code' );
		$secret_key    = $this->get_option( 'secret_key' );
		$this->signer  = new WC_Kaypay_Sdk_Signer( $merchant_code, $secret_key );
	}

	public function process_webhook() {
		$stdin = file_get_contents( 'php://input' );

		$signature = filter_var( $_SERVER[ 'HTTP_' . HEADER_KAYPAY_SIGNATURE ], FILTER_DEFAULT, FILTER_FLAG_STRIP_HIGH );
		if ( ! $this->verify_signature( $signature, $stdin ) ) {
			exit;
		}

		$payload = json_decode( $stdin, true );
		if ( ! is_array( $payload ) ) {
			$message = 'Could not decode Webhook payload';
			$this->logger->error( compact( [ 'message', 'stdin' ] ) );
			exit;
		}

		switch ( $payload['type'] ) {
			case 'order.payment_succeeded':
				$this->order_payment_succeeded( $this->get_order( $payload ), $payload );
				break;
			case 'order.payment_failed':
				$this->order_payment_failed( $this->get_order( $payload ), $payload );
				break;
		}
	}

	public function verify_signature( $signature, $data ) {
		$expected = $this->signer->sign( $data );
		if ( $signature === $expected ) {
			return true;
		} else {
			$message = 'Signatures do not match';
			$this->logger->error( compact( 'message', 'signature', 'expected', 'data' ) );

			return false;
		}
	}

	/**
	 * @param $order WC_Order
	 * @param $payload array
	 */
	protected function order_payment_succeeded( $order, $payload ) {
		if ( $order->has_status( wc_get_is_paid_statuses() ) ) {
			$this->logger->error( [
				'message'  => 'Order is already paid',
				'order_id' => $order->get_id(),
			] );
			exit;
		}

		$payloadData = $payload['data'];
		$this->validate_amount( $order, $payloadData['totalAmount'] );

		$order->add_order_note( __( 'Kaypay payment succeeded', 'kaypay-text' ) );
		$order->update_meta_data( __( 'Kaypay Order', 'kaypay-text' ), $payloadData['orderId'] );
		$order->payment_complete( $payloadData['orderId'] );
	}

	/**
	 * @param $order WC_Order
	 * @param $payload array
	 */
	protected function order_payment_failed( $order, $payload ) {
		$note = __( 'Kaypay payment failed', 'kaypay-text' );
		if ( ! empty( $payload['note'] ) ) {
			$note = sprintf( '%s: %s', __( 'Kaypay', 'kaypay-text' ), $payload['note'] );
		}

		$order->update_status( 'failed', $note );
	}

	/**
	 * @param $payload array
	 *
	 * @return bool|WC_Order|WC_Order_Refund
	 */
	private function get_order( $payload ) {
		$payloadData = $payload['data'];

		return wc_get_order( $payloadData['merchantRefId'] );
	}

	/**
	 * @param WC_Order $order
	 * @param float $totalAmount
	 *
	 * @return void
	 */
	private function validate_amount( $order, $totalAmount ) {
		$orderTotal = $order->get_total();
		if ( number_format( $orderTotal, 2, '.', '' ) !== number_format( $totalAmount, 2, '.', '' ) ) {
			/* translators: %s: Total amount. */
			$order->update_status( 'on-hold', sprintf( __( 'Kaypay: payment amounts do not match (%s).', 'kaypay-text' ), $totalAmount ) );
			exit;
		}
	}
}