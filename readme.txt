=== Absolute Relative URLs ===
Contributors: intuitart
Tags: absolute, relative, url, seo, portable, website
Requires at least: 4.4.0
Tested up to: 5.7
Stable tag: 1.6.2
Version: 1.6.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Want to host your Wordpress site from a different domain? This plugin will help!

== Description ==

* Develop/stage in one domain, go live in another.
* Backup a production site from one domain, restore to a test site at another domain.
* Migrate from one domain to another with minimal effort.
* Ease migration between stand alone and multi-site installations.
* Ease migration between domain and sub-folder installations.
* Switch between ssl and non-ssl sites.
* Always present your content in an SEO friendly way.

We aim to achieve these capabilities with this plugin. The idea is to remove creator urls as content is produced, and play the current url when content is viewed. By default, Wordpress saves the local url with content, and that makes it a challenge to access your content from a different domaim, even when you have a legitimate reason to do so.

This plugin makes your Wordpress content adaptable in that you can present content from a domain other than the one in it which it was produced. It achieves this by saving URLs as relative URLs. At the same time it supports SEO requirements by reverting to absolute URLs when content is viewed.

In addition to moving the whole site to a new domain, you can identify specific domains as being related. This allows you to copy raw content from one related site and paste it into another. The plugin will recognize the related domain and remove it as it gets saved. Then it will display the absolute URLs of the current domain when it is viewed.

For the technically inclined, the plugin removes the get_bloginfo('url') and get_bloginfo('wpurl') parts of a URL as content is saved and inserts them again as content is viewed.

We use context and some configurable rules to determine when to apply conversions in both directions. Most of the time you can go with the defaults. If you have a situation where something doesn't appear to work, let me know your experience (with as much detail as possible please).

== Installation ==

1. In WordPress go to Plugins->Add New and locate the plugin (e.g. search ‘absolute relative url’
1. Click the install button
1. Activate the plugin through the ‘Plugins’ menu

That's it! Check your database after you've saved some content. URLs should be root relative. Check your editor. URLs should be absolute. Check the source on your web page. URLs should be absolute.

The plugin does not retroactively modify urls in your database unless you manually update content. However, it can convert urls as needed. See notes about related sites.

Should you stop using the plugin your website will still work as the plugin uses root relative urls and browsers assume the same domain when they see a relative url. Exceptions would be when a you are running in a subdirectory and that is part of your site url, if you are providing an RSS feed to third parties where absolute urls are required, or if you use the multi-site conversion.

* New in version 1.6.2

Fixed instance() method, so it can be called statically and avoid deprecation warning about calling non-static method.

* New in version 1.6.1

Converted to a singleton instantiated class in place of static.

Modified the filter to add related site urls so that you only need to specify each url. You no longer need to specify whether the url represents the site or wp url. The original method still works, for now.

    // no longer requires ['wpurl'] or ['url'] to be specified, just add a url
    add_filter( 'of_absolute_relative_urls_related_sites',
    	function( $related_sites ) {
    		$related_sites[] = "http://multifolder.apatterson.org/site2";
    		return $related_sites;
    	}
    );

Introduced filters to override Site and Wordpress URLs. Use cases for this have come up a couple of times in the support forum (@colinguqbio, @houba_houbi). Now it is possible without hacking the code.

    // example: set Wordpress url to a specific url
    add_filter( 'of_absolute_relative_urls_wpurl', function() { return "https://www.mydomain.com/blog"; } );

    // example: set Site url same as Wordpress url
    add_filter( 'of_absolute_relative_urls_url', function( $url, $wpurl ) { return $wpurl; }, 10, 2 );

* New in version 1.6.0

With the introduction of the Wordpress block editor, urls were not being converted to absolute urls when editing content. They were converted properly when viewing content on the front end, and that is where it is important. But for consistency, and to meet the intent of the plugin, you now see absolute urls in the block editor. This is enabled by default. You can disable it with the following code in your functions.php:

	// disable conversion to absolute urls when editing using the block editor
	add_filter( 'of_absolute_relative_urls_use_block_editor', function() { return false; } );

A new feature in this version is the ability to remove and restore the 'sites/<n>' part of the upload path when running in a multi-site environment. Unlike url conversions, where most websites will continue to display appropriately when you deactivate this plugin, this feature requires this plugin to be active in order to restore the sites part of the upload path. On the other hand, you should be able to move a stand alone site into a multi-site environment and let this plugin insert the sites part of the upload path without having to convert your database. To enable this feature, add the following filter to your functions.php:

	// enable multi-site feature for upload path
	add_filter( 'of_absolute_relative_urls_parse_sites_path', function() { return true; } );

Tested plugin in a multi-site environment and confirmed that conversion of urls, except for the sites part of the upload path, work as expected. In a multi-domain environment, the domain is removed and restored. In a multi-folder environment, the domain and folder are removed and restored. This is the same as running stand-alone with a domain only, or in a folder within a domain.

Another feature introduced in this version is the ability to disable the conversion to absolute or relative urls. There are other wordpress plugins that convert to relative urls, but don't offer the complement. Disable the conversion to absolute urls and you have the same functionality. Or should you want to revert back to having absolute urls stored in the database, disable relative urls, then edit and save content to restore the full urls to your database. To disable one or the other, add one of the following to your functions.php:

	// disable absolute url conversion on your website
	add_filter( 'of_absolute_relative_urls_enable_absolute', function() { return false; } );

	// disable relative url conversion on your website
	add_filter( 'of_absolute_relative_urls_enable_relative', function() { return false; } );

What may be the most poweful new feature is the ability to display your site under your current domain, even when it was created under a different domain, without doing any conversion. That's right. We've enhanced the related sites feature to do real time conversion from existing content, not just new content. You can now migate one domain to another, add a related site for the former domain, and enable related sites for existing content. To do this last bit, add the following to your functions.php:

	// enable related sites feature for existing content
	add_filter( 'of_absolute_relative_urls_enable_related_sites_existing_content', function() { return true; } );

When you set up related sites, and the site url and wordpress url are the same, you can now specify either one. For example, either of the following will work:

	// add a related site using 'site url'
	add_filter( 'of_absolute_relative_urls_related_sites',
		function( $related_sites ) {
			$related_sites[]['url'] = "http://apatterson.org/site2";
			return $related_sites;
		}
	);
	// add a related site using 'wordpress url'
	add_filter( 'of_absolute_relative_urls_related_sites',
		function( $related_sites ) {
			$related_sites[]['wpurl'] = "http://site2.apatterson.org";
			return $related_sites;
		}
	);

One more thing. We now parse 'data-link' urls along with others such as 'href' and 'src'. We discovered these being used in the gallery component of the block editor.

* New in version 1.5.4

Allow urls from related sites to be saved as relative urls. This makes it easier to copy/paste html content from one site to another (e.g. staging to production, production to development). Note that this doesn't solve all problems with copy/paste from one site to another. For example, if images on one site are in a different folder, they will still need to be tweaked manually.

To add related sites, add a filter and function to your functions.php similar to the following:

	// add related sites to be saved as relative urls
	add_filter( 'of_absolute_relative_urls_related_sites', 'my_related_sites' );
	function my_related_sites( $related_sites ) {
		$related_sites[] = [
			'wpurl' => 'https://www.chennabaree.com', // wp url
			'url' => 'https://www.chennabaree.com' // site url
		];
		$related_sites[] = [
			'wpurl' => 'https://www.schoolofunusualarts.com'
		];
		return $related_sites;
	}

Note: if site url and wp url are identical, you only need to specify 'wpurl'.

* New in version 1.5.0, 1.5.1

Enable all options instead of specific options. In functions.php, put:

	// enable all options
	add_filter( 'of_absolute_relative_urls_enable_all', function() { return true; } );

Manage filters that get processed by modifying the array of filters. Build a function to add or remove filter names in the array. Then in functions.php, put:

	// modify list of filters to include or exclude
	add_filter( 'of_absolute_relative_urls_{type}_filters', 'your_function' );

where {type} is 'view', 'save', 'option' or 'exclude_option'.


== Changelog ==

= 1.6.1 =
* Modified filter to specify related sites_path
* Converted plugin to singleton class
* Introduced related plugin to manage settings
* Added a public function to return current plugin version

= 1.6.0 =
* Fixed things so that urls are converted to absolute urls when editing content using the block editor
* Added feature to remove and restore the 'sites/<n>' part of the upload path when running in a multi-site environment
* Added ability to disable either relative or absolute conversions
* Restructured the code where filters are set, in part to simplify and in part to make it easier to disable relative or absolute conversions
* Added ability to convert related sites in real time
* Dropped the ability to filter 'all' options, now only supports identifying options to be filtered (this was always the default)
* We now parse 'data-link' urls used by the gallery in the block editor

= 1.5.6 =
* Fixed problems displaying urls when Wordpress Address and Site Address in General Settings are not the same url

= 1.5.5 =
* Fixed array definition to use array() instead of square brackets and be compatible with php prior to version 5.4
* Updated Description documentation

= 1.5.4 =
* Add related sites to list of urls that can be saved as relative urls (thanks @tythewebguy)
* Reinstated conversion of urls that are the only content in a field (affects things like header image urls)
* Reduced both save and view conversions to run in a single grep_replace
* Put copy on github in case anyone wants to fork or contribute, https://github.com/intuitart/Absolute-Relative-URLs
* Tested Wordpress version 4.9.x

= 1.5.3 =
* Ignore // at beginning of url when displaying urls as this is sometimes used for schema relative urls (thanks @ublac)
* Ignore urls in content that is not prefixed by src, href, etc. when saving urls (thanks @timbobo)
* Created a single pattern that is used for all save and display filters
* Appended a / when saving a url and a domain without a trailing slash was used
* Tested Wordpress version 4.8

= 1.5.2 =

* Tweaked algorithm that generates absolute urls to better catch edge cases.
* Move WP options, for exclusion from 'all' options, into separate file.
* Moved derivation of 1st and 2nd urls required when creating absolute urls so it only runs once, on class init().

= 1.5.1 =

* Enable 'all' options filter wasn't working. Fixed.
* Added filter to allow additional option exclusions when 'all' options are enabled.
* Updated readme.txt.

= 1.5.0 =

* Tested up to WP 4.7
* Wrapped code in a class.
* Added a couple more editor option hooks to catch more urls.
* Included img 'srcset' attribute when viewing content.
* Added filters to allow additional view/save hooks or options to be added.
* Added ability to filter all options, with exclusions, instead of filtering specific options. This is not enabled by default. Excluded are the built in Wordpress options.

= 1.4.2 =

* Tested up to WP 4.6.1
* Updated readme.txt
* Added icon to display on plugins page

= 1.4.1 =

* Updated readme.txt to include wordpress.org installation and format correctly in validator
* Renamed plugin file and folder to match plugin name submitted to Wordpress

= 1.4 =

* Added function to more reliably determine site's base upload path (typically 'wp-content/uploads')
* Distinguished between wordpress and site urls so that wordpress can run separate from domain root
* Tested and confirmed the following scenarios work, all from the same database:
 * Wordpress and site urls are the same and running from root (http or https)
 * Wordpress and site urls are the same and running from a subdirectory (e.g ~/wordpress)
 * Wordpress url is subdirectory and site url is root directory

= 1.3 =

* Cleaned up to meet wordpress.org coding standards
* Tweaked the code to use trailingslashit($string) rather than hard code $string . ‘/’

= 1.2 =

* Add filters for 'stylesheet' and 'template' options to catch things like header image
* Moved view filter for tinymce to option so save and view are at the same level
* Added ability to parse object data types when saving and viewing
* Explicitly handle string data type rather than assuming string
* Return content unfiltered for data types other than array, object and string
* Put view, save and options filters in arrays to document and make it easier to add/remove filters
* Updated description and installation

= 1.1 =

* Added updates to the excerpt field when it is entered separately from the content

= 1.0 =

* First release, catches post_content and widget_black-studio-tinymce updates

