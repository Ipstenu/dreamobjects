<?php

/* 
Copyright 2012 Mika Epstein (email: ipstenu@ipstenu.org)

    This file is part of DreamObjects, a plugin for WordPress.

    DreamObjects is free software: you can redistribute it and/or modify
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

// Draw the menu page itself

        ?>
<div class="wrap">
        
<div id="icon-dreamobjects" class="icon32"></div>
<h2><?php _e("DreamObjects", dreamobjects); ?></h2>

<h2><?php _e("Configuration", dreamobjects); ?></h2>

<?php
    if (isset($_POST['update'])){
        // Update the Keys
        if ($DH_DO_KEYNEW = $_POST['DH_DO_KEYNEW'])
        { update_option('dreamobj_settings_key', $DH_DO_KEYNEW);}
        if ($DH_DO_SECRET_KEYNEW = $_POST['DH_DO_SECRET_KEYNEW'])
        { update_option('dreamobj_settings_secretkey', $DH_DO_SECRET_KEYNEW);}
        if ($DH_DO_BUCKETNEW = $_POST['DH_DO_BUCKETNEW'])
        { update_option('dreamobj_settings_bucket', $DH_DO_BUCKETNEW);}
        ?>
        <div id="message" class="updated fade"><p><strong><?php _e('Options Updated!', dreamobjects); ?></strong></p></div>
        <?php   } 
    
$DH_DO_KEY = get_option('dreamobj_settings_key');
$DH_DO_SECRET_KEY = get_option('dreamobj_settings_secretkey');
$DH_DO_BUCKET = get_option('dreamobj_settings_bucket');
?>

<h3><?php _e('Configuration', dreamobjects); ?></h3>

<p><?php _e('Configure WP to converse with DreamHost DreamObjects.', dreamobjects); ?></p>

<form method="post" width='1'>
<fieldset class="options">
<table class="form-table">
    <tbody>
        <tr valign="top">
            <th scope="row"><label for="DH_DO_KEYNEW"><?php _e('Key', dreamobjects); ?></label></th>
            <td><input type="text" name="DH_DO_KEYNEW" value="<?php echo $DH_DO_KEY; ?>" class="regular-text"/>
            <p class="description">This is your public key.</p></td>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="DH_DO_SECRET_KEYNEW"><?php _e('Secret Key', dreamobjects); ?></label></th>
            <td><input type="text" name="DH_DO_SECRET_KEYNEW" value="<?php echo $DH_DO_SECRET_KEY; ?>" class="regular-text"/>
            <p class="description">This is your secret key.</p></td>
        </tr>
<?php

// This mess is for your connections.

// Static DH Info
$HOST = 'objects.dreamhost.com';

// require the amazon sdk for php library
require_once 'AWSSDKforPHP/sdk.class.php';

// Instantiate the S3 class and point it at the desired host

$Connection = new AmazonS3(array('key'=>$DH_DO_KEY,'secret'=>$DH_DO_SECRET_KEY,'certificate_authority'=>true));
$Connection->set_hostname($HOST);
$Connection->allow_hostname_override(false);

// Set the S3 class to use objects.dreamhost.com/bucket
// instead of bucket.objects.dreamhost.com
$Connection->enable_path_style();

// Get the buckets
$ListResponse = $Connection->list_buckets();

if (!$ListResponse->isOK()) {
    // If we can't get buckets, bailout
    ?>
    <div id="message" class="error">
    <h3><?php _e('Could not connect to DreamHost!', dreamobjects ); ?></h3>
    <p><?php _e('Please double check your keys, as they\'re giving us horrible errors.', dreamobjects); ?></p>
    </div><?php
} else {

    // We're in!  Now to work....
    $Buckets = $ListResponse->body->Buckets->Bucket;
    ?>
    <tr valign="top">
    <th scope="row"><label for="DH_DO_BUCKETNEW"><?php _e('Select your Bucket', dreamobjects); ?></label></th>
    <td><select name="DH_DO_BUCKETNEW">
        <option value="-1">(none)</option><?php
    foreach ($Buckets as $Bucket) {
    
        if ( $Bucket->Name == $DH_DO_BUCKET ) { $dh_do_bucketselect="selected='selected'"; } else {$dh_do_bucketselect= '';}
        echo "<option value='" . $Bucket->Name . "' " . $dh_do_bucketselect . ">" . $Bucket->Name . "</strong>\t(" . $Bucket->get_bucket_object_count . " Objects)</option>\n";
    } ?></select>
    </td>
    </tr>
    <?php
}
?>
</tbody>
</table>
        </fieldset>
        
        <p class="submit"><input class='button-primary' type='submit' name='update' value='<?php _e("Update Options", dreamobjects); ?>' id='submitbutton' /></p>
        </form>
<?php
    if ( $ListResponse->isOK() && !is_null($DH_DO_BUCKET)) {

        ?><h3><?php _e('Saved Backups', dreamobjects); ?></h3>
        <p><?php _e('Here are all the objects in your bucket.', dreamobjects); ?></p>

        <ul><?php

            $ObjectsListResponse = $Connection->list_objects($DH_DO_BUCKET);
            $Objects = $ObjectsListResponse->body->Contents;
            foreach ($Objects as $Object) {
                echo "<li>" . $Object->Key . "\t" . $Object->Size . "\t" . $Object->LastModified . "</li>\n";
            }
        ?></ul><?php

        }
?>
        </div>