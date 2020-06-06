<?php
/**
 * Plugin Name:       		WooCommerce Simple Installment Gateway
 * Plugin URI:        		https://1daywebsite.ch
 * Description:       		A simple way to add installment payment option, with three monthly payments and a CHF 30 fee as per brack.ch: https://www.brack.ch/ratenzahlung
 * Version:           		1.0.0
 * Author:            		AFB
 * Author URI:        		https://1daywebsite.ch
  Tested up to:			5.4.1 
 * WC requires at least:	3.0
 * WC tested up to:		4.2
 * Text Domain:       		woocommerce-simple-installment-gateway
 * Domain Path: 		/languages
 * License:           		GPL-2.0+
 * License URI:       		http://www.gnu.org/licenses/gpl-2.0.txt
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

if(simple_installment_gateway_is_woocommerce_active()){
	/**
	* Add new payment gateway class: WC_Simple_Installment_Gateway
	*/
	add_filter('woocommerce_payment_gateways', 'add_simple_installment_gateway');
	function add_simple_installment_gateway( $gateways ){
		$gateways[] = 'WC_Simple_Installment_Gateway';
		return $gateways; 
	}
	/**
	* Load class file and load plugin text domain
	*/
	add_action('plugins_loaded', 'init_simple_installment_gateway');
	function init_simple_installment_gateway(){
		require 'class-woocommerce-simple-installment-gateway.php';
	}
	add_action( 'plugins_loaded', 'simple_installment_gateway_load_plugin_textdomain' );
	function simple_installment_gateway_load_plugin_textdomain() {
	  load_plugin_textdomain( 'woocommerce-simple-installment-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
	/**
	* Add Installment fee to cart
	*/	
	function simple_installment_gateway_fee() {
		$chosen_payment_method = WC()->session->get('chosen_payment_method');
		if( $chosen_payment_method == 'simple_installment') {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}
		$simple_install_method_feebox = new WC_Simple_Installment_Gateway();
		$amount_fee_box = $simple_install_method_feebox->get_option('fee_box');	
		WC()->cart->add_fee( __('Gebühr für Ratenzahlung (Kreditprüfung)','woocommerce-simple-installment-gateway'), $amount_fee_box, false, 'standard' );
		}
	}	
	add_action( 'woocommerce_cart_calculate_fees', 'simple_installment_gateway_fee', 40, 1 );
	/**
	* Function to check if a customer has one completed order
	*/	
	function simple_installment_gateway_has_bought() {
		// Get all customer orders
		$customer_orders = get_posts( array(
			'numberposts' => -1,
			'meta_key'    => '_customer_user',
			'meta_value'  => get_current_user_id(),
			'post_type'   => 'shop_order', // WC orders post type
			'post_status' => 'wc-completed' // Only orders with status "completed"
		) );

		// return "true" when customer has already at least one order (false if not)
		return count($customer_orders) > 0 ? true : false; 
	} 
	/**
	* Filters available payment gateways based on: minimum order amoount, guest checkout, customer with one completed order
	*/	
	add_action( 'woocommerce_available_payment_gateways', 'simple_installment_gateway_filter_gateways' );
	function simple_installment_gateway_filter_gateways( $args ) {
		$simple_install_method = new WC_Simple_Installment_Gateway();
		$minimum_order_amount = $simple_install_method->get_option('minimum_order_amount');
		$guest_checkout = $simple_install_method->get_option('guest_checkout');
		$already_customer = $simple_install_method->get_option('already_customer');	
		//1. Mindestbestellbetrag nicht erreicht
		$order_amount = WC()->cart->total;
		if( $order_amount <= $minimum_order_amount && isset($args['simple_installment']) ) {	
			unset( $args['simple_installment'] );
		}
		//2. Guest Users sehen Ratenzahlung sowieso nicht
		if( $guest_checkout !== 'yes' && isset($args['simple_installment']) ) {	
			unset( $args['simple_installment'] );
		}
		//3. Bei eingeloggten Nutzern wird überprüft, ob sie schon eine fertig gestellte Bestellung haben
		if ( is_user_logged_in() && simple_installment_gateway_has_bought() == false && isset($args['simple_installment'] ) ) {	
			unset( $args['simple_installment'] );
		}	
		return $args;
	}
	/**
	* Refresh payment gateways on checkout page (so the fee shows up)
	*/
	add_action( 'woocommerce_review_order_before_payment', 'simple_installment_refresh_checkout_on_payment_methods_change' );
	function simple_installment_refresh_checkout_on_payment_methods_change() {
		?>
		<script type="text/javascript">
			(function($){
				$( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() {
					$('body').trigger('update_checkout');
				});
			})(jQuery);
		</script>
		<?php
	}
	/**
	* Output for order emails
	*/	
	add_action( 'woocommerce_email_order_meta', 'simple_installment_email_order_meta', 10, 3 );
	function simple_installment_email_order_meta( $order, $plain_text = false ) {
		if ( 'simple_installment' === $order->payment_method ) {
			echo '<h3>'. __('Ratenzahlungen','woocommerce-simple-installment-gateway') .'<h3/><p><b>' . __('Anzahl Monate','woocommerce-simple-installment-gateway') . '</b>: ' . get_post_meta( $order->get_id(), 'installment_months', true ) . '</p><p><b>' . __('Monatsrate','woocommerce-simple-installment-gateway') . '</b>: ' . get_post_meta( $order->get_id(), 'installment_rate', true ) . '</p><p>' . __('Es gelten die Konditionen gemäss unseren AGB.','woocommerce-simple-installment-gateway') . '</p>';
		}	
	}
}
/**
* Check if WooCommerce is active, otherwise don't run plugin
* @return bool
*/
function simple_installment_gateway_is_woocommerce_active() {
	$active_plugins = (array) get_option('active_plugins', array());
	if (is_multisite()) {
		$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	}
	return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}
