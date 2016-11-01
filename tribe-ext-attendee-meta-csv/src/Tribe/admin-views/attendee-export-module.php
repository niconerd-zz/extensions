<div class="card" id="tribe-export-attendee-list-form">
	<h2 class="title"><?php _e( 'Event Tickets: Export Attendee Meta CSV' ); ?></h2>
	<p><?php _e( 'Use this tool to export all Attendee Meta information in the CSV format. The export will include meta information for any WooCommerce ticket that was attached to an event in The Events Calendar.' ); ?></p>
	<p><a href="<?php print wp_nonce_url( admin_url(), 'tribe-export-attendee-list', 'tribe-export-attendee-list');?>" target="_blank"><?php _e( 'Export Attendee Meta CSV' ); ?></a></p>
</div>

