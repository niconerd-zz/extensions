<?php
/**
 * Plugin Name: The Events Calendar PRO: Cleanup recurring events
 * Description: Adds a recurring event cleanup tool to WP Admin > Tools > Available tools
 * Version: 0.1.1
 * Author: Modern Tribe, Inc.
 * Author URI: http://m.tri.be/1x
 * License: GPLv2 or later
 */
defined( 'WPINC' ) or die;

class Tribe__Extension__Recurring_Cleanup {

	/**
	 * The semantic version number of this extension; should always match the plugin header.
	 */
	const VERSION = '0.1.1';

	/**
	 * Each plugin required by this extension
	 *
	 * @var array Plugins are listed in 'main class' => 'minimum version #' format
	 */
	public $plugins_required = array(
		'Tribe__Events__Main' => null,
		'Tribe__Events__Pro__Main' => null,
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

		add_action( 'tool_box', array( $this, 'output_cleanup_box' ) );
	}

	/**
	 * Returns query containing events with the most recurrences.
	 *
	 * Shows how many recurrences each recurring event has.
	 * Includes events from all statuses including in the trash.
	 *
	 * @return array|null|object
	 */
	protected function get_recurring_event_list() {
		global $wpdb;

		$count_recurrences_sql = "
		SELECT 
			`post_title` as title,
			`post_parent` as event_id, 
			COUNT(*) As recurrences 
		FROM 
			{$wpdb->posts}
		WHERE 
			`post_parent` <> 0 
			AND `post_type` = 'tribe_events' 
		GROUP BY 
			`post_parent` 
		ORDER BY 
			`recurrences` DESC";

		return $wpdb->get_results( $count_recurrences_sql, ARRAY_A );
	}

	/**
	 * Gets a list of events with many recurrences.
	 *
	 * @return array Each one an HTML <a> link to an event series.
	 */
	protected function get_recurring_event_table_cells() {
		$recurrence_count_results = $this->get_recurring_event_list();
		$recurrence_table_cells = array();

		foreach ( $recurrence_count_results as $row ) {
			$row_output = array();

			$event_edit_url = get_edit_post_link( $row['event_id'] );

			foreach ( $row as $cell ) {

				$row_output[] = sprintf(
					'<a href="%s">%s</a>',
					$event_edit_url,
					$cell
				);

			}

			$recurrence_table_cells[] = $row_output;
		}

		return $recurrence_table_cells;
	}

	/**
	 * Deletes all recurrences for a series of events.
	 *
	 * @param string $event_id The parent event ID for the series.
	 *
	 * @return array|null|object The query result
	 */
	protected function delete_recurrence( $event_id ) {
		global $wpdb;

		$delete_query = $wpdb->prepare(
			"
			delete a,b,c,d
			FROM {$wpdb->posts} a
			LEFT JOIN {$wpdb->term_relationships} b ON ( a.ID = b.object_id )
			LEFT JOIN {$wpdb->postmeta} c ON ( a.ID = c.post_id )
			LEFT JOIN {$wpdb->term_taxonomy} d ON ( d.term_taxonomy_id = b.term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} e ON ( e.term_id = d.term_id )
			WHERE a.post_parent = %s",
			$event_id
		);

		return $wpdb->get_results( $delete_query );
	}

	/**
	 * Echoes the admin UI.
	 */
	public function output_cleanup_box() {

		// Process delete event form.
		if (
			isset( $_POST['tribe-recurring-cleanup-eventid'] )&&
			isset( $_POST['tribe-recurring-cleanup-backup-confirmation'] ) &&
			isset( $_POST['tribe-recurring-cleanup-submit'] ) &&
			check_ajax_referer( 'tribe-recurring-cleanup' )
		) {
			$event_id = intval( $_POST['tribe-recurring-cleanup-eventid'] );
			$this->delete_recurrence( intval( $_POST['tribe-recurring-cleanup-eventid'] ) );
			$notifications = __( 'Recurrences deleted for event ID ' ) . $event_id;
		}

		// This class is included in Common as of 4.3, we have it here for backwards compatibility.
		require_once dirname( __FILE__ ) . '/src/Tribe/Simple_Table.php';

		$recurrence_table_headings = array( 'Event', 'ID', '# of recurrences' );
		$recurrence_table_cells = $this->get_recurring_event_table_cells();
		$simple_table = new Tribe__Simple_Table( $recurrence_table_cells, $recurrence_table_headings );
		$simple_table->table_attributes = array( 'cellspacing' => '0', 'cellpadding' => '5' );
		$simple_table->html_escape_td_values = false;

		$recurrences_table = $simple_table->output_table();

		require_once dirname( __FILE__ ) . '/src/Tribe/admin-views/recurring-cleanup-module.php';
	}
}

new Tribe__Extension__Recurring_Cleanup();
