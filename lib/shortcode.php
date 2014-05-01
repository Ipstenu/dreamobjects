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

/* Register shortcodes. */
add_action( 'init', 'dreamobjects_add_shortcodes' );

function dreamobjects_add_shortcodes() {
    add_shortcode( 'dreamobjects', 'dreamobjects_func' );
}


// [dreamobjects folder="folder" prefix="prefix"]
function dreamobjects_func( $atts ) {
	extract( shortcode_atts( array(
		'folder' => 'XXXX', // $folder
		'prefix' => 'XXXX', // $prefix
	), $atts ) );

	// Prefix - http://ceph.com/docs/master/radosgw/s3/bucketops/
	$nobucket = "<p><strong>".__('Error!', dreamobjects)."</strong> ".__('DreamObjects Bucket not available.', dreamobjects)."</p>";
/*
	if ( is_null({$prefix}) ) {

		if folder is empty AND options are not set then use prefix
		
		if folder is set, use that
		
		if folder is empty and options are set, use that
		
		if nothing is set OR if both prefix and folder are set, fail.
		
		FWIW, none of this really works yet
*/
	if ( $prefix = "XXXX") {

	    if ( $folder != "XXXX") {
	    	$bucket = $folder; 
	    }
	    elseif ( get_option('dh-do-bucketup') && (get_option('dh-do-bucketup') != "XXXX") && !is_null(get_option('dh-do-bucketup')) ) {
	    	$bucket = get_option('dh-do-bucketup');
	    }
	    else {
	    	return $nobucket;
	    }

        $s3 = new AmazonS3( array('key' => get_option('dh-do-key'), 'secret' => get_option('dh-do-secretkey')) );
    	$s3->set_hostname('objects.dreamhost.com');
    	$s3->allow_hostname_override(false);
    	$s3->enable_path_style();
        $bucket = get_option('dh-do-bucketup');
        $uploads = $s3->get_object_list( $bucket );
        $return = '<ul>';
    		if (($uploads = $s3->get_object_list( $bucket ) ) !== false) {
        		krsort($uploads);
                foreach ($uploads as $object) {
                    $objecturl = $s3->get_object_url( $bucket , $object );
                    $return .= '<li><a href="'. $objecturl .'">'. $object .'</a></li>';
                }
            }
        $return .= '</ul>';
        return $return;
	} elseif ( $prefix != "XXXX") {
	
		// Do Prefix Checks here
		return "This is a temp file";
		
	} else {
	    return $nobucket;
	}

}