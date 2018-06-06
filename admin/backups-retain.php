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

if (!defined( 'ABSPATH' )) { die(); }

use Aws\S3\S3Client;

global $wpdb;

DreamObjects_Core::install();
$dreamobjects_table_name = $wpdb->prefix . 'dreamobjects_backup_log';
$frequency               = get_option( 'dh-do-notify' );
$total                   = get_option( 'dh-do-retain' );
$showbackups             = TRUE;
$emptybackups            = FALSE;

?><h3>Recent Backup Status</h3><?php

if ( get_option( 'dh-do-notify' ) === 'all' ) { 
	?><p><?php echo __( 'Showing all backups on the cloud is a little crazy and kills servers. You\'ll need to go to your panel.', 'dreamobjects' ); ?></p><?php
} elseif ( $frequency === 'disabled' ) {
	?><p><?php echo __( 'You have disabled status notifications. If you just want to see successful backups, chose that.', 'dreamobjects' ); ?></p><?php	
} else {
	
	if ( $frequency === 'all' ) {
		$statusmatch = $wpdb->get_results( "SELECT * FROM '.$dreamobjects_table_name.';" );
	} else {
		$statusmatch = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$dreamobjects_table_name} WHERE frequency LIKE %s;", $frequency ) );
	}

	if ( empty( $statusmatch ) || $frequency == 'failure' ) {
		$showbackups = FALSE;
	}

	if ( $showbackups ) {

		$statusshow = array_slice($statusmatch, -$total);  // returns last "total" items.
		$timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', (time()+600) ), get_option( 'time_format' ) );
		$linksvalid_string = sprintf( __( 'Links are valid until %s (aka 10 minutes from page load). After that time, you need to reload this page.', 'dreamobjects' ), $timestamp );
	
		try {
			$s3 = new S3Client( DreamObjects_Core::$s3Options );
		} catch ( \Aws\S3\Exception\S3Exception $e) {
			echo $e->getAwsErrorCode() . "\n";
			echo $e->getMessage() . "\n";
			$emptybackups = TRUE;
		}
	
		$bucket  = get_option( 'dh-do-bucket' );
		$homeurl = home_url();
		$prefix  = explode( '//', $homeurl );
		$prefix  = next( $prefix );
		$maxkeys = get_option( 'dh-do-retain' ) + 1;
	
		try {
			$backups = $s3->listObjectsV2( array( 'Bucket' => $bucket, 'Prefix' => $prefix, 'MaxKeys' => $maxkeys, ) );
			$backupsarray = $backups->toArray();
		} catch ( S3Exception $e ) {
			$emptybackups = TRUE;
		}

		if ( empty( $backupsarray ) || !array_key_exists( 'Contents', $backupsarray ) || count( $backupsarray['Contents'] ) <= 1 ) {
			$emptybackups = TRUE;
		}
	} else {
		$emptybackups = TRUE;
	}

	if ( $emptybackups ) {
		echo __( 'There are no backups currently stored. Why not run a backup now?', 'dreamobjects' );
	} else {
		?><p><?php echo __( 'All backups can be downloaded from this page without logging in to DreamObjects.', 'dreamobjects' ); ?></p>
		<p><?php echo $linksvalid_string; ?></p><?php

		foreach( $statusshow as $key => $value ) {
			?><p><?php echo $value->text;
			
			if ( $showbackups === TRUE && $value->frequency === 'success' ) {
				foreach ( $backups['Contents'] as $backup ) {
					if ( $value->filename === $backup['Key'] ) {
						echo '<br />'. __( 'Download:', 'dreamobjects' ) .' <a href="'.$s3->getObjectUrl($bucket, $backup['Key'], '+10 minutes' ).'">'.$backup['Key'] .'</a> - '.size_format($backup['Size']);	
					}
				}
			}
			?></p><?php 
		}
	}

	// If there are no backups and the logs are empty, use the old way
	if ( !$showbackups && !$emptybackups ) {
		echo '<ol>';
		foreach ($backups as $backup) {
			echo '<li><a href="'.$s3->getObjectUrl($bucket, $backup['Key'], '+10 minutes' ).'">'.$backup['Key'] .'</a> - '.size_format($backup['Size']).'</li>';
		}
		echo '</ol>';
	}
}