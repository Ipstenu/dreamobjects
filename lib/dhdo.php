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

use Aws\S3\S3Client as AwsS3DHDO;

use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\S3\Exception\S3Exception;


class DHDO {
    // INIT - hooking into this lets us run things when a page is hit.

	public function __construct() {
		// Do we need the AWS stuff?
		if (false === class_exists('Symfony\Component\ClassLoader\UniversalClassLoader', false)) {
			require_once DHDO_PLUGIN_DIR.'aws/aws-autoloader.php';
		}
	}

    public static function init() {

        // SCHEDULER
        if ( isset($_POST['dh-do-schedule']) && current_user_can('manage_options') ) {
            wp_clear_scheduled_hook('dh-do-backup');
            if ( $_POST['dh-do-schedule'] != 'disabled' ) {
                wp_schedule_event(current_time('timestamp',true)+86400, $_POST['dh-do-schedule'], 'dh-do-backup');
                $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-schedule' ) ), get_option('time_format') );
                $nextbackup = sprintf(__('Next backup: %s', dreamobjects), $timestamp );
                DHDO::logger('Scheduled '.$_POST['dh-do-schedule'].' backup. ' .$nextbackup);
            }
        }

        // RESET
        if ( current_user_can('manage_options') && isset($_POST['dhdo-reset']) && $_POST['dhdo-reset'] == 'Y'  ) {
            delete_option( 'dh-do-backupsection' );
            delete_option( 'dh-do-boto' );
            delete_option( 'dh-do-bucket' );
            delete_option( 'dh-do-key' );
            delete_option( 'dh-do-schedule' );
            delete_option( 'dh-do-secretkey' );
            delete_option( 'dh-do-section' );
            delete_option( 'dh-do-logging' );
            DHDO::logger('reset');
           }

        // LOGGER: Wipe logger if blank
        if ( current_user_can('manage_options') && isset($_POST['dhdo-logchange']) && $_POST['dhdo-logchange'] == 'Y' ) {
            if ( !isset($_POST['dh-do-logging'])) {
                DHDO::logger('reset');
            }
        }       
        
        // UPDATE OPTIONS
        if ( isset($_GET['settings-updated']) && isset($_GET['page']) && ( $_GET['page'] == 'dreamobjects-menu' || $_GET['page'] == 'dreamobjects-menu-backup' ) ) add_action('admin_notices', array('DHDOMESS','updateMessage'));

        // BACKUP ASAP
        if ( current_user_can('manage_options') &&  isset($_GET['backup-now']) && $_GET['page'] == 'dreamobjects-menu-backup' ) {
            wp_schedule_single_event( current_time('timestamp', true)+60, 'dh-do-backupnow');
            add_action('admin_notices', array('DHDOMESS','backupMessage'));
            DHDO::logger('Scheduled ASAP backup in 60 seconds.' );
        }
        
        // BACKUP
        if ( wp_next_scheduled( 'dh-do-backupnow' ) && ( $_GET['page'] == 'dreamobjects-menu' || $_GET['page'] == 'dreamobjects-menu-backup' ) ) {
            add_action('admin_notices', array('DHDOMESS','backupMessage'));
        }
    }

    // Returns the URL of the plugin's folder.
    function getURL() {
        return plugins_url() . '/';
    }
   

    /**
     * Logging function
     *
     */

    // Acutal logging function
    public static function logger($msg) {
    
    if ( get_option('dh-do-logging') == 'on' ) {
           $file = DHDO_PLUGIN_DIR."/debug.txt"; 
           if ($msg == "reset") {
               $fd = fopen($file, "w+");
               $str = "";
           }
           elseif ( get_option('dh-do-logging') == 'on') {    
               $fd = fopen($file, "a");
               $str = "[" . date("Y/m/d h:i:s", current_time('timestamp')) . "] " . $msg . "\n";
           }
              fwrite($fd, $str);
              fclose($fd);
          }
    }

    /**
     * Generate Backups and the functions needed for that to run
     *
     */
        
    // Scan folders to collect all the filenames
    function rscandir($base='') {
        $data = array_diff(scandir($base), array('.', '..', '/cache/') );
    
        $subs = array();
        foreach($data as $key => $value) :
            if ( is_dir($base . '/' . $value) ) :
                unset($data[$key]);
                $subs[] = DHDO::rscandir($base . '/' . $value);
            elseif ( is_file($base . '/' . $value) ) :
                $data[$key] = $base . '/' . $value;
            endif;
        endforeach;
    
        foreach ( $subs as $sub ) {
            $data = array_merge($data, $sub);
        }
        return $data;
        DHDO::logger('Scanned folders and files to generate list for backup.');
    }
    
    // The actual backup
    function backup() {
        DHDO::logger('Begining Backup.');
        global $wpdb;
        require_once(ABSPATH . '/wp-admin/includes/class-pclzip.php');

        // Pull in data for what to backup
        $sections = get_option('dh-do-backupsection');
        if ( !$sections ) {
            $sections = array();
        }
        
        $file = WP_CONTENT_DIR . '/upgrade/dreamobject-backups.zip';
        $fileurl = content_url() . '/upgrade/dreamobject-backups.zip';
        $zip = new PclZip($file);
        $backups = array();

        // All me files!
        if ( in_array('files', $sections) ) {
            $backups = array_merge($backups, DHDO::rscandir(WP_CONTENT_DIR));
            DHDO::logger('Files in wp-content added to backup list.');
        } 
        
        // And me DB!
        if ( in_array('database', $sections) ) {
            set_time_limit(300);
            $tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->prefix . "%'");
            $result = shell_exec('mysqldump --single-transaction -h ' . DB_HOST . ' -u ' . DB_USER . ' --password="' . DB_PASSWORD . '" ' . DB_NAME . ' ' . implode(' ', $tables) . ' > ' .  WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql');
            $sqlfile = WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql';
            $sqlsize = size_format( @filesize($sqlfile) );
            DHDO::logger('SQL file created: '. $sqlfile .' ('. $sqlsize .').');
            $backups[] = $sqlfile;
            DHDO::logger('SQL added to backup list.');
        }
        
        if ( !empty($backups) ) {
            set_time_limit(300);  // Increased timeout to 5 minutes. If the zip takes longer than that, I have a problem.
            DHDO::logger('Creating zip file...');
            DHDO::logger('NOTICE: If the log stops here, PHP failed to create a zip of your wp-content folder. Please consider cleaning out unused files (like plugins and themes), or increasing the server\'s PHP memory, RAM or CPU.');
            $zip->create($backups);
            DHDO::logger('Calculating zip file size ...');
            $zipsize = size_format( @filesize($file) );
            DHDO::logger('Zip file generated: '. $file .' ('. $zipsize .').');
            
            // Upload

		  	$s3 = AwsS3DHDO::factory(array(
				'key'      => get_option('dh-do-key'),
			    'secret'   => get_option('dh-do-secretkey'),
			    'base_url' => 'http://objects.dreamhost.com',
			));

            $bucket = get_option('dh-do-bucket');
            $parseUrl = parse_url(trim(home_url()));
            $url = $parseUrl['host'];
            if( isset($parseUrl['path']) ) 
                { $url .= $parseUrl['path']; }
            
            // Rename file
            $newname = $url.'/'.date_i18n('Y-m-d-His', current_time('timestamp')) . '.zip';
            DHDO::logger('New filename '. $newname .'.');

			// Uploading
            set_time_limit(180);

			DHDO::logger('Begining upload to DreamObjects servers.');

			// Check the size of the file before we upload, in order to compensate for large files
			if ( @filesize($file) >= (100 * 1024 * 1024) ) {

				// Files larger than 100megs go through Multipart
				DHDO::logger('Filesize is over 100megs, using Multipart uploader.');
				
				// High Level
				DHDO::logger('Prepare the upload parameters.');
				
				$uploader = UploadBuilder::newInstance()
				    ->setClient($s3)
				    ->setSource($file)
				    ->setBucket($bucket)
				    ->setKey($newname)
				    ->setMinPartSize(25 * 1024 * 1024)
				    ->setOption('Metadata', array(
				        'UploadedBy' => 'DreamObjectsBackupPlugin'
				    ))
				    ->setOption('ACL', 'private')
				    //->setOption('ContentType', 'application/zip')
				    ->setConcurrency(3)
				    ->build();
				
				DHDO::logger('Perform the upload. Abort the upload if something goes wrong.');
				try {
				    $uploader->upload();
				    DHDO::logger('Upload complete');
				} catch (MultipartUploadException $e) {
				    $uploader->abort();
				    DHDO::logger('Upload failed: '.$e->getMessage() );
				    DHDO::logger( $e );
				}
				
/*
				// Lowlevel

				// 2. Create a new multipart upload and get the upload ID.
				$result = $s3->createMultipartUpload(array(
				    'Bucket'       => $bucket,
				    'Key'          => $newname,
				    'ACL'          => 'private',
				    'ContentType'  => 'application/zip',
				    'Metadata'     => array(
				        'UploadedBy' => 'DreamObjectsBackupPlugin',
				        'UploadedDate' => date_i18n('Y-m-d-His', current_time('timestamp'))
				    )
				));
				$uploadId = $result['UploadId'];
	
				// 3. Upload the file in parts.
				try {    
				    $uploadfile = fopen($file, 'r');
				    if ( $uploadfile === false ) {
				    	DHDO::logger('Error: Zip not found.');
				    } else {
				    	
				    	$chunkSize = (5 * 1024 * 1024);
					    $parts = array();
					    $partNumber = 1;
					    
					    $part_counts = getMultipartCounts(filesize($file), $chunkSize );
					    
					    while (!feof($uploadfile)) {
					        $result = $s3->uploadPart(array(
					            'Body'        => fread($uploadfile, $chunkSize ),
					            'Bucket'      => $bucket,
					            'Key'         => $newname,
					            'PartNumber'  => $partNumber,
					            'UploadId'    => $uploadId,
					        ));
		
					        DHDO::logger('Adding part #'.$partNumber.' of '.$part_counts.' to multipart upload.');
		
					        $parts[] = array(
					            'PartNumber' => $partNumber++,
					            'ETag'       => $result['ETag'],
					        );
					        
					    }
					    fclose($uploadfile);
					    
					    DHDO::logger('All parts added to multipart. Preparing to upload ...');
					}
				    
				} catch (S3Exception $e) {
				    $result = $s3->abortMultipartUpload(array(
				        'Bucket'   => $bucket,
				        'Key'      => $newname,
				        'UploadId' => $uploadId
				    ));
				
				    DHDO::logger('Multipart upload aborted. '. $e );
				}
				
				// 4. Complete multipart upload.
				try {
					$result = $s3->completeMultipartUpload(array(
					    'Bucket'   => $bucket,
					    'Key'      => $newname,
					    'Parts'    => $parts,
					    'UploadId' => $uploadId,
					));
					$url = $result['Location'];
	
					DHDO::logger('Multipart upload complete'. $url);
				} catch (Exception $e) {
				    DHDO::logger('Multipart upload unable to complete: '. $e );
				}
*/
			} else {
				// If it's under 100megs, do it the old way
				DHDO::logger('Filesize is under 100megs. This will be less spammy.');
				
				set_time_limit(180); // 3 min 
				try {
					$result = $s3->putObject(array(
					    'Bucket'       => $bucket,
					    'Key'          => $newname,
					    'SourceFile'   => $file,
					    'ContentType'  => 'application/zip',
					    'ACL'          => 'private',
					    'Metadata'     => array(
					        'UploadedBy'   => 'DreamObjectsBackupPlugin',
					        'UploadedDate' => date_i18n('Y-m-d-His', current_time('timestamp')),
					    )
					));
					DHDO::logger('Upload complete');
				} catch (S3Exception $e) {
				    DHDO::logger('Upload failed: '. $e->getMessage() );
				}
			}

            // Cleanup
            if(file_exists($file)) { 
                @unlink($file);
                DHDO::logger('Deleting zip file: '.$file.' ...');
            }
            if(file_exists($sqlfile)) { 
                @unlink($sqlfile);
                DHDO::logger('Deleting SQL file: '.$sqlfile.' ...');
            }
        }
        
        // Cleanup Old Backups
        DHDO::logger('Checking for backups to be deleted.');
        if ( $backup_result = 'Yes' && get_option('dh-do-retain') && get_option('dh-do-retain') != 'all' ) {
            $num_backups = get_option('dh-do-retain');

		  	$s3 = AwsS3DHDO::factory(array(
				'key'      => get_option('dh-do-key'),
			    'secret'   => get_option('dh-do-secretkey'),
			    'base_url' => 'http://objects.dreamhost.com',
			));

            $bucket = get_option('dh-do-bucket');
            
            $parseUrl = parse_url(trim(home_url()));
            $prefixurl = $parseUrl['host'];
            if( isset($parseUrl['path']) ) 
                { $prefixurl .= $parseUrl['path']; }
            
            $backups = $s3->getIterator('ListObjects', array('Bucket' => $bucket, "Prefix" => $prefixurl ) );
            
            if ($backups !== false) {
                krsort($backups);
                $count = 0;
                foreach ($backups as $object) {
                    if ( ++$count > $num_backups ) {
                        $s3->deleteObject( array(
                        	'Bucket' => $bucket,
                        	'Key'    => $object['Key'],
                        ));
                        DHDO::logger('Removed backup '. $object['Key'] .' from DreamObjects, per user retention choice.');
                    }    
                }
            }
        } else {
	        DHDO::logger('Per user retention choice, not deleteing a single old backup.');
        }
        DHDO::logger('Backup Complete.');
        DHDO::logger('');
    }
    function cron_schedules($schedules) {
        $schedules['daily'] = array('interval'=>86400, 'display' => 'Once Daily');
        $schedules['weekly'] = array('interval'=>604800, 'display' => 'Once Weekly');
        $schedules['monthly'] = array('interval'=>2592000, 'display' => 'Once Monthly');
        return $schedules;
    }
}