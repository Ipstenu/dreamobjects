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

include_once( DHDO_PLUGIN_DIR. '/AWSSDKforPHP/sdk.class.php');

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
	
	<p>CDN Doesn't actually work right now. Why are you here?</p>

    <div id="dho-primary">
    	<div id="dho-content">
    		<div id="dho-leftcol">
    		    <p>Options will be set here</p>
                    <form method="post" action="options.php">
                        <?php
                            settings_fields( 'dh-do-cdner-settings' );
                            do_settings_sections( 'dh-do-cdner_page' );
                            submit_button(__('Update Options','dreamobjects'), 'primary');
                        ?>
                    </form>
    			</div>
    			<div id="dho-rightcol">
                    <?php if ( get_option('dh-do-bucketcdn') && ( !get_option('dh-do-bucketcdn') || (get_option('dh-do-bucketcdn') != "XXXX") ) ) { ?>
                    <p>List some things here</p>
                    <?php } ?>
    		</div>
    	</div>
    </div>
</div>