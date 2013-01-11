# DreamObjects Connection #

This repository holds the beta version of the plugin. It should work, but use at your own risk.

**Contributors:** Ipstenu, DanCoulter  
**Tags:** cloud, dreamhost, dreamobjects, backup 
**Requires at least:** 3.4  
**Tested up to:** 3.5  
**Stable tag:** 2.3
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Connect your WordPress site to DreamHost's DreamObjects.

## Description ##

DreamHost has its own Cloud - <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects&#153;</a> - an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.

Well now that we've gotten the sales-pitch out of the way, DreamObjects Connections will plugin your WordPress site into DreamObjects, tapping into the amazing power of automated backups, fileuploaders, and more!

<em>Please do not open DreamHost Support Tickets for this plugin.</em> Honestly, the support techs are fantastic, but they can't debug this yet, so they'll just send you here anyway. Post in the <a href="http://wordpress.org/support/plugin/dreamobjects">support forum here</a>, and I'll get to you ASAP.

### Backup Features ###
* Automatically backs up your site (DB and files) to your DreamObjects cloud on a daily, weekly, or monthly schedule.
* Retains 15, 30, 60, or 90 backups at any given time (so as not to charge you the moon when you have a large site).
* Provides <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> hooks to do the same

### Uploader ###
* Allows you to upload files to any bucket
* Determine if files are public (default) or private
* If configured, the shortcode <code>[dreamobjects]</code> will display a list of all your upload files

### To Do ###
* CDN (when available)
* Better <code>[dreamobjects]</code> support for folders
* Multipart file uploads to avoid large file upload problems (either get <a href="http://docs.amazonwebservices.com/AmazonS3/latest/dev/LLuploadFilePHP.html">multipart fileupload</a> working or <a href="http://superuser.com/questions/336219/how-do-i-split-a-zip-file-into-multiple-segments">split the zip</a>)

## Installation ##

1. Sign up for <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects</a>
1. Install and Activate the plugin
1. Fill in your Key and Secret Key

### Backups ###
1. Pick your backup Bucket
1. Select what you want to backup
1. Chose when you want to backup
1. Relax and let DreamHost do the work

### Uploader ###
1. Pick your bucket
1. Upload a file to the bucket

## Frequently asked questions ##

### General Questions ###

<strong>What does it do?</strong>

DreamObjects Connection connects your WordPress site to your DreamObjects cloud storage, allowing you to upload files directly to your cloud, or automatically store backups.

<strong>Do you work for DreamHost?</strong>

Yes, but this isn't an official DreamHost plugin at this time. It just works.

<strong>Do I have to host my  DreamHost?</strong>

Yes and no. You have to use Dream<em>Objects</em>, which belongs to Dream<em>Host</em>. This plugin was built on and specifically for DreamHost servers, so I can give you no assurance it'll work on other hosts.

<strong>Can I use this on a Windows Server?</strong>

You can try, and let me know how it goes. I built this for DreamHost, so it has only been tested on Linux boxes.

<strong>Can I use this on Multisite?</strong>

Not at this time. Backups for Multisite are a little messier, and I'm not sure how I want to handle that yet.

<strong>How big a site can this back up?</strong>

The hard limit is 2G. The practical limit is 200megs.

<strong>Why does my backup run but not back anything up?</strong>

Size. It's actually running the backup, but code that copies code up to DreamObjects has some limitations that happen on Amazon as well. Basically, the larger your backup, the more likely it is to decide it doesn't want to copy the file up without killing your server. Since the code is smart enough, it won't thrash your server, but it will magically decide to not copy files up.

You can test if this is happening by trying to only backup the SQL. If that works, then it's the size of your total backup. 

<strong>Wait, you said it could back up 2G! What gives?</strong>

There are a few things at play here:

1. The size of your backup
2. The file upload limit size in your PHP
3. The amount of server memory
4. The amount of available CPU

In a perfect world, you have enough to cope with all that. When you have a very large site, however, not so much. You can try increasing your <a href="http://wiki.dreamhost.com/PHP.ini#Increase_Filesize_Upload_Limit">PHP filesize upload limit</a>, or if your site really is that big, consider a VPS. Remember you're using WordPress to run backups here, so you're at the mercy of a middle-man. The DreamObjects itself can handle 2G, but once you hit a few hundred megs, everything else starts getting weird.

The fix would be to do multipart file uploads.

<strong>Where's the Database in the zip?</strong>

**I admit, it's in a weird spot:** /wp-content/upgrade/dreamobject-db-backup.sql  

Why there? Security. It's a safer spot, though safest would be a non-web-accessible folder. Maybe in the future.

### Using the Plugin ###

<strong>How often can I schedule backups?</strong>

You can schedule them daily, weekly, or monthly.

<strong>Can I force a backup to run now?</strong>

Yep! It actually sets it to run in 60 seconds, but works out the same.

<strong>I kicked off an ASAP backup, but it says don't refresh the page. How do I know it's done?</strong>

By revisiting the page, <em>but not</em> pressing refresh. Refresh is a funny thing. It re-runs what you last did, so you might accidently kick off another backup. You probably don't want that. The list isn't dynamically generated either, so just sitting on the page waiting won't do anything except entertain you as much as watching paint dry.

**My suggestions:** Visit another part of your site and go get a cup of coffee, or something else that will kill time for about two minutes. Then come back to the backups page. Just click on it from the admin sidebar. You'll see your backup is done.  

(Yes, I want to make a better notification about that, I have to master AJAX.)

<strong>How long does it keep backups?</strong>

Since you get charged on space used for DreamObjects, the default is to retain the last 15 backups. If you need more, you can save up to 90 backups, however that's rarely needed.

<strong>Can I keep them forever?</strong>

If you chose 'all' then yes, however this is not recommended. DreamObjects (like most S3/cloud platforms) charges you based on space and bandwidth, so if you have a large amount of files stored, you will be charged more money.

<strong>Who can upload files?</strong>

Anyone who can upload media can upload files, so this generally covers Authors and up. Only the Administrators can set the upload bucket, however.

<strong>How do I use the CLI?</strong>
If you have <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> installed on your server (which DreamHost servers do), you can use the following commands:

<pre>wp dreamobjects backup</pre>

### Errors ###
<strong>What's this <code>S3::listBuckets()</code> error?</strong>

Any time you see an error like this, it means the plugin can't talk to your DreamObjects buckets:
<code>
**"warning:** S3::listBuckets(): [403]   
Unexpected HTTP status in /wp-content/plugins/dreamobjects/lib/S3.php on line 249"
</code>

Reasons why include the key/secretkey pair aren't actually setup correctly, the bucket was deleted after adding it to the plugin, or your DreamObjects account is disabled. Double check that your keys are correct and the bucket exists.

<strong>The automated backup is set to run at 3am but it didn't run till 8am!</strong>

That's actually not an error. WordPress kicks off cron jobs when someone visits your site, so if no one visted the site from 3am to 8am, then the job to backup wouldn't run until then.

<strong>Can I see a log of what happens?</strong>

You can enable logging on the main DreamObjects screen. This is intended to be temporary (i.e. for debugging weird issues) rather than something you leave on forever. If you turn off logging, the log wipes itself for your protection.

<strong>Nothings happening when I press the backup ASAP button.</strong>

First turn on logging, then run it again. If it gives output, then it's running.

Second, try <em>just</em> backing up SQL. You may have a very large site, which has known to be problematic.

Then log in via SSH and run 'wp dreamobjects backup' to see if that works.

## Screenshots ##
###1. DreamObjects Private Key###
![DreamObjects Private Key](http://s.wordpress.org/extend/plugins/dreamobjects-connection/screenshot-1.png)

###2. Your DreamObjects Public Key###
![Your DreamObjects Public Key](http://s.wordpress.org/extend/plugins/dreamobjects-connection/screenshot-2.png)

###3. The Settings Page###
![The Settings Page](http://s.wordpress.org/extend/plugins/dreamobjects-connection/screenshot-3.png)

###4. The backup page###
![The backup page](http://s.wordpress.org/extend/plugins/dreamobjects-connection/screenshot-4.png)

###5. The uploader page, as seen by Admins###
![The uploader page, as seen by Admins](http://s.wordpress.org/extend/plugins/dreamobjects-connection/screenshot-5.png)

###6. The uploader page, as seen by Authors###
![The uploader page, as seen by Authors](http://s.wordpress.org/extend/plugins/dreamobjects-connection/screenshot-6.png)


## Changelog ##

### Version 2.3 ###
Jan 3, 2012 by Ipstenu

* Optional logging (good for debugging)
* No longer takes a backup right away when saving settings (good for testing lots of stuff)
* Hiding keys if set for security
* Reset option (wipes all settings)

### Version 2.2 ###
Dec 30, 2012 by Ipstenu

* Fixed date/time issue with backups displaying wrong (did not impact functionality, just bad date conversion)
* Changed refs of siteurl to home_url, in order to fix wp-cli backups going astray under certain conditions
* Security fixes (from duck_ aka Jon Cave)

### Version 2.1 ###
Dec 21, 2012 by Ipstenu

* Made a change to how times are generated using current_time correctly, vs time (props Regan, a DreamHost customer, for letting me log into your site!)
* Changed date() to date_i18n() (thank you @Rarst for your 'tsk' - it lights a fire)
* Cleaning up debug errors
* Fixed uninstall

### Version 2.0 ###
Nov 1, 2012 by Ipstenu

* Backup retention - chose your own adventure.

### Version 1.2 ###
Oct 11, 2012 by Ipstenu

* Uploader added
* Shortcode to list uploaded files added
* Moved New Bucket code to the main settings page, where you can see your buckets now

### Version 1.1 ###
Sept 27, 2012 by Ipstenu 

* <em>All minor changes, but since people had been using 1.0, I thought a kick was in order.</em>
* Security (nonce, abspath, etc)
* Better defines
* wp-cli (still not 100%)

### Version 1 ###

Sept 2012, by Ipstenu

* Forked <a href="http://wordpress.org/extend/plugins/wp-s3-backups/">WP S3 Backups</a> to work with DreamObjects.
* Upgraded <a href="http://undesigned.org.za/2007/10/22/amazon-s3-php-class">Amazon S3 PHP Class</a> to latest version
* Pretified, consolidated, organized, and formatted.
* Saving temp files to upgrade (vs it's own folder)

## Upgrade notice ##
