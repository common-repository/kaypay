<?php

class WC_Kaypay_Sdk_Signer {
	/**
	 * @var string
	 */
	private $merchant_code;

	/**
	 * @var string
	 */
	private $secret_key;

	/**
	 * @param $merchant_code string
	 * @param $secret_key string
	 */
	public function __construct( $merchant_code, $secret_key ) {
		$this->merchant_code = $merchant_code;
		$this->secret_key    = $secret_key;
	}

	/**
	 * @return string
	 */
	public function get_merchant_code() {
		return $this->merchant_code;
	}

	/**
	 * @param $data string
	 *
	 * @return string
	 */
	public function sign( $data ) {
		$hashed  = hash_hmac( 'sha256', $data, $this->secret_key, true );
		$encoded = base64_encode( $hashed );

		return sprintf( 'v1=%s', $encoded );
	}
}