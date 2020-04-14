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

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

use Aws\S3\S3Client;

global $wpdb;

DreamObjects_Core::install();
$dreamobjects_table_name = $wpdb->prefix . 'dreamobjects_backup_log';
$frequency               = get_option( 'dh-do-notify' );
$total                   = get_option( 'dh-do-retain' );
$no_show                 = array( 'all', 'failure' );
$showbackups             = ( in_array( $frequency, $no_show ) || ( 'all' == $total ) ) ? false : true;

echo '<h3>' . esc_html__( 'Recent Backup Status', 'dreamobjects' ) . '</h3>';

if ( 'disabled' === $frequency ) {
	echo '<p>' . esc_html__( 'You have disabled status notifications. If you only want to see successful backups, chose that.', 'dreamobjects' ) . '</p>';
} else {
	if ( 'all' === $frequency ) {
		$statusmatch = $wpdb->get_results( "SELECT * FROM {$dreamobjects_table_name}" );
	} else {
		$statusmatch = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$dreamobjects_table_name} WHERE frequency LIKE %s;", $frequency ) );
	}

	// If we have backups, we can go!
	if ( ! empty( $statusmatch ) ) {
		$statusshow = array_slice( $statusmatch, -$total );  // returns last "total" items.

		// We don't show backup links for certain cases
		if ( $showbackups ) {
			$emptybackups = false;
			$timestamp  = get_date_from_gmt( date( 'Y-m-d H:i:s', ( time() + 600 ) ), get_option( 'time_format' ) );
			// translators: %s is the time the links expire.
			$linksvalid_string = sprintf( __( 'Links are valid until %s (aka 10 minutes from page load). After that time, you need to reload this page.', 'dreamobjects' ), $timestamp );

			try {
				$s3 = new S3Client( DreamObjects_Core::$s3_options );
			} catch ( \Aws\S3\Exception\S3Exception $e ) {
				echo wp_kses_post( $e->getAwsErrorCode() . "\n" );
				echo wp_kses_post( $e->getMessage() . "\n" );
				$emptybackups = true;
			}

			$bucket  = get_option( 'dh-do-bucket' );
			$homeurl = home_url();
			$prefix  = explode( '//', $homeurl );
			$prefix  = next( $prefix );
			$maxkeys = get_option( 'dh-do-retain' ) + 1;

			try {
				$backups      = $s3->listObjectsV2( array(
					'Bucket'  => $bucket,
					'Prefix'  => $prefix,
					'MaxKeys' => $maxkeys,
				) );
				$backupsarray = $backups->toArray();
			} catch ( S3Exception $e ) {
				$emptybackups = true;
			}

			if ( empty( $backupsarray ) || ! array_key_exists( 'Contents', $backupsarray ) || count( $backupsarray['Contents'] ) <= 1 ) {
				$emptybackups = true;
			}
		}

		// If we have backups to show, we have extra info.
		if ( $showbackups && ! $emptybackups ) {
			echo '<p>' . esc_html__( 'All backups can be downloaded from this page without logging in to DreamObjects.', 'dreamobjects' ) . ' ' . esc_html( $linksvalid_string ) . '</p>';
		} elseif ( $showbackups && $emptybackups ) {
			echo '<p>' . esc_html__( 'While backups have been made, they are not available to be downloaded directly. Please log in to your DreamHost panel to download your backups.', 'dreamobjects' ) . '</p>';
		}
		echo '<ul>';
		foreach ( $statusshow as $key => $value ) {
			echo '<li><strong>' . esc_html( $value->text ) . '</strong>';
			// we're only going to show a link if it was a success and we can find the file.
			if ( $showbackups && 'success' === $value->frequency && ! $emptybackups ) {
				foreach ( $backups['Contents'] as $backup ) {
					if ( $value->filename === $backup['Key'] ) {
						$bucket_size = size_format( $backup['Size'] );
						$bucket_data = $s3->getCommand('GetObject', [
							'Bucket' => $bucket,
							'Key'    => $backup['Key'],
						]);
						$bucket_url  = $s3->createPresignedRequest( $bucket_data, '+10 minutes' );
						$bucket_link = (string) $bucket_url->getUri();
						echo '<br />' . esc_html__( 'Download:', 'dreamobjects' ) . ' <a href="' . esc_url_raw( $bucket_link ) . '">' . esc_html( $backup['Key'] ) . '</a> - ' . esc_html( $bucket_size );
					}
				}
			}
			echo '</li>';
		}
		echo '</ul>';
	} elseif ( 'failure' === $frequency ) {
		echo '<p>' . esc_html__( 'Congratulations! There are no logged failures.', 'dreamobjects' ) . '</p>';
	} else {
		echo '<p>' . esc_html__( 'There are no backups currently found on the server. Why not run a backup now?', 'dreamobjects' ) . '</p>';
	}
}
