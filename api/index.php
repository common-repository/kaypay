<?php

function kaypay_verify_rest_request_signature( WP_REST_Request $request ) {
	$body   = $request->get_body();
	$header = $request->get_header( HEADER_KAYPAY_SIGNATURE );

	$verify_key   = __FUNCTION__;
	$verify_value = $request->offsetGet( $verify_key );
	if ( $verify_value === false ) {
		// if previous verification failed, do not try again
		return false;
	}

	$verify_result = ( new WC_Kaypay_Payment_Api() )->verify_signature( $header, $body );

	// keep track of verification result to avoid multiple failures
	$request->offsetSet( $verify_key, $verify_result );

	return $verify_result;
}

require __DIR__ . '/v1/products/extract.php';
require __DIR__ . '/v1/orders/extract.php';
require __DIR__ . '/v1/version.php';
