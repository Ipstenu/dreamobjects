=== DreamObjects Plugin ===
Contributors: Ipstenu,DanCoulter
Tags: cloud, dreamhost, dreamobjects
Requires at least: 3.4
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect to DreamHost's DreamObjects

== Description ==

DreamHost has it's own Cloud - <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects</a>.

DreamObjects is a cost-effective, public cloud storage service, perfect a scalable storage solution your WordPress site.

= Features =
* Automatically backs up your site (DB and files) to your DreamObjects cloud on a daily, weekly, or monthly schedule.
* Provides <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> hooks to do the same

= To Do =
* CDN hook up

== Installation ==

1. Sign up for <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects</a>
1. Install and Activate the plugin
1. Fill in your Key and Secret Key

= For Backups... =
1. Pick your backup Bucket
1. Select what you want to backup
1. Chose when you want to backup
1. Relax and let DreamHost do the work


== Frequently asked questions ==

= What does it do? =

DreamObjects allows you to store your data securely, redundantly and inexpensively. The DreamObjects plugin will create a backup of your site, zip it, and toss it up into a DreamObjects bucket in an automated manner.

= Do I have to use DreamHost? =

Yes and no. You have to use Dream<em>Objects</em>, which belongs to Dream<em>Host</em>.

= How often can I schedule backups? =

You can schedule them daily, weekly, or monthly.

= Can I force a backup to run now? =

Yep! It actually sets it to run in 60 seconds, but works out the same.

= How do I use the CLI? =
If you have <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> installed on your server (which DreamHost servers do), you can use the following commands:

<pre>wp dreamobjects backup</pre>



== Screenshots ==



== Changelog ==

= Version 1 =

* Forked <a href="http://wordpress.org/extend/plugins/wp-s3-backups/">WP S3 Backups</a> to work with DreamObjects.
* Upgraded <a href="http://undesigned.org.za/2007/10/22/amazon-s3-php-class">Amazon S3 PHP Class</a> to latest version
* Pretified, consolidated, organized, and formatted.

== Upgrade notice ==
