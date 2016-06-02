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
    <h2><?php echo __('DreamObjects Backup Settings', 'dreamobjects'); ?></h2>

	<?php settings_errors(); ?>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'dh-do-backuper-settings' );
		do_settings_sections( 'dh-do-backuper_page' );
        submit_button( __('Update Options','dreamobjects') , 'primary');
        ?>
    </form>

    <?php 
	$backupsection = get_option('dh-do-backupsection');
    if ( ( get_option('dh-do-bucket') != "XXXX" ) && !empty( $backupsection ) ) {
        include('backups-retain.php');
	?>

    <form method="post" action="options.php">
        <?php
            settings_fields( 'dh-do-backupnow-settings' );
            do_settings_sections( 'dh-do-backupnow_page' );
            
            $nextscheduled = wp_next_scheduled( 'dh-do-backupnow' );
            if ( empty( $nextscheduled ) && get_option('dh-do-backupnow') !== 'Y' ) {
				submit_button( __('Backup ASAP','dreamobjects') , 'secondary');
            } else {
	            echo '<p>';
	            submit_button( __('Backup In Progress','dreamobjects') , 'secondary', null,  null, array('disabled'=>'disabled') );
	            echo '</p>';
            }
        ?>
    </form>
	<?php
	}
	?>

</div>