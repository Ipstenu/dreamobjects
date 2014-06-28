# DreamObjects Connection #
**Tags:** cloud, dreamhost, dreamobjects, backup  
**Requires at least:** 3.4  
**Tested up to:** 3.9
**Stable tag:** 3.4
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Connect your WordPress site to DreamHost's DreamObjects.

## Description ##

This is the GitHome of DreamObjects, and is where I do the dev work. Betas are pushed here as soon as they work, and when there's 'enough' to push a new version, it goes to WordPress Plugins SVN.

## 27 June 2014 ##

Testing Version 3.5!

<em>What's New...</em>

Where to start?

* Changed SDK to newest version: 2.6.9 (<a href="http://blogs.aws.amazon.com/php/post/Tx2Q8T2MTERKJS4/Release-AWS-SDK-for-PHP-Version-2-6-9">official release notes</a>)
* Added support for ZipArchive, with graceful fallback to PclZip if needed
* `/cache/` folder is not backed up anymore
* Backs up `wp-config.php` sometimes... (if you put it in a weird place, I'm not responsible)
* Backup ignores `wp-content` if it's nearly 2G (blame PHP, not me!)
* Zip has shorter paths (unzipped, it's /dreamobjects-backup/wp-content/etc)
* Improved multipart uploads, which should allow for large files in a better way
* Force disable on Multisite, which you shouldn't be using since it breaks six ways from Sunday anyway
* Security tightening: hiding things, making things harder for people to run, safer command usage
* Improved debug logging
* Removed uploader for both security and support reasons. It was bad and I feel bad.
* New wp-cli command: `wp dreamobjects resetlog` (resets the debug log)

<em>Needs testing</em>

* Large shared sites - 500megs+ on shared SHOULD work now, with Multipart uploads
* zips on large sites - Over 2G of data SHOULD fail now!

<em>Remaining Issues...</em>

* When people use WP_HOME and WP_SITEURL in their wp-config, it doesn't always work. I don't know why. It shouldn't care.

