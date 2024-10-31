<?php

namespace Elementor_Pay_Addons;
use Elementor\Controls_Manager;
use Elementor\Includes\Widgets\Traits\Button_Trait;

class EPA_Widget_Checkout_Button extends Widget_Checkout_Base {

	use Button_Trait;
	
	public static $name = 'epa_checkout_button';

	public function get_name() {
		return self::$name;
	}

	public function get_title() {
		return esc_html__( 'Checkout button', 'elementor-pay-addons' );
	}

	public function get_icon() {
		return 'paicon-buy-now';
	}

	public function get_categories() {
		return [ 'pay-addons' ];
	}

	public function get_keywords() {
		return [ 'pay', 'checkout', 'stripe'];
	}
	
	public function get_custom_help_url()
	{
			return 'https://docs.payaddons.com';
	}

  public function get_script_depends() {
		return [];
	}

  public function get_style_depends() {
		return [];
	}

	protected function register_base_button_controls() {

		$this->start_controls_section(
			'section_button',
			[
				'label' => esc_html__( 'Button', 'elementor-pay-addons' ),
			]
		);

		$this->register_button_content_controls();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Button', 'elementor-pay-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_button_style_controls();

		$this->end_controls_section();
	}

	protected function register_controls()
	{
		parent::register_controls();
		$this->register_base_button_controls();
	}

	protected function render() {
		// add custom class
		$this->add_render_attribute( 'wrapper', 'class', 'epa-checkout-button-wrapper' );
		$this->render_button();
		$this->render_error_message();

		if ( \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
			?>
			<script>
				window.elementor_edit_mode = true;
			</script>
			<?php
		}
	}
}