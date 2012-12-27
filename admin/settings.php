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


include_once (PLUGIN_DIR . '/lib/S3.php');
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
				<h2><?php _e("DreamObjects", dreamobjects); ?></h2>

				<form method="post" action="options.php">
					<input type="hidden" name="action" value="update" />
					<?php wp_nonce_field('update-options'); ?>
					<input type="hidden" name="page_options" value="dh-do-key,dh-do-secretkey" />

					<p><?php _e('DreamObjects™ is an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.', dreamobjects); ?></p>
					<p><?php _e('Once you\'ve configured your keypair here, you\'ll be able to use the features of this plugin.', dreamobjects); ?></p>

<table class="form-table">
    <tbody>
        <tr valign="top"><th colspan="2"><h3><?php _e('DreamObject Settings', dreamobjects); ?></h3></th></tr>
        <tr valign="top">
            <th scope="row"><label for="dh-do-key"><?php _e('Access Key', dreamobjects); ?></label></th>
            <td><input type="text" name="dh-do-key" value="<?php echo get_option('dh-do-key'); ?>" class="regular-text"/></td>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="dh-do-secretkey"><?php _e('Secret Key', dreamobjects); ?></label></th>
            <td><input type="text" name="dh-do-secretkey" value="<?php echo get_option('dh-do-secretkey'); ?>" class="regular-text"/></td>
        </tr>
</tbody>
</table>

<p class="submit"><input class='button-primary' type='Submit' name='update' value='<?php _e("Update Options", dreamobjects); ?>' id='submitbutton' /></p>
				</form>
				
<?php if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) : ?>

    <h3><?php _e('Buckets', dreamobjects); ?></h3>
    
    <?php
    $s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey')); 
    $buckets = $s3->listBuckets();

    if ( !empty($buckets) ) :
        foreach ( $buckets as $b ) :
            echo "<li>".$b;
            if ( $b == get_option('dh-do-bucket') ) 
                {$string = ' <strong>'. __(' - Used for Backups', dreamobjects).'</strong>';
                echo $string;}
            elseif ( $b == get_option('dh-do-bucketup') ) 
                {$string = ' <strong>'. __(' - Used for Uploads', dreamobjects).'</strong>';
                echo $string;
                }
            else
                {_e(' - Unused', dreamobjects);}
            echo "</li>";
        endforeach;
    endif;
    
    ?>
 
    <h3><?php _e('Create A New Bucket', dreamobjects); ?></h3>

    <p><?php _e('If you need to create a new bucket, just enter the name and click Create Bucket. All buckets are created as private.', dreamobjects); ?></p>
    <form  method="post" action="options.php">
        <input type="hidden" name="action" value="update" />
        <?php wp_nonce_field('update-options'); ?>
        <input type="text" name="do-do-new-bucket" id="new-bucket" value="<?php echo $_GET['do-do-new-bucket']; ?>" />
        <p class="submit"><input class='button-secondary' type='Submit' name='newbucket' value='<?php _e("Create Bucket", dreamobjects); ?>' id='submitbutton' /></p>
    </form>
<!--
    <h3><?php _e('Logging', dreamobjects); ?></h3>

    <form  method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>
        <p class="description"><label for="dh-do-logging"><input <?php if ( get_option('dh-do-logging') == 'yes' ) echo 'checked="checked"' ?> type="checkbox" name="dh-do-logging" value="yes" id="dh-do-logging" /></label> <?php _e('Having issues with backups? Turn on logging.', dreamobjects); ?></p>

        <input type="hidden" name="action" value="update" />

        <p class="submit"><input class='button-secondary' type='Submit' name='logging' value='<?php _e("Enable Logging", dreamobjects); ?>' id='submitbutton' /></p>
    </form>

    <?php if ( get_option('dh-do-logging') == 'yes') {
        
        // Show Log
        
    }
    ?>
-->
    <?php
    
else:

    ?><p><?php _e("Please fill in your Access Key and Secret Key. You cannot use the rest of this plugin without those!", dreamobjects); ?></p><?php

endif; // Show backup settings
?>
			</div>
