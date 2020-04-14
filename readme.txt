=== DreamObjects Backups ===
Contributors: Ipstenu
Tags: cloud, dreamhost, dreamobjects, backup
Requires at least: 5.0
Tested up to: 5.4
Stable tag: 4.3.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Backup your WordPress site to DreamHost's Cloud: DreamObjects.

== Description ==

DreamHost has its own Cloud - <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects&#153;</a> - an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.

Well now that we've gotten the sales-pitch out of the way, DreamObjects Backups will plugin your WordPress site into DreamObjects, tapping into the amazing power of automated backups!

<em>Please <strong>do not</strong> open DreamHost Support Tickets for this plugin.</em> Post in the <a href="http://wordpress.org/support/plugin/dreamobjects">support forum here</a>, and I'll get to you as soon as I can.

= Backup Features =

* Automatically backs up your site (DB and files) to your DreamObjects cloud on a daily, weekly, or monthly schedule.
* Retains a limitable number of backups at any given time (so as not to charge you the moon when you have a large site).
* Provides <a href="https://wp-cli.org/">wp-cli</a> hooks to do the same

= Credit =

Version 3.5 and up would not have been possible without the work Brad Touesnard did with <a href="https://wordpress.org/plugins/amazon-web-services/">Amazon Web Services</a>. His incorporation of the AWS SDK v 2.x was the cornerstone to this plugin working better.

= Privacy Policy =

By using this plugin, data will be sent to DreamObjects, a subsidiary of DreamHost. As you are required to register for a DreamObjects account, you have already agreed to the DreamHost.com [terms of service](https://www.dreamhost.com/legal/terms-of-service/) and [privacy policy](https://www.dreamhost.com/legal/privacy-policy/). Those terms are applicable to all data transmitted.

The following information is sent to DreamObjects:

* Your domain name and IP address
* Your private and secret keys
* Your backup zip file

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

Your database and your `wp-content` folder. It also attempts to backup your `.htaccess` and `wp-config.php` files if it can be sure it's found them.

<strong>How big a site can this back up?</strong>

PHP has a hard limit of 2G (see <a href="http://docs.aws.amazon.com/aws-sdk-php/guide/latest/faq.html#why-can-t-i-upload-or-download-files-greater-than-2gb">Why can't I upload or download files greater than 2GB?</a>). Sorry.

<strong>Why does my backup run but not back anything up?</strong>

Your backup may be too big for your server to handle. A quick way to test if this is happening still is by trying to only backup the SQL. If that works, then it's the size of your total backup.

<strong>Wait, you said it could back up 2G! What gives?</strong>

There are a few things at play here:

1. The size of your backup
2. The file upload limit size in your PHP
3. The amount of server memory
4. The amount of available CPU

In a perfect world, you have enough to cope with all that. When you have a very large site, however, not so much. You can try increasing your <a href="http://wiki.dreamhost.com/PHP.ini#Increasing_the_PHP_Memory_Limit">PHP memory limit</a>, or if your site really is that big, consider a VPS. Remember you're using WordPress to run backups here, so you're at the mercy of a middle-man. Just because PHP has a hard limit of 2G doesn't mean it'll even get that far.

I have, personally, verified a 250MB zip file on a shared host, with no timeouts, no server thrashing, and no PHP errors, so if this is still happening, turn on debugging and check the log. If the log stalls on creating the zip, then you've hit the memory wall. It's possible to increase your memory limit via PHP, <em>however</em> doing this on a shared server means you're probably getting too big for this sort of backup solution in the first place. If your site is over 500megs and you're still on shared, you need to seriously think about your future. This will be much less of an issue on VPS and dedicated boxes, where you don't have the same limits.

<strong>Where's the Database in the zip?</strong>

I admit, it's in a weird spot: `/wp-content/upgrade/RANDOM-dreamobjects-backup.sql`

Why there? Security. It's a safer spot, though safest would be a non-web-accessible folder. Maybe in the future. Keeping it there makes it easy for the plugin to delete.

= Using the Plugin =

<strong>How often can I schedule backups?</strong>

You can schedule them daily, weekly, or monthly.

<strong>Can I force a backup to run now?</strong>

Yep! It actually sets it to run in 60 seconds, but works out the same.

<strong>I disabled `wp-cron`. Will this work?</strong>

Yes, <em>provided</em> you still call cron via a grownup cron job (i.e. `curl http://domain.com/wp-cron.php` or something similar). That will call your regular backups. ASAP backup, however, will need you to manually visit the cron page.

<strong>How long does it keep backups?</strong>

Since you get charged on space used for DreamObjects, the default is to retain the last 5 backups. If you need more history you can save all your backups.

<strong>Can I keep them forever?</strong>

If you chose 'all' then yes, however this is not recommended. DreamObjects (like most S3/cloud platforms) charges you based on space and bandwidth, so if you have a large amount of files stored, you will be charged more money.

<strong>How do I use the CLI?</strong>

If you have <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> installed on your server (which DreamHost servers do), you can use the following commands:

<pre>
wp dreamobjects backup
wp dreamobjects reset log
wp dreamobjects reset settings
</pre>

The 'backup' command runs an immediate backup, while the `reset` command wipes either the log or the settings.

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

= 4.3.0 =

April 2020 by Ipstenu

* Fixed: corrected static functions for PHP 7.4+ compatibility
* Fixed: correct undefined variable issue
* Updated: Settings panel shows better details in the logs
* Updated: AWS SDK to version 3.134.6

= Previous Versions =

See changelog.txt

== Upgrade notice ==
