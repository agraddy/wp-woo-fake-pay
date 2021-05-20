<?php
/*
Plugin Name: Fake Pay For WooCommerce
Description: Creates a fake payment gateway for admin users.
Author: Anthony Graddy, Melchior Kokernoot
Author URI: https://www.dashboardq.com, https://melchiorkokernoot.nl
Plugin URI: https://github.com/agraddy/wp-woo-fake-pay
Version: 1.0.1
*/

$content = <<<EOT
        <div class="notice notice-error is-dismissible">
			<p>It appears WooCommerce is not active.</p>
        </div>
EOT;

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
  add_action( 'plugins_loaded', 'fake_pay_init_gateway_class' );
  add_filter( 'woocommerce_payment_gateways', 'fake_pay_add_gateway_class' );
} else {
  unset( $_GET['activate'] );
  add_action( 'admin_notices', function () use ( $content ) {
	echo $content;
  } );
}

function fake_pay_init_gateway_class() {
  class WC_Gateway_Fake_Pay extends WC_Payment_Gateway {
	public function __construct() {
	  $this->id                 = 'fake_pay';
	  $this->method_title       = 'Fake Pay';
	  $this->method_description = 'Creates a fake payment gateway for admin users.';

	  $this->init_form_fields();
	  $this->init_settings();

	  $this->title       = $this->get_option( 'title' );
	  $this->description = $this->get_option( 'description' );

	  if ( ! current_user_can( 'administrator' ) ) {
		$this->enabled = false;
	  }

	  add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	public function init_form_fields() {
	  $this->form_fields = [
		  'enabled'     => [
			  'title'   => __( 'Enable/Disable', 'woocommerce' ),
			  'type'    => 'checkbox',
			  'label'   => __( 'Enable Fake Pay', 'woocommerce' ),
			  'default' => 'no',
		  ],
		  'title'       => [
			  'title'       => __( 'Title', 'woocommerce' ),
			  'type'        => 'text',
			  'description' => __( 'Payment method title that the customer will see on your website.', 'woocommerce' ),
			  'default'     => __( 'Fake Pay', 'woocommerce' ),
			  'desc_tip'    => true,
		  ],
		  'description' => [
			  'title'       => __( 'Description', 'woocommerce' ),
			  'type'        => 'textarea',
			  'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce' ),
			  'desc_tip'    => true,
			  'default'     => __( 'This option is only available to admin users.', 'woocommerce' ),
		  ],
	  ];
	}

	public function process_payment( $order_id ): array {
	  global $woocommerce;
	  $order = new WC_Order( $order_id );

	  if ( ! current_user_can( 'administrator' ) ) {
		$order->update_status( 'failed', '' );
		$error_message = 'This payment option is not available.';
		wc_add_notice( __( 'Payment error:', 'woothemes' ) . $error_message, 'error' );

		return [];
	  }

	  $order->payment_complete();

	  // Remove cart
	  $woocommerce->cart->empty_cart();

	  // Return thankyou redirect
	  return [
		  'result'   => 'success',
		  'redirect' => $this->get_return_url( $order ),
	  ];
	}
  }
}

function fake_pay_add_gateway_class( $methods ) {
  $methods[] = 'WC_Gateway_Fake_Pay';

  return $methods;
}

