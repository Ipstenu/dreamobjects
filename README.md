# DreamObjects Backup #

This is the development repository for the DreamObjects Backup plugin.

* [WP Readme](readme.txt)
* [Changelog](changelog.txt)
* [WordPress Repoitory](https://wordpress.org/plugins/dreamobjects/)

## Update

To update libraries, run:  `composer update` 

To update for WP:

* Copy the code from ~/Development/wordpress/plugins-git/dreamobjects to ~/Development/wordpress/plugins-svn/trunk/dreamobjects

```
rsync -va --delete --exclude debug.txt --exclude vendor/ --exclude .git/ --exclude .DS_Store --exclude composer.* --exclude README.md ~/Development/wordpress/plugins-git/dreamobjects/ .
```

* Run `svn status` to see what’s missing or needs removing
* Run svn commit: `svn ci -m "Version X"`
* Run SVN CP

```
svn cp https://plugins.svn.wordpress.org/dreamobjects/trunk https://plugins.svn.wordpress.org/dreamobjects/tags/TAG/
```