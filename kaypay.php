<?php
/**
 * Plugin Name: Kaypay Payment Gateway
 * Plugin URI: https://docs.kaypay.vn
 * Description: WooCommerce payment method to Buy Now Pay Later with Kaypay.
 * Version: 1.2.0
 * Requires at least: 5.3
 * Requires PHP: 7.0
 * WC requires at least: 4.8
 * WC tested up to: 7.4.1
 * Author: Kaypay Vietnam
 * Author URI: https://kaypay.vn
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kaypay-text
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'HEADER_KAYPAY_SIGNATURE' ) ) {
	define( 'HEADER_KAYPAY_SIGNATURE', 'X_KAYPAY_SIGNATURE' );
}

if ( ! defined( 'KAYPAY_PLUGIN_FILE' ) ) {
	define( 'KAYPAY_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'KAYPAY_PLUGIN_BASENAME' ) ) {
	define( 'KAYPAY_PLUGIN_BASENAME', plugin_basename( KAYPAY_PLUGIN_FILE ) );
}

if ( ! defined( 'KAYPAY_PLUGIN_DIR' ) ) {
	define( 'KAYPAY_PLUGIN_DIR', __DIR__ );
}

if ( ! defined( 'KAYPAY_LOGGER_URL' ) ) {
	define( 'KAYPAY_LOGGER_URL', 'https://hooks.slack.com/services/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' );
}

if ( ! defined( 'KAYPAY_VERSION' ) ) {
	define( 'KAYPAY_VERSION', '1.2.0' );
}

require_once KAYPAY_PLUGIN_DIR . '/admin/plugins.php';

add_action( 'plugins_loaded', function () {
	// 1. Generate the POT file from source code:           wp i18n make-pot --exclude=wp-woo . languages/kaypay-text.pot
	// 2. Update existing PO files with new messages:       wp i18n update-po languages/kaypay-text.pot
	// 3. Translate messages with Poedit or similar apps
	// 4. Generate MO files from PO files:                  wp i18n make-mo languages
	//
	// Quick installation: 	curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && cp wp-cli.phar /usr/local/bin/wp
	// Change user:					su -s /bin/bash -- www-data
	load_plugin_textdomain( 'kaypay-text', false, dirname( KAYPAY_PLUGIN_BASENAME ) . '/languages' );

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		deactivate_plugins( KAYPAY_PLUGIN_BASENAME );

		return;
	}

	require_once KAYPAY_PLUGIN_DIR . '/api/index.php';
	require_once KAYPAY_PLUGIN_DIR . '/payment/api.php';
	require_once KAYPAY_PLUGIN_DIR . '/payment/gateway.php';
	require_once KAYPAY_PLUGIN_DIR . '/sdk/logger.php';
	require_once KAYPAY_PLUGIN_DIR . '/sdk/signer.php';
	require_once KAYPAY_PLUGIN_DIR . '/storefront/single-product.php';
} );

add_filter( 'wc_price', function ( $return, $price, $args, $unformatted_price ) {
	return str_replace( '<bdi', "<bdi data-kaypay-unformatted-price=\"$unformatted_price\"", $return );
}, 10, 4 );

add_filter( 'woocommerce_payment_gateways', function ( $methods ) {
	$methods[] = 'WC_Kaypay_Payment_Gateway';

	return $methods;
} );

add_action( 'woocommerce_api_wc_kaypay_payment_gateway', function () {
	( new WC_Kaypay_Payment_Api() )->process_webhook();
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'kaypay', 'https://js.kaypay.net/v1/woocommerce.js', [ 'jquery' ] );
} );
