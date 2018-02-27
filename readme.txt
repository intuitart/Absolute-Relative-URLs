=== Absolute <> Relative URLs ===
Contributors: intuitart
Tags: absolute, relative, url, seo, portable, website
Requires at least: 4.4.0
Tested up to: 4.9
Stable tag: 1.5.6
Version: 1.5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Save relative URLs to database. Present absolute URLs for viewing.

== Description ==

This plugin makes your Wordpress content adaptable in that you can present content from a domain other than the one in it which it was created. It achieves this by saving URLs as relative URLs. At the same time it supports SEO requirements by reverting to absolute URLs when they are displayed.

This is useful when moving a site from development to staging to production. Or perhaps your site has been taken over by a new organization and they want to run under a different domain. Or perhaps you want to move the ~/uploads folder ourside of Wordpress core. Whatever the reason, simply change the Wordpress Address (URL) and Site Address (URL) in your new environment, and you should be good to go.

In addition to moving the whole site to a new domain, you can identify specific domains as being related. This allows you to copy raw content from one related site and paste it into another. The plugin will recognize the related domain and remove it as it gets saved. Then it will display the absolute URLs of the current domain when it is viewed.

For the technically inclined, the plugin removes the get_bloginfo('url') and get_bloginfo('wpurl') portions of a URL as content is saved and inserts them again as content is viewed.

== Installation ==

1. In WordPress go to Plugins->Add New and locate the plugin (e.g. search ‘absolute relative url’
1. Click the install button
1. Activate the plugin through the ‘Plugins’ menu

Alternatively you can download the plugin from www.oxfordframework.com/absolute-relative-urls, upload it through the Wordpress plugin uploader, and activate through the Plugins menu.

That's it! Check your database after you've saved some content. URLs should be root relative. Check your editor. URLs should be absolute. Check the source on your web page. URLs should be absolute.

The plugin does not retroactively modify urls in your database unless you manually update content.

Should you stop using the plugin your website will still work as the plugin uses root relative urls and browsers assume the same domain when they see a relative url. Exceptions would be when a you are running in a subdirectory and that is part of your site url, or if you are providing an RSS feed to third parties where absolute urls are required.

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
