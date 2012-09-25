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

if( class_exists( 'DHDO' ) ) {
	WP_CLI::addCommand( 'dreamobjects', 'DreamObjects_Command' );
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
	 * Run a backup right now
	 *
	 * @param array $args
	 * @param array $vars
	 */
	function backup( $args = array(), $vars = array() ) {
		do_action('dh-do-backup', array('DHDO', 'backup'));
		WP_CLI::success( 'Backup Complete' );
	}
	
	function schedule( $args = array(), $vars = array() ) {
		// take a variable here
		// schedule [daily|weekly|monthly|disable]
		WP_CLI::success( 'If this was written, it would work.' );
	}
	

	/**
	 * Help function for this command
	 */
	public static function help() {
		WP_CLI::line( <<<EOB
usage: wp dreamobjects [backup|schedule]

Available sub-commands:
	backup    run a backup now
	schedule  [daily|weekly|monthly|disable]
EOB
	);
	}
}