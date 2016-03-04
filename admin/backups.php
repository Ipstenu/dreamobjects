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

use Aws\S3\S3Client;

?>
<script type="text/javascript">
    var ajaxTarget = "<?php echo DHDO::getURL() ?>backup.ajax.php";
    var nonce = "<?php echo wp_create_nonce('dreamobjects'); ?>";
</script>

<div class="wrap">
    <div id="icon-dreamobjects" class="icon32"></div>
    <h2><?php echo __("DreamObjects Backup Settings", 'dreamobjects'); ?></h2>

    <div id="dho-primary">
	    	<div id="dho-content">
	    		<div id="dho-leftcol">
		    		<?php settings_errors(); ?>
	            <form method="post" action="options.php">
	                <?php
	                settings_fields( 'dh-do-backuper-settings' );
					do_settings_sections( 'dh-do-backuper_page' );
	                submit_button( __('Update Options','dreamobjects') , 'primary');
	                ?>
	            </form>
	    		</div>
			<div id="dho-rightcol">
                <?php if ( get_option('dh-do-bucket') && ( !get_option('dh-do-bucket') || (get_option('dh-do-bucket') != "XXXX") ) ) { ?>
                <?php 
                    $num_backups = get_option('dh-do-retain');
                    if ( $num_backups == 'all') { $num_backups = 'WP';}
                    $show_backup_header = sprintf(__('Latest %s Backups', 'dreamobjects'),$num_backups ); 
                ?>                    
                
                <h3><?php echo $show_backup_header; ?></h3>

                <div id="backups">
                    <ul><?php 
						$timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', (time()+600) ), get_option('time_format') );
						$linksvalid_string = sprintf( __('Links are valid until %s (aka 10 minutes from page load). After that time, you need to reload this page.', 'dreamobjects'), $timestamp );									

						$config = array(
						    'key'     => get_option('dh-do-key'),
						    'secret'  => get_option('dh-do-secretkey'),
						    'base_url' => 'http://objects.dreamhost.com',
						);
						
						try {
							$s3 = S3Client::factory( $config );
						} catch ( \Aws\S3\Exception\S3Exception $e) {
						    echo $e->getAwsErrorCode() . "\n";
						    echo $e->getMessage() . "\n";
						}
            
                        $bucket = get_option('dh-do-bucket');
                        $prefix = next(explode('//', home_url()));
                        
                        try {
                        		$objects = $s3->getIterator('ListObjects', array('Bucket' => $bucket, 'Prefix' => $prefix));
							$objectsarray = $objects->toArray();
							
							if ( empty($objectsarray) ) {
								echo __('There are no backups currently stored. Why not run a backup now?', 'dreamobjects');
							} else {
								?>
								<p><?php echo __('All backups can be downloaded from this page without logging in to DreamObjects.', 'dreamobjects'); ?></p>
								<p><?php echo $linksvalid_string; ?></p><?php

								krsort($objectsarray);                                
                                echo '<ol>';
								foreach ($objects as $object) {
								    echo '<li><a href="'.$s3->getObjectUrl($bucket, $object['Key'], '+10 minutes').'">'.$object['Key'] .'</a> - '.size_format($object['Size']).'</li>';								    
								}
								echo '</ol>';
							}
						} catch (S3Exception $e) {
							echo __('There are no backups currently stored. Why not run a backup now?', 'dreamobjects');
						}

                    ?></ul>
                </div> <!-- Backups -->

                <form method="post" action="options.php">
                    <?php
                        settings_fields( 'dh-do-backupnow-settings' );
                        do_settings_sections( 'dh-do-backupnow_page' );
                        submit_button( __('Backup ASAP','dreamobjects') , 'secondary');
                    ?>
                </form>
			<?php } ?>
    			</div>
    	</div>
    </div>
</div>