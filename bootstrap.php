<?php

namespace Elementor_Pay_Addons;

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}
use Elementor\Plugin;
use Elementor_Pay_Addons\Traits\Admin;
use Elementor_Pay_Addons\Stripe\Stripe_Settings;
use Elementor_Pay_Addons\Core\Build_Assets;
use Elementor_Pay_Addons\API\Rest_API_Stripe_Checkout_Controller;
use Elementor_Pay_Addons\API\Rest_API_Stripe_Webhooks_Controller;
use Elementor_Pay_Addons\API\Rest_API_Settings_Controller;
use Elementor_Pay_Addons\API\Rest_API_Emails_Controller;
final class Bootstrap {
    use Admin;
    const MINIMUM_ELEMENTOR_VERSION = '3.2.0';

    // instance container
    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        if ( $this->is_compatible() ) {
            // register assets
            new Build_Assets();
            // register hooks
            $this->register_hooks();
        }
    }

    public function is_compatible() {
        // Check if Elementor is installed and activated
        if ( !did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [$this, 'admin_notice_missing_main_plugin'] );
            return false;
        }
        // Check for required Elementor version
        if ( !version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
            add_action( 'admin_notices', [$this, 'admin_notice_minimum_elementor_version'] );
            return false;
        }
        return true;
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have Elementor installed or activated.
     *
     * @since 1.0.0
     * @access public
     */
    public function admin_notice_missing_main_plugin() {
        if ( isset( $_GET['activate'] ) ) {
            unset($_GET['activate']);
        }
        $message = sprintf( 
            /* translators: 1: Plugin name 2: Elementor */
            esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'elementor-pay-addons' ),
            '<strong>' . esc_html__( 'Elementor Payment Addons', 'elementor-pay-addons' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor', 'elementor-pay-addons' ) . '</strong>'
         );
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    /**
     * Admin notice
     *
     * Warning when the site doesn't have a minimum required Elementor version.
     *
     * @since 1.0.0
     * @access public
     */
    public function admin_notice_minimum_elementor_version() {
        if ( isset( $_GET['activate'] ) ) {
            unset($_GET['activate']);
        }
        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
            esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-pay-addons' ),
            '<strong>' . esc_html__( 'Elementor Payment Addons', 'elementor-pay-addons' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor', 'elementor-pay-addons' ) . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }

    protected function register_hooks() {
        add_filter(
            'plugin_row_meta',
            [$this, 'plugin_row_meta'],
            10,
            2
        );
        // rest api
        add_action( 'rest_api_init', array($this, 'register_apis') );
        // admin section
        if ( is_admin() ) {
            // Admin
            add_action( 'admin_menu', array($this, 'admin_menu') );
            add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
        }
        // Enqueue editor style
        add_action( 'elementor/editor/before_enqueue_scripts', [$this, 'editor_enqueue_scripts'] );
        // Frontend CSS
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_common_scripts'], 998 );
        // Register Scripts
        add_action( 'elementor/frontend/before_register_scripts', [$this, 'register_scripts'], 998 );
        // Frontend JS
        add_action( 'elementor/frontend/before_enqueue_scripts', [$this, 'enqueue_scripts'], 998 );
    }

    public function plugin_row_meta( $plugin_meta, $plugin_file ) {
        if ( EPA_ADDONS_BASENAME === $plugin_file ) {
            $row_meta = [
                'docs' => '<a href="https://docs.payaddons.com" aria-label="' . esc_attr( esc_html__( 'View Elementor Pay Addon Documentation', 'elementor-pay-addons' ) ) . '" target="_blank">' . esc_html__( 'Docs', 'elementor-pay-addons' ) . '</a>',
            ];
            $plugin_meta = array_merge( $plugin_meta, $row_meta );
        }
        return $plugin_meta;
    }

    public function is_edit() {
        return Plugin::instance()->editor->is_edit_mode() || Plugin::instance()->preview->is_preview_mode();
    }

    public function register_apis() {
        $settings_api = new Rest_API_Settings_Controller();
        $stripe_api = new Rest_API_Stripe_Checkout_Controller();
        $webhooks_api = new Rest_API_Stripe_Webhooks_Controller();
        $emails_api = new Rest_API_Emails_Controller();
        $settings_api->register_routes();
        $webhooks_api->register_routes();
        $stripe_api->register_routes();
        $emails_api->register_routes();
    }

    // editor styles
    public function editor_enqueue_scripts() {
        wp_register_style(
            'pay-addons-icons',
            EPA_ADDONS_ASSET_URL . 'lib/paicons/style.css',
            [],
            EPA_PLUGIN_VERSION
        );
        wp_enqueue_style( 'pay-addons-icons' );
        // editor style
        wp_enqueue_style(
            'epa-editor',
            EPA_ADDONS_ASSET_URL . 'admin/css/editor.css',
            false,
            EPA_PLUGIN_VERSION
        );
        // preview widget
        $this->enqueue_scripts();
    }

    public function enqueue_common_scripts() {
        wp_register_style(
            'pay-addons',
            EPA_ADDONS_ASSET_URL . 'frontend/css/pay-addons.css',
            false,
            EPA_PLUGIN_VERSION
        );
        wp_enqueue_style( 'pay-addons' );
    }

    public function enqueue_scripts() {
        // if( $this->is_edit() ) {
        // 	return;
        // }
        // TODO: only load if have payment elements
        wp_enqueue_script( 'stripe-v3' );
        wp_enqueue_script( 'pay-addons' );
        wp_localize_script( 'pay-addons', 'epaSettings', array(
            'root'   => esc_url_raw( rest_url() . EPA_ADDONS_REST_API ),
            'nonce'  => wp_create_nonce( 'wp_rest' ),
            'apiKey' => Stripe_Settings::get_publishable_key(),
        ) );
    }

    public function register_scripts() {
        wp_register_script( 'stripe-v3', 'https://js.stripe.com/v3/' );
        wp_register_script(
            'pay-addons',
            EPA_ADDONS_ASSET_URL . 'frontend/js/pay-addons.js',
            ['elementor-frontend', 'jquery'],
            EPA_PLUGIN_VERSION
        );
    }

}
