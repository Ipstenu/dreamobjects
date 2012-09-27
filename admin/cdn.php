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

$cdnsections = get_option('dh-do-cdnsection');
    if ( !$cdnsections ) {
        $cdnsections = array();
    }
?>

<script type="text/javascript">
	var ajaxTarget = "<?php echo self::getURL() ?>backup.ajax.php";
	var nonce = "<?php echo wp_create_nonce('dreamobjects'); ?>";
</script>

<div class="wrap">
	<div id="icon-dreamobjects" class="icon32"></div>
	<h2><?php _e("CDN", dreamobjects); ?></h2>
	
	<p><?php _e("Configure your site to use DreamObjects for CDN.", dreamobjects); ?></p>
	
	<p>CDN Doesn't actually work right now, but we're stubbing it out to test.</p>

	<h3><?php _e('Settings', dreamobjects); ?></h3>
	<form method="post" action="options.php">
		<input type="hidden" name="action" value="update" />
		<?php wp_nonce_field('update-options'); ?>
		<input type="hidden" name="page_options" value="dh-do-bucketcdn,dh-do-cdn" />

<table class="form-table">
    <tbody>

<?php if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) : ?>
<?php
	$s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey')); 
	$buckets = $s3->listBuckets();
?>
        <tr valign="top">
            <th scope="row"><label for="dh-do-bucketcdn"><?php _e('Bucket Name', dreamobjects); ?></label></th>
            <td><select name="dh-do-bucketcdn">
                                    <option value="XXXX">(select a bucket)</option>
		<?php foreach ( $buckets as $b ) : ?>
<option <?php if ( $b == get_option('dh-do-bucketcdn') ) echo 'selected="selected"' ?>><?php echo $b ?></option>
		<?php endforeach; ?>
	</select>
            <p class="description"><?php _e('Select from pre-existing buckets.', dreamobjects); ?></p>
            <?php if ( get_option('dh-do-bucket') && ( !get_option('dh-do-bucket') || (get_option('dh-do-bucket') != "XXXX") ) ) { 
                $alreadyusing = sprintf(__('You are already using the bucket "%s" for backups. While you can reuse this bucket, it would be best not to.', dreamobjects), get_option('dh-do-bucket')  );
                echo '<p class="description">' . $alreadyusing . '</p>';
            } ?>            
            </td>
        </tr>

<?php if ( get_option('dh-do-bucketcdn') && (get_option('dh-do-bucketcdn') != "XXXX") && !is_null(get_option('dh-do-bucketcdn')) ) : ?>
        <tr valign="top">
            <th scope="row"><label for="dh-do-whatcdn"><?php _e('What to move to CDN', dreamobjects); ?></label></th>
            <td>
								<p><label for="dh-do-cdnsection-theme">
								<input <?php if ( in_array('theme', $cdnsections) ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-cdnsection[]" value="files" id="dh-do-cdnsection-theme" />
								<?php _e('Theme Files', dreamobjects); ?>
							</label><br />
							<label for="dh-do-cdnsection-images">
								<input <?php if ( in_array('images', $cdnsections) ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-cdnsection[]" value="database" id="dh-do-cdnsection-images" />
								<?php _e('Images', dreamobjects); ?>
							</label><br />
						</p>
				<p class="description"><?php _e('You can select portions of your site to backup.', dreamobjects); ?></p>
				</td>
        </tr>        

<?php endif; // Show backup settings ?>
        
<?php endif; // Show bucket list ?>
</tbody>
</table>

<p class="submit"><input class='button-primary' type='Submit' name='update' value='<?php _e("Update Options", dreamobjects); ?>' id='submitbutton' /></p>