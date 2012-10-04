=== DreamObjects Plugin ===
Contributors: Ipstenu, DanCoulter
Tags: cloud, dreamhost, dreamobjects
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to DreamHost's DreamObjects

== Description ==

DreamHost has it's own Cloud - <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects</a>.

DreamObjects™ is an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.

= Backup Features =
* Automatically backs up your site (DB and files) to your DreamObjects cloud on a daily, weekly, or monthly schedule.
* Provides <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> hooks to do the same

= To Do =
* Permit on-demand uploads/downloads
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


== Frequently asked questions ==

= What does it do? =

DreamObjects allows you to store your data securely, redundantly and inexpensively. The backup portion of this plugin will create a backup of your site, zip it, and toss it up into your DreamObjects bucket in an automated manner.

= Do I have to use DreamHost? =

Yes and no. You have to use Dream<em>Objects</em>, which belongs to Dream<em>Host</em>. This plugin was built on and specifically for DreamHost servers, so there's no assurance it'll work on other hosts.

= How often can I schedule backups? =

You can schedule them daily, weekly, or monthly.

= Can I force a backup to run now? =

Yep! It actually sets it to run in 60 seconds, but works out the same.

= Why doesn't my backup run at the exact same time? =

This is a little technical, so bear with me. It runs based on wp_cron, which is triggered when someone visits your site. If the backup is set for 12:35pm, but no one visits your site until 4:10pm, then it's not going to run until then. There's nothing wrong with that!

= How do I use the CLI? =
If you have <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> installed on your server (which DreamHost servers do), you can use the following commands:

<pre>wp dreamobjects backup</pre>

While the schedule option shows, it doesn't fully work yet. Sorry.

= Do you work for DreamHost? =

Yes, but this isn't an official DreamHost plugin at this time. It just works.

== Screenshots ==
1. DreamObjects Private Key
1. Your DreamObjects Public Key

== Changelog ==

= Version 1.1 =
<em>All minor changes, but since people had been using 1.0, I thought a kick was in order.</em>

* Security (nonce, abspath, etc)
* Better defines
* wp-cli (still not 100%)

= Version 1 =

* Forked <a href="http://wordpress.org/extend/plugins/wp-s3-backups/">WP S3 Backups</a> to work with DreamObjects.
* Upgraded <a href="http://undesigned.org.za/2007/10/22/amazon-s3-php-class">Amazon S3 PHP Class</a> to latest version
* Pretified, consolidated, organized, and formatted.
* Saving files to upgrade (vs it's own folder)

== Upgrade notice ==
