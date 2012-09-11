<?php
/**
 * Implement DreamObjects commands
 *
 * @package wp-cli
 * @subpackage commands/community
 * @maintainer Andreas Creten (http://twitter.com/andreascreten)
 */
class DreamObects_Command extends WP_CLI_Command {
	/**
	 * Example subcommand
	 *
	 * @param array $args
	 */
	function backup( $args = array() ) {
		// Make a backup now.
		do_action('dh-do-backup', array('DHDO', 'backup'));
		WP_CLI::success( 'Backup Complete' );
	}
	static function help() {
		WP_CLI::line( 'usage: wp dreamobjects backup' );
	}
}
// Register the class as the 'example' command handler
WP_CLI::add_command( 'dreamobjects', 'DreamObects_Command' );