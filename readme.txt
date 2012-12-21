=== DreamObjects Plugin ===
Contributors: Ipstenu, DanCoulter
Tags: cloud, dreamhost, dreamobjects
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to DreamHost's DreamObjects

== Description ==

DreamHost has its own Cloud - <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects</a>.

DreamObjects™ is an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.

= Backup Features =
* Automatically backs up your site (DB and files) to your DreamObjects cloud on a daily, weekly, or monthly schedule.
* Retains 15, 30, 60, or 90 backups at any given time (so as not to charge you the moon when you have a large site).
* Provides <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> hooks to do the same

= Uploader =
* Allows you to upload files to any bucket
* Determine if files are public (default) or private
* If configured, the shortcode <code>[dreamobjects]</code> will display a list of all your upload files

= To Do =
* CDN (when available)

== Installation ==

1. Sign up for <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects</a>
1. Install and Activate the plugin
1. Fill in your Key and Secret Key

= Backups =
1. Pick your backup Bucket
1. Select what you want to backup
1. Chose when you want to backup
1. Relax and let DreamHost do the work

= Uploader =
1. Pick your bucket
1. Upload a file to the bucket

== Frequently asked questions ==

= What does it do? =

DreamObjects connects your WordPress site to your DreamObjects cloud storage, allowing you to upload files directly to your cloud, or automatically store backups.

= Do I have to use DreamHost? =

Yes and no. You have to use Dream<em>Objects</em>, which belongs to Dream<em>Host</em>. This plugin was built on and specifically for DreamHost servers, so there's no assurance it'll work on other hosts.

= Can I use this on a Windows Server? =

You can try, and let me know how it goes. I built this for DreamHost, so it has only been tested on Linux boxes.

= How often can I schedule backups? =

You can schedule them daily, weekly, or monthly.

= Can I force a backup to run now? =

Yep! It actually sets it to run in 60 seconds, but works out the same.

= How long does it keep backups for? =

Since you get charged on space used for DreamObjects, the default is to retain the last 15 backups. If you need more, you can save up to 90 backups, however that's rarely needed.

= Can I keep them forever? =

If you chose 'all' then yes, however this is not recommended. DreamObjects (like most S3/cloud platforms) charges you based on space and bandwidth, so if you have a large amount of files stored, you will be charged more money.

= Who can upload files? =

Anyone who can upload media can upload files, so this generally covers Authors and up. Only the Administrators can set the upload bucket, however.

= How do I use the CLI? =
If you have <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> installed on your server (which DreamHost servers do), you can use the following commands:

<pre>wp dreamobjects backup</pre>

= Where's the Database in the zip? =

I admit, it's in a weird spot: /wp-content/upgrade/dreamobject-db-backup.sql

Why there? Security. It's a safer spot, though safest would be a non-web-accessible folder. Maybe in the future.

= Do you work for DreamHost? =

Yes, but this isn't an official DreamHost plugin at this time. It just works.

== Screenshots ==
1. DreamObjects Private Key
1. Your DreamObjects Public Key
1. The Settings Page
1. The backup page
1. The uploader page, as seen by Admins
1. The uploader page, as seen by Authors

== Changelog ==

= Version 2.1 =
Dec 21, 2012 by Ipstenu

* Made a change to how times are generated (using current_time correctly, vs time)
* Changed date() to date_i18n() (thank you @Rarst!)
* Cleaning up debug errors

= Version 2.0 =
Nov 1, 2012 by Ipstenu

* Backup retention - chose your own adventure.

= Version 1.2 =
Oct 11, 2012 by Ipstenu

* Uploader added
* Shortcode to list uploaded files added
* Moved New Bucket code to the main settings page, where you can see your buckets now

= Version 1.1 =
Sept 27, 2012 by Ipstenu 

* <em>All minor changes, but since people had been using 1.0, I thought a kick was in order.</em>
* Security (nonce, abspath, etc)
* Better defines
* wp-cli (still not 100%)

= Version 1 =

Sept 2012, by Ipstenu

* Forked <a href="http://wordpress.org/extend/plugins/wp-s3-backups/">WP S3 Backups</a> to work with DreamObjects.
* Upgraded <a href="http://undesigned.org.za/2007/10/22/amazon-s3-php-class">Amazon S3 PHP Class</a> to latest version
* Pretified, consolidated, organized, and formatted.
* Saving temp files to upgrade (vs it's own folder)

== Upgrade notice ==
