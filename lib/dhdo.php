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
use Guzzle\Plugin\Log\LogPlugin; // DEBUGGING ONLY

class DHDO {

	const DIRECTORY_SEPARATORS = '/\\';

    // INIT - hooking into this lets us run things when a page is hit.
    public static function init() {

        // The Scheduler
        if ( isset($_POST['dh-do-schedule']) && current_user_can('manage_options') ) {
	        
	        check_admin_referer('dh-do-backuper-settings-options');
            wp_clear_scheduled_hook('dh-do-backup');
            
            $do_schedule = sanitize_text_field($_POST['dh-do-schedule']);
            
            if ( $do_schedule != 'disabled' ) {
                wp_schedule_event(current_time('timestamp',true)+86400, $do_schedule, 'dh-do-backup');
                $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'dh-do-schedule' ) ), get_option('time_format') );
                $scheduledbackup = sprintf( __('Scheduled %s backup.', 'dreamobjects' ), $do_schedule );
                $nextbackup = sprintf( __('Next backup: %s', 'dreamobjects' ), $timestamp );
                DHDO::logger( $scheduledbackup.' '.$nextbackup );
            }
        }

        // LOGGER: Wipe logger if blank
        if ( current_user_can('manage_options') && get_option('dh-do-logging') == 'off' ) {
            DHDO::logger('reset');
        }       

        // BACKUP ASAP
        if ( current_user_can('manage_options') && isset($_POST['dh-do-backupnow']) ) {
	        check_admin_referer('dh-do-backupnow-settings-options');
            wp_schedule_single_event( current_time('timestamp', true)+60, 'dh-do-backupnow');
            $message = __('Scheduled ASAP backup in 60 seconds.', 'dreamobjects' );
            DHDO::logger( $message );
        }

    }

    // Returns the URL of the plugin's folder.
    static function getURL() {
        return plugins_url() . '/';
    }
   
    /**
     * Logging function
     *
     */
    public static function logger($msg) {
		$file = DHDO_PLUGIN_DIR."/debug.txt";     
		if ( $msg == "reset" ) {
			$fd = fopen($file, "w+");
			$str = "";
	        fwrite($fd, $str);
	        fclose($fd);
		} elseif ( get_option('dh-do-logging') == 'on' ) {    
            $fd = fopen($file, "a");
            $str = "[" . date("Y/m/d h:i:s", current_time('timestamp')) . "] " . $msg . "\n";
	        fwrite($fd, $str);
	        fclose($fd);
       	}
    }

    /**
     * Status Notifications
     *
     * Updates status log with 
     *
     * @since 4.0
     * @param type $filename  Nicename of the file being saved
     * @param type $message   Content of message
     * @param type $frequency Determination what is shown and when.
     *
     */
    public static function notifier($filename, $message, $frequency) {

		if ( get_option('dh-do-notify') !== 'disabled' ) {
		    global $wpdb;
			$table_name = $wpdb->prefix . 'dreamobjects_backup_log';
			
			$wpdb->insert( 
				$table_name, 
				array( 
					'filename' => $filename, 
					'frequency' => $frequency, 
					'text' => "[" . date("Y/m/d h:i:s", current_time('timestamp')) . "] " . $message, 
				)
			);
		}
    }

    /**
     * Generate Backups and the functions needed for that to run
     *
     */
        
    // Scan folders to collect all the filenames
    function rscandir($base='') {
        $data = array_diff(scandir($base), array('.', '..'));
        $omit = array('\/cache');
    
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
        $message = __('Scanned folders and files to generate list for backup.', 'dreamobjects' );
        DHDO::logger( $message );
    
        foreach( $omit as $omitter ) {
        		$data = preg_grep( $omitter , $data, PREG_GREP_INVERT);
        }
        
        DHDO::logger( print_r($data) );

        return $data;
        $message = __('Scanned folders and files to generate list for backup.', 'dreamobjects' );
        DHDO::logger( $message );
    }
    
    // The actual backup
    function backup() {
        global $wpdb;

        $backupfolder = WP_CONTENT_DIR . '/upgrade/';
        $backuphash = wp_hash( wp_rand() );
        $file = $backupfolder.$backuphash.'-dreamobject-backup.zip';
        $fileurl = content_url() . '/upgrade/dreamobject-backup.zip';

	    $bucket = get_option('dh-do-bucket');
	    $parseUrl = parse_url(trim(home_url()));
	    $url = $parseUrl['host'];
	    if( isset($parseUrl['path']) ) { $url .= $parseUrl['path']; }
	    $filenicename = $url.'/'.date_i18n('Y-m-d-His', current_time('timestamp')) . '.zip';
        
	    $message = __('Beginning Backup', 'dreamobjects' );
        DHDO::logger( $message );

		if (!is_dir( WP_CONTENT_DIR . '/upgrade/' )) {
			$message = __('Upgrade folder missing. This will cause serious issues with WP in general, so we will create it for you.', 'dreamobjects' );
			DHDO::logger( $message );
		    mkdir( WP_CONTENT_DIR . '/upgrade/' );       
		}
        
        // Pull in data for what to backup
        $sections = get_option('dh-do-backupsection');
        if ( !$sections ) {
            $sections = array();
        }

        // Pre-Cleanup
		foreach ( glob ( $backupfolder.'*.zip' ) as $oldzip ) {
			$message = sprintf( __('Leftover zip file found, deleting %s ...', 'dreamobjects' ), $oldzip );
			DHDO::logger( $message );
			@unlink($oldzip);
		}

		try {
			$zip = new ZipArchive( $file );
			$zaresult = true;
			$message = __( 'ZipArchive found and will be used for backups.', 'dreamobjects' );
			DHDO::logger( $message );
		} catch ( Exception $e ) {
			$error_string = $e->getMessage();
			$zip = new PclZip($file);
			$message = sprintf( __('ZipArchive not found. Error:  %s', 'dreamobjects' ), $error_string );
			DHDO::logger( $message );
			$message = __( 'PclZip will be used for backups.', 'dreamobjects' );
			DHDO::logger( $message );
			require_once(ABSPATH . '/wp-admin/includes/class-pclzip.php');
			$zaresult = false;
		}

        $backups = array();

        // All me files!
        if ( in_array('files', $sections) ) {

			$message = __( 'Calculating backup size...', 'dreamobjects' );
			DHDO::logger( $message );

			$trimdisk = WP_CONTENT_DIR ;
			$diskcmd = sprintf("du -s %s", WP_CONTENT_DIR );
			$diskusage = exec( $diskcmd );
			$diskusage = trim(str_replace($trimdisk, '', $diskusage));
			
			DHDO::logger(size_format( $diskusage * 1024 ).' of diskspace will be processed.');
			
			if ($diskusage < ( 2000 * 1024 ) ) {
				$backups = array_merge($backups, DHDO::rscandir(WP_CONTENT_DIR));
				$message = sprintf( __('%d files added to backup list.', 'dreamobjects' ), count($backups) );
				DHDO::logger( $message );
			} else {
				$message = __('ERROR! PHP is unable to backup your wp-content folder. Please consider cleaning out unused files (like plugins and themes).', 'dreamobjects' );
				DHDO::logger( $message );
			}

			if ( file_exists(ABSPATH .'wp-config.php') ) {
		        $backups[] = ABSPATH .'wp-config.php' ;
				$message = __('wp-config.php added to backup list.', 'dreamobjects' );
				DHDO::logger( $message );
		    }

			if ( file_exists(ABSPATH .'.htaccess') ) {
		        $backups[] = ABSPATH .'.htaccess' ;
				$message = __('A copy of .htaccess has been added to backup list.', 'dreamobjects' );
				DHDO::logger( $message );
		    }


        } 
        
        // And me DB!
        if ( in_array('database', $sections) ) {
            set_time_limit(300);
           
			$sqlfile = $backupfolder.$backuphash.'-dreamobjects-backup.sql';
            $tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->prefix . "%'");
            $tables_string = implode( ' ', $tables );

			// Pre cleanup

	        // Pre-Cleanup
			foreach ( glob ( $backupfolder.'*.sql' ) as $oldsql ) {
				$message = sprintf( __('Leftover SQL file found, deleting %s ...', 'dreamobjects' ), $oldsql );
				DHDO::logger( $message );
				@unlink($oldsql);
			}
            
            $dbcmd = sprintf( "mysqldump -h'%s' -u'%s' -p'%s' %s %s --single-transaction 2>&1 >> %s",
            DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, $tables_string, $sqlfile );
            
            exec( $dbcmd );
            
            $sqlsize = size_format( @filesize($sqlfile) );
			$message = sprintf( __('SQL file created: %1$s (%2$s) ...', 'dreamobjects' ), $sqlfile, $sqlsize );
			DHDO::logger( $message );
            $backups[] = $sqlfile;
            $message = __( 'SQL added to backup list.' , 'dreamobjects' );
            DHDO::logger( $message );

        }
        
        if ( !empty($backups) ) {
            set_time_limit(300);  // Increased timeout to 5 minutes. If the zip takes longer than that, I have a problem.
            if ( $zaresult != 'true' ) {
            $message = __( 'Creating zip file using PclZip.' , 'dreamobjects' );
            DHDO::logger( $message );
            $message = __( 'NOTICE: If the log stops here, PHP failed to create a zip of your wp-content folder. Please consider increasing the server\'s PHP memory, RAM or CPU.' , 'dreamobjects' );
            DHDO::logger( $message );
            	$zip->create($backups);

            } else {
            $message = __( 'Creating zip file using ZipArchive.' , 'dreamobjects' );
            DHDO::logger( $message );
            $message = __( 'NOTICE: If the log stops here, PHP failed to create a zip of your wp-content folder. Please consider increasing the server\'s PHP memory, RAM, or CPU.' , 'dreamobjects' );
            DHDO::logger( $message );
	            	try {
		            	$zip->open( $file, ZipArchive::CREATE );
		            	$trimpath =  ABSPATH ;
	
			            foreach($backups as $backupfiles) {
			            	if (strpos( $backupfiles , DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR ) === false) {
				            	$zip->addFile($backupfiles, 'dreamobjects-backup'.str_replace($trimpath, '/', $backupfiles) );
				            	//DHDO::logger( $backupfiles );
				            }
						}
						
						$zip->close();
	            	} catch ( Exception $e ) {
	            		$error_string = $e->getMessage();
	            		$message = sprintf( __('ZipArchive failed to complete: %s', 'dreamobjects' ), $error_string );
					DHDO::logger( $message );
					DHDO::notifier( $filenicename, $message, 'failure' );
	            	}
            }

			if ( @file_exists( $file ) ) { 
            	DHDO::logger('Calculating zip file size ...');
				$zipsize = size_format( @filesize($file) );
				$message = sprintf( __('Zip file created: %1$s (%2$s) ...', 'dreamobjects' ), $filenicename, $zipsize );
				DHDO::logger( $message );
			} else {
				@unlink($file);
				$message = __('Zip file failed to generate. Nothing will be backed up.', 'dreamobjects' );
				DHDO::logger( $message );
				DHDO::notifier( $filenicename, $message, 'failure' );
			}
			
			// Delete SQL
            if(file_exists($sqlfile)) { 
                @unlink($sqlfile);
                $message = sprintf( __('Deleting SQL file: %s', 'dreamobjects' ), $sqlfile );
                DHDO::logger( $message );
            }			
            
            // Upload

			if ( @file_exists( $file ) ) {
	
			  	$s3 = AwsS3DHDO::factory(array(
					'key'      => get_option('dh-do-key'),
				    'secret'   => get_option('dh-do-secretkey'),
				    'base_url' => 'https://'.get_option('dh-do-hostname'),
				));
	
/*
				// https://dreamxtream.wordpress.com/2013/10/29/aws-php-sdk-logging-using-guzzle/
				// This should never be used, but if it has to be commented out, it's a bad day.
				$logPlugin = LogPlugin::getDebugPlugin(TRUE,
				//Don't provide this parameter to show the log in PHP output
					fopen(DHDO_PLUGIN_DIR.'/debug2.txt', 'a')
				);
				$s3->addSubscriber($logPlugin);
*/
	
				// Uploading
	            set_time_limit(180);
				$message = __('Beginning upload to DreamObjects servers.', 'dreamobjects' );
				DHDO::logger( $message );
	
				// Check the size of the file before we upload, in order to compensate for large files
				if ( @filesize($file) >= (100 * 1024 * 1024) ) {
	
					// Files larger than 100megs go through Multipart
					$message = __('File size is over 100megs, using Multipart uploader.', 'dreamobjects' );
					DHDO::logger( $message );
					
					// High Level
					$message = __('Preparing the upload parameters and upload parts in 25M chunks.', 'dreamobjects' );
					DHDO::logger( $message );
					
					$uploader = UploadBuilder::newInstance()
					    ->setClient($s3)
					    ->setSource($file)
					    ->setBucket($bucket)
					    ->setKey($filenicename)
					    ->setMinPartSize(25 * 1024 * 1024)
					    ->setOption('Metadata', array(
					        'UploadedBy' => 'DreamObjectsBackupPlugin',
					        'UploadedDate' => date_i18n('Y-m-d-His', current_time('timestamp'))
					    ))
					    ->setOption('ACL', 'private')
					    ->setConcurrency(3)
					    ->build();
					
					// This will be called in the following try
					$uploader->getEventDispatcher()->addListener(
					    'multipart_upload.after_part_upload', 
					    function($event) {
						    $message = sprintf( __( 'Part %d uploaded ...', 'dreamobjects' ), $event["state"]->count() );
					        DHDO::logger( $message );
					    }
					);
					
					try {
						$message = __( 'Beginning Multipart upload to the cloud. This may take a while (5 minutes for every 75 megs or so).', 'dreamobjects' );
						DHDO::logger( $message );
						set_time_limit(180);
					    $uploader->upload();
					    $message = __('SUCCESS: Multipart upload to the cloud complete!', 'dreamobjects' );
					    DHDO::logger( $message );
					    	DHDO::notifier( $filenicename, $message, 'success' );
					} catch (MultipartUploadException $e) {
					    $uploader->abort();
					    $message = sprintf( __('FAILURE: Multipart upload to the cloud failed: %s', 'dreamobjects' ), $e->getMessage() );
					    DHDO::logger( $message );
					    DHDO::notifier( $filenicename, $message, 'failure' );
					}
	
				} else {
					// If it's under 100megs, do it the old way
					$message = __('File size is under 100megs. This will be less spammy.', 'dreamobjects' );
					DHDO::logger( $message );
					
					set_time_limit(180); // 3 min 
					try {
						$result = $s3->putObject(array(
						    'Bucket'       => $bucket,
						    'Key'          => $filenicename,
						    'SourceFile'   => $file,
						    'ContentType'  => 'application/zip',
						    'ACL'          => 'private',
						    'Metadata'     => array(
						        'UploadedBy'   => 'DreamObjectsBackupPlugin',
						        'UploadedDate' => date_i18n('Y-m-d-His', current_time('timestamp')),
						    )
						));
						$message = __('SUCCESS: Upload to the cloud complete!', 'dreamobjects' );
						DHDO::logger( $message );
						DHDO::notifier( $filenicename, $message, 'success');
					} catch (S3Exception $e) {
						$message = sprintf( __('FAILURE: Upload to the cloud failed: %s', 'dreamobjects' ), $e->getMessage() );
					    DHDO::logger( $message );
					    DHDO::notifier( $filenicename, $message, 'failure' );
					}
				}
				
/*
				// https://dreamxtream.wordpress.com/2013/10/29/aws-php-sdk-logging-using-guzzle/
				$s3->getEventDispatcher()->removeSubscriber($logPlugin);
*/
			} else {
				$message = __('FAILURE: Nothing to upload.', 'dreamobjects' );
				DHDO::logger( $message );
				DHDO::notifier( $filenicename, $message, 'failure' );
			}

            // Cleanup
            if(file_exists($file)) { 
                @unlink($file);
                $message = sprintf( __('Deleting zip file: %s', 'dreamobjects' ), $file );
                DHDO::logger( $message);
            }
            if(file_exists($sqlfile)) { 
                @unlink($sqlfile);
                $message = sprintf( __('Deleting sql file: %s', 'dreamobjects' ), $sqlfile );
                DHDO::logger( $message );
            }
        }
        
        // Cleanup Old Backups
        $message = __('Checking for backups to be deleted from the cloud.', 'dreamobjects' );
        DHDO::logger( $message );
        if ( $backup_result = 'Yes' && get_option('dh-do-retain') && get_option('dh-do-retain') != 'all' ) {
            $num_backups = get_option('dh-do-retain');

		  	$s3 = AwsS3DHDO::factory(array(
				'key'      => get_option('dh-do-key'),
			    'secret'   => get_option('dh-do-secretkey'),
			    'base_url' => 'https://'.get_option('dh-do-hostname'),
			));

            $bucket = get_option('dh-do-bucket');
            
            $parseUrl = parse_url(trim(home_url()));
            $prefixurl = $parseUrl['host'];
            if( isset($parseUrl['path']) ) 
                { $prefixurl .= $parseUrl['path']; }
            
            $backups = $s3->getIterator('ListObjects', array('Bucket' => $bucket, "Prefix" => $prefixurl ) );
            
            if ($backups !== false) {
            	$backups = $backups->toArray();
                krsort($backups);
                $count = 0;
                foreach ($backups as $object) {
                    if ( ++$count > $num_backups ) {
                        $s3->deleteObject( array(
                        	'Bucket' => $bucket,
                        	'Key'    => $object['Key'],
                        ));
                        $message = sprintf( __('Removed backup %s from the cloud, per user retention choice', 'dreamobjects' ), $object['Key'] );
                        DHDO::logger( $message );
                    }    
                }
            }
        } else {
	        $message = __('Per user retention choice, not deleting a single old backup.', 'dreamobjects' );
	        DHDO::logger( $message );
        }
        $message = __('Backup Complete.', 'dreamobjects' );
        DHDO::logger( $message );
        DHDO::logger('');
        delete_option( 'dh-do-backupnow' );
    }
    public function cron_schedules($schedules) {
        $schedules['daily'] = array('interval'=>86400, 'display' => 'Once Daily');
        $schedules['weekly'] = array('interval'=>604800, 'display' => 'Once Weekly');
        $schedules['monthly'] = array('interval'=>2592000, 'display' => 'Once Monthly');
        return $schedules;
    }
}