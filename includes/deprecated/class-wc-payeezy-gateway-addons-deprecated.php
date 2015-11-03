<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Payeezy_Gateway_Addons_Deprecated class.
 *
 * @extends WC_Payeezy_Gateway_Addons
 */
class WC_Payeezy_Gateway_Addons_Deprecated extends WC_Payeezy_Gateway_Addons {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'scheduled_subscription_payment_' . $this->id, array( $this, 'process_scheduled_subscription_payment' ), 10, 3 );
			add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'remove_renewal_order_meta' ), 10, 4 );
			add_action( 'woocommerce_subscriptions_changed_failing_payment_method_' . $this->id, array( $this, 'change_failing_payment_method' ), 10, 3 );
		}
	}

	/**
	 * Store the card details on the order
	 *
	 * @param int $order_id
	 * @param array $card
	 * @return array
	 */
	protected function save_subscription_meta( $order_id, $card ) {
		update_post_meta( $order_id, '_payeezy_token', $card['token'] );
		update_post_meta( $order_id, '_payeezy_expiry', $card['expiry'] );
		update_post_meta( $order_id, '_payeezy_cardtype', $card['cardtype'] );
	}

	/**
	 * process_scheduled_subscription_payment function.
	 *
	 * @param float $amount_to_charge The amount to charge.
	 * @param WC_Order $order The WC_Order object of the order which the subscription was purchased in.
	 * @param int $product_id The ID of the subscription product for which this payment relates.
	 */
	public function process_scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) {
		$result = $this->process_subscription_payment( $order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		} else {
			WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
		}
	}

	/**
	 * Don't transfer customer meta when creating a parent renewal order.
	 *
	 * @param string $order_meta_query MySQL query for pulling the metadata
	 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 * @return string
	 */
	public function remove_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
		if ( 'parent' == $new_order_role ) {
			$order_meta_query .= " AND `meta_key` NOT IN ( '_payeezy_token', '_payeezy_expiry', '_payeezy_cardtype' ) ";
		}
		return $order_meta_query;
	}

	/**
	 * Check if order contains subscriptions.
	 *
	 * @param  int $order_id
	 * @return bool
	 */
	protected function order_contains_subscription( $order_id ) {
		return class_exists( 'WC_Subscriptions_Order' ) && ( WC_Subscriptions_Order::order_contains_subscription( $order_id ) || WC_Subscriptions_Renewal_Order::is_renewal( $order_id ) );
	}

	/**
	 * Update the card details for a subscription after using Payeezy to complete a payment to make up for
	 * an automatic renewal payment which previously failed.
	 *
	 * @param WC_Order $original_order The original order in which the subscription was purchased.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 * @param string $subscription_key A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
	 */
	public function change_failing_payment_method( $original_order, $renewal_order, $subscription_key ) {
		$new_token = get_post_meta( $renewal_order->id, '_payeezy_token', true );
		$new_expiry = get_post_meta( $renewal_order->id, '_payeezy_expiry', true );
		$new_cardtype = get_post_meta( $renewal_order->id, '_payeezy_cardtype', true );

		update_post_meta( $original_order->id, '_payeezy_token', $new_token );
		update_post_meta( $original_order->id, '_payeezy_expiry', $new_expiry );
		update_post_meta( $original_order->id, '_payeezy_cardtype', $new_cardtype );
	}
}
