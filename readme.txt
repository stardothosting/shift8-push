=== Shift8 Push ===
* Contributors: shift8
* Donate link: https://www.shift8web.ca
* Tags: push, staging, production, content
* Requires at least: 3.0.1
* Tested up to: 6.3
* Stable tag: 1.0.1
* License: GPLv3
* License URI: http://www.gnu.org/licenses/gpl-3.0.html

This is a plugin that pushes a single post or page to an external site via the REST API

== Instructions for setup ==

1. Generate API keys on the destination server
2. Setup plugin on your source server and configure API keys
3. When editing a single post or page, a "Push" button will appear to push the changes to the server.

== Want to see the plugin in action? ==

There isn't anything to see! This is transparent API interactions from the source server to the destination server.

== Features ==

- Fully pushes all content of a single post or page from your source server (i.e. staging) to the destination server (i.e. production)
- If the page or post doesnt exist, it will create it and clone the slug
- If the page or post exists, it will overwrite the content with the source server.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/shif8-push` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to the plugin settings page and define your settings

== Frequently Asked Questions ==

= I tested it on my site and its not working for me! =

Visit the support forums here and let us know. We will try our best to help!

== Screenshots ==

1. Main settings page of plugin admin

== Changelog ==

= 0.0.1 =
* Plugin created

= 0.1.0 = 
* Wordpress 6.3 compatibility

= 1.0.1 =
* Wordpress update
