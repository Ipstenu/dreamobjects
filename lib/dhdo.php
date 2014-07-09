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
        DHDO::logger('Scanned folders and files to generate list for backup.');
    
        foreach( $omit as $omitter ) {
        	$data = preg_grep( $omitter , $data, PREG_GREP_INVERT);
        }
        
        DHDO::logger( print_r($data) );

        return $data;
        DHDO::logger('Scanned folders and files to generate list for backup.');
    }
    
    // The actual backup
    function backup() {
        DHDO::logger('Begining Backup.');
        global $wpdb;

		if (!is_dir( content_url() . '/upgrade/' )) {
			DHDO::logger('Upgrade folder missing. This will cause serious issues with WP in general, so we will create it for you.');
		    mkdir( content_url() . '/upgrade/' );       
		}
        
        // Pull in data for what to backup
        $sections = get_option('dh-do-backupsection');
        if ( !$sections ) {
            $sections = array();
        }
        
        $file = WP_CONTENT_DIR . '/upgrade/dreamobject-backups.zip';
        $fileurl = content_url() . '/upgrade/dreamobject-backups.zip';

        // Pre-Cleanup
        if(file_exists($file)) { 
            @unlink($file);
            DHDO::logger('Leftover zip file found, deleting '.$file.' ...');
        }

		try {
				$zip = new ZipArchive( $file );
				$zaresult = true;
				DHDO::logger('ZipArchive found and will be used for backups.');
		} catch ( Exception $e ) {
				$error_string = $e->getMessage();
				$zip = new PclZip($file);
				DHDO::logger('ZipArchive not found. Error: '. $error_string );
				DHDO::logger('PclZip will be used for backups.');
				require_once(ABSPATH . '/wp-admin/includes/class-pclzip.php');
				$zaresult = false;
		}

        $backups = array();

        // All me files!
        if ( in_array('files', $sections) ) {

			DHDO::logger( 'Calculating backup size...');

			$trimdisk = WP_CONTENT_DIR ;
			$diskcmd = sprintf("du -s %s", WP_CONTENT_DIR );
			$diskusage = exec( $diskcmd );
			$diskusage = trim(str_replace($trimdisk, '', $diskusage));
			
			DHDO::logger(size_format( $diskusage * 1024 ).' of diskspace will be processed.');
			
			if ($diskusage < ( 2000 * 1024 ) ) {
				$backups = array_merge($backups, DHDO::rscandir(WP_CONTENT_DIR));
				DHDO::logger( count($backups) .' files added to backup list.');
			} else {
				DHDO::logger( 'ERROR! PHP is unable to backup your wp-content folder. Please consider cleaning out unused files (like plugins and themes).');
			}

			if ( file_exists(ABSPATH .'wp-config.php') ) {
		        $backups[] = ABSPATH .'wp-config.php' ;
				DHDO::logger( 'wp-config.php added to backup list.');
		    }

        } 
        
        // And me DB!
        if ( in_array('database', $sections) ) {
            set_time_limit(300);

			$sqlfile = WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql';
            $tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->prefix . "%'");
            $tables_string = implode( ' ', $tables );

			// Pre cleanup
	        if(file_exists($sqlfile)) { 
	            @unlink($sqlfile);
	            DHDO::logger('Leftover sql file found, deleting '.$sqlfile.' ...');
	        }
            
            $dbcmd = sprintf( "mysqldump -h'%s' -u'%s' -p'%s' %s %s --single-transaction 2>&1 >> %s",
            DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, $tables_string, $sqlfile );
            
            exec( $dbcmd );
            
            $sqlsize = size_format( @filesize($sqlfile) );
            DHDO::logger('SQL file created: '. $sqlfile .' ('. $sqlsize .').');
            $backups[] = $sqlfile;
            DHDO::logger('SQL added to backup list.');

        }
        
        if ( !empty($backups) ) {
            set_time_limit(300);  // Increased timeout to 5 minutes. If the zip takes longer than that, I have a problem.
            if ( $zaresult != 'true' ) {
            	DHDO::logger('Creating zip file using PclZip.');
            	DHDO::logger('NOTICE: If the log stops here, PHP failed to create a zip of your wp-content folder. Please consider increasing the server\'s PHP memory, RAM or CPU.');
            	$zip->create($backups);

            } else {
            	DHDO::logger('Creating zip file using ZipArchive.');
            	DHDO::logger('NOTICE: If the log stops here, PHP failed to create a zip of your wp-content folder. Please consider cleaning out unused files (like plugins and themes), or increasing the server\'s PHP memory, RAM or CPU.');
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
            		DHDO::logger('ZipArchive failed to complete: '. $error_string );
            	}

            }

			if ( @file_exists( $file ) ) { 
            	DHDO::logger('Calculating zip file size ...');
				$zipsize = size_format( @filesize($file) );
				DHDO::logger('Zip file generated: '. $file .' ('. $zipsize .').');
			} else {
				@unlink($file);
				DHDO::logger('Zip file failed to generate. Nothing will be backed up.');
			}
            
            // Upload

			if ( @file_exists( $file ) ) {
	
			  	$s3 = AwsS3DHDO::factory(array(
					'key'      => get_option('dh-do-key'),
				    'secret'   => get_option('dh-do-secretkey'),
				    'base_url' => 'http://objects.dreamhost.com',
				));
	
/*
				// https://dreamxtream.wordpress.com/2013/10/29/aws-php-sdk-logging-using-guzzle/
				$logPlugin = LogPlugin::getDebugPlugin(TRUE,
				//Don't provide this parameter to show the log in PHP output
					fopen(DHDO_PLUGIN_DIR.'/debug2.txt', 'a')
				);
				$s3->addSubscriber($logPlugin);
*/
	
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
					DHDO::logger('Prepare the upload parameters and upload parts in 25M chunks.');
					
					$uploader = UploadBuilder::newInstance()
					    ->setClient($s3)
					    ->setSource($file)
					    ->setBucket($bucket)
					    ->setKey($newname)
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
					        DHDO::logger( 'Part '. $event["state"]->count() . ' uploaded ...');
					    }
					);
					
					try {
						DHDO::logger('Begin upload. This may take a while (5min for every 75 megs or so).');
						set_time_limit(180);
					    $uploader->upload();
					    DHDO::logger('Upload complete');
					} catch (MultipartUploadException $e) {
					    $uploader->abort();
					    DHDO::logger('Upload failed: '.$e->getMessage() );
					}
	
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
				
/*
				// https://dreamxtream.wordpress.com/2013/10/29/aws-php-sdk-logging-using-guzzle/
				$s3->getEventDispatcher()->removeSubscriber($logPlugin);
*/
			} else {
				DHDO::logger('Nothing to upload.');
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
            	$backups = $backups->toArray();
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