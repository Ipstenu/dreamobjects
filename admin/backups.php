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

//DHDOU::backup()
        include_once( PLUGIN_DIR. '/lib/S3.php');
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
				<h2><?php _e("Backups", dreamobjects); ?></h2>
				
				<p><?php _e("Configure your site for backups by selecting your bucket, what you want to backup, and when.", dreamobjects); ?></p>

				<h3><?php _e('Settings', dreamobjects); ?></h3>
				<form method="post" action="options.php">
					<input type="hidden" name="action" value="update" />
					<?php wp_nonce_field('update-options'); ?>
					<input type="hidden" name="page_options" value="dh-do-bucket,dh-do-section,dh-do-schedule" />

<table class="form-table">
    <tbody>

<?php if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) : ?>
						<?php
							$s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey')); 
							$buckets = $s3->listBuckets();
						?>
        <tr valign="top">
            <th scope="row"><label for="dh-do-bucket"><?php _e('Bucket Name', dreamobjects); ?></label></th>
            <td><select name="dh-do-bucket">
                                    <option value="XXXX">(select a bucket)</option>
								<?php foreach ( $buckets as $b ) : ?>
									<option <?php if ( $b == get_option('dh-do-bucket') ) echo 'selected="selected"' ?>><?php echo $b ?></option>
								<?php endforeach; ?>
							</select>
            <p class="description"><?php _e('Select from pre-existing buckets.', dreamobjects); ?></p>
            
            <p class="description"><?php _e('Or create a bucket:', dreamobjects); ?></p>
            <input type="text" name="dh-do-newbucket" id="new-s3-bucket" value="" />
            </td>
        </tr>

<?php if ( !get_option('dh-do-bucket') || (get_option('dh-do-bucket') != "XXXX") ) : ?>

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
				</select></td>
        </tr>
        
        <tr valign="top">
        <th scope="row"></th>
        <td><?php
                  $timestamp = wp_next_scheduled( 'dh-do-backup' ); 
                  $nextbackup = sprintf(__('Next scheduled backup is at %s', dreamobjects), get_date_from_gmt( date('Y-m-d H:i:s', $timestamp) , 'F j, Y h:i a' ) );
            ?>
            <p class="description"><?php _e('How often do you want to backup your files? Daily is recommended.', dreamobjects); ?></p>
            <?php if ( get_option('dh-do-schedule') != "disabled" ) {?>
            <p class="description"><?php echo $nextbackup; ?></p>
            <?php } // Show next scheduled ?>
        </td>
        </tr>

<?php endif; // Show backup settings ?>
        
<?php endif; // Show bucket list ?>
</tbody>
</table>

<p class="submit"><input class='button-primary' type='Submit' name='update' value='<?php _e("Update Options", dreamobjects); ?>' id='submitbutton' /></p>

				</form>
				
<?php if ( get_option('dh-do-bucket') && ( !get_option('dh-do-bucket') || (get_option('dh-do-bucket') != "XXXX") ) ) { ?>
				<h3><?php _e('Latest Ten Backups', dreamobjects); ?></h3>
				<p><?php _e('You can download the backups if you\'re logged into DreamObjects.', dreamobjects); ?></p>

				<div id="backups">
				    <ul>
					<?php 
						if ( get_option('dh-do-bucket') ) {
						    $s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey'));
    if (($backups = $s3->getBucket(get_option('dh-do-bucket'), next(explode('//', get_bloginfo('siteurl'))) ) ) !== false) {
        krsort($backups);
        $count = 0;
        foreach ($backups as $object) {
            $object['label'] = sprintf(__('WordPress Backup from %s', dreamobjects), get_date_from_gmt( date('Y-m-d H:i:s', $object['time']) , 'F j, Y h:i a' ) );
            $object = apply_filters('dh-do-backup-item', $object);
								
			if ( ++$count > 10 ) break;
            ?><li><a href="<?php echo $s3->getAuthenticatedURL(get_option('dh-do-bucket'), $object['name'], 3600, false, true); ?>"><?php echo $object['label']; ?></a></li><?php
        }
    }
						} // if you picked a bucket
					?>
				    </ul>
				</div>
			</div>

			<form method="post" action="admin.php?page=dreamobjects-menu-backup&backup-now=true">
    <input type="hidden" name="action" value="backup" />
    <?php wp_nonce_field('backup-now'); ?>
    <h3><?php _e('Backup ASAP!', dreamobjects); ?></h3>
    <p><?php _e('Oh you really want to do a backup right now? Schedule your backup to start in a minute. Be careful! This may take a while, and slow your site down, if you have a big site.', dreamobjects); ?></p>

    <?php $timestamp = wp_next_scheduled( 'dh-do-backup' ); 
            $nextbackup = sprintf(__('Keep in mind, your next scheduled backup is at %s', dreamobjects), get_date_from_gmt( date('Y-m-d H:i:s', $timestamp) , 'F j, Y h:i a' ) ); 
            ?>
    <?php if ( get_option('dh-do-schedule') != "disabled" ) {?>
    <p><?php echo $nextbackup; ?></p>
    <?php } ?>


    <p class="submit"><input class='button-primary' type='Submit' name='backup' value='<?php _e("Backup ASAP", dreamobjects); ?>' id='submitbutton' /></p>
                </form>
            <?php
        } else {    
        ?><p><?php _e('Until you connect to a bucket, you can\'t see anything here.', dreamobjects); ?></p><?php
        }