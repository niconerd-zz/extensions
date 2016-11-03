<?php
// Do not load directly.
defined( 'WPINC' ) || die;

class Tribe__Extension__Example extends Tribe__Extension {

	public function init() {
		add_filter( 'tribe_fb_event_img', '__return_false' );
	}

}
