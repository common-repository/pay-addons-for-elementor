<?php

namespace Elementor_Pay_Addons\Shared;

use Elementor_Pay_Addons\Stripe\Stripe_Settings;
use Elementor_Pay_Addons\Stripe\Stripe_Helper;
use Elementor_Pay_Addons\Shared\Utils;

class Controls
{

  /**
   * @param array      $array
   * @param int|string $position
   * @param mixed      $insert
   */
  static function array_insert(&$array, $position, $insert)
  {
    if (is_int($position)) {
      array_splice($array, $position, 0, $insert);
    } else {
      $pos   = array_search($position, array_keys($array));
      $array = array_merge(
        array_slice($array, 0, $pos),
        $insert,
        array_slice($array, $pos)
      );
    }
  }

  public static function get_session_checkout_setting_fields($prefix = '')
  {
    $isPremium = epa_fs()->can_use_premium_code();

    $default_currency = Stripe_Settings::get_setting('default_currency');
    $setting_controls = [
      [
        $prefix . 'premium',
        [
          'label' => esc_html__( 'premium', 'textdomain' ),
				  'type' => \Elementor\Controls_Manager::HIDDEN,
				  'default' => $isPremium,
        ]
      ],
      [
        $prefix . 'payment_method_types',
        [
          'label' => esc_html__('Payment Methods', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT2,
          'multiple' => true,
          'options' => Utils::get_supported_payment_methods($default_currency),
          'default' => ['card'],
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'mode',
        [
          'label' => esc_html__('Mode', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT,
          'default' => 'payment',
          'options' => [
            'payment'  => esc_html__('one-time', 'elementor-pay-addons'),
            'subscription'  => esc_html__('subscription', 'elementor-pay-addons'),
          ],
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'submit_type',
        [
          'label' => esc_html__('Button type', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT,
          'default' => 'auto',
          'options' => [
            'auto'  => esc_html__('auto', 'elementor-pay-addons'),
            'pay'  => esc_html__('pay', 'elementor-pay-addons'),
            'book' => esc_html__('book', 'elementor-pay-addons'),
            'donate' => esc_html__('donate', 'elementor-pay-addons'),
          ],
          'condition' => [
            $prefix . 'mode' => 'payment',
          ],
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'success_url',
        [
          'label' => esc_html__('Success redirect url', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT,
          'options' => Utils::get_pages_options(),
          'default' => Stripe_Settings::get_setting('success_url'),
          'label_block' => true,
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'cancel_url',
        [
          'label' => esc_html__('Cancel redirect url', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT,
          'options' => Utils::get_pages_options(),
          'default' => Stripe_Settings::get_setting('cancel_url'),
          'label_block' => true,
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'billing_address_collection',
        [
          'label' => esc_html__('Billing address required', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SWITCHER,
          'return_value' => 'yes',
          'default' => 'yes',
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'allow_promotion_codes',
        [
          'label' => esc_html__('Enable promotion', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SWITCHER,
          'return_value' => 'yes',
          'default' => 'yes',
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'automatic_tax',
        [
          'label' => esc_html__('Enable automatic taxes', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SWITCHER,
          'return_value' => 'yes',
          'default' => 'yes',
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'tax_behavior',
        [
          'label' => esc_html__('Tax Behaviors', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT,
          'default' => 'exclusive',
          'description' => esc_html__('Only be used when automatic tax enabled. This determines whether or not tax is already included in your pricing. For example, an inclusive line item with an amount of $10 totals to $10, whereas an exclusive line item with an amount of $10 totals to $10 + tax', 'elementor-pay-addons'),
          'options' => [
            // 'unspecified'  => esc_html__('unspecified', 'elementor-pay-addons'),
            'exclusive'  => esc_html__('exclusive', 'elementor-pay-addons'),
            'inclusive'  => esc_html__('inclusive', 'elementor-pay-addons'),
          ],
          'condition' => [
            $prefix . 'automatic_tax' => 'yes',
          ],
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'phone_number_collection',
        [
          'label' => esc_html__('Phone number required', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SWITCHER,
          'return_value' => 'yes',
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'terms_of_service',
        [
          'label' => esc_html__('Enable terms of service', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SWITCHER,
          'return_value' => 'yes',
          'frontend_available' => true,
        ]
      ],
      [
        $prefix . 'shipping_address_collection',
        [
          'label' => esc_html__('Shipping address countries', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT2,
          'multiple' => true,
          'options' => Countries::get_countries(),
          'default' => ['US'],
          'frontend_available' => true,
        ]
      ]
    ];

    return $setting_controls;
  }

  public static function get_session_checkout_line_item($prefix = '')
  {
    $default_currency = Stripe_Settings::get_setting('default_currency');

    $price_list = Stripe_Helper::get_price_item_list();
    return [
      [
        'enable_pricing_plan',
        [
          'label' => esc_html__('Use stripe price', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SWITCHER,
          'description' => __('Need to create the product and its price in Stripe dashboard.<a href="https://dashboard.stripe.com/products" target="_blank">Create price</a>', 'elementor-pay-addons'),
          'return_value' => 'yes',
          'default' => 'no',
          'condition' => [
            'premium' => true,
          ],
        ]
      ],
      [
        'price',
        [
          'label' => esc_html__('Price', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT,
          'dynamic' => [
            'active' => true,
          ],
          'condition' => [
            'enable_pricing_plan' => 'yes',
          ],
          'label_block' => true,
          'options' => $price_list,
          'frontend_available' => true,
        ]
      ],
      [
        'quantity',
        [
          'label' => esc_html__('Quantity', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::NUMBER,
          'min' => 1,
          'default' => 1,
          'dynamic' => [
            'active' => true,
          ],
          'frontend_available' => true,
        ]
      ],
      [
        'price_data.currency',
        [
          'label' => esc_html__('Currency', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT,
          'default' => $default_currency,
          'options' => Utils::get_currencies_options(),
          'frontend_available' => true,
          'condition' => [
            'enable_pricing_plan!' => 'yes',
          ],
        ]
      ],
      [
        'price_data.unit_amount',
        [
          'label' => esc_html__('Amount', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::NUMBER,
          'min' => 1,
          'default' => '1.00',
          'dynamic' => [
            'active' => true,
          ],
          'frontend_available' => true,
          'condition' => [
            'enable_pricing_plan!' => 'yes',
          ],
        ]
      ],
      [
        'enable_recurring',
        [
          'label' => esc_html__('Recurring', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SWITCHER,
          'description' => esc_html__('set Mode as subscription in General Settings is required.', 'elementor-pay-addons'), 
          'return_value' => 'yes',
          'default' => 'false',
          'condition' => [
            'mode' => 'subscription',
            'enable_pricing_plan!' => 'yes',
          ],
        ]
      ],
      [
        'price_data.recurring.interval_count',
        [
          'label' => esc_html__('Number of intervals', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::NUMBER,
          'default' => 1,
          'condition' => [
              'enable_pricing_plan!' => 'yes',
              'enable_recurring' => 'yes',
              'mode' => 'subscription',
          ],
          'frontend_available' => true,
        ]
      ],
      [
        'price_data.recurring.interval',
        [
          'label' => esc_html__('Recurring frequency', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::SELECT,
          'default' => 'month',
          'options' => [
            'day'  => esc_html__('day', 'elementor-pay-addons'),
            'week'  => esc_html__('week', 'elementor-pay-addons'),
            'month'  => esc_html__('month', 'elementor-pay-addons'),
            'year'  => esc_html__('year', 'elementor-pay-addons'),
          ],
          'condition' => [
              'enable_pricing_plan!' => 'yes',
              'enable_recurring' => 'yes',
              'mode' => 'subscription',
          ],
          'frontend_available' => true,
        ]
      ],
      [
        'price_data.product_data.name',
        [
          'label' => esc_html__('Product Name', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::TEXT,
          'default' => esc_html__('your product name', 'elementor-pay-addons'),
          'dynamic' => [
            'active' => true,
          ],
          'label_block' => true,
          'frontend_available' => true,
          'condition' => [
            'enable_pricing_plan!' => 'yes',
          ],
        ]
      ],
      [
        'price_data.product_data.description',
        [
          'label' => esc_html__('Product Description', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::TEXTAREA,
          'dynamic' => [
            'active' => true,
          ],
          'frontend_available' => true,
          'condition' => [
            'enable_pricing_plan!' => 'yes',
          ],
        ]
      ],
      [
        'price_data.product_data.images',
        [
          'label' => esc_html__('Add Product Images(max 8)', 'elementor-pay-addons'),
          'type' => \Elementor\Controls_Manager::GALLERY,
          // 'show_label' => false,
          'default' => [],
          'frontend_available' => true,
          'condition' => [
            'enable_pricing_plan!' => 'yes',
          ],
        ]
      ]
    ];
  }

  public static function get_session_checkout_metadata($prefix = '')
  {
    $repeater = new \Elementor\Repeater();
			
		$repeater->add_control(
			'metadata_key', [
				'label' => esc_html__( 'Key', 'elementor-pay-addons' ),
        'description' => esc_html__('40 characters max', 'elementor-pay-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'frontend_available' => true,
			]
		);

		$repeater->add_control(
			'metadata_value', [
				'label' => esc_html__( 'Value', 'elementor-pay-addons' ),
        'description' => esc_html__('500 characters max', 'elementor-pay-addons' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'frontend_available' => true,
			]
		);

    return [
      $prefix . 'metadata',
			[
				'label' => esc_html__( 'Metadata', 'elementor-pay-addons' ),
				'description' => esc_html__( 'Set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format.', 'elementor-pay-addons' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [],
				'title_field' => '{{{ metadata_key }}} : {{{ metadata_value }}}',
				'frontend_available' => true,
			]
    ];
  }

};
