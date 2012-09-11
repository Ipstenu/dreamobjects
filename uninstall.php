<?php

/* 
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

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

// Deregister

unregister_setting( 'dh-do-key', 'dreamobj_settings' );
unregister_setting( 'dh-do-secretkey', 'dreamobj_settings' );
unregister_setting( 'dh-do-bucket', 'dreamobj_settings' );
unregister_setting( 'dh-do-section', 'dreamobj_settings' );
unregister_setting( 'dh-do-schedule', 'dreamobj_settings' );