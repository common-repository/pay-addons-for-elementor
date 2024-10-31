<?php

namespace Elementor_Pay_Addons;
use Elementor\Plugin;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor_Pay_Addons\Stripe\Stripe_API;
use Elementor_Pay_Addons\Stripe\Stripe_Helper;

class EPA_Widget_confirmation_summary extends Widget_Base {

	public static $name = 'epa_confirmation_summary';

	public function get_name() {
		return self::$name;
	}

	public function get_title() {
		return esc_html__( 'Confirmation summary', 'elementor-pay-addons' );
	}

	public function get_icon() {
		return 'paicon-cart-summary';
	}

	public function get_categories() {
		return [ 'pay-addons' ];
	}

	public function get_keywords() {
		return [ 'summary', 'thank you', 'confirmation', 'purchase' ];
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

  protected function register_controls() {
    $this->start_controls_section(
			'confirmation_message',
			[
				'label' => esc_html__( 'Confirmation Message', 'elementor-pay-addons' ),
			]
		);

		$this->add_control(
			'confirmation_message_active',
			[
				'label' => esc_html__( 'Confirmation Message', 'elementor-pay-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementor-pay-addons' ),
				'label_off' => esc_html__( 'Hide', 'elementor-pay-addons' ),
				'default' => 'yes',
				'selectors' => [
					'{{WRAPPER}}' => '--epa-confirmation-message-display: block;',
				],
			]
		);

		$this->add_control(
			'confirmation_message_text',
			[
				'label' => esc_html__( 'Confirmation Message', 'elementor-pay-addons' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => esc_html__( 'Thank you. Your payment has been received.', 'elementor-pay-addons' ),
				'label_block' => true,
				'condition' => [
					'confirmation_message_active!' => '',
				],
			]
		);

		$this->add_control(
			'confirmation_message_alignment',
			[
				'label' => esc_html__( 'Alignment', 'elementor-pay-addons' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'start' => [
						'title' => esc_html__( 'Start', 'elementor-pay-addons' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'elementor-pay-addons' ),
						'icon' => 'eicon-text-align-center',
					],
					'end' => [
						'title' => esc_html__( 'End', 'elementor-pay-addons' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'condition' => [
					'confirmation_message_active!' => '',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--epa-confirmation-message-alignment: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

    $this->start_controls_section(
			'payment_detail_message',
			[
				'label' => esc_html__( 'Payment Details', 'elementor-pay-addons' ),
			]
		);

    $this->add_control(
			'payment_detail_active',
			[
				'label' => esc_html__( 'Payment Details', 'elementor-pay-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementor-pay-addons' ),
				'label_off' => esc_html__( 'Hide', 'elementor-pay-addons' ),
				'default' => 'yes'
			]
		);
    
    $this->add_control(
			'payment_detail_title',
			[
				'label' => esc_html__( 'Title Message', 'elementor-pay-addons' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => esc_html__( 'Payment Details', 'elementor-pay-addons' ),
				'label_block' => true,
				'condition' => [
					'payment_detail_active!' => '',
				],
			]
		);
   
		$this->end_controls_section();
    
    $this->start_controls_section(
			'billing_detail_message',
			[
				'label' => esc_html__( 'Billing Details', 'elementor-pay-addons' ),
			]
		);
  
    $this->add_control(
			'billing_detail_active',
			[
				'label' => esc_html__( 'Billing Details', 'elementor-pay-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementor-pay-addons' ),
				'label_off' => esc_html__( 'Hide', 'elementor-pay-addons' ),
				'default' => 'yes'
			]
		);
   
    $this->add_control(
			'billing_detail_title',
			[
				'label' => esc_html__( 'Title Message', 'elementor-pay-addons' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => esc_html__( 'Billing Details', 'elementor-pay-addons' ),
				'label_block' => true,
				'condition' => [
					'billing_detail_active!' => '',
				],
			]
		);

		$this->end_controls_section();

    $this->start_controls_section(
			'typography_title',
			[
				'label' => esc_html__( 'Typography', 'elementor-pay-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'confirmation_message_title',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Confirmation Message', 'elementor-pay-addons' ),
			]
		);

		$this->add_control(
			'confirmation_message_color',
			[
				'label' => esc_html__( 'Color', 'elementor-pay-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--epa-confirmation-message-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'confirmation_message_typography',
				'selector' => '{{WRAPPER}} .epa-confirmation-summary__thankyou',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'confirmation_message_text_shadow',
				'label' => esc_html__( 'Text Shadow', 'elementor-pay-addons' ),
				'selector' => '{{WRAPPER}} .epa-confirmation-summary__thankyou',
			]
		);


		$this->add_control(
			'general_text_title',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'General Text', 'elementor-pay-addons' ),
			]
		);

		$this->add_control(
			'general_text_color',
			[
				'label' => esc_html__( 'Color', 'elementor-pay-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--general-text-color: {{VALUE}};',
				],
			]
		);
    
    $this->add_control(
			'section_text',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Section Text', 'elementor-pay-addons' ),
			]
		);

    $this->add_control(
			'section_title_color',
			[
				'label' => esc_html__( 'Header Color', 'elementor-pay-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--epa-confirmation-section-header-color: {{VALUE}};',
				],
			]
		);

    $this->add_control(
			'section_bg_color',
			[
				'label' => esc_html__( 'Background Color', 'elementor-pay-addons' ),
				'type' => Controls_Manager::COLOR,
        'default' => '#f6f9fc',
				'selectors' => [
					'{{WRAPPER}}' => '--epa-confirmation-section-bg-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

  }

  protected function get_session_id() {
    return $_GET['session_id'] ?? '';
  }

  protected function validationCheck() {
    $session_id = $this->get_session_id();
    if(!$this->is_editor_preview() && empty($session_id)) {
      $this->render_error_message(esc_html__('session_id parameter is required.', 'elementor-pay-addons'));
      return false;
    }
    return true;
  }

  protected function is_editor_preview() {
    $is_preview = (
			isset( $_GET['preview'] )
			&& isset( $_GET['preview_id'] )
			&& isset( $_GET['preview_nonce'] )
			&& wp_verify_nonce( $_GET['preview_nonce'], 'post_preview_' . $_GET['preview_id'] )
		);
    return Plugin::instance()->editor->is_edit_mode() || $is_preview;
  }

	protected function render() {
    if(!$this->validationCheck()) {
      return;
    }
		$settings = $this->get_settings_for_display();
		if ( $this->is_editor_preview() ) {
      $placeholder = Stripe_Helper::get_receipt_placeholders_sample();
    } else {
      $session_id = $this->get_session_id();
      try {
        $placeholder = Stripe_API::retrieve_receipt_checkout_session($session_id);
      } catch (\Exception $ex) {
        $this->render_error_message(__($ex->getMessage()));
        return;
    }
    }
    ?>
    <div class="epa-confirmation-summary">
      <p class="epa-confirmation-summary__thankyou"><?php echo esc_html($settings['confirmation_message_text']) ?></p>
      <?php if($settings['payment_detail_active']) { $this->render_payment_details($placeholder); } ?>
      <?php if($settings['billing_detail_active']) { $this->render_billing_detail($placeholder); } ?>
    </div>
    <?php
	}

  protected function render_error_message($message) {
		?>
		<div class="epa-alert epa-alert-outline">
			<div class="epa-alert-icon"><i class="fa fa-info-circle"></i></div>
			<div class="epa-alert-message"><?php echo esc_html($message) ?></div>
		</div>
		<?php
	}

  protected function render_payment_details($placeholder) {
		$settings = $this->get_settings_for_display();
    ?>
    <h4><?php echo esc_html($settings['payment_detail_title']) ?></h4>
    <div class="epa-confirmation-summary__section payment-detail">
      <div>DATE: <strong><?php echo esc_html($placeholder['created']) ?></strong></div>
      <div>EMAIL: <strong><?php echo esc_html($placeholder['customer.email']) ?></strong></div>
      <div>TOTAL: <strong><?php echo esc_html($placeholder['amount_currency']) ?></strong></div>
      <div>PAYMENT METHOD: 
        <strong>
          <?php if($placeholder['payment_method'] == 'card') {
            echo esc_html($placeholder['payment_method.card.brand'] . ' - ' . $placeholder['payment_method.card.last4']);
          } else {
            echo esc_html($placeholder['payment_method']);
          } ?>
        </strong>
      </div>
    </div>
    <?php
  }

  protected function render_billing_detail($placeholder) {
		$settings = $this->get_settings_for_display();
    ?>
    <h4><?php echo esc_html($settings['billing_detail_title']) ?></h4>
    <div class="epa-confirmation-summary__section billing-detail">
      <div><?php echo esc_html($placeholder['billing_detail.name']) ?></div>
      <div><?php echo esc_html("
      {$placeholder['billing_detail.address.line1']}\n
      {$placeholder['billing_detail.address.line2']}\n
      {$placeholder['billing_detail.address.city']}, {$placeholder['billing_detail.address.state']} {$placeholder['billing_detail.address.postal_code']}\n
      {$placeholder['billing_detail.address.country']}
      ") ?></div>
      <div><i class="fas fa-envelope"></i><?php echo esc_html($placeholder['billing_detail.email']) ?></div>
      <div><i class="fas fa-phone"></i><?php echo esc_html($placeholder['billing_detail.phone']) ?></div>
    </div>
  <?php
  }
}