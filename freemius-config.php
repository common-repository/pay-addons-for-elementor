<?php

if ( !function_exists( 'epa_fs' ) ) {
    // Create a helper function for easy SDK access.
    function epa_fs() {
        global $epa_fs;
        if ( !isset( $epa_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_12024_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_12024_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $epa_fs = fs_dynamic_init( array(
                'id'              => '12024',
                'slug'            => 'elementor-pay-addons',
                'type'            => 'plugin',
                'public_key'      => 'pk_579565cca2ef886114dd20fe9ac26',
                'is_premium'      => false,
                'has_addons'      => false,
                'has_paid_plans'  => true,
                'trial'           => array(
                    'days'               => 14,
                    'is_require_payment' => true,
                ),
                'has_affiliation' => 'selected',
                'menu'            => array(
                    'slug'       => 'elementor-pay-addons',
                    'first-path' => 'admin.php?page=elementor-pay-addons',
                    'support'    => false,
                ),
                'is_live'         => true,
            ) );
        }
        return $epa_fs;
    }

    // Init Freemius.
    epa_fs();
    // Signal that SDK was initiated.
    do_action( 'epa_fs_loaded' );
}