<?php

if( class_exists( 'DHDO' ) ) {
	WP_CLI::add_command( 'dreamobjects', 'DreamObjects_Command' );
}

/**
 * Manage the Google XML Sitemap plugin
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

	/**
	 * Help function for this command
	 */
	public static function help() {
		WP_CLI::line( <<<EOB
usage: wp dreamobjects [backup]

Available sub-commands:
	backup    run a backup now
EOB
	);
	}
}