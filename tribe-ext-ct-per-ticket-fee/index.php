<?php
/**
 * Plugin Name: The Events Calendar Extension: Community Tickets Fee Options
 * Description: Additional options to Community Tickets fees.
 * Version: 0.2.0
 * Author: Modern Tribe, Inc.
 * Author URI: http://m.tri.be/1971
 * License: GPLv2 or later
 */

defined( 'WPINC' ) || die;

/**
 * Class Tribe__Extension__Tickets_Email_Options
 */
class Tribe__Extension__Community_Per_Ticket_Fee {

	/**
	 * The semantic version number of this extension; should always match the plugin header.
	 */
	const VERSION = '0.2.0';

	/**
	 * Each plugin required by this extension
	 *
	 * @var array Plugins are listed in 'main class' => 'minimum version #' format
	 */
	public $plugins_required = array(
		'Tribe__Tickets__Main'                     => '4.2',
		'Tribe__Events__Main'                      => '4.2',
		'Tribe__Tickets_Plus__Main'                => '4.2',
		'Tribe__Events__Community__Main'           => '4.2',
		'Tribe__Events__Community__Tickets__Main'  => '4.2',
	);

	/**
	 * The constructor; delays initializing the extension until all other plugins are loaded.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 100 );
	}

	/**
	 * Extension hooks and initialization; exits if the extension is not authorized by Tribe Common to run.
	 */
	public function init() {

		// Exit early if our framework is saying this extension should not run.
		if ( ! function_exists( 'tribe_register_plugin' ) || ! tribe_register_plugin( __FILE__, __CLASS__, self::VERSION, $this->plugins_required ) ) {
			return;
		}

		require_once dirname( __FILE__ ) . '/src/Tribe/Tribe__Events__Community__Tickets__Cart_Custom.php';

		// Swap the actions for cart calculations out.
		remove_action( 'woocommerce_cart_calculate_fees', array( Tribe__Events__Community__Tickets__Main::instance(), 'calculate_cart_fees' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_cart_fees' ) );

		// @TODO Filter the split payments actions.
	}

	/**
	 * Recalculates cart fees.
	 *
	 * @see (action) woocommerce_cart_calculate_fees
	 */
	public function calculate_cart_fees( $wc_cart ) {
		$cart = new Tribe__Events__Community__Tickets__Cart_Custom();
		$cart->calculate_cart_fees( $wc_cart );
	}

}

new Tribe__Extension__Community_Per_Ticket_Fee();



