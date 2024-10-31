<?php

namespace Elementor_Pay_Addons\API;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
use Elementor_Pay_Addons\Stripe\Stripe_API;
use Elementor_Pay_Addons\Stripe\Stripe_Settings;
use Elementor_Pay_Addons\Stripe\Stripe_Webhook_State;
use Elementor_Pay_Addons\Shared\Utils;
use Elementor_Pay_Addons\Core\Mailer;
use Elementor_Pay_Addons\Shared\Logger;
class Rest_API_Stripe_Webhooks_Controller extends \WP_REST_Controller {
    public function __construct() {
        $this->namespace = EPA_ADDONS_REST_API . 'stripe';
        $this->rest_base = 'webhook';
        Stripe_Webhook_State::get_monitoring_began_at();
    }

    public function register_routes() {
        register_rest_route( $this->namespace, $this->rest_base, array(array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_webhooks'),
            'permission_callback' => array($this, 'verify_access'),
        )) );
    }

    public function verify_access( \WP_REST_Request $request ) {
        return true;
    }

    public function handle_webhooks( \WP_REST_Request $request ) {
        try {
            // Parse the message body (and check the signature if possible)
            $webhookSecret = Stripe_Settings::get_setting( 'webhook_secret' );
            if ( !empty( $webhookSecret ) ) {
                try {
                    $event = \Stripe\Webhook::constructEvent( $request->get_body(), $request->get_header( 'stripe-signature' ), $webhookSecret );
                } catch ( \Exception $e ) {
                    Logger::error( 'Exception on StripeWebhook crypto:' . $e->getMessage() );
                    Stripe_Webhook_State::set_last_webhook_failure_at( time() );
                    Stripe_Webhook_State::set_last_error_reason( Stripe_Webhook_State::VALIDATION_FAILED_SIGNATURE_INVALID );
                    return http_response_code( 403 );
                }
            } else {
                $event = $request->get_json_params();
            }
            $event = apply_filters( 'elementor_pay_addons/webhook/event', $event );
            $type = $event['type'];
            $object = $event['data']['object'];
            switch ( $type ) {
                case 'checkout.session':
                    // do nothing
                    break;
                case 'checkout.session.async_payment_succeeded':
                    $intent = Stripe_API::retrieve_payment_intent( $object['payment_intent'], [] );
                    $this->send_payment_success_email( $intent, null );
                    break;
                case 'checkout.session.async_payment_failed':
                    $intent = Stripe_API::retrieve_payment_intent( $object['payment_intent'], [] );
                    $this->send_payment_failed_email( $intent );
                    break;
                case 'charge.succeeded':
                    break;
                case 'invoice.upcoming':
                    break;
                case 'invoice.payment_succeeded':
                    break;
                case 'invoice.payment_failed':
                    break;
                case 'payment_intent.payment_failed':
                    $intent = $object;
                    $error_message = ( $intent->last_payment_error ? $intent->last_payment_error->message : "" );
                    Logger::info( 'Webhook received! PaymentIntent event ' . $intent['id'] . ' failed:' . $error_message );
                    $this->send_payment_failed_email( $intent );
                    break;
                case 'payment_intent.succeeded':
                    $intent = $object;
                    // Process valid response.
                    $this->send_payment_success_email( $intent, null );
                    break;
            }
            Stripe_Webhook_State::set_last_webhook_success_at( $event['created'] );
            http_response_code( 200 );
        } catch ( \Exception $e ) {
            Logger::error( 'Exception detail:', json_encode( $e ) );
            return new \WP_Error('stripe_error', __( $e->getMessage() ), array(
                'status' => 400,
            ));
        }
    }

    public function is_redirect_based_payment_method( $charge ) {
        $payment_method = $charge['payment_method_details']['type'];
        $redirection_prone = [
            'paypal',
            'ideal',
            'giropay',
            'bancontact',
            'sofort',
            'p24',
            'eps',
            'fpx',
            'bacs_debit',
            'au_becs_debit',
            'grabpay',
            'oxxo',
            'alipay',
            'wechat_pay'
        ];
        return in_array( $payment_method, $redirection_prone );
    }

    function send_payment_success_email( $intent ) {
        $placeholders = self::get_receipt_placeholders( $intent['id'] );
        // if subscription
        if ( $intent['invoice'] ) {
            $invoice = Stripe_API::retrieve_invoice( $intent['invoice'], [] );
            if ( $invoice['subscription'] ) {
                $subscription = Stripe_API::retrieve_subscription( $invoice['subscription'], [] );
                if ( $subscription['metadata'] ) {
                    self::append_metadata_placeholder( $subscription, $placeholders );
                }
            }
        }
        $email = Mailer::get_emails()['epa_email_pay_success'];
        $email->set_placeholders( $placeholders )->trigger();
        $invoice_email = Mailer::get_emails()['epa_email_invoice'];
        $invoice_email->set_placeholders( $placeholders )->trigger();
    }

    function send_payment_failed_email( $intent ) {
        // Send an email to the customer asking them to retry their order
        $placeholders = self::get_receipt_placeholders( $intent['id'], $intent['last_payment_error'] );
        $email = Mailer::get_emails()['epa_email_pay_fail'];
        $email->set_placeholders( $placeholders )->trigger();
    }

    public static function get_receipt_placeholders( $payment_intent_id, $last_payment_error = [] ) {
        $payment_intent = Stripe_API::retrieve_payment_intent( $payment_intent_id, [
            'expand' => ['customer', 'payment_method', 'latest_charge'],
        ] );
        $placeholders = array();
        $billDetail = $payment_intent['payment_method']['billing_details'];
        $shippingDetail = $payment_intent['shipping'];
        $charge = $payment_intent['latest_charge'];
        if ( !empty( $payment_intent['customer'] ) ) {
            $customer = Stripe_API::retrieve_customer( $payment_intent['customer']['id'], [] );
            $placeholders['customer.email'] = $customer['email'];
            $placeholders['customer.name'] = $customer['name'];
            $placeholders['customer.phone'] = $customer['phone'];
            $placeholders['customer.address.country'] = $customer['address']['country'];
            $placeholders['customer.address.state'] = $customer['address']['state'];
            $placeholders['customer.address.city'] = $customer['address']['city'];
            $placeholders['customer.address.line1'] = $customer['address']['line1'];
            $placeholders['customer.address.line2'] = $customer['address']['line2'];
            $placeholders['customer.address.postal_code'] = $customer['address']['postal_code'];
        }
        if ( empty( $placeholders['customer.name'] ) ) {
            $placeholders['customer.name'] = $billDetail['name'];
        }
        if ( empty( $placeholders['customer.email'] ) ) {
            $placeholders['customer.email'] = $billDetail['email'];
        }
        $placeholders['payment_intent_id'] = $payment_intent['id'];
        $placeholders['description'] = $payment_intent['description'];
        $placeholders['amount'] = intval( $payment_intent['amount'] / 100 );
        $placeholders['currency'] = $payment_intent['currency'];
        $placeholders['created'] = Utils::format_stripe_date( $payment_intent['created'] );
        $placeholders['amount_currency'] = Utils::format_amount_with_symbol( $payment_intent['amount'], $payment_intent['currency'] );
        if ( !empty( $payment_intent['metadata'] ) ) {
            self::append_metadata_placeholder( $payment_intent, $placeholders );
        }
        $placeholders['billing_detail.address.country'] = $billDetail['address']['country'];
        $placeholders['billing_detail.address.state'] = $billDetail['address']['state'];
        $placeholders['billing_detail.address.city'] = $billDetail['address']['city'];
        $placeholders['billing_detail.address.line1'] = $billDetail['address']['line1'];
        $placeholders['billing_detail.address.line2'] = $billDetail['address']['line2'];
        $placeholders['billing_detail.address.postal_code'] = $billDetail['address']['postal_code'];
        $placeholders['billing_detail.address.formatted'] = Utils::get_formatted_address( $billDetail['address'] );
        $placeholders['billing_detail.email'] = $billDetail['email'];
        $placeholders['billing_detail.name'] = $billDetail['name'];
        $placeholders['billing_detail.phone'] = $billDetail['phone'];
        $placeholders['payment_method.type'] = $payment_intent['payment_method']['type'];
        $placeholders['payment_method.card.brand'] = $payment_intent['payment_method']['card']['brand'];
        $placeholders['payment_method.card.last4'] = $payment_intent['payment_method']['card']['last4'];
        if ( !empty( $shippingDetail ) ) {
            $placeholders['shipping_detail.address.country'] = $shippingDetail['address']['country'];
            $placeholders['shipping_detail.address.state'] = $shippingDetail['address']['state'];
            $placeholders['shipping_detail.address.city'] = $shippingDetail['address']['city'];
            $placeholders['shipping_detail.address.line1'] = $shippingDetail['address']['line1'];
            $placeholders['shipping_detail.address.line2'] = $shippingDetail['address']['line2'];
            $placeholders['shipping_detail.address.postal_code'] = $shippingDetail['address']['postal_code'];
            $placeholders['shipping_detail.address.formatted'] = Utils::get_formatted_address( $shippingDetail['address'] );
            $placeholders['shipping_detail.name'] = $shippingDetail['name'];
            $placeholders['shipping_detail.phone'] = $shippingDetail['phone'];
        }
        if ( !empty( $last_payment_error ) ) {
            $placeholders['error.message'] = $last_payment_error['message'];
        }
        $placeholders['receipt.url'] = $charge['receipt_url'];
        return $placeholders;
    }

    public static function append_metadata_placeholder( &$payment_intent, &$placeholders ) {
        $placeholders['metadata'] = json_encode( $payment_intent['metadata'] );
        $payment_intent_metadata = $payment_intent['metadata']->toArray();
        foreach ( $payment_intent_metadata as $k => $v ) {
            $placeholders['metadata.' . $k] = $v;
        }
    }

}
