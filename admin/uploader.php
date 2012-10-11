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

include_once( PLUGIN_DIR. '/lib/S3.php');
?>

<script type="text/javascript">
	var ajaxTarget = "<?php echo self::getURL() ?>uploader.ajax.php";
	var nonce = "<?php echo wp_create_nonce('dreamobjects'); ?>";
</script>

<div class="wrap">
	<div id="icon-dreamobjects" class="icon32"></div>
	<h2><?php _e("Uploads", dreamobjects); ?></h2>
	
	<p><?php _e("Upload files directly to DreamObjects.", dreamobjects); ?></p>
	<h3><?php _e('Settings', dreamobjects); ?></h3>
	<form method="post" action="options.php">
		<input type="hidden" name="action" value="update" />
		<?php wp_nonce_field('update-options'); ?>
		<input type="hidden" name="page_options" value="dh-do-bucketup,dh-do-uploadpub" />

<table class="form-table">
    <tbody>

<?php if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) : ?>
<?php
	$s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey')); 
	$buckets = $s3->listBuckets();
?>
        <tr valign="top">
            <th scope="row"><label for="dh-do-bucketup"><?php _e('Bucket Name', dreamobjects); ?></label></th>
            <td><select name="dh-do-bucketup">
                                    <option value="XXXX">(select a bucket)</option>
		<?php foreach ( $buckets as $b ) : ?>
<option <?php if ( $b == get_option('dh-do-bucketup') ) echo 'selected="selected"' ?>><?php echo $b ?></option>
		<?php endforeach; ?>
	</select>
            <p class="description"><?php _e('Select from your pre-existing buckets.', dreamobjects); ?></p>
            <?php if ( get_option('dh-do-bucket') && ( !get_option('dh-do-bucket') || (get_option('dh-do-bucket') != "XXXX") ) ) { 
                $alreadyusing = sprintf(__('You are already using the bucket "%s" for backups. While you can reuse this bucket, it would be best not to.', dreamobjects), get_option('dh-do-bucket')  );
                echo '<p class="description">' . $alreadyusing . '</p>';
            } ?>            
            </td>
        </tr>
        
        <tr valign="top">
            <th scope="row"><label for="dh-do-uploadpub"><?php _e('Privacy', dreamobjects); ?></label></th>
            <td><input type="checkbox" name="dh-do-uploadpub" id="dh-do-uploadpub" value="1" <?php checked( '1' == get_option('dh-do-uploadpub') ); ?> /> <?php _e('Private Uploads', dreamobjects); ?>
            <p class="description"><?php _e('Designate if your uploads are public or private. If checked, all uploads are private.', dreamobjects); ?></p>

</td>
        </tr>
        
        <?php endif; // Show bucket list ?>
</tbody>
</table>

<p class="submit"><input class='button-primary' type='Submit' name='update' value='<?php _e("Update Options", dreamobjects); ?>' id='submitbutton' /></p>
	</form>

<?php if ( get_option('dh-do-bucketup') && (get_option('dh-do-bucketup') != "XXXX") && !is_null(get_option('dh-do-bucketup')) ) : ?>
	<h3><?php _e('Upload File', dreamobjects); ?></h3>

<table class="form-table">
    <tbody>
        <tr>
        <tr valign="top">
            <td>
            <p><?php _e('Please select a file by clicking the \'Browse\' button and press \'Upload\' to start uploading your file.', dreamobjects); ?></p>
           	<form action="" method="post" enctype="multipart/form-data" name="uploader" id="uploader">
              <input name="theFile" type="file" />
              <input name="Submit" type="submit" value="Upload">
        	</form>
        	</td>
        </tr>
     </tbody>
</table>       


<div id="uploaders">
<h3><?php _e('Available Files', dreamobjects); ?></h3>

<p><?php _e('The files listed below are all linked using the public URL. If an image has been uploaded with \'private\' permissions, it will not display.', dreamobjects); ?></p>
            
    <ul><?php 
        if ( get_option('dh-do-bucket') ) {
            $s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey'));
            $bucket = get_option('dh-do-bucketup');
        		if (($uploads = $s3->getBucket( $bucket ) ) !== false) {
            		krsort($uploads);
                    foreach ($uploads as $object) {
                           $object['label'] = sprintf(__('Uploaded on %s', dreamobjects), get_date_from_gmt( date('Y-m-d H:i:s', $object['time']) , 'F j, Y h:i a' ) );
                        ?><li><a href="https://objects.dreamhost.com/<?php echo $bucket .'/'. $object[name]; ?>"><?php echo $object['name']; ?></a> - <?php echo $object['label']; ?></li><?php
                    }
                }
		} // if you picked a bucket
					?>
				    </ul>
             </div></td>
        
        </tr>
    </tbody>
</table>
<?php endif; // Show backup settings ?>
</div>
