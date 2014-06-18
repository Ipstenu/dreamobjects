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
		$sections = get_option('dh-do-backupsection');
		if ( !$sections ) {
			$sections = array();
		}
		?>

			<script type="text/javascript">
				var ajaxTarget = "<?php echo DHDO::getURL() ?>backup.ajax.php";
				var nonce = "<?php echo wp_create_nonce('dreamobjects'); ?>";
			</script>
			<div class="wrap">
				<div id="icon-dreamobjects" class="icon32"></div>
				<h2><?php echo __("DreamObjects Backups", dreamobjects); ?></h2>

				<p><?php echo __('DreamObjects&#153; is an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.', dreamobjects); ?></p>

<?php if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) { ?>

    <h3><?php echo __('Debug Logging', dreamobjects); ?></h3>

    <p><?php echo __('If you\'re trying to troubleshoot problems, like why backups only work for SQL, you can turn on logging to see what\'s being kicked off and when. Generally you should not leave this on all the time.', dreamobjects); ?></p>
    <p><?php echo __('When you turn off logging, the file will wipe itself out for your protection.', dreamobjects); ?></p>

    <form method="post" action="options.php">
        <?php settings_fields( 'dh-do-logging-settings' ); ?>
        <p><input type="checkbox" name="dh-do-logging" <?php checked( get_option('dh-do-logging') == 'on',true); ?> /> <?php echo __('Enable logging (if checked)', dreamobjects); ?> <?php
    if ( get_option('dh-do-logging') == 'on' ) { ?>&mdash; <span class="description"><?php echo __('Your logfile is located at ', dreamobjects); ?><a href="<?php echo plugins_url( 'debug.txt?nocache' , dirname(__FILE__) );?>"><?php echo plugins_url( 'debug.txt' , dirname(__FILE__) );?></a></span></p>
        <?php
    }
    ?>
        <input type="hidden" name="dhdo-logchange" value="Y">
        <input type="hidden" name="page_options" value="dh-do-logging" />
        <p class="submit"><input class='button-secondary' type='Submit' name='logging' value='<?php echo __("Configure Logging", dreamobjects); ?>' id='submitbutton' /></p>
    </form>
    

                    <h3><?php echo __('Reset Options', dreamobjects); ?></h3>
                    <p><?php echo __('Click the button to wipe out all settings. This will reset your keypair, as well as all plugin options and wipe the debug log. It will <em>not</em> remove any backups.', dreamobjects); ?></p>
     				<form method="post" action="options.php">
					<?php settings_fields( 'dh-do-reset-settings' ); ?>
					<input type="hidden" name="page_options" value="dh-do-reset" />
					<input type="hidden" name="dhdo-reset" value="Y">
					<?php submit_button('Reset Options', 'primary'); ?>
                    </form>
    
    <?php
    
} else {

    ?>
     				<form method="post" action="options.php">
					<?php
                        settings_fields( 'dh-do-keypair-settings' );
                        do_settings_sections( 'dh-do-keypair_page' );
                        submit_button();
					?>
                    </form>
    <?php
} 
?>
			</div>