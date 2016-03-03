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
				<?php settings_errors(); ?>

				<p><?php printf( __( '<a href="%s">DreamObjects&#153;</a> is an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.', dreamobjects ), 'https://www.dreamhost.com/cloud/storage/' ); ?></p>

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