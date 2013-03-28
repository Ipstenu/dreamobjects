<?php

/*
Plugin Name: DreamObjects Connection
Plugin URI: https://github.com/Ipstenu/dreamobjects
Description: Connect your WordPress install to your DreamHost DreamObjects buckets.
Version: 3.2.1b
Author: Mika Epstein
Author URI: http://ipstenu.org/

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
 
// First we check to make sure you meet the requirements
global $wp_version;
$exit_msg_version = 'This plugin is not supported on pre-3.5 WordPress installs.';
if (version_compare($wp_version,"3.5","<")) { exit($exit_msg_version); }
$exit_msg_multisite = 'This plugin is not supported on Multisite.';
if( is_multisite() ) { exit($exit_msg_multisite); }
$exit_msg_php = 'This plugin is not supported on PHP 5.2 and older. Please upgrade to at least 5.3.';
if(version_compare(PHP_VERSION, '5.3.0') >= 0) { exit($exit_msg_php); }

require_once dirname(__FILE__) . '/lib/defines.php';
require_once dirname(__FILE__) . '/lib/dhdo.php';
require_once dirname(__FILE__) . '/lib/messages.php';
require_once dirname(__FILE__) . '/lib/settings.php';

// WP-CLI
if ( defined('WP_CLI') && WP_CLI ) {
	include( dirname(__FILE__) . '/lib/wp-cli.php' );
}

// Stylesheets
function dreamobjects_stylesheet() {
    wp_register_style( 'dreamobj-style', plugins_url('dreamobjects.css', __FILE__) );
    wp_enqueue_style( 'dreamobj-style' );
}
add_action('admin_print_styles', 'dreamobjects_stylesheet');