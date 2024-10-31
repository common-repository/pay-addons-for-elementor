<?php

namespace Elementor_Pay_Addons\Core;

defined( 'ABSPATH' ) || exit;
class Build_Assets {
    /**
     * Collection of default widgets.
     *
     * @since 1.0.0
     * @access private
     */
    private $widgets;

    public function __construct() {
        $this->widgets = Config::get_active_elements();
        add_action( 'elementor/elements/categories_registered', [$this, 'register_categories'] );
        add_action( 'elementor/widgets/register', [$this, 'register_widgets'] );
        add_action( 'elementor/controls/register', [$this, 'register_controls'] );
    }

    public function add_widget( $widget_name ) {
        $widget_dir = EPA_ADDONS_PATH . '/includes/elements/';
        include $widget_dir . $widget_name . '.php';
    }

    public function register_widgets( $widgets_manager ) {
        // dependencies
        $this->add_widget( 'checkout-base' );
        // check if the widget is exists
        foreach ( $this->widgets as $widget ) {
            $this->add_widget( $widget );
        }
        foreach ( $this->widgets as $widget_slug ) {
            $class_name = '\\Elementor_Pay_Addons\\EPA_Widget_' . \Elementor_Pay_Addons\Shared\Utils::make_classname( $widget_slug );
            if ( class_exists( $class_name ) ) {
                $widgets_manager->register( new $class_name() );
            }
        }
    }

    public function register_controls( $controls_manager ) {
    }

    public function register_categories( $elements_manager ) {
        $elements_manager->add_category( 'pay-addons', [
            'title' => esc_html__( 'Pay Addons', 'textdomain' ),
            'icon'  => 'fa fa-plug',
        ] );
    }

}
