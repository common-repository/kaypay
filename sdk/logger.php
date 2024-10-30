<?php

class WC_Kaypay_Sdk_Logger {
	protected function __construct() {
		// use getInstance() obtain logger
	}

	public function debug( array $data ) {
		$this->notify_kaypay( $data );
	}

	public function error( array $data ) {
		error_log( print_r( $data, true ) );
		$this->notify_kaypay( $data );
	}

	protected function backtrace() {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );

		return array_filter( $backtrace, [ $this, 'backtrace_filter' ] );
	}

	protected function backtrace_filter( array $frame ) {
		if ( isset( $frame['file'] ) ) {
			if ( strpos( $frame['file'], 'kaypay' ) === false ) {
				// avoid leaking unrelated source code
				return false;
			}

			if ( substr( $frame['file'], - 11 ) === '/logger.php' ) {
				// skip noisy stack frames
				return false;
			}
		}

		return true;
	}

	protected function notify_kaypay( array $data ) {
		$url = KAYPAY_LOGGER_URL;
		if ( strlen( $url ) === 0 ) {
			// environment has been configured to turn off error reporting
			return;
		}

		$data['home_url']  = home_url();
		$data['version']   = KAYPAY_VERSION;
		$data['backtrace'] = $this->backtrace();
		$text              = wp_json_encode( $data, JSON_PRETTY_PRINT );
		$body              = [ 'text' => $text ];

		$args     = array(
			'body'        => json_encode( $body ),
			'data_format' => 'body',
			'timeout'     => '3',
			'redirection' => '0',
			'blocking'    => true,
			'headers'     => array(
				'Content-Type' => 'application/json; charset=utf-8'
			)
		);
		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			$error_messages = $response->get_error_messages();
			error_log( print_r( compact( 'url', 'error_messages' ), true ) );
		}
	}

	/**
	 * @var WC_Kaypay_Sdk_Logger|null
	 */
	private static $instance = null;

	/**
	 * @return WC_Kaypay_Sdk_Logger
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new WC_Kaypay_Sdk_Logger();
		}

		return self::$instance;
	}
}