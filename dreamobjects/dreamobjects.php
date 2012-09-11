<?php

/*
Plugin Name: DreamObjects
Plugin URI: 
Description: Screwing around with DreamObjects CDN
Version: 1.0
Author: Mika Epstein
Author URI: http://ipstenu.org/

Copyright 2012 Mika Epstein (email: ipstenu@ipstenu.org)

    This file is part of DreamObjects, a plugin for WordPress.

    DreamObjects is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    Disabler is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WordPress.  If not, see <http://www.gnu.org/licenses/>.

*/

define( 'DREAMOBJ_PATH', plugin_dir_path(__FILE__) );

// Adding options
add_action('admin_init', 'dreamobj_activate' );

function dreamobj_activate() {
    register_setting( 'dreamobj_settings_key', 'dreamobj_settings' );
    register_setting( 'dreamobj_settings_secretkey', 'dreamobj_settings' );
    register_setting( 'dreamobj_settings_bucket', 'dreamobj_settings' );
}

// CSS
add_action('admin_print_styles', 'dreamobj_add_my_stylesheet');
function dreamobj_add_my_stylesheet() {
    wp_register_style( 'dreamobj-style', plugins_url('dreamobjects.css?2012109106', __FILE__) );
    wp_enqueue_style( 'dreamobj-style' );
    }

// Menu Pages
add_action('admin_menu', 'dreamobj_menu');
function dreamobj_menu() {
   add_menu_page(__('DreamObjects'), __('DreamObjects'), 'manage_options', 'dreamobjects-menu', 'dreamobjects_toplevel_page', plugins_url('dreamobjects/images/dreamobj-color.png'));
    add_submenu_page('dreamobjects-menu', __('Backups'), __('Backups'), 'manage_options', 'dreamobjects/backup.php');
}

function dreamobjects_toplevel_page() {
    require DREAMOBJ_PATH.'/config.php';
}