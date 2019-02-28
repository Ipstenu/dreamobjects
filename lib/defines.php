<?php
/*
 * This file is part of DreamObjects, a plugin for WordPress.
 *
 * DreamObjects is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * DreamObjects is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WordPress.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Standard content folder defines.
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

// Setting Options.
add_option( 'dh-do-hostname', 'us-east-1' );
add_option( 'dh-do-key', '' );
add_option( 'dh-do-secretkey', '' );
add_option( 'dh-do-bucket', 'XXXX' );
add_option( 'dh-do-schedule', 'disabled' );
add_option( 'dh-do-backupsection', '' );
add_option( 'dh-do-retain', '5' );
add_option( 'dh-do-logging', 'off' );
add_option( 'dh-do-notify', 'success' );

// Requirements
$dreamobjects_requirements_check = array(
	'php'       => '7.0',
	'wp'        => '4.8',
	'curl'      => '7.16.2',
	'multisite' => false,
	'curlssl'   => true,
	'plugins'   => array(
		'Amazon Web Services' => 'amazon-web-services/amazon-web-services.php',
		'BackupBuddy'         => 'backupbuddy/backupbuddy.php',
	),
);

add_option( 'dh-do-requirements', $dreamobjects_requirements_check );
