=== DreamObjects Backups ===
Contributors: Ipstenu,DanCoulter
Donate link: 
Tags: cloud, dreamhost, dreamobjects
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect to DreamHost's DreamObjects

== Description ==

DreamHost has it's own Cloud - <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects</a>.

DreamObjects is a cost-effective, public cloud storage service, perfect a scalable storage solution your WordPress backups. The DreamObjects Backups plugin will automate that process for you, sending your whole WordPress folder, and database, up into the cloud on a daily, weekly, or monthly schedule.

Alternately you can use <a href="https://github.com/wp-cli/wp-cli#what-is-wp-cli">wp-cli</a> to manually make a backup.

<pre>wp dreamobjects backup</pre>

== Installation ==

1. Sign up for <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects</a>
1. Install and Activate the plugin
1. Fill in your Key and Secret Key
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

Yep!

== Screenshots ==



== Changelog ==

= Version 1 =

* Forked <a href="http://wordpress.org/extend/plugins/wp-s3-backups/">WP S3 Backups</a> to work with DreamObjects.
* Pretified, consolidated, organized, and formatted.

== Upgrade notice ==
