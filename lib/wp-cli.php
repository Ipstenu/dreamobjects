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

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Manage the DreamObjects plugin
 *
 * @package wp-cli
 * @subpackage commands/community
 * @maintainer Mika A. Epstein
 */
class DreamObjects_Command extends WP_CLI_Command {

	/**
	 * Runs an immediate backup.
	 *
	 * ## EXAMPLES
	 *
	 *      wp dreamobjects backup
	 */
	public function backup() {
		do_action( 'dh-do-backup', array( 'DHDO', 'backup' ) );
		WP_CLI::success( 'Backup Complete' );
	}

	/**
	 * Reset the backup log
	 *
	 * ## EXAMPLES
	 *
	 *      wp dreamobjects reset log
	 */
	public function reset( $args, $assoc_args ) {

		$valid_resets = array( 'settings', 'log' );

		// Check for valid arguments.
		if ( empty( $args[0] ) || ! in_array( $args[0], $valid_resets ) ) {
			WP_CLI::error( __( 'You must provide something to be reset.', 'dreamobjects' ) );
		} else {
			switch ( $args[0] ) {
				case 'settings':
					DHDO::DreamObjects_Core( 'kill_it_all' );
					$message = __( 'Settings reset', 'dramobjects' );
					break;
				case 'log':
					DHDO::logger( 'reset' );
					$message = __( 'Debug log reset', 'dramobjects' );
					break;
			}
			WP_CLI::success( $message );
		}
	}

}

if ( class_exists( 'DHDO' ) ) {
	WP_CLI::add_command( 'dreamobjects', 'DreamObjects_Command' );
}
