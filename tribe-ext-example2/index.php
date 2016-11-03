<?php
/**
 * Plugin Name:     [Base Plugin Name] Extension: [Extension name]
 * Description:     [Extension Description]
 * Version:         1.0.0
 * Plugin URI:      https://theeventscalendar.com/extension/[example]
 * Extension Class: [Tribe__Extension__Example2]
 * Extension File:  index.php
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

// Do not load directly.
defined( 'WPINC' ) || die;
// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) { return; }

class Tribe__Extension__Example2 extends Tribe__Extension {

	public function init() {
		add_filter( 'tribe_fb_event_img', '__return_false' );
	}

}
