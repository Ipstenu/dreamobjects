<?php

/*
Plugin Name: DreamObjects Backups
Plugin URI: https://github.com/Ipstenu/dreamobjects
Description: Connect your WordPress install to your DreamHost DreamObjects buckets.
Version: 3.5.2
Author: Mika Epstein
Author URI: http://ipstenu.org/
Network: false
Text Domain: dreamobjects
Domain Path: /i18n

Copyright 2012 Mika Epstein (email: ipstenu@ipstenu.org)

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

/**
 * @package dh-do-backups
 */
 
function dreamobjects_core_incompatibile( $msg ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
	deactivate_plugins( __FILE__ );
    wp_die( $msg );
}

if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {

	require_once ABSPATH . '/wp-admin/includes/plugin.php';
		
	if ( version_compare( PHP_VERSION, '5.3.3', '<' ) ) {
		dreamobjects_core_incompatibile( __( 'The official Amazon Web Services SDK, which DreamObjects Backups relies on, requires PHP 5.3 or higher. The plugin has now disabled itself.', 'dreamobjects' ) );
	}
	elseif ( !function_exists( 'curl_version' ) 
		|| !( $curl = curl_version() ) || empty( $curl['version'] ) || empty( $curl['features'] )
		|| version_compare( $curl['version'], '7.16.2', '<' ) )
	{
		dreamobjects_core_incompatibile( __( 'The official Amazon Web Services SDK, which DreamObjects Backups relies on, requires cURL 7.16.2+. The plugin has now disabled itself.', 'dreamobjects' ) );
	}
	elseif ( !( $curl['features'] & CURL_VERSION_SSL ) ) {
		dreamobjects_core_incompatibile( __( 'The official Amazon Web Services SDK, which DreamObjects Backups relies on, requires that cURL is compiled with OpenSSL. The plugin has now disabled itself.', 'dreamobjects' ) );
	}
	elseif ( !( $curl['features'] & CURL_VERSION_LIBZ ) ) {
		dreamobjects_core_incompatibile( __( 'The official Amazon Web Services SDK, which DreamObjects Backups relies on, requires that cURL is compiled with zlib. The plugin has now disabled itself.', 'dreamobjects' ) );
	} elseif ( is_multisite() ) {
		dreamobjects_core_incompatibile( __( 'Sorry, but DreamObjects Backups is not currently compatible with WordPress Multisite, and should not be used. The plugin has now disabled itself.', 'dreamobjects' ) );
	} elseif (is_plugin_active( 'amazon-web-services/amazon-web-services.php' )) {
	dreamobjects_core_incompatibile( __( 'Running both DreamObjects Backups AND BackupBuddy at once will cause a rift in the space/time continuum, because we use different versions of the AWS SDK. Please deactivate BackupBuddy if you wish to use DreamObjects.', 'dreamobjects' ) );
	} elseif (is_plugin_active( 'backupbuddy/backupbuddy.php' )) {
	dreamobjects_core_incompatibile( __( 'Running both DreamObjects Backups AND Amazon Web Services at once will cause a rift in the space/time continuum, because we use different versions of the AWS SDK. Please deactivate Amazon Web Services if you wish to use DreamObjects.', 'dreamobjects' ) );
	}
}
 
require_once 'lib/defines.php';
require_once 'lib/dhdo.php';
require_once 'lib/messages.php';
require_once 'lib/settings.php';

if (false === class_exists('Symfony\Component\ClassLoader\UniversalClassLoader', false)) {
	require_once 'aws/aws-autoloader.php';
}

// WP-CLI
if ( defined('WP_CLI') && WP_CLI ) {
	include( 'lib/wp-cli.php' );
}