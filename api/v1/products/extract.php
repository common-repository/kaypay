<?php

add_action( 'rest_api_init', function () {
	register_rest_route( 'kaypay/v1', '/products/extract', [
		'methods'             => 'POST',
		'permission_callback' => 'kaypay_verify_rest_request_signature',
		'callback'            => function ( WP_REST_Request $request ) {
			$parameters = $request->get_json_params();
			if ( empty( $parameters['url'] ) ) {
				return new WP_REST_Response(
					[
						'message' => "Missing `url` parameter",
						'data'    => compact( 'parameters' )
					],
					400
				);
			}

			$url     = sanitize_url( $parameters['url'] );
			$post_id = url_to_postid( $url );

			if ( $post_id === 0 ) {
				$parsed_url = parse_url( $url );
				parse_str( $parsed_url["query"], $query_vars );
				if ( ! empty( $query_vars['product'] ) ) {
					$query = new WP_Query( [
						'post_type' => 'product',
						'name'      => $query_vars['product'],
					] );
					if ( ! empty( $query->posts ) && $query->is_singular ) {
						$post_id = $query->post->ID;
					}
				}
			}

			if ( $post_id === 0 ) {
				return new WP_REST_Response(
					[ 'message' => "Could not resolve URL", 'data' => compact( 'url' ) ],
					500
				);
			}

			$product = wc_get_product( $post_id );
			if ( $product === false ) {
				return new WP_REST_Response(
					[ 'message' => "Could not get product", 'data' => compact( 'post_id', 'url' ) ],
					404
				);
			}

			$data = [
				'product'           => $product->get_data(),
				'product_permalink' => $product->get_permalink(),
			];

			if ( $product->get_image_id() ) {
				$data['product_image_url'] = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
			}

			return new WP_REST_Response( compact( 'data' ) );
		},
	] );
} );