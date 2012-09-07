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

// Adding options
add_action('admin_init', 'dreamobj_activate' );

function dreamobj_activate() {
    register_setting( 'dreamobj_settings_key', 'dreamobj_settings' );
    register_setting( 'dreamobj_settings_secretkey', 'dreamobj_settings' );
    register_setting( 'dreamobj_settings_cannonicalid', 'dreamobj_settings' );
    register_setting( 'dreamobj_settings_cannonicalname', 'dreamobj_settings' );
}



// Menu Pages
add_action('admin_menu', 'dreamobj_menu');

function dreamobj_menu() {
    add_management_page( 'DreamObjects', 'DreamObjects', 'manage_options', 'dreamobjects', 'dreamobj_options');
}

// Draw the menu page itself
function dreamobj_options() {
        ?>
        <div class="wrap">
        
        <div id="icon-edit-comments" class="icon32"></div>
        <h2><?php _e("DreamObjects", dreamobjects); ?></h2>

        <?php
        
                if (isset($_POST['update']))
                {
                // Update the Keys
                    if ($AWS_KEYNEW = $_POST['AWS_KEYNEW'])
                    { update_option('dreamobj_settings_key', $AWS_KEYNEW);}
                    if ($AWS_SECRET_KEYNEW = $_POST['AWS_SECRET_KEYNEW'])
                    { update_option('dreamobj_settings_secretkey', $AWS_SECRET_KEYNEW);}
                    if ($AWS_CANONICAL_IDNEW = $_POST['AWS_CANONICAL_IDNEW'])
                    { update_option('dreamobj_settings_cannonicalid', $AWS_CANONICAL_IDNEW);}
                    if ($AWS_CANONICAL_NAMENEW = $_POST['AWS_CANONICAL_NAMENEW'])
                    { update_option('dreamobj_settings_cannonicalname', $AWS_CANONICAL_NAMENEW);}
        ?>
                <div id="message" class="updated fade"><p><strong><?php _e('Options Updated!', dreamobjects); ?></strong></p></div>

<?php   } 
    
    $AWS_KEY = get_option('dreamobj_settings_key');
    $AWS_SECRET_KEY = get_option('dreamobj_settings_secretkey');
    $AWS_CANONICAL_ID = get_option('dreamobj_settings_cannonicalid');
    $AWS_CANONICAL_NAME = get_option('dreamobj_settings_cannonicalname');

?>

<h3>Configuration</h3>

<p>Enter your secret information here.</p>

        <form method="post" width='1'>
        <fieldset class="options">
        <p><strong><?php _e('Key', dreamobjects); ?>:</strong> <input type="text" name="AWS_KEYNEW" value="<?php echo $AWS_KEY; ?>"/></p>
        <p><strong><?php _e('Secret Key', dreamobjects); ?>:</strong> <input type="text" name="AWS_SECRET_KEYNEW" value="<?php echo $AWS_SECRET_KEY; ?>"/></p>
        <p><strong><?php _e('Canonical ID', dreamobjects); ?>:</strong> <input type="text" name="AWS_CANONICAL_IDNEW" value="<?php echo $AWS_CANONICAL_ID; ?>"/></p>
        <p><strong><?php _e('Canonical Name', dreamobjects); ?>:</strong> <input type="text" name="AWS_CANONICAL_NAMENEW" value="<?php echo $AWS_CANONICAL_NAME; ?>"/></p>
        </fieldset>
        
        <p class="submit"><input class='button-primary' type='submit' name='update' value='<?php _e("Update Options", dreamobjects); ?>' id='submitbutton' /></p>
        </form>

<h3>Conjunction Junction</h3>

<p>This is where I need error handling - If the connection fails, stop!</p>

<?php
// Static DH Info
$HOST = 'objects.dreamhost.com';
define('AWS_KEY', $AWS_KEY);
define('AWS_SECRET_KEY', $AWS_SECRET_KEY);
define('AWS_CANONICAL_ID', $AWS_CANONICAL_ID);
define('AWS_CANONICAL_NAME', $AWS_CANONICAL_NAME);

// require the amazon sdk for php library
require_once 'AWSSDKforPHP/sdk.class.php';

// Instantiate the S3 class and point it at the desired host

$Connection = new AmazonS3(array('key'=>$AWS_KEY,'secret'=>$AWS_SECRET_KEY,'certificate_authority'=>true));
$Connection->set_hostname($HOST);
$Connection->allow_hostname_override(false);

// Set the S3 class to use objects.dreamhost.com/bucket
// instead of bucket.objects.dreamhost.com
$Connection->enable_path_style();

?>

<h3>Buckets</h3>

<p>If all of the above was done correctly, show mah buckets!</p>

<ul>
<?php
$ListResponse = $Connection->list_buckets();
$Buckets = $ListResponse->body->Buckets->Bucket;
foreach ($Buckets as $Bucket) {
        echo "<li>" . $Bucket->Name . "\t" . $Bucket->CreationDate . "</li>\n";
}
?>
</ul>
        
        </div> <?php
        }