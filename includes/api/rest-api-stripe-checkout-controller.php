<?php

namespace Elementor_Pay_Addons\API;

if (!defined('ABSPATH')) {
    exit();
}

// Exit if accessed directly
use Elementor_Pay_Addons\Stripe\Stripe_API;
use Elementor_Pay_Addons\Shared\Security_Utils;
use Elementor_Pay_Addons\Shared\Logger;
use Elementor_Pay_Addons\Shared\Utils;

class Rest_API_Stripe_Checkout_Controller extends \WP_REST_Controller 
{
    public function __construct()
    {
      $this->namespace = EPA_ADDONS_REST_API . 'stripe';
      $this->rest_base = 'checkout-session';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, $this->rest_base, array(
            array(
                'methods'            => \WP_REST_Server::CREATABLE,
                'callback'          => array($this, 'create_checkout_session'),
                'permission_callback' => array($this, 'verify_access')
            ),
        ));
    }

    public function verify_access(\WP_REST_Request $request) {
        return Security_Utils::client_access_check($request);
    }

    public function create_checkout_session(\WP_REST_Request $request)
    {
        $postData = sanitize_post($request->get_params());
        
        // Logger::info('create_checkout_session', $postData);

        try {
            unset($postData['ID']);
            unset($postData['filter']);
            unset($postData['rest_route']);
            unset($postData['_locale']);
            
            $args = $this->get_checkout_session_args($postData, $request);
            $checkout = Stripe_API::create_checkout_session($args);
            return new \WP_REST_Response(array(
                'sessionId' => $checkout->id
            ));
        } catch (\Exception $ex) {
            Logger::error('create_checkout_session ' . $ex->getMessage());
            return new \WP_Error('stripe_error', __($ex->getMessage()), array('status' => 400));
        }
    }

    public function get_checkout_session_args($postData) {
        $defaultSession = array(
            'success_url' => get_site_url(),
            'cancel_url' => get_site_url(),
            'mode' => 'payment',
        );

        $session = array_merge($defaultSession, $postData);
        $session['success_url'] = add_query_arg('session_id', '{CHECKOUT_SESSION_ID}', esc_url_raw($session['success_url']));

        $payment_methods = $session['payment_method_types'] ?? [];
        if (count($payment_methods) === 1 && in_array('automatic', $payment_methods)) {
			unset($session['payment_method_types']);
		}
        if(count($payment_methods) > 1) {
            $payment_methods = array_filter($payment_methods, function($method) {
                return $method !== 'automatic';
            });
            if (!empty($payment_methods)) {
                $session['payment_method_types'] = array_values($payment_methods); 
            }
        }
        
        if (in_array('wechat_pay', $payment_methods)) {
            $session['payment_method_options'] = [
                'wechat_pay' => [
                    'client' => "web"
                ],
            ];
        }

        if (isset($postData['email'])) {
            $session['customer_email'] = sanitize_email($postData['email']);
        }
        if (isset($postData['billing_address_collection'])) {
            $session['billing_address_collection'] = 'required';
        }
        if (isset($postData['phone_number_collection'])) {
            $session['phone_number_collection'] = array(
                'enabled' => boolval($postData['phone_number_collection'])
            );
        }
        if (isset($postData['shipping_address_collection'])) {
            $session['shipping_address_collection'] = [
                'allowed_countries' => $postData['shipping_address_collection']
            ];
        }
        if (isset($postData['automatic_tax'])) {
            $session['automatic_tax'] = array(
                'enabled' => boolval($postData['automatic_tax'])
            );
            $session['tax_id_collection'] = array(
                'enabled' => true
            );
        }

        if (isset($session['terms_of_service'])) {
            $session['consent_collection'] = array(
              'terms_of_service' => boolval($postData['terms_of_service']) ? 'required' :'none'
            );
            unset($session['terms_of_service']);
        }

        if($session['line_items'][0]['price_data']) {
            $description = Utils::format_stripe_desc(
                $session['line_items'][0]['price_data']['product_data']['name'],
                $session['line_items'][0]['price_data']['product_data']['description']);
        }

        $session['subscription_data'] = [];
        $session['payment_intent_data'] = [];
        if ($session['mode'] == 'payment') {
            $session['payment_intent_data']['metadata'] = $session['metadata'] ?: array();
            if($description) {
                $session['payment_intent_data']['description'] = $description;
            }
        }

        if ($session['mode'] == 'subscription') {
            $session['subscription_data']['metadata'] = $session['metadata'] ?: array();
            if($description) {
                $session['subscription_data']['description'] = $description;
            }
        }
        return apply_filters(
            'elementor_pay_addons/api/checkout_session_args',
            $session
        );
    }
}
