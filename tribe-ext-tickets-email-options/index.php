<?php
/**
 * Plugin Name: Event Tickets Extension: Additional Email Options
 * Description: Extra options for Event Tickets emails such as notifying an organizer of ticket purchase.
 * Version: 0.3.0
 * Author: Modern Tribe, Inc.
 * Author URI: http://m.tri.be/1971
 * License: GPLv2 or later
 */

defined( 'WPINC' ) || die;

/**
 * Class Tribe__Extension__Tickets_Email_Options
 */
class Tribe__Extension__Tickets_Email_Options {

	/**
	 * The semantic version number of this extension; should always match the plugin header.
	 */
	const VERSION = '0.3.0';

	/**
	 * Each plugin required by this extension
	 *
	 * @var array Plugins are listed in 'main class' => 'minimum version #' format
	 */
	public $plugins_required = array(
		'Tribe__Tickets__Main'      => '4.3',
		'Tribe__Tickets_Plus__Main' => '4.3',
		'Tribe__Events__Main'       => '4.3',
	);

	/**
	 * RSVP class
	 *
	 * @var Object Stores RSVP class
	 */
	public $rsvp_class;

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

		require_once dirname( __FILE__ ) . '/src/functions/tickets.php';
		require_once dirname( __FILE__ ) . '/src/Tribe/Attendee_Table.php';
		require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';

		$this->rsvp_class = Tribe__Tickets__RSVP::get_instance();

		add_action( 'admin_init', array( $this, 'add_settings' ) );

		if ( tribe_get_option( 'ticket-extension-enable-attendee-meta', false ) ) {
			add_action( 'tribe_tickets_ticket_email_ticket_bottom', array( $this, 'echo_ticket_email_meta' ) );
		}

		if ( tribe_get_option( 'ticket-extension-enable-attendee-meta', false ) ) {
			add_action( 'event_tickets_rsvp_tickets_generated',  array( $this, 'send_email' ) );
		}

		if ( tribe_get_option( 'ticket-extension-enable-woo-emails', false ) ) {
			add_action( 'woocommerce_order_item_meta_start', array( $this, 'woocommerce_echo_event_info' ), 100, 4 );
		}
	}

	/**
	 * Adds settings options
	 */
	public function add_settings() {
		$setting_helper = new Tribe__Settings_Helper();

		$setting_helper->add_field(
			'ticket-extension-enable-attendee-meta',
			array(
				'type'            => 'checkbox_bool',
				'label'           => __( 'Tickets Email: Add Attendee Meta Info', 'tribe-extension' ),
				'tooltip'         => __( 'This adds the attendee meta info to the tickets email, will cause duplicate meta info in versions of Event Tickets where this feature is built-in.', 'tribe-extension' ),
				'validation_type' => 'boolean',
			),
			'event-tickets',
			'ticket-enabled-post-types',
			false
		);

		$setting_helper->add_field(
			'ticket-extension-enable-organizer-cc',
			array(
				'type'            => 'checkbox_bool',
				'label'           => __( 'Tickets Email: CC Organizer', 'tribe-extension' ),
				'tooltip'         => __( 'Carbon Copy the Organizer email when an order for tickets/RSVPs is approved, sharing the full tickets details.', 'tribe-extension' ),
				'validation_type' => 'boolean',
			),
			'event-tickets',
			'ticket-enabled-post-types',
			false
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$setting_helper->add_field(
				'ticket-extension-enable-woo-emails',
				array(
					'type'            => 'checkbox_bool',
					'label'           => __( 'WooCommerce Emails: Add Event Information', 'tribe-extension' ),
					'tooltip'         => __( 'Attach event information to WooCommerce emails, such as the the Attendee meta information.', 'tribe-extension' ),
					'validation_type' => 'boolean',
				),
				'event-tickets',
				'ticket-enabled-post-types',
				false
			);
		}
	}

	/**
	 * Echoes the attendee meta when attached to relevant Woo Action
	 *
	 * @see action woocommerce_order_item_meta_end
	 */
	public function woocommerce_echo_event_info( $item_id, $item, $order, $plain_text = '' ) {

		$wootix = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
		// Generate tickets early so we can get attendee meta.
		// Note, if the default order status is one that does affect stock, no tickets will be generated.
		$wootix->generate_tickets(
			$order->id,
			$order->get_status(),
			$order->get_status()
		);

		$event = $wootix->get_event_for_ticket( $item['product_id'] );

		// Show event details if this ticket is for a tribe event.
		if ( ! empty( $event ) ) {
			$event_time = tribe_events_event_schedule_details( $event, '<em>', '</em>' );
			$event_address = tribe_get_full_address( $event );
			$event_details = array();

			if ( ! empty( $event_time ) ) {
				$event_details[] = $event_time;
			}
			if ( ! empty( $event_address ) ) {
				$event_details[] = $event_address;
			}

			printf(
				'<div class="tribe-event-details">%1$s</div>',
				implode( $event_details, '<br />' )
			);
		}

		$this->echo_attendee_meta( $order->id, $item['product_id'] );

		// @TODO This gets included once for each order line item, find a better place to output this.
		echo '<style type="text/css">';
		include( 'src/resources/tribe-attendee-meta-table.css' );
		echo '</style>';
	}

	/**
	 * Echoes attendee meta for every attendee in selected order
	 *
	 * @param string $order_id  Order or RSVP post ID.
	 * @param string $ticket_id The specific ticket to output attendees for.
	 */
	public function echo_attendee_meta( $order_id, $ticket_id = null ) {
		$attendees = tribe_get_attendees_by_order( $order_id );

		foreach ( $attendees as $attendee ) {
			// Skip attendees that are not for this ticket type.
			if ( ! empty( $ticket_id ) && $ticket_id != $attendee['product_id'] ) {
				continue;
			}

			$table_columns = array();

			$table_columns[] = array(
				sprintf(
					'<strong class="tribe-attendee-meta-heading">%1$s</strong>',
					esc_html_x( 'Ticket ID', 'tribe-extension', 'Attendee meta table.' )
				),
				sprintf(
					'<strong class="tribe-attendee-meta-heading">%1$s</strong>',
					esc_html( $attendee['ticket_id'] )
				),
			);

			$fields = $this->get_attendee_meta( $attendee['product_id'], $attendee['qr_ticket_id'] );
			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					$table_columns[] = array(
						esc_html( $field['label'] ),
						esc_html( $field['value'] ),
					);
				}
			}

			$table_columns[] = array(
				esc_html_x( 'Security Code', 'tribe-extension', 'Attendee meta table.' ),
				esc_html( $attendee['security_code'] ),
			);

			$table = new Tribe__Simple_Table( $table_columns );
			$table->html_escape_td_values = false;
			$table->table_attributes = array(
				'class' => 'tribe-attendee-meta',
			);

			echo $table->output_table();
		}
	}

	/**
	 * Get attendee meta
	 *
	 * @param string $ticket_id    Ticket ID.
	 * @param string $qr_ticket_id QR Ticket ID.
	 */
	public function get_attendee_meta( $ticket_id, $qr_ticket_id ) {
		$output = array();

		$meta_fields = Tribe__Tickets_Plus__Main::instance()->meta()->get_meta_fields_by_ticket( $ticket_id );
		$meta_data = get_post_meta( $qr_ticket_id, Tribe__Tickets_Plus__Meta::META_KEY, true );

		foreach ( $meta_fields as $field ) {
			if ( 'checkbox' === $field->type && isset( $field->extra['options'] ) ) {
				$values = array();
				foreach ( $field->extra['options'] as $option ) {
					$key = $field->slug . '_' . sanitize_title( $option );

					if ( isset( $meta_data[ $key ] ) ) {
						$values[] = $meta_data[ $key ];
					}
				}

				$value = implode( ', ', $values );
			} elseif ( isset( $meta_data[ $field->slug ] ) ) {
				$value = $meta_data[ $field->slug ];
			} else {
				continue;
			}

			if ( ! empty( $value ) ) {
				$output[ $field->slug ] = array(
					'slug' => $field->slug,
					'label' => $field->label,
					'value' => $value,
				);
			}
		}

		return $output;
	}

	/**
	 * Sends email based on $order_id
	 *
	 * @param string $order_id Order ID.
	 */
	public function send_email( $order_id ) {

		$attendees = tribe_get_attendees_by_order( $order_id );

		if ( empty( $attendees ) ) {
			return;
		}

		// Get the organizer's email if one exists.
		$to = tribe_get_organizer_email( $attendees['0']['event_id'] );
		$event_name = get_the_title( $attendees['0']['event_id'] );
		$site_name = get_bloginfo( 'name' );
		$attendee_count = count( $attendees );

		if ( ! is_email( $to ) ) {
			return;
		}

		$attendee_table_generator = new Tribe__Events__Attendee_Table();

		$content     = $attendee_table_generator->generate_attendee_table( $attendees );
		$headers     = apply_filters( 'tribe_rsvp_email_headers', array( 'Content-type: text/html' ) );
		$attachments = array();
		$subject     = sprintf( __( 'Your event %1$s has %2$d new attendee(s) - %3$s', 'tribe-extension' ), $event_name, $attendee_count, $site_name );

		wp_mail( $to, $subject, $content, $headers, $attachments );

	}

	/**
	 * Inject custom meta in to tickets email
	 *
	 * Fixed copy of Tribe__Tickets_Plus__Meta__Render::ticket_email_meta() from 4.2.1
	 *
	 * @param array $ticket Attendee data.
	 */
	public function echo_ticket_email_meta( $ticket ) {
		$meta_fields = Tribe__Tickets_Plus__Main::instance()->meta()->get_meta_fields_by_ticket( $ticket['product_id'] );
		$meta_data = get_post_meta( $ticket['qr_ticket_id'], Tribe__Tickets_Plus__Meta::META_KEY, true );

		if ( empty( $meta_fields ) || empty( $meta_data ) ) {
			return;
		}

		?>
		<table class="inner-wrapper" border="0" cellpadding="0" cellspacing="0" width="620" bgcolor="#f7f7f7" style="margin:0 auto !important; width:620px; padding:0;">
			<tr>
				<td valign="top" class="ticket-content" align="left" width="580" border="0" cellpadding="20" cellspacing="0" style="padding:20px; background:#f7f7f7;" colspan="2">
					<h6 style="color:#909090 !important; margin:0 0 4px 0; font-family: 'Helvetica Neue', Helvetica, sans-serif; text-transform:uppercase; font-size:13px; font-weight:700 !important;"><?php esc_html_e( 'Attendee Information', 'event-tickets-plus' ); ?></h6>
				</td>
			</tr>
			<?php
			foreach ( $meta_fields as $field ) {

				if ( 'checkbox' === $field->type && isset( $field->extra['options'] ) ) {
					$values = array();
					foreach ( $field->extra['options'] as $option ) {
						$key = $field->slug . '_' . sanitize_title( $option );

						if ( isset( $meta_data[ $key ] ) ) {
							$values[] = $meta_data[ $key ];
						}
					}

					$value = implode( ', ', $values );
				} elseif ( isset( $meta_data[ $field->slug ] ) ) {
					$value = $meta_data[ $field->slug ];
				} else {
					continue;
				}

				?>
				<tr>
					<th valign="top" class="event-tickets-meta-label_<?php echo esc_attr( $field->slug ); ?>" align="left" border="0" cellpadding="20" cellspacing="0" style="padding:0 20px; background:#f7f7f7;min-width:100px;">
						<?php echo esc_html( $field->label ); ?>
					</th>
					<td valign="top" class="event-tickets-meta-data_<?php echo esc_attr( $field->slug ); ?>" align="left" border="0" cellpadding="20" cellspacing="0" style="padding:0 20px; background:#f7f7f7;">
						<?php echo esc_html( $value ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php
	}
}

new Tribe__Extension__Tickets_Email_Options();



