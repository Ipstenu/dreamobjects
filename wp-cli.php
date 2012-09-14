<?php
/**
 * Implement DreamObjects commands
 *
 * @package wp-cli
 * @subpackage commands/community
 * @maintainer Mika Epstein (http://halfelf.org)
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
	
	function schedule( $args, $assoc_args  ) {
	
	// if daily, set daily, etc etc
	// Check for 'daily, monthly, yearly, disable'
	
	WP_CLI::success( 'Schedule changed to '$args[]'');
	
	}

	/**
	 * Disable the WP Super Cache
	 *
	 * @param array $args
	 * @param array $vars
	 */
	function disable( $args = array(), $vars = array() ) {
		if ( function_exists( 'wp_super_cache_disable' ) ) {

			wp_clear_scheduled_hook('dh-do-backup');
			
			if(!$super_cache_enabled) {
				WP_CLI::success( 'The WP Super Cache is disabled.' );
			} else {
				WP_CLI::error('The WP Super Cache is still enabled, check its settings page for more info.');
			}
		} else {
			WP_CLI::error('The WP Super Cache could not be found, is it installed?');
		}
	}

	public static function help() {
		WP_CLI::line( <<<EOB
usage: wp dreamobjects [backup|enable|disable] --schedule=[daily|weekly|monthly]

Available sub-commands:
	backup     Runs a backup right now
	enable     enables automated backups (defaulted to daily)
	disable    disables automated backups
EOB
	);
	}

}
// Register the class as the 'example' command handler
WP_CLI::add_command( 'dreamobjects', 'DreamObects_Command' );