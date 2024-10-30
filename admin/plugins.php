<?php

add_filter( 'plugin_row_meta', function ( $links, $file ) {
	if ( $file !== KAYPAY_PLUGIN_BASENAME ) {
		return $links;
	}

	$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=kaypay' );
	$row_meta     = array(
		'settings' => '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'kaypay-text' ) . '</a>',
	);

	return array_merge( $links, $row_meta );
}, 10, 2 );