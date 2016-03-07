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

function dreamobjects_pass_php( ) {
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$version = $dreamobjects_requirements_check['php'];
	if ( version_compare( phpversion(), $version, '<=' ) ) {
		return false;
	} else {
		return true;
	}
}
function dreamobjects_pass_php_message( ) {	
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$version = $dreamobjects_requirements_check['php'];
	echo '<div class="error"><p>';
	echo sprintf( __( 'DreamObjects Backups cannot be activated as it requires PHP version %s or greater.', 'dreamobjects' ), $version );
	echo '</p></div>';
}

function dreamobjects_pass_wp( ) {
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$version = $dreamobjects_requirements_check['wp'];
	if ( version_compare( get_bloginfo( 'version' ), $version, '<=' ) ) {
		return false;
	} else {
	return true;
	}
}
function dreamobjects_pass_wp_message() {
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$version = $dreamobjects_requirements_check['wp'];
	echo '<div class="error"><p>';
	echo sprintf( __( 'DreamObjects Backups cannot be activated as it requires WordPress %s or greater.', 'dreamobjects' ), $version );
	echo '</p></div>';
}

function dreamobjects_pass_curl( ) {
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$version = $dreamobjects_requirements_check['curl'];
	if ( !function_exists( 'curl_version' ) 
		|| !( $curl = curl_version() ) || empty( $curl['version'] ) || empty( $curl['features'] )
		|| version_compare( $curl['version'], $version, '<=' ) )
	{
		return false;
	} else {
		return true;
	}
}
function dreamobjects_pass_curl_message() {
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$version = $dreamobjects_requirements_check['curl'];
	echo '<div class="error"><p>';
	echo sprintf( __( 'DreamObjects Backups cannot be activated as it requires cURL version %s or greater.', 'dreamobjects' ), $version );
	echo '</p></div>';
}

function dreamobjects_pass_multisite ( ) {
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$allowed = $dreamobjects_requirements_check['multisite'];
	if ( is_multisite() && $allowed === false ) {
		return false;
	} else {
		return true;
	}
}
function dreamobjects_pass_multisite_message() {
	echo '<div class="error"><p>';
	echo __( 'DreamObjects Backups cannot be activated as it is not currently compatible with WordPress Multisite, and should not be used.', 'dreamobjects' );
	echo '</p></div>';
}

function dreamobjects_pass_curlssl ( ) {
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$required = $dreamobjects_requirements_check['curlssl'];
	if ( !( $curl = curl_version() ) || !( $curl['features'] && CURL_VERSION_SSL && $required === true ) || !( $curl['features'] && CURL_VERSION_LIBZ && $required === true ) ) {
		return false;
	} else {
		return true;
	}
}
function dreamobjects_pass_curlssl_message() {
	echo '<div class="error"><p>';
	echo __( 'DreamObjects Backups cannot be activated as it requires cURL to be compiled with OpenSSL.', 'dreamobjects' );
	echo '</p></div>';
}

function dreamobjects_pass_plugins ( ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$plugins = $dreamobjects_requirements_check['plugins'];

	foreach ( $plugins as $plugin => $path ) {
		if ( is_plugin_active( $path ) ) {
			return false;
		} else {
			return true;
		}
	}
}
function dreamobjects_pass_plugins_message() {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );
	$plugins = $dreamobjects_requirements_check['plugins'];

	foreach ( $plugins as $plugin => $path ) {
		if ( is_plugin_active( $path ) ) {
			echo '<div class="error"><p>';
			echo sprintf( __( 'DreamObjects Backups cannot be activated as it is not compatible with %s. Using both will cause a rift in the space/time continuum, because we use different versions of the AWS SDK. Please deactivate %s if you wish to use DreamObjects Backups.', 'dreamobjects' ), $plugin, $plugin );
			echo '</p></div>';
		}
	}
}