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

$screen = get_current_screen();

// For the DreamObjects Page
if ($screen->id == 'toplevel_page_dreamobjects-menu') {

    // Introduction
    $screen->add_help_tab( array(
		'id'      => 'dreamobjects-menu-base',
		'title'   => __('Overview', 'dreamobjects'),
		'content' => 
		'<h3>' . __('Welcome to DreamObjects Backups', 'dreamobjects') .'</h3>' .
		'<p>' . __( 'DreamObjects&#153; is an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.', 'dreamobjects' ) . '</p>' .
		'<p>' . __( 'This plugin was built on and for DreamHost Servers. While it may work on other webhosts, provided of course you have DreamObjects, it does so at your own risk.', 'dreamobjects' ) . '</p>' .
		'<p>' . __( 'If you haven\'t already signed up for DreamObjects, you won\'t find this plugin of any use at all.', 'dreamobjects' ) . '</p>'
		));
    $screen->set_help_sidebar(
        '<h4>' . __('For more information:', 'dreamobjects') .'</h4>' .
        
        '<p><a href="http://dreamhost.com/cloud/dreamobjects/">' . __('DreamObjects Homepage', 'dreamobjects' ) . '</a></p>' .
        '<p><a href="http://wiki.dreamhost.com/DreamObjects_Overview">' . __('Overview', 'dreamobjects' ) . '</a></p>' .
        '<p><a href="http://docs.dreamobjects.net/">' . __('API', 'dreamobjects' ) . '</a></p>' .
        '<p><a href="http://wordpress.org/support/plugin/dreamobjects">' . __('Get Help', 'dreamobjects' ) . '</a></p>'
        );

    // Setup
    $screen->add_help_tab( array(
		'id'      => 'dreamobjects-menu-signup',
		'title'   => __('Setup', 'dreamobjects'),
		'content' =>
		'<h3>' . __('Setup', 'dreamobjects') .'</h3>' .
		'<ol>' .
		  '<li>' . __( 'Sign up for <a href="http://dreamhost.com/cloud/dreamobjects/">DreamObjects</a>', dreamobjects ) . '</li>' .
		  '<li>' . __( 'Install and Activate the plugin', dreamobjects ) . '</li>' .
		  '<li>' . __( 'Fill in your Key and Secret Key', dreamobjects ) . '</li>' .
        '</ol>'
	  ));
    
    // Terminology
    $screen->add_help_tab( array(
		'id'      => 'dreamobjects-menu-terms',
		'title'   => __('Terminology', 'dreamobjects'),
		'content' =>
		'<h3>' . __('Terminology', 'dreamobjects') .'</h3>' .
		'<p><strong>' . __( 'Object: ', 'dreamobjects') .'</strong>' . __( 'Files uploaded to DreamObjects.', 'dreamobjects') . '</p>' .
		'<p><strong>' . __( 'Bucket: ', 'dreamobjects') .'</strong>' . __( 'A mechanism for grouping objects, similar to a folder. One key distinction is that bucket names must be unique, like a domain name, since they are used to create public URLs to stored objects.', 'dreamobjects') . '</p>' .
		'<p><strong>' . __( 'Access Key: ', 'dreamobjects') .'</strong>' . __( 'A similar concept to a username for DreamObjects users. One or more can be created for each user if desired. Each access key will allow access to all of the buckets and their contents for a user. You will need this key to connect.', 'dreamobjects') . '</p>' .
		'<p><strong>' . __( 'Secret Key: ', 'dreamobjects') .'</strong>' . __( 'A similar concept to a password for DreamObjects users. A secret key is automatically generated for each access key and cannot be changed. Never give anyone your secret key.', 'dreamobjects') . '</p>' .
		'<p><strong>' . __( 'Key Pair: ', 'dreamobjects') .'</strong>' . __( 'A singular term used to describe both an access key and its secret key.', 'dreamobjects') . '</p>'
	  ));
	   }

// Backup Page
if ($screen->id == 'dreamobjects_page_dreamobjects-menu-backup') {
    
    // Base Help
    $screen->add_help_tab( array(
		'id'      => 'dreamobjects-menu-backup-base',
		'title'   => __('Overview', 'dreamobjects'),
		'content' => 
		'<h3>' . __('DreamObjects Backups', 'dreamobjects') .'</h3>' .
		'<p>' . __( 'Backing up your WordPress site to DreamObjects will allow you to have a safe and secure backup of your site. This is useful to run before you upgrade WordPress, or make big changes.', 'dreamobjects' ) . '</p>' .
		'<p>' . __( 'Backups can be scheduled to run daily, weekly or monthly. You also have the option to run a backup right now.', 'dreamobjects' ) . '</p>' .
		'<p>' . __( 'The default backup retention is 15 backups, however you can change this t0 30, 60, 90, or all backups (where \'all\' is all backups, forever and ever). Keep in mind you will be charged for the space you use, so chose wisely.', 'dreamobjects' ) . '</p>'
      ));
	}
else
    return;