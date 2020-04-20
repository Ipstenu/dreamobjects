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

class DreamObjects_Core {

	public $version = '4.3.0';

	static public $s3_options;

	public function __construct() {

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Filter Cron
		add_filter( 'cron_schedules', array( 'DHDO', 'cron_schedules' ) );

		// Etc
		add_action( 'dh-do-backup', array( 'DHDO', 'backup' ) );
		add_action( 'dh-do-backupnow', array( 'DHDO', 'backup' ) );
		add_action( 'init', array( 'DHDO', 'init' ) );

		if ( isset( $_GET['page'] ) && ( 'dh-do-backup' === $_GET['page'] || 'dh-do-backupnow' === $_GET['page'] ) ) {
			wp_enqueue_script( 'jquery' );
		}

		register_activation_hook( __FILE__, array( $this, 'install' ) );
		add_action( 'plugins_loaded', array( $this, 'update_check' ) );

		// Default options for everything
		self::$s3_options = array(
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
	public static function install() {
		global $wpdb;

		// create the database table just in case it's missing
		$dreamobjects_table_name = $wpdb->prefix . 'dreamobjects_backup_log';
		if ( $wpdb->get_var( "show tables like '$dreamobjects_table_name'" ) !== $dreamobjects_table_name ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE $dreamobjects_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				filename tinytext NOT NULL,
				text text NOT NULL,
				frequency tinytext NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// Figure out if we need to change the hostname...
		if ( self::datacenter_move_east( 'deadline' ) && get_option( 'dh-do-hostname' ) !== 'us-east-1' ) {
			update_option( 'dh-do-hostname', 'us-east-1' );
		}
	}

	// Update check
	public function update_check() {
		if ( ! get_option( 'dh-do-version' ) || get_option( 'dh-do-version' ) !== $this->version ) {
			self::install();
			update_option( 'dh-do-version', $this->version );
		}
	}

	// Are we past the deadline to move the datacenter?
	public static function datacenter_move_east( $type ) {
		$deadline = new DateTime( '2018-06-21' );
		$gonegirl = new DateTime( '2018-10-01' );
		$toolate  = new DateTime( '2018-12-31' );
		$now      = new DateTime();
		$return   = false;

		switch ( $type ) {
			case 'deadline':
				// If it's AFTER the deadline, let's show this:
				if ( $now >= $deadline ) {
					$return = true;
				}
				break;
			case 'gonegirl':
				// If it's BEFORE gone girl
				if ( $now >= $gonegirl ) {
					$return = true;
				}
				break;
			case 'toolate':
				// If it's BEFORE toolate
				if ( $now >= $toolate ) {
					$return = true;
				}
				break;
			default:
				$return = false;
				break;
		}

		return $return;
	}

	public function admin_notices() {

		// Datacenter Move Notice
		if ( self::datacenter_move_east( 'deadline' ) && ! self::datacenter_move_east( 'gonegirl' ) ) {
			if ( ! PAnD::is_admin_notice_active( 'datacenter-move-east-forever' ) ) {
				return;
			}
			// translators: %s is a link to the help doc
			$message = sprintf( __( 'As of June 21, 2018, DreamObjects has moved to a new datacenter. The <strong>DreamObjects Backup Plugin</strong> has automatically begun using the new datacenter, but you will need to <a href="%s" target="_new">migrate existing data to the new cluster on your own</a>. In order to prevent possible file collisions, it is recommend you migrate your old files to a new and differently named bucket.', 'dreamobjects' ), 'https://help.dreamhost.com/hc/en-us/articles/360002135871-Cluster-migration-procedure' );
			?>
			<div data-dismissible="datacenter-move-east-forever" class="notice notice-warning is-dismissible"><p><strong><?php __( 'NOTICE!', 'dreamobjects' ); ?></strong>
			<?php
				echo wp_kses_post( $message );
			?>
			</p></div>
			<?php
		} elseif ( self::datacenter_move_east( 'gonegirl' ) && ! self::datacenter_move_east( 'toolate' ) ) {
			if ( ! PAnD::is_admin_notice_active( 'datacenter-gonegirl-forever' ) ) {
				return;
			}
			// translators: %s is a link to the help doc
			$message = sprintf( __( 'The old DreamObjects us-west-1 datacenter was shut down on <strong>October 1, 2018</strong>. You should have already <a href="%s" target="_new">migrated any existing data to the new cluster</a>. Any data not migrated is no longer recoverable.', 'dreamobjects' ), 'https://help.dreamhost.com/hc/en-us/articles/360002135871-Cluster-migration-procedure' );
			?>
			<div data-dismissible="datacenter-gonegirl-forever" class="notice notice-warning is-dismissible"><p><strong><?php __( 'NOTICE!', 'dreamobjects' ); ?></strong>
			<?php
				echo wp_kses_post( $message );
			?>
			</p></div>
			<?php
		}

	}

	public function kill_it_all() {
		delete_option( 'dh-do-backupsection' );
		delete_option( 'dh-do-bucket' );
		delete_option( 'dh-do-key' );
		delete_option( 'dh-do-schedule' );
		delete_option( 'dh-do-secretkey' );
		delete_option( 'dh-do-section' );
		delete_option( 'dh-do-logging' );
		delete_option( 'dh-do-retain' );
		delete_option( 'dh-do-notify' );
		delete_option( 'dh-do-reset' );
		delete_option( 'dh-do-hostname' );
		delete_option( 'dh-do-requirements' );
		delete_option( 'dh-do-backupnow' );
		delete_option( 'dh-do-resetplugin' );

		// Unschedule
		wp_clear_scheduled_hook( 'dh-do-backupnow' );
		wp_clear_scheduled_hook( 'dh-do-backup' );

		// Delete table
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}dreamobjects_backup_log" );
	}

}

new DreamObjects_Core();
