<?php
namespace Elementor_Pay_Addons\Core;

defined( 'ABSPATH' ) || exit;

use Elementor_Pay_Addons\Shared\Logger;

class Config_Service {
	private static $_config = null;

  static function import_template( $fileurl ) {
    $response = wp_remote_get( $fileurl );
    $fileContent = wp_remote_retrieve_body( $response );

    $result = \Elementor\Plugin::instance()->templates_manager->import_template( [
            'fileData' => base64_encode( $fileContent ),
            'fileName' => basename($fileurl),
        ]
    );

    if ( empty( $result ) || empty( $result[0] ) ) {
        return;
    }

    // update_post_meta( $result[0]['template_id'], '_elementor_conditions', [ 'include/general' ] );
  }

  static function load_config() {
    $configs = get_transient('epa_stripe_configs');
    if (false === $configs || empty($configs)) {
      try {
        $response = wp_remote_get(EPA_PLUGIN_TEMPLATE_URL);
        $configs = json_decode(wp_remote_retrieve_body($response), true);
        set_transient('epa_stripe_configs', $configs, 60 * MINUTE_IN_SECONDS);
      } catch (\Exception $ex) {
        // try again
        $response = wp_remote_get(EPA_PLUGIN_TEMPLATE_URL);
        $configs = json_decode(wp_remote_retrieve_body($response), true);
        Logger::error('fetch configs error ' . $ex->getMessage());
      }
    }
    return $configs;
  }

  static function get_templates() {
    $configs = self::load_config();
    return $configs['templates'];
  }

  static function get_test_config() {
    $configs = self::load_config();
    return $configs['setting'];
  }
}