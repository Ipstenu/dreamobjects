# DreamObjects Backup #

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html) [![Stable Version: 4.3.0](https://img.shields.io/badge/Version-4.3.0-success)](https://wordpress.org/plugins/dreamobjects/)

This is the development repository for the DreamObjects Backup plugin.

## Update

To update libraries, run:  `composer update`

To update for WP:

* Copy the code from ~/Development/wordpress/plugins-git/dreamobjects to ~/Development/wordpress/plugins-svn/trunk/dreamobjects

```
rsync -va --delete --exclude debug.txt --exclude .git/ --exclude .DS_Store --exclude README.md ~/Development/wordpress/plugins-git/dreamobjects/ .
```

(Or if you're on Mika's computer `git wp-sync dreamobjects` does the same thing)

* Run `svn status` to see what’s missing or needs removing
* Run svn commit: `svn ci -m "Version X"`
* Run SVN CP

```
svn cp https://plugins.svn.wordpress.org/dreamobjects/trunk https://plugins.svn.wordpress.org/dreamobjects/tags/TAG/
```
