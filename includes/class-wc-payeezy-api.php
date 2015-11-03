<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Payeezy_API
 */
 class WC_Payeezy_API {
	private $_base_url;
	private $_url;
	private $_merchant_token;

	const API_KEY = 'tF92QqXikGklcppFBAfzZnDFghkjBXrN';
	const API_SECRET = 'ab66d97bab5a4781c50fff107a1eb10e0bb40258385fb7a4da2fa5b320d128ec';

	/**
	 * authorize function
	 * 
	 * @param WC_Payeezy_Gateway $gateway
	 * @param WC_Order           $order
	 * @param float              $amount
	 * 
	 * @return mixed
	 */
	public function authorize( $gateway, $order, $amount, $card ) {
		$payload = $this->get_payload( $gateway, $order, $amount, 'authorize', $card );
		$header_array = $this->hmac_authorization_token( $payload );
		$response = json_decode( $this->post_transaction( $payload, $header_array ) );
		return $response;
	}

	/**
	 * purchase function
	 * 
	 * @param WC_Payeezy_Gateway $gateway
	 * @param WC_Order           $order
	 * @param float              $amount
	 * 
	 * @return mixed
	 */
	public function purchase( $gateway, $order, $amount, $card ) {
		$payload = $this->get_payload( $gateway, $order, $amount, 'purchase', $card );
		$header_array = $this->hmac_authorization_token( $payload );
		$response = json_decode( $this->post_transaction( $payload, $header_array ) );
		return $response;
	}

	/**
	 * capture function
	 * 
	 * @param WC_Payeezy_Gateway $gateway
	 * @param WC_Order           $order
	 * @param float              $amount
	 * 
	 * @return mixed
	 */
	public function capture( $gateway, $order, $amount ) {
		$payload = $this->get_payload( $gateway, $order, $amount, 'capture' );
		$header_array = $this->hmac_authorization_token( $payload );
		$response = json_decode( $this->post_transaction( $payload, $header_array ) );
		return $response;
	}

	/**
	 * refund function
	 * 
	 * @param WC_Payeezy_Gateway $gateway
	 * @param WC_Order           $order
	 * @param float              $amount
	 * 
	 * @return mixed
	 */
	public function refund( $gateway, $order, $amount ) {
		$payload = $this->get_payload( $gateway, $order, $amount, 'refund' );
		$header_array = $this->hmac_authorization_token( $payload );
		$response = json_decode( $this->post_transaction( $payload, $header_array ) );
		return $response;
	}

	/**
	 * void function
	 * 
	 * @param WC_Payeezy_Gateway $gateway
	 * @param WC_Order           $order
	 * @param float              $amount
	 * 
	 * @return mixed
	 */
	public function void( $gateway, $order, $amount ) {
		$payload = $this->get_payload( $gateway, $order, $amount, 'void' );
		$header_array = $this->hmac_authorization_token( $payload );
		$response = json_decode( $this->post_transaction( $payload, $header_array ) );
		return $response;
	}

	/**
	 * verify function
	 * 
	 * @param WC_Payeezy_Gateway $gateway
	 * 
	 * @return mixed
	 */
	public function verify( $gateway ) {
		$payload = $this->get_token_payload( $gateway );
		$header_array = $this->hmac_authorization_token( $payload );
		$response = json_decode( $this->post_transaction( $payload, $header_array ) );
		return $response;
	}

	/**
	 * get_payload function
	 * 
	 * @param WC_Payeezy_Gateway $gateway
	 * @param WC_Order           $order
	 * @param float              $amount
	 * @param string             $transaction_type
	 * 
	 * @return string
	 */
	public function get_payload( $gateway, $order, $amount, $transaction_type, $card = '' ) {
		$order_number = $order->id;
		$amount_in_cents = $amount * 100;
		$cardholder_name = $order->billing_first_name . ' ' . $order->billing_last_name;

		if ( 'yes' == $gateway->sandbox ) {
			$this->_base_url = 'https://api-cert.payeezy.com/v1/transactions';
			$this->_merchant_token = 'fdoa-a480ce8951daa73262734cf102641994c1e55e7cdf4c02b6';
		} else {
			$this->_base_url = 'https://api.payeezy.com/v1/transactions';
			$this->_merchant_token = $gateway->merchant_token;
		}

		if ( 'authorize' == $transaction_type || 'purchase' == $transaction_type ) {
			if ( ! empty( $card ) ) {
				$data = array(
					'merchant_ref' => wc_clean( $order_number ),
					'transaction_type' => wc_clean( $transaction_type ),
					'method' => 'token',
					'amount' => wc_clean( $amount_in_cents ),
					'currency_code' => wc_clean( strtoupper( get_woocommerce_currency() ) ),
					'token' => array(
						'token_type' => 'FDToken',
						'token_data' => array(
							'type' => wc_clean( $card['cardtype'] ),
							'value' => wc_clean( $card['token'] ),
							'cardholder_name' => wc_clean( $cardholder_name ),
							'exp_date' => wc_clean( $card['expiry'] ),
						),
					),
					'billing_address' => array(
						'street' => wc_clean( substr( $order->billing_address_1, 0, 30 ) ),
						'zip_postal_code' => wc_clean( substr( $order->billing_postcode, 0, 10 ) ),
					),
					'level2' => array(
						'tax1_amount' => number_format( $order->order_tax, '2', '.', '' ),
						'customer_ref' => wc_clean( $order_number ),
					),
				);
			} else {
				$card_number = str_replace( ' ', '', $_POST['payeezy-card-number'] );
				$exp_date_array = explode( "/", $_POST['payeezy-card-expiry'] );
				$exp_month = trim( $exp_date_array[0] );
				$exp_year = trim( $exp_date_array[1] );
				$exp_date = $exp_month . substr( $exp_year, -2 );
				$data = array(
					'merchant_ref' => wc_clean( $order_number ),
					'transaction_type' => wc_clean( $transaction_type ),
					'method' => 'credit_card',
					'amount' => wc_clean( $amount_in_cents ),
					'currency_code' => wc_clean( strtoupper( get_woocommerce_currency() ) ),
					'credit_card' => array(
						'type' => wc_clean( $this->get_card_type( $card_number ) ),
						'cardholder_name' => wc_clean( $cardholder_name ),
						'card_number' => wc_clean( $card_number ),
						'exp_date' => wc_clean( $exp_date ),
						'cvv' => wc_clean( $_POST['payeezy-card-cvc'] ),
					),
					'billing_address' => array(
						'street' => wc_clean( substr( $order->billing_address_1, 0, 30 ) ),
						'zip_postal_code' => wc_clean( substr( $order->billing_postcode, 0, 10 ) ),
					),
					'level2' => array(
						'tax1_amount' => number_format( $order->order_tax, '2', '.', '' ),
						'customer_ref' => wc_clean( $order_number ),
					),
				);
			}
			$this->_url = $this->_base_url;
		} else {
			$tran_meta = get_post_meta( $order_number, '_payeezy_transaction', true );
			$this->_url = $this->_base_url . '/' . $tran_meta['transaction_id'];
			$data = array(
				'merchant_ref' => wc_clean( $order_number ),
				'transaction_type' => wc_clean( $transaction_type ),
				'method' => 'credit_card',
				'amount' => wc_clean( $amount_in_cents ),
				'currency_code' => wc_clean( strtoupper( get_woocommerce_currency() ) ),
				'transaction_tag' => wc_clean( $tran_meta['transaction_tag'] ),
			);
		}
		return json_encode( $data, JSON_FORCE_OBJECT );
	}

	public function get_token_payload( $gateway ) {
		if ( 'yes' == $gateway->sandbox ) {
			$this->_url = 'https://api-cert.payeezy.com/v1/transactions';
			$this->_merchant_token = 'fdoa-a480ce8951daa73262734cf102641994c1e55e7cdf4c02b6';
		} else {
			$this->_url = 'https://api.payeezy.com/v1/transactions';
			$this->_merchant_token = $gateway->merchant_token;
		}
		$customer_id = get_current_user_id();
		$amount_in_cents = 0;
		$card_number = str_replace( ' ', '', $_POST['payeezy-card-number'] );
		$exp_date_array = explode( "/", $_POST['payeezy-card-expiry'] );
		$exp_month = trim( $exp_date_array[0] );
		$exp_year = trim( $exp_date_array[1] );
		$exp_date = $exp_month . substr( $exp_year, -2 );
		$data = array(
			'merchant_ref' => wc_clean( $customer_id ),
			'transaction_type' => 'authorize',
			'method' => 'credit_card',
			'amount' => wc_clean( $amount_in_cents ),
			'currency_code' => wc_clean( strtoupper( get_woocommerce_currency() ) ),
			'credit_card' => array(
				'type' => wc_clean( $this->get_card_type( $card_number ) ),
				'cardholder_name' => wc_clean( get_user_meta( $customer_id, 'billing_first_name', true) . ' ' . get_user_meta( $customer_id, 'billing_last_name', true ) ),
				'card_number' => wc_clean( $card_number ),
				'exp_date' => wc_clean( $exp_date ),
				'cvv' => wc_clean( $_POST['payeezy-card-cvc'] ),
			),
		);
		return json_encode( $data, JSON_FORCE_OBJECT );
	}

	/**
	 * hmac_authorization_token function
	 * 
	 * @param string $payload
	 * 
	 * @return array
	 */
	public function hmac_authorization_token( $payload ) {
		$nonce = strval( hexdec( bin2hex( openssl_random_pseudo_bytes( 4, $cstrong ) ) ) );
		$timestamp = strval( time() * 1000 ); //time stamp in milli seconds
		$data = self::API_KEY . $nonce . $timestamp . $this->_merchant_token . $payload;
		$hash_algorithm = "sha256";
		$hmac = hash_hmac( $hash_algorithm, $data, self::API_SECRET, false ); // HMAC Hash in hex
		$authorization = base64_encode( $hmac );
		return array(
			'authorization' => $authorization,
			'nonce' => $nonce,
			'timestamp' => $timestamp,
		);
	}

	/**
	 * post_transaction function
	 * 
	 * @param string $payload
	 * @param array  $headers
	 * 
	 * @return string|WP_Error
	 */
	public function post_transaction( $payload, $headers ) {
		$request = curl_init();
		curl_setopt( $request, CURLOPT_URL, $this->_url );
		curl_setopt( $request, CURLOPT_POST, true );
		curl_setopt( $request, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $request, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $request, CURLOPT_HEADER, false );
		curl_setopt(
			$request, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'apikey:' . strval( self::API_KEY ),
				'token:' . strval( $this->_merchant_token ),
				'Authorization:' . $headers['authorization'],
				'nonce:' . $headers['nonce'],
				'timestamp:' . $headers['timestamp'],
			)
		);
		$response = curl_exec( $request );
		if ( false === $response ) {
			return new WP_Error( 'curl_error', __( 'cURL Error: ', 'woocommerce-payeezy' ) . curl_error( $request ) );
		}
		curl_close( $request );
		return $response;
	}

	/**
	 * get_card_type function
	 * 
	 * @param string $number
	 * 
	 * @return string
	 */
	private function get_card_type( $number ) {
		if ( preg_match( '/^4[0-9]{12}(?:[0-9]{3})?$/', $number ) ) {
			return 'Visa';
		} elseif ( preg_match( '/^3[47][0-9]{13}$/', $number ) ) {
			return 'American Express';
		} elseif ( preg_match( '/^5[1-5][0-9]{14}$/', $number ) ) {
			return 'MasterCard';
		} elseif ( preg_match( '/^6(?:011|5[0-9]{2})[0-9]{12}$/', $number ) ) {
			return 'Discover';
		} elseif  (preg_match( '/^(?:2131|1800|35\d{3})\d{11}$/', $number ) ) {
			return 'JCB';
		} elseif ( preg_match( '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', $number ) ) {
			return 'Diners Club';
		}
	}
}
