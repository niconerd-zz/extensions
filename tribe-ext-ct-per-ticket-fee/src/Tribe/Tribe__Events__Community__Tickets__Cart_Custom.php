<?php

defined( 'WPINC' ) or die;

if ( class_exists( 'Tribe__Events__Community__Tickets__Cart' ) ) {

	class Tribe__Events__Community__Tickets__Cart_Custom extends Tribe__Events__Community__Tickets__Cart {

		/**
		 * loops over items in an order and breaks them down into receivers, amounts, and opportunities for fees
		 *
		 * @param array $items Items to loop over (cart items, order items, etc)
		 *
		 * @return array Array of receivers and fees
		 */
		public function parse_order( $items ) {
			$receivers = array();
			$fees = array();

			$main = Tribe__Events__Community__Tickets__Main::instance();
			$options = get_option( Tribe__Events__Community__Tickets__Main::OPTIONNAME );

			if ( $main->is_split_payments_enabled() ) {
				$site_receiver_email = $options['paypal_receiver_email'];
			} else {
				$woocommerce_options = get_option( 'woocommerce_paypal_settings' );
				$site_receiver_email = isset( $woocommerce_options['receiver_email'] ) ? $woocommerce_options['receiver_email'] : '';
			}

			if ( count( $items ) > 0 ) {
				foreach ( $items as $item ) {
					if ( empty( $item['quantity'] ) && empty( $item['qty'] ) ) {
						continue;
					}

					$event_id = get_post_meta( $item['product_id'], '_tribe_wooticket_for_event', true );

					// if the event doesn't exist, skip
					if ( ! $event_id || ! ( $event = get_post( $event_id ) ) ) {
						continue;
					}

					$event_creator = get_user_by( 'id', $event->post_author );
					$receiver_email = $site_receiver_email;

					if ( $main->is_split_payments_enabled() ) {
						$creator_options = $main->payment_options_form()->get_meta( $event_creator->ID );
						$receiver_email = $creator_options['paypal_account_email'];
					}

					$payment_fee_setting = $main->get_payment_fee_setting( $event );

					$product_id = $item['product_id'];
					$line_item = $item['line_total'];
					$receiver_total = $this->gateway()->ticket_price( $line_item, 'pass' !== $payment_fee_setting );

					// set up the receiver
					if ( isset( $receivers[ $receiver_email ] ) ) {
						$receivers[ $receiver_email ]['amount'] = number_format( $receivers[ $receiver_email ]['amount'] + $receiver_total, 2, '.', '' );
					} else {
						$receiver = array(
							'user_id' => $event_creator->ID,
							'payment_fee_setting' => $payment_fee_setting,
							'email' => $receiver_email,
							'amount' => 0,
							'primary' => 'false',
						);

						$receiver['amount'] = number_format( $receiver['amount'] + $receiver_total, 2, '.', '' );
						$receivers[ $receiver_email ] = $receiver;
					}//end else

					// track flat fee deduction requirements
					if ( ! isset( $fees[ $receiver_email ] ) ) {
						$fees[ $receiver_email ] = array();
					}


					// The following is new code that gives each ticket its own flat fee, rather than each events

					$quantity = (int) empty( $item['quantity'] ) ? $item['qty'] : $item['quantity'];
					$item_price = $item['data']->price;

					// Account for free events.
					if ( empty( $item_price ) ) {
						continue;
					}

					for ( $i = 0; $i < $quantity; $i++ ) {
						$fees[ $receiver_email ][] = array(
							'event_id' => $event_id,
							'price' =>  $this->gateway()->ticket_price( $item_price, 'pass' !== $payment_fee_setting ),
						);
					}
				}
			}

			return array(
				'receivers' => $receivers,
				'fees' => $fees,
			);
		}

	}

}