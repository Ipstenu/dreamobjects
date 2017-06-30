=== DreamObjects Backups ===
Contributors: Ipstenu
Tags: cloud, dreamhost, dreamobjects, backup
Requires at least: 4.0
Tested up to: 4.8
Stable tag: 4.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Backup your WordPress site to DreamHost's DreamObjects.

== Description ==

DreamHost has its own Cloud - <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects&#153;</a> - an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.

Well now that we've gotten the sales-pitch out of the way, DreamObjects Connections will plugin your WordPress site into DreamObjects, tapping into the amazing power of automated backups!

<em>Please <strong>do not</strong> open DreamHost Support Tickets for this plugin.</em> Post in the <a href="http://wordpress.org/support/plugin/dreamobjects">support forum here</a>, and I'll get to you ASAP.

= Backup Features =

* Automatically backs up your site (DB and files) to your DreamObjects cloud on a daily, weekly, or monthly schedule.
* Retains a limitable number of backups at any given time (so as not to charge you the moon when you have a large site).
* Provides <a href="https://wp-cli.org/">wp-cli</a> hooks to do the same

= Credit =

Version 3.5 and up would not have been possible without the work Brad Touesnard did with <a href="https://wordpress.org/plugins/amazon-web-services/">Amazon Web Services</a>. His incorporation of the AWS SDK v 2.x was the cornerstone to this plugin working better.

== Installation ==

1. Sign up for [DreamObjects](http://dreamhost.com/cloud/dreamobjects/)
1. Install and Activate the plugin
1. Fill in your Key and Secret Key
1. Go to the backups page
1. Make your settings selections (how often, what, how many)
1. Relax and let DreamHost do the work

== Frequently asked questions ==

= General Questions =

<strong>What does it do?</strong>

DreamObjects Backups connects your WordPress site to your DreamObjects cloud storage, allowing you to automatically store backups of your content.

<strong>Do you work for DreamHost?</strong>

Yes, but this isn't an official DreamHost plugin at this time. It just works.

<strong>Do I have to host my website on DreamHost?</strong>

No, but using it anywhere else is unsupported. You have to use Dream<em>Objects</em>, which belongs to Dream<em>Host</em>. This plugin was built on and specifically for DreamHost servers, so I can give you no assurance it'll work on other hosts. BotoRsync, for example, and WP-CLI are installed on DreamHost servers. I can't vouch for any others. I haven't tested this at all on Windows.

<strong>Can I use this on Multisite?</strong>

Not at this time. Backups for Multisite are a little messier, and I'm not sure how I want to handle that yet.

<strong>What does it backup?</strong>

Your database and your `wp-content` folder.

In a perfect world it would also backup your `wp-config.php` and `.htaccess`, but those are harder to grab since there aren't consistent locations.

<strong>How big a site can this back up?</strong>

PHP has a hard limit of 2G (see <a href="http://docs.aws.amazon.com/aws-sdk-php/guide/latest/faq.html#why-can-t-i-upload-or-download-files-greater-than-2gb">Why can't I upload or download files greater than 2GB?</a>), so as long as this is uploading a zip of your content, it will be stuck there. Sorry.

<strong>Why does my backup run but not back anything up?</strong>

Your backup may be too big for your server to handle.

A quick way to test if this is happening still is by trying to only backup the SQL. If that works, then it's the size of your total backup.

<strong>Wait, you said it could back up 2G! What gives?</strong>

There are a few things at play here:

1. The size of your backup
2. The file upload limit size in your PHP
3. The amount of server memory
4. The amount of available CPU

In a perfect world, you have enough to cope with all that. When you have a very large site, however, not so much. You can try increasing your <a href="http://wiki.dreamhost.com/PHP.ini#Increasing_the_PHP_Memory_Limit">PHP memory limit</a>, or if your site really is that big, consider a VPS. Remember you're using WordPress to run backups here, so you're at the mercy of a middle-man. Just because PHP has a hard limit of 2G doesn't mean it'll even get that far.

I have, personally, verified a 250MB zip file, with no timeouts, no server thrashing, and no PHP errors, so if this is still happening, turn on debugging and check the log. If the log stalls on creating the zip, then you've hit the memory wall. It's possible to increase your memory limit via PHP, <em>however</em> doing this on a shared server means you're probably getting too big for this sort of backup solution in the first place. If your site is over 500megs and you're still on shared, you need to seriously think about your future. This will be much less of an issue on VPS and dedicated boxes, where you don't have the same limits.

<strong>Where's the Database in the zip?</strong>

I admit, it's in a weird spot: `/wp-content/upgrade/RANDOM-dreamobjects-backup.sql`

Why there? Security. It's a safer spot, though safest would be a non-web-accessible folder. Maybe in the future. Keeping it there makes it easy for me to delete.

<strong>My backup is small, but it won't back up!</strong>

Did you use defines for your HOME_URL and/or SITE_URL? For some reason, PHP gets bibbeldy about that. I'm working on a solution!

= Using the Plugin =

<strong>How often can I schedule backups?</strong>

You can schedule them daily, weekly, or monthly.

<strong>Can I force a backup to run now?</strong>

Yep! It actually sets it to run in 60 seconds, but works out the same.

<strong>I disabled `wp-cron`. Will this work?</strong>

Yes, <em>provided</em> you still call cron via a grownup cron job (i.e. 'curl http://domain.com/wp-cron.php'). That will call your regular backups. ASAP backup, however, will need you to manually visit the cron page.

<strong>I kicked off an ASAP backup, but it says don't refresh the page. How do I know it's done?</strong>

By revisiting the page, <em>but not</em> pressing refresh. Refresh is a funny thing. It re-runs what you last did, so you might accidentally kick off another backup. You probably don't want that. The list isn't dynamically generated either, so just sitting on the page waiting won't do anything except entertain you as much as watching paint dry.

My suggestions: Visit another part of your site and go get a cup of coffee, or something else that will kill time for about two minutes. Then come back to the backups page. Just click on it from the admin sidebar. You'll see your backup is done.

(Yes, I want to make a better notification about that, I have to master AJAX.)

<strong>How long does it keep backups?</strong>

Since you get charged on space used for DreamObjects, the default is to retain the last 15 backups. If you need more, you can save up to 90 backups, however that's rarely needed.

<strong>Can I keep them forever?</strong>

If you chose 'all' then yes, however this is not recommended. DreamObjects (like most S3/cloud platforms) charges you based on space and bandwidth, so if you have a large amount of files stored, you may be charged more money.

<strong>How do I use the CLI?</strong>

If you have <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> installed on your server (which DreamHost servers do), you can use the following commands:

<pre>
wp dreamobjects backup
wp dreamobjects resetlog
</pre>

The 'backup' command runs an immediate backup, while the `resetlog` command wipes your debug log.

<strong>Why doesn't it have a CDN?</strong>

Because we went with a slightly different feature with the CDN, and as such it's best as a separate plugin. Don't worry, they'll play nice!

<strong>Where did the uploader go!?</strong>

Away. It was never really used well and the CDN plugin will handle this much better. WP's just not the best tool for the job there.

= Errors =

<strong>Can I see a log of what happens?</strong>

You can enable logging on the main DreamObjects screen. This is intended to be temporary (i.e. for debugging weird issues) rather than something you leave on forever. If you turn off logging, the log wipes itself for your protection.

<strong>The automated backup is set to run at 3am but it didn't run till 8am!</strong>

That's not an error. WordPress kicks off cron jobs when someone visits your site, so if no one visited the site from 3am to 8am, then the job to backup wouldn't run until then.

<strong>Why is nothing happening when I press the backup ASAP button?</strong>

First turn on logging, then run it again. If it gives output, then it's running, so read the log to see what the error is. If it just 'stops', it should have suggestions as to why.

You can also log in via SSH and run `wp dreamobjects backup` to see if that works.

== Screenshots ==
1. DreamObjects Private Key
1. Your DreamObjects Public Key
1. The Settings Page
1. The backup page
1. Stored backups

== Changelog ==

= 4.0.4 = 

June 2016 by Ipstenu

* Fix typo that caused ad-hoc notice to display twice
* Correct display of completed backups
* Fix unexpected output on activation in some situations
* Correct typo that prevented disabling notifications
* Updated screenshots

= 4.0.3 =

May 2016 by Ipstenu

* Corrected path to wp-cli.

= 4.0.2 =

May 2016 by Ipstenu

* Update AWS files
* Change hostname to objects-us-west-1.dream.io
* Change language on new setups based on user feedback
* Improve check on when to show content
* Prevent multiple ASAP backups from running at the same time

= 4.0.1 =

March 2016 by Ipstenu

* Fixing .htaccess backups
* Fixing fwrite, moving it to a more checked location since it's being stupid.

= 4.0 =

March 2016 by Ipstenu

* Moved to Composer for SDK
* Upgraded SDK to 2.7.27 (nb: 2.8.x fails to run for some reason)
* Improved security and sanitization
* Migrated to settings API
* Removed 'reset' and changed to allowing users to edit keys ad hoc
* Improve translations
* Status log displayed in dash
* Plugin will now try to backup .htaccess if it can

= 3.5.2 =

April 2015 by Ipstenu

* Hashes and 4.2 compat.

= 3.5.1 =

December 11, 2014 by Ipstenu

* Changed SDK to newest version: 2.7.9
* Corrected deprecated warning with WP-CLI

= 3.5 =

August 11, 2014 by Ipstenu

* Changed SDK to newest version: 2.6.12 (<a href="http://blogs.aws.amazon.com/php/post/Tx2PDR0J3NL0YKN/Release-AWS-SDK-for-PHP-Version-2-6-12">official release notes</a>)
* Many code concepts learned from <a href="https://wordpress.org/plugins/amazon-web-services/">Amazon Web Services</a>.
* Added support for ZipArchive, with graceful fallback to PclZip if needed, to fix Windows unzip issues
* `/cache/` folder is not backed up anymore
* Backs up `wp-config.php` sometimes... (if you put it in a weird place, I'm not responsible)
* Backup ignores `wp-content` if it's nearly 2G (blame PHP, not me!)
* Zip has shorter paths (unzipped, it's /dreamobjects-backup/wp-content/etc)
* Improved multipart uploads, which should allow for large files in a better way (worked up to 428M!)
* Force disable on Multisite, which you shouldn't be using since it breaks six ways from Sunday anyway
* Security tightening: hiding things, making things harder for people to run, safer command usage
* Improved debug logging
* Removed uploader for both security and support reasons. It was bad and I feel bad.
* New wp-cli command: `wp dreamobjects resetlog` (resets the debug log)

= Previous Versions =

See changelog.txt

== Upgrade notice ==

4.0 is a major visual overhaul. Code functionality remains the same, but the interface has been improved.