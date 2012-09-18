<?php

if( class_exists( 'DHDO' ) ) {
	WP_CLI::add_command( 'dreamobjects', 'DreamObjects_Command' );
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