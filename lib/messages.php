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

class DHDOMESS {

    // Messages
    public static function updateMessage() {
        echo "<div id='message' class='updated fade'><p><div class='dashicons dashicons-yes'></div> <strong>".__('Options Updated!', dreamobjects)."</strong></p></div>";
    }
    
    public static function backupMessage() {
        $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-backupnow' ) ), get_option('time_format') );
        $string = sprintf( __('You have an ad-hoc backup scheduled for today at %s. Do not hit refresh on the backups page. You may continue using your site per usual, the backup will run behind the scenes.', dreamobjects), $timestamp );
        echo "<div id='message' class='updated fade'><p><div class='dashicons dashicons-clock'></div> <strong>".$string."</strong></p></div>";
    }
}