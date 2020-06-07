<?php 

class WC_Simple_Installment_Gateway extends WC_Payment_Gateway {

    private $order_status;

	public function __construct(){
		$this->id = 'simple_installment';
		$this->method_title = __('Bequeme Ratenzahlung in Monatsraten','woocommerce-simple-installment-gateway');
		$this->method_description = __( 'Have your customers pay in installments.', 'woocommerce-simple-installment-gateway' );
		$this->title = $this->get_option('title');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->fee_box = $this->get_option('fee_box');
		$this->months_installments = $this->get_option('months_installments');
		$cart_total = WC()->cart->total;
		$cart_total_rate =  $cart_total / $this->months_installments;
		$cart_total_rate = round( $cart_total_rate, 2, PHP_ROUND_HALF_UP );
		$this->rate = 'CHF ' . number_format( $cart_total_rate, 2, ".", "'");

		$this->description_text = $this->get_option('description_text');
		
		$this->description = '<p>' . $this->description_text . '</p><table class="shop_table"><tbody><tr><td>' . __('Anzahl Raten','woocommerce-simple-installment-gateway') . '</td><td>' . $this->months_installments . '</td></tr><tr><td>' . __('Betrag Rate','woocommerce-simple-installment-gateway') . '</td><td>' . $this->rate . '</td></tbody></table>';
		
		$this->order_status = $this->get_option('order_status');
		$this->hide_text_box = $this->get_option('hide_text_box');

		add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'simple_installment_update_order_meta' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'simple_installment_display_admin_order_meta' ), 10, 1 );
	}

	public function init_form_fields(){
		$this->form_fields = array(
			'enabled' => array(
			'title' 		=> __( 'Enable/Disable', 'woocommerce-simple-installment-gateway' ),
			'type' 			=> 'checkbox',
			'label' 		=> __( 'Enable Simple Installment Payment Gateway', 'woocommerce-simple-installment-gateway' ),
			'default' 		=> 'yes'
			),
			'title' => array(
				'title' 		=> __( 'Method Title', 'woocommerce-simple-installment-gateway' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the title', 'woocommerce-simple-installment-gateway' ),
				'default'		=> __( 'Bequeme Ratenzahlung in Monatsraten', 'woocommerce-simple-installment-gateway' ),
				'desc_tip'		=> true,
			),
			'description_text' => array(
				'title' => __( 'Customer Message', 'woocommerce-simple-installment-gateway' ),
				'type' => 'textarea',
				'css' => 'width:500px;',
				'default' => __( 'Sie können den Bestellbetrag bequem in den folgenden Raten begleichen. Mehr dazu in unserer E-Mail-Bestätigung:', 'woocommerce-simple-installment-gateway' ),
				'description' 	=> __( 'The general message which you want it to appear to the customer in the checkout page. The installment info will be generated automatically and added to this message in table form.', 'woocommerce-simple-installment-gateway' ),
			),			
			'minimum_order_amount' => array(
				'title' 		=> __( 'Minimum cart total necessary', 'woocommerce-simple-installment-gateway' ),
				'type' 			=> 'text',
				'description' 	=> __( 'What is the minimum order amount (based on cart total) necessary to activate this method?', 'woocommerce-simple-installment-gateway' ),
				'default'		=> 0,
			),	
			'guest_checkout' => array(
				'title' 		=> __( 'Show payment method to guests (not logged-in users, non-customers)?', 'woocommerce-simple-installment-gateway' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Activate for guests?', 'woocommerce-simple-installment-gateway' ),
				'default' 		=> 'no',
				'description' 	=> __( 'By default only logged-in existing customers with a completed order can see this payment method.', 'woocommerce-simple-installment-gateway' ),
			),
			'months_installments' => array(
				'title' 		=> __( 'Number of monthly installments', 'woocommerce-simple-installment-gateway' ),
				'type' 			=> 'text',
				'description' 	=> __( 'How long should the installment plan run? 3 months? 12 months or longer? Type in the number of months', 'woocommerce-simple-installment-gateway' ),
				'default'		=> 3,
			),	
			'fee_box' => array(
				'title' 		=> __( 'Extra Fee for Installments', 'woocommerce-simple-installment-gateway' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Add the amount of the extra fee for processing the installment plan', 'woocommerce-simple-installment-gateway' ),
				'default'		=> 30,
			),
			'order_status' => array(
				'title' => __( 'Order Status After The Checkout', 'woocommerce-simple-installment-gateway' ),
				'type' => 'select',
				'options' => wc_get_order_statuses(),
				'default' => 'wc-on-hold',
				'description' 	=> __( 'The default order status if this gateway used in payment.', 'woocommerce-simple-installment-gateway' ),
			),
			'hide_text_box' => array(
				'title' 		=> __( 'Hide Admin Notice', 'woocommerce-simple-installment-gateway' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Hide', 'woocommerce-simple-installment-gateway' ),
				'default' 		=> 'no',
				'description' 	=> __( 'If you do not want to show the admin notice box to customers (so they can send you a message), enable this option.', 'woocommerce-simple-installment-gateway' ),
			),					
		);
	}
	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_options() {
		?>
		<h3><?php _e( 'Simple Installment Gateway', 'woocommerce-simple-installment-gateway' ); ?></h3>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-1">
					<div id="post-body-content">
						<table class="form-table">
							<?php $this->generate_settings_html();?>
						</table><!--/.form-table-->
					</div>
				</div>
			</div>	
				<?php
	}

	public function validate_fields() {
	    if($this->text_box_required === 'no'){
	        return true;
        }

	    if($this->hide_text_box === 'no'){
			$textbox_value = (isset($_POST['other_payment-admin-note']))? trim($_POST['other_payment-admin-note']): '';
			if($textbox_value === ''){
				wc_add_notice( __('Please, complete the payment information.','woocommerce-simple-installment-gateway'), 'error');
				return false;
			}
			return true;
		}	
	}

	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status($this->order_status, __( 'Awaiting payment', 'woocommerce-simple-installment-gateway' ));
		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );
		if(isset($_POST[ $this->id.'-admin-note']) && trim($_POST[ $this->id.'-admin-note'])!=''){
			$order->add_order_note(esc_html($_POST[ $this->id.'-admin-note']),1);
		}
		// Remove cart
		$woocommerce->cart->empty_cart();
		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);	
	}

	public function payment_fields(){
	    ?>
		<fieldset>
			<p class="form-row form-row-wide">
                <label for="<?php echo $this->id; ?>-admin-note"><?php echo ($this->description); ?> </label>
                <?php if($this->hide_text_box !== 'yes'){ ?>
				    <textarea id="<?php echo $this->id; ?>-admin-note" class="input-text" type="text" name="<?php echo $this->id; ?>-admin-note"></textarea>
                <?php } ?>
			</p>						
			<div class="clear"></div>
		</fieldset>
		<?php
	}
	/**
	* Create custom fields - post meta with installment details
	*/
    public function simple_installment_update_order_meta( $order_id ) {
		//Custom field for about installment details: thank you page
		update_post_meta( $order_id, 'installment_description', $this->description );
		//Custom field for about installment details: email & order admin page
		update_post_meta( $order_id, 'installment_months', $this->months_installments );
		update_post_meta( $order_id, 'installment_rate', $this->rate );
	}	
	/**
	* Output for the order received page.
	*/
	public function thankyou_page( $order_id ) {
		if ( $this->description ) {
			$order = wc_get_order( $order_id );
			echo '<p><strong>'.__('Installment Rates', 'woocommerce-simple-installment-gateway').':</strong> <br/>' . get_post_meta( $order->id, 'installment_description', true ) . '</p>';
		}
	}
	/**
	* Output for order admin page.
	*/	
	public function simple_installment_display_admin_order_meta( $order ){
		echo '<h3>'. __('Ratenzahlungen','woocommerce-simple-installment-gateway') .'<h3/><p><b>' . __('Anzahl Monate','woocommerce-simple-installment-gateway') . '</b>: ' . get_post_meta( $order->get_id(), 'installment_months', true ) . '</p><p><b>' . __('Monatsrate','woocommerce-simple-installment-gateway') . '</b>: ' . get_post_meta( $order->get_id(), 'installment_rate', true ) . '</p><p>';
	}
}
