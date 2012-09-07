<?php
/*
Plugin Name: DreamObjects
Plugin URI: 
Description: Screwing around with DreamObjects CDN
Version: 2.2
Author: Mika Epstein
Author URI: http://ipstenu.org/

Copyright 2010-11 Mika Epstein (email: ipstenu@ipstenu.org)

    This file is part of DreamObjects, a plugin for WordPress.

    Disabler is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    Disabler is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WordPress.  If not, see <http://www.gnu.org/licenses/>.

*/

define('AWS_KEY', 'place access key here');
define('AWS_SECRET_KEY', 'place secret key here');
define('AWS_CANONICAL_ID', 'your DHO Username');
define('AWS_CANONICAL_NAME', 'Also your DHO Username!');
$HOST = 'objects.dreamhost.com';

// require the amazon sdk for php library
require_once 'AWSSDKforPHP/sdk.class.php';

// Instantiate the S3 class and point it at the desired host
$Connection = new AmazonS3();
$Connection->set_hostname($HOST);
$Connection->allow_hostname_override(false);

// Set the S3 class to use objects.dreamhost.com/bucket
// instead of bucket.objects.dreamhost.com
$Connection->enable_path_style();


// Adding options
add_action('admin_init', 'dreamobj_activate' );
function dreamobj_activate() {
    register_setting( 'dreamobj_settings_key', 'dreamobj_settings' );
    register_setting( 'dreamobj_settings_secretkey', 'dreamobj_settings' );
    register_setting( 'dreamobj_settings_cannonicalid', 'dreamobj_settings' );
    register_setting( 'dreamobj_settings_cannonicalname', 'dreamobj_settings' );
}


// Draw the menu page itself
function dreamobj_options_do_page() {
    ?>
    <div class="wrap">
        <h2>DreamObject Options</h2>
        <form method="post" action="options.php">
            <?php settings_fields('dreamobj_settings_key'); ?>
            <?php $options = get_option('dreamobj_settings'); ?>
            <table class="form-table">
                <tr valign="top"><th scope="row">A Checkbox</th>
                    <td><input name="dreamobj_setting[option1]" type="checkbox" value="1" <?php checked('1', $options['option1']); ?> /></td>
                </tr>
                <tr valign="top"><th scope="row">Some text</th>
                    <td><input type="text" name="dreamobj_setting[sometext]" value="<?php echo $options['sometext']; ?>" /></td>
                </tr>
            </table>
            <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>
    </div>
    <?php   
}