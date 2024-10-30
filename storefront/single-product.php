<?php

add_action( 'woocommerce_before_add_to_cart_form', function () {
	global $product;

	wc_get_template(
		'kaypay/single-product/before-add-to-cart-form.php',
		[
			'amount' => $product->get_price()
		],
		'',
		KAYPAY_PLUGIN_DIR . '/templates/'
	);
} );
