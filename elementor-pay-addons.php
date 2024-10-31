<?php

/**
 * Plugin Name: Elementor Stripe Payment
 * Description: The easiest way to add STRIPE payment functionality to your Elementor-powered website! Drag & Drop to build your one-time and recurring payment form together with elementor without creating an entire online store.
 * Version:     1.13.3
 * Author:    	Payment Addons, support@payaddons.com 
 * Author URI:	https://payaddons.com
 * Text Domain: pay-addons-for-elementor
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: elementor 
 * Elementor tested up to: 3.23.4
 * Elementor Pro tested up to: 3.23.3
 * 
  */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Defining plugin constants.
 *
 * @since 1.0.0
 */
define('EPA_PLUGIN_NAME', 'Elementor Pay Addons');
define('EPA_PLUGIN_VERSION', '1.13.3');
define('EPA_PLUGIN_URL', 'https://payaddons.com/');
define('EPA_PLUGIN_TEMPLATE_URL', 'https://api.payaddons.com/elementor');
define('EPA_ADDONS_REST_API', 'epa/v1/');
define('EPA_ADDONS_FILE', __FILE__);
define('EPA_ADDONS_BASENAME', plugin_basename(__FILE__));
define('EPA_ADDONS_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('EPA_ADDONS_ASSET_PATH', EPA_ADDONS_PATH . '/assets/');
define('EPA_ADDONS_EMAILS_TEMPLATE_PATH', EPA_ADDONS_PATH . '/includes/templates/emails/');
define('EPA_ADDONS_URL', trailingslashit(plugins_url('/', __FILE__)));
define('EPA_ADDONS_ASSET_URL', EPA_ADDONS_URL . '/assets/');
define('EPA_ADDONS_LOG_FOLDER', plugin_dir_path(__FILE__) . 'logs');

require_once('freemius-config.php');
require_once EPA_ADDONS_PATH . '/autoload.php';
require_once EPA_ADDONS_PATH . '/vendor/autoload.php';
require_once EPA_ADDONS_PATH . '/bootstrap.php';
require_once EPA_ADDONS_PATH . '/includes/functions.php';

/**
 * Run plugin after all others plugins
 *
 * @since 1.0.0
 */
add_action( 'plugins_loaded', function() {
	\Elementor_Pay_Addons\Bootstrap::instance();
} );

/**
 * Activation hook
 *
 * @since v1.0.0
 */
register_activation_hook(__FILE__, function () {
	register_uninstall_hook( __FILE__, 'epa_plugin_uninstall' );
});

/**
 * Deactivation hook
 *
 * @since v1.0.0
 */
register_deactivation_hook(__FILE__, function () {
});

/**
 * Handle uninstall
 *
 * @since v1.0.0
 */
if ( !function_exists('epa_plugin_uninstall') ) {
	function epa_plugin_uninstall(){
		if (!get_option('epa_keep_data')) {
			// Delete options.
			delete_option( 'epa_stripe_settings' );
			delete_option( 'epa_sys_settings' );
		}
		
		epa_fs()->add_action('after_uninstall', 'epa_fs_uninstall_cleanup');
	}
}