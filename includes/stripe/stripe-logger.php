<?php

namespace Elementor_Pay_Addons\Stripe;

use Elementor_Pay_Addons\Shared\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Log all things!
 *
 * @since 4.0.0
 * @version 4.0.0
 */
class Stripe_Logger {

	public static $logger;
	const WC_LOG_FILENAME = 'elementor-pay-addons';

	/**
	 * Utilize WC logger class
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public static function log( $message, $start_time = null, $end_time = null ) {
    // TODO
    Logger::debug($message);
	}
}
