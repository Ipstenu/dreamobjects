<?php

/*
Plugin Name: DreamObjects Connection
Plugin URI: https://github.com/Ipstenu/dreamobjects
Description: Connect your WordPress install to your DreamHost DreamObjects buckets.
Version: 3.4.3
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
	if ( version_compare( PHP_VERSION, '5.3.3', '<' ) ) {
		dreamobjects_core_incompatibile( __( 'The official Amazon Web Services SDK, which DreamObjects relies on, requires PHP 5.3 or higher. The plugin has now disabled itself.', 'dreamobjects' ) );
	}
	elseif ( !function_exists( 'curl_version' ) 
		|| !( $curl = curl_version() ) || empty( $curl['version'] ) || empty( $curl['features'] )
		|| version_compare( $curl['version'], '7.16.2', '<' ) )
	{
		dreamobjects_core_incompatibile( __( 'The official Amazon Web Services SDK, which DreamObjects relies on, requires cURL 7.16.2+. The plugin has now disabled itself.', 'dreamobjects' ) );
	}
	elseif ( !( $curl['features'] & CURL_VERSION_SSL ) ) {
		dreamobjects_core_incompatibile( __( 'The official Amazon Web Services SDK, which DreamObjects relies on, requires that cURL is compiled with OpenSSL. The plugin has now disabled itself.', 'dreamobjects' ) );
	}
	elseif ( !( $curl['features'] & CURL_VERSION_LIBZ ) ) {
		dreamobjects_core_incompatibile( __( 'The official Amazon Web Services SDK, which DreamObjects relies on, requires that cURL is compiled with zlib. The plugin has now disabled itself.', 'dreamobjects' ) );
	}
}
 
require_once dirname(__FILE__) . '/lib/defines.php';
require_once dirname(__FILE__) . '/lib/dhdo.php';
require_once dirname(__FILE__) . '/lib/messages.php';
require_once dirname(__FILE__) . '/lib/settings.php';

// WP-CLI
if ( defined('WP_CLI') && WP_CLI ) {
	include( dirname(__FILE__) . '/lib/wp-cli.php' );
}