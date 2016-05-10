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
				<h2><?php echo __("DreamObjects Backups", 'dreamobjects'); ?></h2>
				<?php settings_errors(); ?>
				
				<p><?php printf( __( 'DreamObjects Backups allows WordPress to backup your site\'s critical files to <a href="%s">DreamObjects&#153;</a>, housing your data in an inexpensive, scalable object storage service that provides a reliable, flexible cloud solution.', 'dreamobjects'), 'https://www.dreamhost.com/cloud/storage/' ); ?></p>

				<?php if ( !get_option('dh-do-key') || !get_option('dh-do-secretkey') ) { ?>
		    			<p><?php printf( __( 'DreamObjects&#153; comes with a 30-day trial to evaluate the service and the plugin. To sign up, go to your <a href="%s">DreamObjects Panel for DreamObjects</a> and create a user. You will then be able to click on the Keys button to retrieve your DreamObjects access and secret keys.', 'dreamobjects' ), 'https://panel.dreamhost.com/index.cgi?tree=cloud.objects&' ); ?></p>
						<p><?php printf( __( 'Once you have your keys, enter them in the form below and save your changes. Your secret key will not be displayed for security. For additional help, please review the official <a href="%s">Using DreamObjects with DreamPress help document</a>.', 'dreamobjects' ), 'https://help.dreamhost.com/hc/en-us/articles/218036948-Using-DreamObjects-with-DreamPress' ); ?></p>
				<?php } ?>

				<form method="post" action="options.php">
				<?php
		            settings_fields( 'dh-do-keypair-settings' );
		            do_settings_sections( 'dh-do-keypair_page' );
					
					if ( get_option('dh-do-key') && get_option('dh-do-secretkey') ) {
			            settings_fields( 'dh-do-logging-settings' );
						do_settings_sections( 'dh-do-logging_page' );
					}
		            submit_button();
				?>
				</form>
			</div>