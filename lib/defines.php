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

// Set up defaults
define( 'DHDO', true);
defined( 'DHDO_PLUGIN_DIR') || define('DHDO_PLUGIN_DIR', realpath(dirname(__FILE__) . '/..'));

// Standard content folder defines.
if ( ! defined( 'WP_CONTENT_DIR' ) )  define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

// Setting Options
if ( !get_option('dh-do-hostname')) {update_option( 'dh-do-hostname', 'objects-us-west-1.dream.io' );}
if ( !get_option('dh-do-key')) {update_option( 'dh-do-key', '' );}
if ( !get_option('dh-do-secretkey')) {update_option( 'dh-do-secretkey', '' );}
if ( !get_option('dh-do-bucket')) {update_option( 'dh-do-bucket', 'XXXX' );}
if ( !get_option('dh-do-schedule')) {update_option( 'dh-do-schedule', 'disabled' );}
if ( !get_option('dh-do-backupsection')) {update_option( 'dh-do-backupsection', '' );}
if ( !get_option('dh-do-retain')) {update_option( 'dh-do-retain', '5' );}
if ( !get_option('dh-do-logging')) {update_option( 'dh-do-logging', 'off' );}
if ( !get_option('dh-do-notify')) {update_option( 'dh-do-notify', 'success' );}

// Requirements
$dreamobjects_requirements_check = array(
	'php'       => '5.3.3',
	'wp'        => '4.0',
	'curl'      => '7.16.2',
	'multisite' => false,
	'curlssl'   => true,
	'plugins'   => array(
		'Amazon Web Services' => 'amazon-web-services/amazon-web-services.php',
		'BackupBuddy'         => 'backupbuddy/backupbuddy.php',
		),
);
update_option( 'dh-do-requirements', $dreamobjects_requirements_check );