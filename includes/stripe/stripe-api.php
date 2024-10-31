<?php

namespace Elementor_Pay_Addons\Stripe;

use Elementor_Pay_Addons\Stripe\Stripe_Logger;
use Elementor_Pay_Addons\Stripe\Stripe_Helper;
use Elementor_Pay_Addons\Stripe\Stripe_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Communicates with Stripe API.
 */
class Stripe_API {

	/**
	 * Stripe API Endpoint
	 */
	const ENDPOINT           = 'https://api.stripe.com/v1/';
	const STRIPE_API_VERSION = '2022-08-01';

	/**
	 * Generates the user agent we use to pass to API request so
	 * Stripe can identify our application.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public static function get_user_agent() {
		$app_info = [
			'name'       => EPA_PLUGIN_NAME,
			'version'    => EPA_PLUGIN_VERSION,
			'url'        => EPA_PLUGIN_URL,
		];

		return [
			'lang'         => 'php',
			'lang_version' => phpversion(),
			'uname'        => php_uname(),
			'application'  => $app_info,
		];
	}

	/**
	 * Generates the headers to pass to API request.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public static function get_headers() {
		$user_agent = self::get_user_agent();
		$app_info   = $user_agent['application'];

		$headers = apply_filters(
			'elementor_pay_addons/api/stripe_request_headers',
			[
				'Authorization'  => 'Basic ' . base64_encode( Stripe_Settings::get_secret_key() . ':' ),
				'Stripe-Version' => self::STRIPE_API_VERSION,
			]
		);

		// These headers should not be overridden for this gateway.
		$headers['User-Agent']                 = $app_info['name'] . '/' . $app_info['version'] . ' (' . $app_info['url'] . ')';
		$headers['X-Stripe-Client-User-Agent'] = wp_json_encode( $user_agent );

		return $headers;
	}

	/**
	 * Send the request to Stripe's API
	 *
	 * @since 3.1.0
	 * @version 4.0.6
	 * @param array  $request
	 * @param string $api
	 * @param string $method
	 * @param bool   $with_headers To get the response with headers.
	 * @return stdClass|array
	 * @throws WC_Stripe_Exception
	 */
	public static function request( $request, $api = 'charges', $method = 'POST', $with_headers = false ) {
		// Stripe_Logger::log( "{$api} request: " . print_r( $request, true ) );

		$headers         = self::get_headers();

		$response = wp_safe_remote_post(
			self::ENDPOINT . $api,
			[
				'method'  => $method,
				'headers' => $headers,
				'body'    => $request,
				'timeout' => 70,
			]
		);

		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			Stripe_Logger::log(
				'Error Response: ' . print_r( $response, true ) . PHP_EOL . PHP_EOL . 'Failed request: ' . print_r(
					[
						'api'             => $api,
						'request'         => $request,
					],
					true
				)
			);

			throw new Exception( print_r( $response, true ), __( 'There was a problem connecting to the Stripe API endpoint.', 'woocommerce-gateway-stripe' ) );
		}

		if ( $with_headers ) {
			return [
				'headers' => wp_remote_retrieve_headers( $response ),
				'body'    => json_decode( $response['body'] ),
			];
		}

		return json_decode( $response['body'] );
	}

	/**
	 * Retrieve API endpoint.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param string $api
	 */
	public static function retrieve( $api ) {
		Stripe_Logger::log( "{$api}" );

		$response = wp_safe_remote_get(
			self::ENDPOINT . $api,
			[
				'method'  => 'GET',
				'headers' => self::get_headers(),
				'timeout' => 70,
			]
		);

		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			Stripe_Logger::log( 'Error Response: ' . print_r( $response, true ) );
			return new WP_Error( 'stripe_error', __( 'There was a problem connecting to the Stripe API endpoint.', 'woocommerce-gateway-stripe' ) );
		}

		return json_decode( $response['body'] );
	}

  public static function set_app_info() {
		\Stripe\Stripe::setAppInfo(
			EPA_PLUGIN_NAME,
			EPA_PLUGIN_VERSION,
			EPA_PLUGIN_URL
		);
	}

	public static function get_stripe_client() {
		self::set_app_info();
    \Stripe\Stripe::setMaxNetworkRetries( 2 );
    return new \Stripe\StripeClient(Stripe_Settings::get_secret_key());
	}

	public static function create_payment_intent($paymentIntent) {
		$stripe = self::get_stripe_client();
    return $stripe->paymentIntents->create($paymentIntent);	
	}
	public static function retrieve_payment_intent($id, $expend) {
		$stripe = self::get_stripe_client();
    return $stripe->paymentIntents->retrieve($id, $expend);	
	}

  public static function create_checkout_session($session = array()) {
		$stripe = self::get_stripe_client();
    return $stripe->checkout->sessions->create($session);
  }

	public static function create_customer($customer) {
		$stripe = self::get_stripe_client();

		// $clock = self::request([
		// 	'frozen_time' => time(),
		// 	'name' => 'Annual renewal',
		// ], 'test_helpers/test_clocks');
		// Stripe_Logger::log('clock', $clock->id);
		// $customer['test_clock'] = $clock->id;

    return $stripe->customers->create($customer);	
	}

	public static function retrieve_customer($id, $expend) {
		$stripe = self::get_stripe_client();
    return $stripe->customers->retrieve($id, $expend);	
	}

	public static function create_subscription($subscription) {
		$stripe = self::get_stripe_client();
    return $stripe->subscriptions->create($subscription);	
	}

	public static function update_subscription($id, $params) {
		$stripe = self::get_stripe_client();
    return $stripe->subscriptions->update($id, $params);	
	}

	public static function retrieve_subscription($subscription_id, $expend) {
		$stripe = self::get_stripe_client();
    return $stripe->subscriptions->retrieve($subscription_id, $expend);	
	}

	public static function retrieve_invoice($invoice_id, $expend) {
		$stripe = self::get_stripe_client();
    return $stripe->invoices->retrieve($invoice_id, $expend);	
	}

	public static function create_subscription_schedule($schedule) {
		$stripe = self::get_stripe_client();
    return $stripe->subscriptionSchedules->create($schedule);	
	}

	public static function update_subscription_schedule($id, $params) {
		$stripe = self::get_stripe_client();
    return $stripe->subscriptionSchedules->update($id, $params);	
	}

	public static function create_product($product) {
		$stripe = self::get_stripe_client();
    return $stripe->products->create($product);	
	}

	public static function create_price($price) {
		$stripe = self::get_stripe_client();
    return $stripe->prices->create($price);	
	}

	public static function retrieve_receipt_checkout_session($session_id) {
		$stripe = self::get_stripe_client();
		$session = $stripe->checkout->sessions->retrieve($session_id,
			['expand' => [
				'customer',
				'payment_intent.payment_method',
				'payment_intent.latest_charge',
				'invoice.payment_intent.payment_method'
				]
			]
		);
		// Stripe_Logger::log($session);

		$placeholder = Stripe_Helper::get_receipt_placeholders($session);
		return $placeholder;
	}

	public static function retrieve_prices() {
		$stripe = self::get_stripe_client();
		$list = $stripe->prices->all([
			'limit' => 100,
			['expand' => ['data.product']]
		]);

		return $list;
	}
}
