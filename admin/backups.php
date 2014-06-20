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

use Aws\S3\S3Client as AwsS3DHDOBACK;

?>
<script type="text/javascript">
    var ajaxTarget = "<?php echo DHDO::getURL() ?>backup.ajax.php";
    var nonce = "<?php echo wp_create_nonce('dreamobjects'); ?>";
</script>

<div class="wrap">
    <div id="icon-dreamobjects" class="icon32"></div>
    <h2><?php echo __("Backups", dreamobjects); ?></h2>

    <div id="dho-primary">
    	<div id="dho-content">
    		<div id="dho-leftcol">
                    <form method="post" action="options.php">
                        <?php
                            settings_fields( 'dh-do-backuper-settings' );
                            do_settings_sections( 'dh-do-backuper_page' );
                            submit_button(__('Update Options','dreamobjects'), 'primary');
                        ?>
                    </form>
    			</div>
    			<div id="dho-rightcol">
                    <?php if ( get_option('dh-do-bucket') && ( !get_option('dh-do-bucket') || (get_option('dh-do-bucket') != "XXXX") ) ) { ?>
                    <?php 
                        $num_backups = get_option('dh-do-retain');
                        if ( $num_backups == 'all') { $num_backups = 'WP';}
                        $show_backup_header = sprintf(__('Latest %s Backups', dreamobjects),$num_backups ); 
                    ?>
                    
                    <h3><?php echo $show_backup_header; ?></h3>
                
                    <div id="backups">
                        <ul><?php 
                            if ( (get_option('dh-do-bucket') != "XXXX") && !is_null(get_option('dh-do-bucket')) ) {

								?><p><?php echo __('All backups can be downloaded from this page without logging in to DreamObjects.', dreamobjects); ?></p><?php
									$timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', (time()+600) ), get_option('time_format') );
									$string = sprintf( __('Links are valid until %s (aka 10 minutes from page load). After that time, you need to reload this page.', dreamobjects), $timestamp );									
								?><p><?php echo $string; ?></p><?php

								$s3 = AwsS3DHDOBACK::factory(array(
									'key'    => get_option('dh-do-key'),
									'secret' => get_option('dh-do-secretkey'),
									'base_url' => 'http://objects.dreamhost.com',
								));
                    
                                $bucket = get_option('dh-do-bucket');
                                $prefix = next(explode('//', home_url()));
                                
                                try {
                                	$objects = $s3->getIterator('ListObjects', array('Bucket' => $bucket, 'Prefix' => $prefix));
									$objects = $objects->toArray();
									krsort($objects);
                                
	                                echo '<ol>';
									foreach ($objects as $object) {
									    echo '<li><a href="'.$s3->getObjectUrl($bucket, $object['Key'], '+10 minutes').'">'.$object['Key'] .'</a> - '.size_format($object['Size']).'</li>';								    
									}
									echo '</ol>';
								} catch (S3Exception $e) {
									echo __('There are no backups currently stored. Why not run a backup now?');
								}
                                
                    		} // if you picked a bucket
                    					?>
                         </ul>
                     </div>
                
                     <form method="post" action="admin.php?page=dreamobjects-menu-backup&backup-now=true">
                         <input type="hidden" name="action" value="backup" />
                         <?php wp_nonce_field('dhdo-backupnow'); ?>
                         <h3><?php echo __('Backup ASAP!', dreamobjects); ?></h3>
                         <p><?php echo __('Oh you really want to do a backup right now? Schedule your backup to start in a minute. Be careful! This may take a while, and slow your site down, if you have a big site. Also if you made any changes to your settings, go back and click "Update Options" before running this.', dreamobjects); ?></p>
                
                         <?php
                             $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-backup' ) ), get_option('date_format').' '.get_option('time_format') );
                             $nextbackup = sprintf(__('Keep in mind, your next scheduled backup is at %s', dreamobjects), $timestamp ); 
                         
                        if ( get_option('dh-do-schedule') != "disabled" && wp_next_scheduled('dh-do-backup') ) {?>
                         <p><?php echo $nextbackup; ?></p>
                         <?php } 
                         
                         submit_button( __('Backup ASAP','dreamobjects'), 'secondary'); ?>
                    </form>
                    <?php } ?>
    		</div>
    	</div>
    </div>
</div>