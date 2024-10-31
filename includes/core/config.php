<?php

namespace Elementor_Pay_Addons\Core;

defined('ABSPATH') || exit;

class Config
{
  public static $elements = [
    'checkout-button' => [
      'name' => 'Checkout Button',
      'desc'  => 'A simple elementor button to redirect your customer to a stripe-hosting checkout page.',
      'plan'  => 'free',
      'category' => 'widget'
    ],
    'confirmation-summary' => [
      'name' => 'Confirmation Summary',
      'desc'  => 'Use payment details, order details, custom message sections to create your Thank You page after a successful purchase redirect.',
      'plan'  => 'free',
      'category' => 'widget'
    ],
    'price-table-elementor' => [
      'name' => 'Pricing Table',
      'desc'  => 'Integrate stripe checkout with Elementor Price Table Widget.',
      'plan'  => 'free',
      'category' => 'widget'
    ],
    'form-integration' => [
      'name' => 'Checkout Form',
      'desc'  => 'Seamlessly compatible with Elementor Form widget to create any one-time or recurring order form, payment form, subscription event, etc.',
      'plan'  => 'pro',
      'category' => 'form',
      'warning' => 'Elementor pro is required'
    ],
  ];

  static function get_default_elements()
  {
    $widgets = array_filter(self::$elements, function ($ele) {
      return $ele['category'] == 'widget';
    });
    return array_fill_keys(array_keys($widgets), 1);
  }

  public static function get_all_elements()
  {
    $defaults      = self::get_default_elements();
    $values = get_option('epa_elements');
    return wp_parse_args($values, $defaults);
  }

  public static function update_elements($elements)
  {
    $values = get_option('epa_elements');
    update_option('epa_elements', wp_parse_args($elements, $values));
  }

  public static function get_active_elements()
  {
    $all_elements = self::get_all_elements();
    $active_elements = [];

    foreach ($all_elements as $key => $value) {
      if ($value == 1) {
        array_push($active_elements, $key);
      }
    }
    return $active_elements;
  }
}
