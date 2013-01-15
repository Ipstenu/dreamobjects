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

if (!defined('ABSPATH')) {
    die();
}

//DHDOU::backup()
        include_once( PLUGIN_DIR. '/AWSSDKforPHP/sdk.class.php');
		$sections = get_option('dh-do-backupsection');
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
				<h2><?php _e("Backups", dreamobjects); ?></h2>

<?php if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) : ?>
				
				<p><?php _e("Configure your site for backups by selecting your bucket, what you want to backup, and when.", dreamobjects); ?></p>

				<h3><?php _e('Settings', dreamobjects); ?></h3>
				<form method="post" action="options.php">
				<?php
				    settings_fields( 'dh-do-backuper-settings' );
                    do_settings_sections( 'dh-do-backuper_page' );
                ?>

                <table class="form-table">
                    <tbody>

						<?php
    						$s3 = new AmazonS3( array('key' => get_option('dh-do-key'), 'secret' => get_option('dh-do-secretkey')) );
    						$s3->set_hostname('objects.dreamhost.com');
    						$s3->allow_hostname_override(false);
    						$s3->enable_path_style();
 
    						$ListResponse = $s3->list_buckets();
							$buckets = $ListResponse->body->Buckets->Bucket;
						?>
                    <tr valign="top">
                        <th scope="row"><label for="dh-do-bucket"><?php _e('Bucket Name', dreamobjects); ?></label></th>
                        <td><select name="dh-do-bucket">
                                <option value="XXXX">(select a bucket)</option>
								<?php foreach ( $buckets as $b ) : ?>
								    <option <?php if ( $b->Name == get_option('dh-do-bucket') ) echo 'selected="selected"' ?>><?php echo $b->Name ?></option>
								<?php endforeach; ?>
							</select>
				        <p class="description"><?php _e('Select from pre-existing buckets.', dreamobjects); ?></p>
				        <?php if ( get_option('dh-do-bucketup') && ( !get_option('dh-do-bucketup') || (get_option('dh-do-bucketup') != "XXXX") ) ) { 
    				            $alreadyusing = sprintf(__('You are currently using the bucket "%s" for Uploads. While you can reuse this bucket, it would be best not to.', dreamobjects), get_option('dh-do-bucket')  );
    				    echo '<p class="description">' . $alreadyusing . '</p>';
    				    } ?>            
            
                        </td>
                    </tr>

<?php  // BEGIN Show Bucket List 
if ( get_option('dh-do-bucket') && (get_option('dh-do-bucket') != "XXXX") && !is_null(get_option('dh-do-bucket')) ) :?>

        <tr valign="top">
            <th scope="row"><label for="dh-do-what"><?php _e('What to Backup', dreamobjects); ?></label></th>
            <td>
								<p><label for="dh-do-backupsection-files">
								<input <?php if ( in_array('files', $sections) ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-backupsection[]" value="files" id="dh-do-backupsection-files" />
								<?php _e('All Files', dreamobjects); ?>
							</label><br />
							<label for="dh-do-backupsection-database">
								<input <?php if ( in_array('database', $sections) ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-backupsection[]" value="database" id="dh-do-backupsection-database" />
								<?php _e('Database', dreamobjects); ?>
							</label><br />
						</p>
				<p class="description"><?php _e('You can select portions of your site to backup.', dreamobjects); ?></p>
				</td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="dh-do-schedule"><?php _e('Schedule', dreamobjects); ?></label></th>
            <td>
                <select name="dh-do-schedule">
								<?php foreach ( array('Disabled','Daily','Weekly','Monthly') as $s ) : ?>
									<option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('dh-do-schedule') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
								<?php endforeach; ?>
				</select>
				<?php
                  $timestamp = wp_next_scheduled( 'dh-do-backup' ); 
                  $nextbackup = sprintf(__('Next scheduled backup is at %s', dreamobjects), date_i18n('F j, Y h:i a', $timestamp) );
            ?>
            <p class="description"><?php _e('How often do you want to backup your files? Daily is recommended.', dreamobjects); ?></p>
            <?php if ( get_option('dh-do-schedule') != "disabled" && wp_next_scheduled('dh-do-backup') ) { ?>
            <p class="description"><?php echo $nextbackup; ?></p>
            <?php } // Show next scheduled ?>
				</td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="dh-do-retain"><?php _e('Backup Retention', dreamobjects); ?></label></th>
            <td>				
                <select name="dh-do-retain">
				    <?php foreach ( array('15','30','60','90','all') as $s ) : ?>
				        <option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('dh-do-retain') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
				    <?php endforeach; ?>
				</select>	
				<p class="description"><?php _e('How many many backups do you want to keep? 30 is recommended.', dreamobjects); ?></p>
				<p class="description"><strong><?php _e('NOTICE!', dreamobjects); ?></strong> <?php _e('DreamObjects charges you based on diskspace used. Setting to \'All\' will retain your backups forwever, however this can cost you a large sum of money over time. Please use cautiously!', dreamobjects); ?></p>
				</td>
        </tr>
        
        <tr valign="top">
        <th scope="row"></th>
        <td>        </td>
        </tr>
   
<?php endif; 
// ENDS how bucket list ?>
</tbody>
</table>

<p class="submit"><input class='button-primary' type='Submit' name='update' value='<?php _e("Update Options", dreamobjects); ?>' id='submitbutton' /></p>

				</form>
				
<?php if ( get_option('dh-do-bucket') && ( !get_option('dh-do-bucket') || (get_option('dh-do-bucket') != "XXXX") ) ) { ?>
                <?php 
                    $num_backups = get_option('dh-do-retain');
                    if ( $num_backups == 'all') { $num_backups = 'WP';}
                    $show_backup_header = sprintf(__('Latest %s Backups', dreamobjects),$num_backups ); 
                ?>
				<h3><?php echo $show_backup_header; ?></h3>
				<p><?php _e('All backups can be downloaded from this page without logging in to DreamObjects.', dreamobjects); ?></p>

				<div id="backups">

    <ul><?php 
        if ( get_option('dh-do-bucketup') && (get_option('dh-do-bucket') != "XXXX") && !is_null(get_option('dh-do-bucket')) ) {

        	$s3 = new AmazonS3( array('key' => get_option('dh-do-key'), 'secret' => get_option('dh-do-secretkey')) );
        	$s3->set_hostname('objects.dreamhost.com');
        	$s3->allow_hostname_override(false);
        	$s3->enable_path_style();
            $bucket = get_option('dh-do-bucket');
            $prefix = next(explode('//', home_url()));
            $uploads = $s3->get_object_list( $bucket, array( 'prefix' => $prefix ) );
        		if ($uploads !== false) {
            		krsort($uploads);
                    foreach ($uploads as $object) {
                        $objecturl = $s3->get_object_url( $bucket , $object, '30 minutes' );
                        echo '<li>&bull; <a href="'. $objecturl .'">'. $object .'</a></li>';
                    }
                }
		} // if you picked a bucket
					?>
     </ul>
     			    <ol>
					<?php 
/*
						if ( get_option('dh-do-bucket') ) {
						    $s3 = new AmazonS3(get_option('dh-do-key'), get_option('dh-do-secretkey'));
    if (($backups = $s3->getBucket(get_option('dh-do-bucket'), next(explode('//', home_url())) ) ) !== false) {
        krsort($backups);
        $count = 0;
        foreach ($backups as $object) {
            $offset = get_option( 'gmt_offset' ) * 60 * 60; // Time offset in seconds
            $ziptime = $object['time'] + $offset; // Converting to local time
            $object['label'] = sprintf(__('WordPress Backup from %s', dreamobjects), date_i18n('F j, Y h:i a', $ziptime) );
            $object = apply_filters('dh-do-backup-item', $object);
								
			if ( ($num_backups != 'WP') && ( ++$count > $num_backups) ) break;
            ?><li><a href="<?php echo $s3->getAuthenticatedURL(get_option('dh-do-bucket'), $object['name'], 3600, false, true); ?>"><?php echo $object['label']; ?></a></li><?php
        }
    }
						} // if you picked a bucket
*/					?>
				    </ol>

				</div>

			<form method="post" action="admin.php?page=dreamobjects-menu-backup&backup-now=true">
    <input type="hidden" name="action" value="backup" />
    <?php wp_nonce_field('dhdo-backupnow'); ?>
    <h3><?php _e('Backup ASAP!', dreamobjects); ?></h3>
    <p><?php _e('Oh you really want to do a backup right now? Schedule your backup to start in a minute. Be careful! This may take a while, and slow your site down, if you have a big site. Also if you made any changes to your settings, go back and click "Update Options" before running this.', dreamobjects); ?></p>

    <?php
     	$timestamp = wp_next_scheduled( 'dh-do-backup' ); 
        $nextbackup = sprintf(__('Keep in mind, your next scheduled backup is at %s', dreamobjects), date_i18n('F j, Y h:i a', $timestamp) ); 
    ?>
    <?php if ( get_option('dh-do-schedule') != "disabled" && wp_next_scheduled('dh-do-backup') ) {?>
    <p><?php echo $nextbackup; ?></p>
    <?php } ?>

    <?php submit_button( 'Backup ASAP', 'primary'); ?>
                </form>
            <?php
        } else {
        ?><p><?php _e('Until you connect to a bucket, you can\'t see anything here.', dreamobjects); ?></p><?php
        }

else:

?><p><?php _e("Please fill in your Access Key and Secret Key. You cannot use the rest of this plugin without those!", dreamobjects); ?></p><?php

endif; // Show backup settings

?>
			</div>