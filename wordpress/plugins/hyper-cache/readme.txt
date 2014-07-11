=== Hyper Cache ===
Tags: cache,chaching,speed,performance,super cache,wp cache,optimization,staticization
Requires at least: 2.5
Tested up to: 3.8.1
Stable tag: trunk
Donate link: http://www.satollo.net/donations

Hyper Cache is flexible and easy to configure cache system for WordPress.

== Description ==

Hyper Cache is a new cache system for WordPress, specifically written for
people which have their blogs on low resources hosting provider 
(cpu and mysql). It works even with hosting based on Microsoft IIS (just tuning
the configuration). It has three invalidation method: all the cache, single post
based and nothing but with control on home and archive pages invalidation.

It has not yet tested for multisite configuration (WordPress 3.0 feature).

An alternative to Hyper Cache is Lite Cache which probably is simpler to configure but
with the same performace level. Lite Cache is able to cache pages for commenters, too,
which are usually excluded from other type of caches.

Some features:

* compatible with the plugin wp-pda which enables a blog to be *accessible from mobile devices*
* manages (both) *plain and gzip compressed pages*
* autoclean system to reduce the disk usage
* 404 caching
* redirects caching
* easy to configure
* Global Translator compatibility
* Last Modified http header compatibility with 304 responses
* compressed storage to reduce the disk space usage
* agents, urls and cookies based rejection configurable
* easy to integrated with other plugins

More can be read on the [official plugin page](http://www.satollo.net/plugins/hyper-cache) and write me
if you have issues to info@satollo.net.

New contributors policy: since in the pas I had problem with link to contributors sites, I removed
them but I would like to add a reference to profile pages of contributors on WordPress, Twitter,
Facebook, Google+. Those links should be safe.

**Check out my other plugins**:

* [Post Layout](http://www.satollo.net/plugins/post-layout "Post Layout WordPress plugin: the easy way to enrich your posts")
* [Comment Notifier](http://www.satollo.net/plugins/comment-notifier "Keep your blog discussions on fire")
* [Feed Layout](http://www.satollo.net/plugins/feed-layout "Feed Layout WordPress plugin: the easy way to enrich your feed contents")
* [Dynatags](http://www.satollo.net/plugins/dynatags "Dynatags WordPress plugin: Create your own custom short tag in seconds")
* [Header and Footer](http://www.satollo.net/plugins/header-footer)
* [Newsletter](http://www.satollo.net/plugins/newsletter)

Thanks to:

* Amaury Balmer for internationalization and other modifications
* Frank Luef for german translation
* HypeScience, Martin Steldinger, Giorgio Guglielmino for test and bugs submissions
* Ishtiaq to ask me about compatibility with wp-pda
* Gene Steinberg to ask for an autoclean system
* Marcis Gasun (fatcow.com) for Bielorussian translation
* many others I don't remember
* Florian Höch
* Quentin
* Mckryak
* Tommy Tung alias Ragnarok


== Installation ==

1. Put the plugin folder into [wordpress_dir]/wp-content/plugins/
2. Go into the WordPress admin interface and activate the plugin
3. Optional: go to the options page and configure the plugin

Before upgrade DEACTIVATE the plugin and then ACTIVATE and RECONFIGURE!

== Frequently Asked Questions ==

See the [Hyper Cache official page](http://www.satollo.net/plugins/hyper-cache) 
    
== Screenshots ==

No screenshots are available.

== Changelog ==

= 2.9.1.6 =

* Fixed some debug noticies

= 2.9.1.5 =

* gzip path by Onno Molenkamp

= 2.9.1.4 =

* Added support for the static front page

= 2.9.1.3 =

* Added a check to remove a warning

= 2.9.1.2 =

* Fixed a file creation on activation

= 2.9.1.1 =

* fixed the new WP_CONTENT_DIR usage

= 2.9.1.0 =

* https fix by (http://foliovision.com)
* fixed the WP_CONTENT_DIR usage
* some "undefined index" notices because people and PHP pretend that an associative array should be checked for a key before asking for its content... (terrible and inefficient)
* cache path save with single quote to avoid problem with windows paths

= 2.9.0.3 =

* potential duplicated content fix

= 2.9.0.2 =

* added French translation

= 2.9.0.1 =

* small fix for trailing slashes

= 2.9.0 =

All patches listed below are by Florian Höch (as soon has his blog will be online I'll give a link to it)

* compression on the fly option when browser accept compressed data but is set to not store it
* added the Vary header
* fixed the Cache-Control/Expires and Last-Modified headers
* remove the trailing slash for permalink (even if WordPress should send a redirect and Hyper Cache should already intercept it)
* added a few safety checks for gzencode/decode functions in cache.php
* new configuration option: allow browser caching
* allow browser to bypass the server-side cache
* some options panel fixes and improvements

= 2.8.9 =

* TW and CN translations changed (by Ragnarok)

= 2.8.8 =

* Internationalization fixes

= 2.8.7 =

* Admin panel fixes
* Introduced the text domain (re-trnslation needed)

= 2.8.6 =

* Chinese translation by Ragnarok!

= 2.8.5 =

* fixed the "is_home" warning issue

= 2.8.4 =

* fixed the single page invalidation

= 2.8.3 =

* fixed the clean from admin panel

= 2.8.2 =

* moved the cache folder to wp-content/cache/hyper-cache
* configuration panel has no more expandable sections
* the cached pages are no more deleted on update

= 2.8.1 =

* fixed the Last Modified header (thanks Yuri C.)