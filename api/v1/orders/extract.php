<?php

add_action( 'rest_api_init', function () {
	register_rest_route( 'kaypay/v1', '/orders/extract', array(
		'methods'             => 'POST',
		'permission_callback' => 'kaypay_verify_rest_request_signature',
		'callback'            => function ( WP_REST_Request $request ) {
			$parameters = $request->get_json_params();
			if ( empty( $parameters['order_id'] ) ) {
				return new WP_REST_Response(
					[
						'message' => "Missing `order_id` parameter",
						'data'    => compact( 'parameters' )
					],
					400
				);
			}

			$order_id = $parameters['order_id'];
			$order    = wc_get_order( $order_id );

			if ( $order === false ) {
				return new WP_REST_Response(
					[ 'message' => "Could not get order", 'data' => compact( 'order_id' ) ],
					404
				);
			}

			$data = [
				'order'       => $order->get_data(),
				'order_items' => [],
			];

			foreach ( $order->get_items() as $item ) {
				$data['order_items'][] = $item->get_data();
			}

			return new WP_REST_Response( compact( 'data' ) );
		},
	) );
} );