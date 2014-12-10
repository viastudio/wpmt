# opt-via
This repository contains versions of WordPress and WordPress plugins. [VIA Studio](http://viastudio.com) open sourced it to demonstrate a real world [WordPress Multitenant](http://jason.pureconcepts.net/2013/04/updated-wordpress-multitenancy/) setup.

## Setup
- Clone this repository on your system. We use `/opt/via/`.
- Create the *single symlink* for the WordPress files. We use:

        ln -s /opt/via/wordpress/latest /var/www/viastudio.com/wordpress

- Update your configuration as outlined in [Giving WordPress Its Own Directory](http://codex.wordpress.org/Giving_WordPress_Its_Own_Directory)

## Additional Notes
We add the additional `latest` symlink for maintainability and flexibility. We can upgrade sites by adding a new versioned directory to `opt-via` and update the `latest` symlink. We also have the flexibility to use a specific version of WordPress for a site by simply changing that site's symlink from `latest` to a versioned directory.

We have found some plugins and themes break once you move WordPress to its own directory. These plugins incorrectly rely on the `SITEURL` option. While you can patch these yourself, we encourage you to inform the author so they can correct their code.

After adopting this WordPress multitenant architecture, we noticed a 400% improvement. Mainly due to all WordPress files fitting within the [PHP OPCache](http://php.net/manual/en/intro.opcache.php). Not only making PHP more performant, but decreasing load on the system.