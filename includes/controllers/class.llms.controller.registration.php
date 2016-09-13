<?php
/**
 * User Registration Forms (excludes checkout registration)
 *
 * @since   3.0.0
 * @version 3.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Controller_Registration {

	public function __construct() {

		add_action( 'init', array( $this, 'register' ) );
		add_action( 'lifterlms_user_registered', array( $this, 'voucher' ), 10, 3 );

	}

	/**
	 * Attempt to redeem a voucher on user registration
	 * if a voucher was submitted during registration
	 *
	 * @param    int        $person_id  WP_User ID of the newly registered user
	 * @param    array      $data       $_POST
	 * @param    string     $screen     screen user registered from [checkout|registration]
	 * @return   void
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function voucher( $person_id, $data, $screen ) {

		if ( 'registration' === $screen && ! empty( $data['llms_voucher'] ) ) {

			$v = new LLMS_Voucher();
			$redeemed = $v->use_voucher( $data['llms_voucher'], $person_id );

			if ( is_wp_error( $redeemed ) ) {

				llms_add_notice( $redeemed->get_error_message(), 'error' );

			}

		}

	}

	/**
	 * Handle submission of user registrration forms
	 * @return   void
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function register() {

		if ( 'POST' !== strtoupper( getenv( 'REQUEST_METHOD' ) ) || empty( $_POST['action'] ) || 'llms_register_person' !== $_POST['action'] || empty( $_POST['_wpnonce'] ) ) { return; }

		wp_verify_nonce( $_POST['_wpnonce'], 'llms_register_person' );

		do_action( 'lifterlms_before_new_user_registration' );
		// already logged in can't register!
		// this shouldn't happen but let's check anyway
		if ( get_current_user_id() ) {
			return llms_add_notice( __( 'Already logged in! Please log out and try again.', 'lifterlms' ), 'error' );
		} // attempt to register new user (performs validations)
		else {
			$person_id = llms_register_user( $_POST, 'registration', true );
		}

		// validation or registration issues
		if ( is_wp_error( $person_id ) ) {
			foreach ( $person_id->get_error_messages() as $msg ) {
				llms_add_notice( $msg, 'error' );
			}
			return;
		} // register should be a user_id at this point, if we're not numeric we have a problem...
		elseif ( ! is_numeric( $person_id ) ) {

			return llms_add_notice( __( 'An unknown error occurred when attempting to create an account, please try again.', 'lirterlms' ), 'error' );

		} else {

			// handle redirect
			wp_safe_redirect( apply_filters( 'lifterlms_registration_redirect', llms_get_page_url( 'myaccount' ) ) );
			exit;

		}

		$new_person = LLMS_Person::create_new_person();

		if (is_wp_error( $new_person )) {

			llms_add_notice( $new_person->get_error_message(), 'error' );
			return;

		}

		llms_set_person_auth_cookie( $new_person );

		// Redirect
		if (wp_get_referer()) {

			$redirect = esc_url( wp_get_referer() );
		} else {

			$redirect = esc_url( get_permalink( llms_get_page_id( 'myaccount' ) ) );

		}

		// Check if voucher exists and if valid and use it
		if (isset( $_POST['llms_voucher_code'] ) && ! empty( $_POST['llms_voucher_code'] )) {
			$code = llms_clean( $_POST['llms_voucher_code'] );

			$voucher = new LLMS_Voucher();
			$voucher->use_voucher( $code, $new_person, false );

			if ( ! empty( $_POST['product_id'] )) {
				$product_id = $_POST['product_id'];
				$valid = $voucher->is_product_to_voucher_link_valid( $code, $product_id );

				if ($valid) {
					wp_redirect( apply_filters( 'lifterlms_registration_redirect', $redirect, $new_person ) );
					exit;
				}
			}
		}

		if ( ! empty( $_POST['product_id'] )) {

			$product_id = $_POST['product_id'];

			$product = new LLMS_Product( $product_id );
			$single_price = $product->get_single_price();
			$rec_price = $product->get_recurring_price();

			if ($single_price > 0 || $rec_price > 0) {

				$checkout_url = get_permalink( llms_get_page_id( 'checkout' ) );
				$checkout_redirect = add_query_arg( 'product-id', $product_id, $checkout_url );

				wp_redirect( apply_filters( 'lifterlms_checkout_redirect', $checkout_redirect ) );
				exit;
			} else {
				$checkout_url = get_permalink( $product_id );

				wp_redirect( apply_filters( 'lifterlms_checkout_redirect', $checkout_url ) );
				exit;
			}
		} else {

		}

	}

}

return new LLMS_Controller_Registration();
