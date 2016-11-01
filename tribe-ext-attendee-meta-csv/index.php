<?php
/**
 * Plugin Name: Event Tickets Extension: Export Event Attendee Meta CSV
 * Description: Adds attendee meta export tool to WP Admin > Tools > Available tools.
 * Version: 0.1.0
 * Author: Modern Tribe, Inc.
 * Author URI: http://m.tri.be/1x
 * License: GPLv2 or later
 */
defined( 'WPINC' ) or die;

/*
 * @TODO This is very much a quick release, mostly targeting data that gets lost when
 * Woo Attendee fields are deleted or renamed. If we use this more in the future, RSVP
 * support and other ticket types should be added, and general code cleanup is in order.
 */
class Tribe__Extension__Attendee_CSV_Export {

	/**
	 * The semantic version number of this extension; should always match the plugin header.
	 */
	const VERSION = '0.1.0';

	/**
	 * Each plugin required by this extension
	 *
	 * @var array Plugins are listed in 'main class' => 'minimum version #' format
	 */
	public $plugins_required = array(
		'Tribe__Tickets__Main'      => null,
		'Tribe__Tickets_Plus__Main' => null,
		'Tribe__Events__Main'       => '3.10',
	);

	/**
	 * The constructor; delays initializing the extension until all other plugins are loaded.
	 */
	public function __construct() {
		// Wait until after all of the plugins have loaded before trying to use other tribe functions/classes.
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

		// Check for the export request, if it's set output the downloadable CSV.
		if (
			isset( $_GET['tribe-export-attendee-list'] ) &&
		     wp_verify_nonce( $_GET['tribe-export-attendee-list'], 'tribe-export-attendee-list' )
		) {
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename=attendee-meta.csv");
			header("Pragma: no-cache");
			header("Expires: 0");

			$this->export_csv( $this->meta_query_woo() );

			exit;
		}

		add_action( 'tool_box', array( $this, 'attendee_export_box' ) );
	}

	/**
	 * Includes the admin view for this tool
	 */
	public function attendee_export_box() {
		require_once dirname( __FILE__ ) . '/src/Tribe/admin-views/attendee-export-module.php';
	}

	/**
	 * Exports the meta info for WooCommerce.
	 *
	 * Get the info from the postmeta for each individual woo order.
	 *
	 * @return array containing database data
	 */
	protected function meta_query_woo() {
		global $wpdb;

		$attendee_export_sql = "
		SELECT
		  ID as event_id,
		  post_title as event_title,
		  b.post_id as ticket_id,
		  d.order_id as order_id,
		  e.meta_value as attendee_array
		FROM
		  {$wpdb->posts} a
		LEFT JOIN
		  {$wpdb->postmeta} b
		ON
		  (b.meta_key = '_tribe_wooticket_for_event' AND b.meta_value = a.ID)
		LEFT JOIN
		  {$wpdb->prefix}woocommerce_order_itemmeta c
		ON
		  (c.meta_key = '_product_id' AND c.meta_value = b.post_id)
		LEFT JOIN
		  {$wpdb->prefix}woocommerce_order_items d
		ON
		  (d.order_item_id = c.order_item_id)
		LEFT JOIN
		  {$wpdb->postmeta} e
		ON
		  (e.post_id = d.order_id AND e.meta_key = '_tribe_tickets_meta')
		WHERE
		  a.post_type = 'tribe_events'";

		return $wpdb->get_results( $attendee_export_sql, ARRAY_A );
	}

	/**
	 * Exports downloadable CSV to the browser, should be called before headers are sent.
	 *
	 * @param array $attendee_posts
	 */
	protected function export_csv( $attendee_posts ) {
		foreach ( $attendee_posts as $postkey => $post ) {

			// Add headings.
			foreach ( $post as $colname => $colval ) {
				if ( 'attendee_array' !== $colname ) {
					$headings[ $colname ] = $colname;
				}
			}

			// Only include ones with meta.
			if ( ! empty( $post['attendee_array'] ) ) {
				$attendees = unserialize( $post['attendee_array'] );

				foreach ( $attendees as $attendee ) {
					$attendee_row = $post;

					foreach ( $attendee[0] as $am_key => $am_val ) {
						// Add headings.
						$headings[ $am_key ] = $am_key;
						$attendee_row[ $am_key ] = $am_val;
					}

					$output[] = $attendee_row;
				}
			}
		}

		// output CSV to browser.
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, $headings );
		foreach ( $output as $order ) {
			$ordered_csv = array();

			foreach ( $headings as $heading ) {
				if ( isset( $order[ $heading ] ) ) {
					$ordered_csv[ $heading ] = $order[ $heading ];
				} else {
					$ordered_csv[ $heading ] = '';
				}
			}

			fputcsv( $out, $ordered_csv );
		}
		fclose( $out );
	}
}

new Tribe__Extension__Attendee_CSV_Export();
