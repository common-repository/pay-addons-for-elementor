<?php

namespace Elementor_Pay_Addons;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor_Pay_Addons\Stripe\Stripe_Settings;

abstract class Widget_Checkout_Base extends Widget_Base {

	protected function register_warning_controls() {
		$this->start_controls_section(
			'epa_global_warning',
			[
				'label' => __('Warning!', 'elementor-pay-addons'),
			]
		);

		$this->add_control(
			'epa_global_warning_text',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => __('The <strong>Stripe credentials</strong> have not been set up on your site yet. Please set them up first.', 'elementor-pay-addons'),
				'content_classes' => 'epa-warning',
			]
		);

		$this->end_controls_section();	
	}

	protected function register_checkout_setting_controls( $args = [] ) {
		// Content pay section
		$this->start_controls_section(
			'stripe_setting_section',
			[
				'label' => esc_html__('Checkout Settings', 'elementor-pay-addons'),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
	
		$checkout_setting_controls = \Elementor_Pay_Addons\Shared\Controls::get_session_checkout_setting_fields();
		
		foreach($checkout_setting_controls as $control) {
			$this->add_control($control[0], $control[1]);
		}

		$checkout_metadata_controls = \Elementor_Pay_Addons\Shared\Controls::get_session_checkout_metadata();
		
		$this->add_control($checkout_metadata_controls[0], $checkout_metadata_controls[1]);

		// $this->end_controls_section();

		// // Content pay section
		// $this->start_controls_section(
		// 	'stripe_price_section',
		// 	[
		// 		'label' => esc_html__('Price Settings', 'elementor-pay-addons'),
		// 		'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
		// 	]
		// );

		$checkout_lineitem_controls = \Elementor_Pay_Addons\Shared\Controls::get_session_checkout_line_item();
		
		foreach($checkout_lineitem_controls as $control) {
			$this->add_control($control[0], $control[1]);
		}

		$this->end_controls_section();
	}

  protected function register_controls()
	{
		if (empty(Stripe_Settings::get_secret_key())) {
			$this->register_warning_controls();
		}
		$this->register_checkout_setting_controls();
	}

	protected function render_error_message() {
		?>
		<div class="epa-alert epa-alert-outline d-none">
			<div class="epa-alert-icon"><i class="fa fa-info-circle"></i></div>
			<div class="epa-alert-message"></div>
		</div>
		<?php
	}
}