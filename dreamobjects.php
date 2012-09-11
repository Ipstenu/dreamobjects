<?php

/*
Plugin Name: DreamObjects
Plugin URI: 
Description: Backup your site to DreamObjects.
Version: 1.0
Author: Mika Epstein
Author URI: http://ipstenu.org/

Copyright 2012 Mika Epstein (email: ipstenu@ipstenu.org)

    This file is part of DreamObjects, a plugin for WordPress.

    DreamObjects is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    Disabler is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WordPress.  If not, see <http://www.gnu.org/licenses/>.

*/

/**
 * @package dh-do-backups
 */

if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	
class DHDO {
	/**
	 * Uses the init action to catch changes in the schedule and pass those on to the scheduler.
	 *
	 */
	function init() {
		if ( isset($_POST['dh-do-schedule']) ) {
			wp_clear_scheduled_hook('dh-do-backup');
			if ( $_POST['dh-do-schedule'] != 'disabled' ) {
				wp_schedule_event(time(), $_POST['dh-do-schedule'], 'dh-do-backup');
			}
		}
		if ( isset($_POST['dh-do-newbucket']) && !empty($_POST['dh-do-newbucket']) ) {
			include_once 'S3.php';
			$_POST['dh-do-newbucket'] = strtolower($_POST['dh-do-newbucket']);
			$s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey')); 
			$s3->putBucket($_POST['dh-do-newbucket']);
			$buckets = $s3->listBuckets();
			if ( is_array($buckets) && in_array($_POST['dh-do-newbucket'], $buckets) ) {
				update_option('dh-do-bucket', $_POST['dh-do-newbucket']);
				$_POST['dh-do-bucket'] = $_POST['dh-do-newbucket'];
			} else {
				update_option('dh-do-bucket', false);
			}
		}
		if ( !get_option('dh-do-bucket') && get_option('dh-do-secretkey') && get_option('dh-do-key') ) add_action('admin_notices', array('DHDO','newBucketWarning'));

		if ( isset($_GET['settings-updated']) && $_GET['page'] ==
'dreamobjects-menu' ) add_action('admin_notices', array('DHDO','updateMessage'));

	}
	
	function newBucketWarning() {
		echo "<div id='message' class='error'><p><strong>".__('You need to select a valid bucket.', dreamobjects)."</strong> ".__('If you tried to create a new bucket, it may have been an invalid name.', dreamobjects)."</p></div>";
	}

	function updateMessage() {
		echo "<div id='message' class='updated fade'><p><strong>".__('Options Updated!', dreamobjects)."</strong></p></div>";
		}

	/**
	 * Return the filesystem path that the plugin lives in.
	 *
	 * @return string
	 */
	function getPath() {
		return dirname(__FILE__) . '/';
	}
	
	/**
	 * Returns the URL of the plugin's folder.
	 *
	 * @return string
	 */
	function getURL() {
		return WP_CONTENT_URL.'/plugins/'.basename(dirname(__FILE__)) . '/';
	}

     // Sets up the settings page
	function add_settings_page() {
		load_plugin_textdomain(dreamobjects, DHDO::getPath() . 'i18n');

		add_menu_page(__('DreamObjects'), __('DreamObjects'), 'manage_options', 'dreamobjects-menu', array('DHDO', 'settings_page'), plugins_url('dreamobjects/images/dreamobj-color.png'));
	}

	// And now styles
    function stylesheet() {
        wp_register_style( 'dreamobj-style', plugins_url('dreamobjects.css?201210910', __FILE__) );
        wp_enqueue_style( 'dreamobj-style' );
    }

	
	/**
	 * Generates the settings page
	 *
	 */
	function settings_page() {
		include_once 'S3.php';
		$sections = get_option('dh-do-section');
		if ( !$sections ) {
			$sections = array();
		}
		?>
			<script type="text/javascript">
				var ajaxTarget = "<?php echo self::getURL() ?>backup.ajax.php";
				var nonce = "<?php echo wp_create_nonce('dreamobjects'); ?>";
			</script>
			<div class="wrap">
				<div id="icon-dreamobjects" class="icon32"></div>
				<h2><?php _e("DreamObjects", dreamobjects); ?></h2>

				<form method="post" action="options.php">
					<input type="hidden" name="action" value="update" />
					<?php wp_nonce_field('update-options'); ?>
					<input type="hidden" name="page_options" value="dh-do-key,dh-do-secretkey,dh-do-bucket,dh-do-section,dh-do-schedule" />


<table class="form-table">
    <tbody>
        <tr valign="top"><th colspan="2"><h3><?php _e('DreamObject Settings', dreamobjects); ?></h3></th></tr>
        <tr valign="top">
            <th scope="row"><label for="dh-do-key"><?php _e('Key', dreamobjects); ?></label></th>
            <td><input type="text" name="dh-do-key" value="<?php echo get_option('dh-do-key'); ?>" class="regular-text"/>
            <p class="description"><?php _e('This is your public key.', dreamobjects); ?></p></td>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="dh-do-secretkey"><?php _e('Secret Key', dreamobjects); ?></label></th>
            <td><input type="text" name="dh-do-secretkey" value="<?php echo get_option('dh-do-secretkey'); ?>" class="regular-text"/>
            <p class="description"><?php _e('This is your secret key.', dreamobjects); ?></p></td>
        </tr>

<?php if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) : ?>
						<?php 
							$s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey')); 
							$buckets = $s3->listBuckets();
						?>
        <tr valign="top">
            <th scope="row"><label for="dh-do-bucket"><?php _e('Bucket Name', dreamobjects); ?></label></th>
            <td><select name="dh-do-bucket">
								<?php foreach ( $buckets as $b ) : ?>
									<option <?php if ( $b == get_option('dh-do-bucket') ) echo 'selected="selected"' ?>><?php echo $b ?></option>
								<?php endforeach; ?>
							</select>
            <p class="description"><?php _e('Select from pre-existing buckets.', dreamobjects); ?></p>
            
            <p class="description"><?php _e('Or create a bucket:', dreamobjects); ?></p>
            <input type="text" name="dh-do-newbucket" id="new-s3-bucket" value="" />
            </td>
        </tr>

        <tr valign="top"><th colspan="2"><h3><?php _e('Backup Settings', dreamobjects); ?></h3></th></tr>

        <tr valign="top">
            <th scope="row"><label for="dh-do-what"><?php _e('What to Backup', dreamobjects); ?></label></th>
            <td>
								<p><label for="dh-do-section-files">
								<input <?php if ( in_array('files', $sections) ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-section[]" value="files" id="dh-do-section-files" />
								<?php _e('All Files', dreamobjects); ?>
							</label><br />
							<label for="dh-do-section-database">
								<input <?php if ( in_array('database', $sections) ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-section[]" value="database" id="dh-do-section-database" />
								<?php _e('Database', dreamobjects); ?>
							</label><br />
						</p>
				<p class="description"><?php _e('You can select portions of your site to backup.', dreamobjects); ?></p>
				</td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="dh-do-schedule"><?php _e('Schedule', dreamobjects); ?></label></th>
            <td><select name="dh-do-schedule">
								<?php foreach ( array('Disabled','Daily','Weekly','Monthly') as $s ) : ?>
									<option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('dh-do-schedule') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
								<?php endforeach; ?>
				<p class="description"><?php _e('How often do you want to backup your files? Daily is recommended.', dreamobjects); ?></p>
				</select></td>
        </tr>
        
<?php endif; ?>
</tbody>
</table>

<p class="submit"><input class='button-primary' type='Submit' name='update' value='<?php _e("Update Options", dreamobjects); ?>' id='submitbutton' /></p>

				</form>
				
				<?php //DHDOU::backup() ?>
				
<?php if ( get_option('dh-do-bucket')) { ?>
				<h3><?php _e('Download recent backups.', dreamobjects); ?></h3>
				<p><?php _e('You will only be able to download backups if you\'re logged into DreamObjects.', dreamobjects); ?></p>
				<div id="backups">
				    <ul>
					<?php 
						if ( get_option('dh-do-bucket') ) {
							$backups = $s3->getBucket(get_option('dh-do-bucket'), next(explode('//', get_bloginfo('siteurl'))));
							krsort($backups);
							$count = 0;
							foreach ( $backups as $key => $backup ) {
								$backup['label'] = sprintf(__('WordPress Backup from %s', dreamobjects), get_date_from_gmt( date('Y-m-d H:i:s', $backup['time']) , 'F j, Y h:i a' ) );
								
								
								$backup = apply_filters('dh-do-backup-item', $backup);
								
								if ( ++$count > 20 ) break;
								?>
									<li><a href="<?php echo $s3->getObjectURL(get_option('dh-do-bucket'), '/'.$backup['name']) ?>"><?php echo $backup['label'] ?></a></li>
								<?php
							}
						}
					?>
				    </ul>
				</div>
<?php } ?>				
			</div>
		<?php
	}
	
	function rscandir($base='') {
		$data = array_diff(scandir($base), array('.', '..'));
	
		$subs = array();
		foreach($data as $key => $value) :
			if ( is_dir($base . '/' . $value) ) :
				unset($data[$key]);
				$subs[] = DHDO::rscandir($base . '/' . $value);
			elseif ( is_file($base . '/' . $value) ) :
				$data[$key] = $base . '/' . $value;
			endif;
		endforeach;
	
		foreach ( $subs as $sub ) {
			$data = array_merge($data, $sub);
		}
		return $data;
	}
	
	function backup() {
		global $wpdb;
		require_once('S3.php');
		require_once(ABSPATH . '/wp-admin/includes/class-pclzip.php');

		// First, we gotta store this
		$target = WP_CONTENT_DIR . '/dreamobjects';
		wp_mkdir_p( $target );

		$sections = get_option('dh-do-section');
		if ( !$sections ) {
			$sections = array();
		}
		
		$file = WP_CONTENT_DIR . '/dreamobjects/dreamobject-backups.zip';
		$zip = new PclZip($file);
		$backups = array();
		//if ( in_array('files', $sections) ) $backups[] = ABSPATH . '/wp-config.php';
		//if ( in_array('files', $sections) ) $backups = array_merge($backups, DHDO::rscandir(ABSPATH . 'wp-content/plugins'));
		//if ( in_array('files', $sections) ) $backups = array_merge($backups, DHDO::rscandir(ABSPATH . 'wp-content/themes'));
		//if ( in_array('files', $sections) ) $backups = array_merge($backups, DHDO::rscandir(ABSPATH . 'wp-content/uploads'));
		if ( in_array('files', $sections) ) $backups = array_merge($backups, DHDO::rscandir(ABSPATH));
		
		if ( in_array('database', $sections) ) {
		
			$tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->prefix . "%'");
			$result = shell_exec('mysqldump --single-transaction -h ' . DB_HOST . ' -u ' . DB_USER . ' --password="' . DB_PASSWORD . '" ' . DB_NAME . ' ' . implode(' ', $tables) . ' > ' .  WP_CONTENT_DIR . '/dreamobjects/dreamobject-db-backup.sql');
			$backups[] = WP_CONTENT_DIR . '/dreamobjects/dreamobject-db-backup.sql';
		}
		
		if ( !empty($backups) ) {
			$zip->create($backups, '', ABSPATH);
			
			$s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey')); 
			$upload = $s3->inputFile($file);
			$s3->putObject($upload, get_option('dh-do-bucket'), next(explode('//', get_bloginfo('siteurl'))) . '/' . date('Y-m-d') . '.zip');
			@unlink($file);
			@unlink(WP_CONTENT_DIR . '/dreamobjects/dreamobject-db-backup.sql');
		}
	}
	
	function cron_schedules($schedules) {
		$schedules['weekly'] = array('interval'=>604800, 'display' => 'Once Weekly');
		$schedules['monthly'] = array('interval'=>2592000, 'display' => 'Once Monthly');
		return $schedules;
	}
}

add_filter('cron_schedules', array('DHDO', 'cron_schedules'));
add_action('admin_menu', array('DHDO', 'add_settings_page'));
add_action('dh-do-backup', array('DHDO', 'backup'));
add_action('init', array('DHDO', 'init'));
add_action('admin_print_styles', array('DHDO', 'stylesheet'));

if ( $_GET['page'] == 'dh-do-backup' ) {
	wp_enqueue_script('jquery');
}