<?php

namespace Elementor_Pay_Addons\Shared;

use WP_Error;
use WP_REST_Request;

class Security_Utils
{
	public static function admin_check()
	{
		if (!current_user_can('manage_options')) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__('You dont have the right permissions', 'epa'),
				['status' => 401]
			);
		}

		return true;
	}

	public static function client_access_check(WP_REST_Request $request)
	{
		$nonce = $request->get_header('X-WP-Nonce');
		if (!wp_verify_nonce($nonce, 'wp_rest')) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__('You are not allowed to do that' . $nonce, 'epa'),
				['status' => 403]
			);
		}
		return true;
	}

	public static function is_elementor_pro() {
		return defined( 'ELEMENTOR_PRO_VERSION' );
	}

	public static function is_pro() {
		return epa_fs()->can_use_premium_code();
	}
}
