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
            delete_option( 'dh-do-bucket' );
            delete_option( 'dh-do-bucketup' );
            delete_option( 'dh-do-key' );
            delete_option( 'dh-do-schedule' );
            delete_option( 'dh-do-secretkey' );
            delete_option( 'dh-do-section' );
            delete_option( 'dh-do-uploader' );
            delete_option( 'dh-do-uploadview' );
            delete_option( 'dh-do-logging' );
            delete_option( 'dh-do-debugging' );
            DHDO::logger('reset');
           }

        // LOGGER: Wipe logger if blank
        if ( current_user_can('manage_options') && isset($_POST['dhdo-logchange']) && $_POST['dhdo-logchange'] == 'Y' ) {
            if ( !isset($_POST['dh-do-logging'])) {
                DHDO::logger('reset');
            }
        }       

        // UPLOADER
        if( current_user_can('manage_options') && isset($_POST['Submit']) && isset($_FILES['theFile']) && $_GET['page'] ==
'dreamobjects-menu-uploader' ) {
          $fileName = sanitize_file_name( $_FILES['theFile']['name']);
          $fileTempPath = realpath($_FILES['theFile']['tmp_name']);
          $fileType = $_FILES['theFile']['type'];
          DHDO::logger('Preparing to upload '. $fileName .' to DreamObjects. Temp data stored as '. $fileTempPath .'.');

		  	$s3 = AwsS3DHDO::factory(array(
				'key'      => get_option('dh-do-key'),
			    'secret'   => get_option('dh-do-secretkey'),
			    'base_url' => 'http://objects.dreamhost.com',
			));
          
            if ( get_option('dh-do-uploadpub') != 1 )
              {   $acl = AmazonS3::ACL_PUBLIC;
                  DHDO::logger('Upload will be public.');
              }
            else
              {   $acl = AmazonS3::ACL_PRIVATE;
                  DHDO::logger('Upload will be private.');
              }
            $bucket = get_option('dh-do-bucketup');

			$result = $s3->putObject(array(
				'Bucket'        => $bucket,
				'Key'           => $fileName, // The name of the file
				'SourceFile'    => $fileTempPath, // The temp path to the file on the server
				'ACL'			=> $acl,
			));

            if ( $result["status"]>=200 and $result["status"]<300 ) {
                add_action('admin_notices', array('DHDOMESS','uploaderMessage'));
                DHDO::logger('Copied '. $fileName .' to DreamObjects.');
            } else {
                add_action('admin_notices', array('DHDOMESS','uploaderError'));
                DHDO::logger('File failed to copy '. $fileTempPath .' to DreamObjects as '. $fileName .'. Error: '. $result["Code"] .' '. $result["MESSAGE"] .' '. $result["status"] .'.');
            }
        }
        
        // UPDATE OPTIONS
        if ( isset($_GET['settings-updated']) && isset($_GET['page']) && ( $_GET['page'] == 'dreamobjects-menu' || $_GET['page'] == 'dreamobjects-menu-backup' || $_GET['page'] == 'dreamobjects-menu-uploader' ) ) add_action('admin_notices', array('DHDOMESS','updateMessage'));

        // BACKUP ASAP
        if ( current_user_can('manage_options') &&  isset($_GET['backup-now']) && $_GET['page'] == 'dreamobjects-menu-backup' ) {
            wp_schedule_single_event( current_time('timestamp', true)+60, 'dh-do-backupnow');
            add_action('admin_notices', array('DHDOMESS','backupMessage'));
            DHDO::logger('Scheduled ASAP backup.');
        }
        
        // BACKUP
        if ( wp_next_scheduled( 'dh-do-backupnow' ) && ( $_GET['page'] == 'dreamobjects-menu' || $_GET['page'] == 'dreamobjects-menu-backup' ) ) {
            add_action('admin_notices', array('DHDOMESS','backupMessage'));
        }
    }

    // Returns the URL of the plugin's folder.
    function getURL() {
        return WP_CONTENT_URL.'/plugins/'.basename(dirname(__FILE__)) . '/';
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
        $zip = new PclZip($file);
        $backups = array();

        // All me files!
        if ( in_array('files', $sections) ) {
            $backups = array_merge($backups, DHDO::rscandir(WP_CONTENT_DIR));
            DHDO::logger('List of files added to the zip.');
        } 
        
        // And me DB!
        if ( in_array('database', $sections) ) {
            set_time_limit(180);
            $tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->prefix . "%'");
            $result = shell_exec('mysqldump --single-transaction -h ' . DB_HOST . ' -u ' . DB_USER . ' --password="' . DB_PASSWORD . '" ' . DB_NAME . ' ' . implode(' ', $tables) . ' > ' .  WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql');
            $sqlfile = WP_CONTENT_DIR . '/upgrade/dreamobject-db-backup.sql';
            $sqlsize = size_format( @filesize($sqlfile) );
            DHDO::logger('SQL file created: '. $sqlfile .' ('. $sqlsize .').');
            $backups[] = $sqlfile;
            DHDO::logger('SQL filename added to zip.');
        }
        
        if ( !empty($backups) ) {
            set_time_limit(180); 
            DHDO::logger('Creating zip file ...');
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
            
            $newname = $url . '/' . date_i18n('Y-m-d-His', current_time('timestamp')) . '.zip';
            
            DHDO::logger('New filename '. $newname .'.');
            set_time_limit(180);
            if ( get_option('dh-do-logging') == 'on' && get_option('dh-do-debugging') == 'on') {$s3->debug_mode = true;}

/*
			$multipart = $s3->createMultipartUpload(array(
			    'ACL'         => 'private',
			    'Bucket'      => $bucket,
			    'Key'         => $file,
			    'ContentType' => 'application/zip',
			    'Metadata' => array(
			        'UploadedBy'   => 'DreamObjectsBackupPlugin',
			        'UploadedDate' => date_i18n('Y-m-d-His', current_time('timestamp')),
			    ),
			));
*/

			$uploader = UploadBuilder::newInstance()
			    ->setClient($s3)
			    ->setSource($file)
			    ->setBucket($bucket)
			    ->setKey($newname)
			    ->setOption('Metadata', array('Foo' => 'Bar'))
			    ->setOption('CacheControl', 'max-age=3600')
			    ->build();

			// Perform the upload. Abort the upload if something goes wrong
			try {
			    $uploader->upload();
			    DHDO::logger('Success! Created backup file '. $newname .' in DreamObjects.');
			    $backup_result = 'Yes';
			} catch (MultipartUploadException $e) {
			    $uploader->abort();
			    DHDO::logger('Failure. File failed to create '. $file .'  as '. $newname .' in DreamObjects.');
			    DHDO::logger( $e );
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
        if ( $backup_result = 'Yes' && get_option('dh-do-retain') && get_option('dh-do-retain') != 'all' ) {
            $num_backups = get_option('dh-do-retain');

		  	$s3 = AwsS3DHDO::factory(array(
				'key'      => get_option('dh-do-key'),
			    'secret'   => get_option('dh-do-secretkey'),
			    'base_url' => 'http://objects.dreamhost.com',
			));

            $bucket = get_option('dh-do-bucket');

            $prefix = next(explode('//', home_url()));
            
            $backups = $s3->getIterator('ListObjects', array('Bucket' => $bucket ));
            
            if ($backups !== false) {
                krsort($backups);
                $count = 0;
                foreach ($backups as $object) {
                    if ( ++$count > $num_backups ) {
                        $s3->deleteObject( array(
                        	'Bucket' => $bucket,
                        	'Key'    => $object['Key'],
                        	'Prefix' => $prefix, // This makes sure we don't delete all our media!
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