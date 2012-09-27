<?php
/*
    This file is part of DreamObjects, a plugin for WordPress.

    DreamObjects is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    DreamObjects is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WordPress.  If not, see <http://www.gnu.org/licenses/>.

*/

if (!defined('ABSPATH')) {
    die();
}


	function newBucketWarning() {
		echo "<div id='message' class='error'><p><strong>".__('You need to select a valid bucket.', dreamobjects)."</strong> ".__('If you tried to create a new bucket, it may have been an invalid name.', dreamobjects)."</p></div>";
	}

	function updateMessage() {
		echo "<div id='message' class='updated fade'><p><strong>".__('Options Updated!', dreamobjects)."</strong></p></div>";
		}
	function backupMessage() {
	   $timestamp = wp_next_scheduled( 'dh-do-backupnow' );
	   $string = sprintf( __('You have an ad-hoc backup scheduled for today at %s (time based on WP time/date settings). Do not hit refresh!', dreamobjects), get_date_from_gmt( date('Y-m-d H:i:s', $timestamp) , 'h:i a' ) );
	   echo "<div id='message' class='updated fade'><p><strong>".$string."</strong></p></div>";
		}
