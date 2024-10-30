<?php

add_action( 'rest_api_init', function () {
	register_rest_route( 'kaypay/v1', '/version', [
		'methods'             => 'GET',
		'permission_callback' => 'kaypay_verify_rest_request_signature',
		'callback'            => function () {
			$data = [
				'kaypay' => KAYPAY_VERSION,
				'php'    => PHP_VERSION,
			];

			if ( defined( 'WC_VERSION' ) ) {
				$data['woocommerce'] = WC_VERSION;
			}

			if ( isset( $GLOBALS['wp_version'] ) ) {
				$data['wordpress'] = $GLOBALS['wp_version'];
			}

			return new WP_REST_Response( compact( 'data' ) );
		},
	] );
} );