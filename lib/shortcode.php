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


// [dreamobjects show="uploads|backups" role="rolename"]
function dreamobjects_func( $atts ) {
	extract( shortcode_atts( array(
		'role' => 'author',
		'show' => 'uploads',
	), $atts ) );

	//return "foo = {$foo}";


//if ($show = 'uploads') {

    if ( get_option('dh-do-bucketup') && (get_option('dh-do-bucketup') != "XXXX") && !is_null(get_option('dh-do-bucketup')) ) {
        include_once( PLUGIN_DIR. '/lib/S3.php');

        $s3 = new S3(get_option('dh-do-key'), get_option('dh-do-secretkey'));
        $bucket = get_option('dh-do-bucketup');
        
        $return = '<ul>';
            if (($uploads = $s3->getBucket( $bucket ) ) !== false) {
                krsort($uploads);
                foreach ($uploads as $object) {
                $return .= "<li><a href=\"https://objects.dreamhost.com/".$bucket."/".$object['name']."\">".$object['name']."</a></li>";
                }
            }
        $return .= '</ul>';       

        return $return;
    } 
// }
}
