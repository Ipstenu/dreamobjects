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

if ( !defined( 'ABSPATH' ) ) die();

class DreamObjects_Core {

	public $version = '4.1.0';
	
	static public $s3Options;

	function __construct() {

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Filter Cron
		add_filter('cron_schedules', array( 'DHDO', 'cron_schedules' ) );
		
		// Etc
		add_action('admin_menu', array( 'DHDOSET', 'add_settings_page' ) );
		add_action('dh-do-backup', array( 'DHDO', 'backup' ) );
		add_action('dh-do-backupnow', array( 'DHDO', 'backup' ) );
		add_action('init', array( 'DHDO', 'init' ) );
		
		if ( isset($_GET['page']) && ( $_GET['page'] == 'dh-do-backup' || $_GET['page'] == 'dh-do-backupnow' ) ) {
			wp_enqueue_script('jquery');
		}

		register_activation_hook( __FILE__ , array( $this, 'install' ) );
		add_action( 'plugins_loaded', array( $this, 'update_check' ) );

		// Default options for everything
		self::$s3Options = array(
			'version'     => '2006-03-01',
			'endpoint'    => 'https://objects-' . get_option( 'dh-do-hostname' ) . '.dream.io',
			'region'      => get_option( 'dh-do-hostname' ),
			'credentials' => array(
				'key'    => get_option( 'dh-do-key' ),
				'secret' => get_option( 'dh-do-secretkey' ),
			),
		);
	}

	// function to create the DB / Options / Defaults
	static function install() {
		global $wpdb;

		// create the database table just in case it's missing
		$dreamobjects_table_name = $wpdb->prefix . 'dreamobjects_backup_log';
		if( $wpdb->get_var("show tables like '$dreamobjects_table_name'") !== $dreamobjects_table_name ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE $dreamobjects_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				filename tinytext NOT NULL,
				text text NOT NULL,
				frequency tinytext NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	
		// Figure out if we need to change the hostname...
		if ( self::datacenter_move_east() && get_option( 'dh-do-hostname' ) !== 'us-east-1' ) {
			update_option( 'dh-do-hostname', 'us-east-1' );
		} else {
			update_option( 'dh-do-hostname', 'us-west-1' );
		}
	}

	// Update check
	function update_check() {
		if ( !get_option( 'dh-do-version' ) || get_option( 'dh-do-version' ) !== $this->version ) {
			self::install();
			update_option( 'dh-do-version', $this->version );
		}
	}

	// Are we past the deadline to move the datacenter?
	static function datacenter_move_east() {
		$deadline = new DateTime( '2018-06-21' );
		$now      = new DateTime();
		$return   = false;

		if ( $now >= $deadline ) $return = true;

		return $return;
	}

	function admin_notices() {

		// Datacenter Move Notice
		if ( self::datacenter_move_east() ) {
			if ( !PAnD::is_admin_notice_active( 'datacenter_move_east' ) ) {
				return;
			}

			$message = sprintf( __( 'As of June 21, 2018, DreamObjects has moved to a new datacenter. While the <strong>DreamObjects Backup Plugin</strong> has automatically begun using the new datacenter, it <em>will not</em> migrate your existing data. You will need to <a href="%s" target="_new">migrate your data to the new cluster on your own</a> to see your old backups.', 'dreamobjects' ), 'https://help.dreamhost.com/hc/en-us/articles/360002135871-Cluster-migration-procedure' );
			?>
			<div data-dismissible="datacenter_move_east" class="notice notice-warning is-dismissible">
				<p><strong><?php __( 'NOTICE!', 'dreamobjects' ); ?></strong> <?php echo $message; ?></p>
			</div>
			<?php
		}
	}

}

new DreamObjects_Core();