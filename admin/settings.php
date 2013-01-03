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

				<p><?php _e('DreamObjects&#153; is an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.', dreamobjects); ?></p>

<?php if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) : ?>
    <h3><?php _e('The Bucket Stuff', dreamobjects); ?></h3>
     <table class="form-table"><tr valign="top">
    <td width="50%"><h4><?php _e('Create A New Bucket', dreamobjects); ?></h4>

    <p><?php _e('If you need to create a new bucket, just enter the name and click Create Bucket.', dreamobjects); ?>
    <br /><?php _e('All buckets are created as "private" buckets.', dreamobjects); ?></p>
    <form  method="post" action="options.php">
        <input type="hidden" name="action" value="update" />
        <?php wp_nonce_field('update-options'); ?>
        <input type="text" name="do-do-new-bucket" id="new-bucket" value="" />
        <p class="submit"><input class='button-secondary' type='Submit' name='newbucket' value='<?php _e("Create Bucket", dreamobjects); ?>' id='submitbutton' /></p>
    </form></td>

    <td width="50%"><h4><?php _e('Bucket List', dreamobjects); ?></h4>
    
    <ul>
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

            echo "</li>";
        endforeach;
    endif;
    ?>
    </ul></td>
    </tr></table>

    <h3><?php _e(' Debug Logging', dreamobjects); ?></h3>

    <p><?php _e('If you\'re trying to troubleshoot problems, like why backups only work for SQL, you can turn on logging to see what\'s being kicked off and when. Generally you should not leave this on all the time.', dreamobjects); ?></p>
    <p><?php _e('When you turn off logging, the file will wipe itself out for your protection.', dreamobjects); ?></p>

    <form  method="post" action="options.php">
        <input type="hidden" name="action" value="update" />
        <?php wp_nonce_field('update-options'); ?>
        <input type="checkbox" name="dh-do-logging" <?php checked( get_option('dh-do-logging') == 'on',true); ?> /> <?php _e('Enable logging (if checked)', dreamobjects); ?>
        <input type="hidden" name="dhdo-logchange" value="Y">
        <input type="hidden" name="page_options" value="dh-do-logging" />
        <p class="submit"><input class='button-secondary' type='Submit' name='logging' value='<?php _e("Configure Logging", dreamobjects); ?>' id='submitbutton' /></p>
    </form>
    <?php
    if ( get_option('dh-do-logging') == 'on' ) {
        ?>
            <p><?php _e('Your logfile is located at ', dreamobjects); ?><a href="<?php echo plugins_url( 'debug.txt' , dirname(__FILE__) );?>"><?php echo plugins_url( 'debug.txt' , dirname(__FILE__) );?></a></p>
        <?php
    }
    ?>

                    <h3><?php _e('Reset Options', dreamobjects); ?></h3>
                    <p><?php _e('Click the button to wipe out all settings. This will reset your keypair, as well as all plugin options including the debug log. It will <em>not</em> remove any backups.', dreamobjects); ?></p>
     				<form method="post" action="options.php">
					<input type="hidden" name="action" value="update" />
					<?php wp_nonce_field('update-options'); ?>
					<input type="hidden" name="page_options" value="dh-do-reset" />
					<input type="hidden" name="dhdo-reset" value="Y">
                    <p class="submit"><input class='button-primary' type='Submit' name='update' value='<?php _e("Reset Options", dreamobjects); ?>' id='submitbutton' /></p>
			</form>
    
    <?php
    
else:

    ?><p><?php _e('Once you\'ve configured your keypair here, you\'ll be able to use the features of this plugin.', dreamobjects); ?></p>
     				<form method="post" action="options.php">
					<input type="hidden" name="action" value="update" />
					<?php wp_nonce_field('update-options'); ?>
					<input type="hidden" name="page_options" value="dh-do-key,dh-do-secretkey" />

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

    <?php

endif; // Show backup settings
?>
			</div>