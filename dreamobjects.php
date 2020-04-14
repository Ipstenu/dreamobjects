<?php
/*
 * Plugin Name: DreamObjects Backups
 * Plugin URI: https://github.com/Ipstenu/dreamobjects
 * Description: Backup your WordPress site on a regular basis to DreamObjects for extra protection. To get started: Activate this plugin and then sign up for an DreamObjects storage plan to get a public and secret key. Go to your DreamObjects configuration page, and save your keys to begin backups.
 * Version: 4.3.0
 * Author: Mika Epstein
 * Author URI: http://halfelf.org/
 * Network: false
 * Text Domain: dreamobjects
 *
 * Copyright 2012-2020 Mika Epstein (email: ipstenu@halfelf.org)
 *
 * This file is part of DreamObjects Backups, a plugin for WordPress.
 *
 * This plugin is free software: you can redistribute it and/or modify
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
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

require_once 'lib/requirements.php';

add_action( 'admin_init', 'dreamobjects_requirements' );

function dreamobjects_requirements() {
	$dreamobjects_requirements_check = get_option( 'dh-do-requirements' );

	foreach ( $dreamobjects_requirements_check as $key => $value ) {
		$run_test = 'dreamobjects_pass_' . $key;
		$message  = $run_test . '_message';

		if ( ! $run_test || call_user_func( $run_test ) === false ) {
			$message = $run_test . '_message';
			add_action( 'admin_notices', $message );
			add_action( 'admin_print_styles', 'dreamobjects_requirements_css' );
			deactivate_plugins( __FILE__ );
		}
	}
}

function dreamobjects_requirements_css() {
	?>
	<style type="text/css">
	div#message.notice.is-dismissible { display: none; }
	</style>
	<?php
}

// Include my own libraries
require_once 'lib/defines.php';
require_once 'lib/core.php';
require_once 'lib/dhdo.php';
require_once 'lib/settings.php';

// Include AWS library
require_once 'vendor/autoload.php';
add_action( 'admin_init', array( 'PAnD', 'init' ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once 'lib/wp-cli.php';
}
