<?php

/*
Plugin Name: Absolute &lt;&gt; Relative URLs NEW
Plugin URI: https://www.oxfordframework.com/absolute-relative-urls
Description: Saves relative URLs to database. Displays absolute URLs.
Author: Andrew Patterson
Author URI: http://www.pattersonresearch.ca
Tags: relative, absolute, url, seo, portable
Version: 1.5.0
Date: 5 Jan 2017
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'of_absolute_relative_urls' ) ) {

	class of_absolute_relative_urls {

		private static $upload_path;
		private static $wpurl;
		private static $url;
		private static $exclude_options = array();
		
		// initialize
		public static function init() {
			self::set_vars();
			self::set_filters();
			self::set_option_filters();
		} // init
		
		// set vars: upload_path, wpurl and url
		private static function set_vars() {
			self::$wpurl = trailingslashit( get_bloginfo( 'wpurl' ) );
			self::$url = trailingslashit( get_bloginfo( 'url' ) );
			$wp_upload = wp_upload_dir();
			if ( ! $wp_upload[ 'error' ] && ( 0 === strpos( $wp_upload[ 'baseurl' ], self::$wpurl ) ) ) {
				self::$upload_path = substr( $wp_upload[ 'baseurl' ], strlen( self::$wpurl ) );
			} else { // fallback
				self::$upload_path = 'wp-content/uploads';
			}
		} // set_vars
		
		// set view and save filters
		private static function set_filters() {
			// View filters (Relative to Absolute)
			$view_filters = array(
				'the_editor_content',
				'the_content',
				'get_the_excerpt',
				'the_excerpt_rss',
				'excerpt_edit_pre',
			);
			$view_filters = apply_filters( 'of_absolute_relative_urls_view_filters', $view_filters );
			foreach( $view_filters as $filter ) {
				add_filter( $filter, array( __CLASS__, 'absolute_url' ) );
			}
			// Save filters (Absolute to Relative)
			$save_filters = array(
				'content_save_pre',
				'excerpt_save_pre',
			);
			$save_filters = apply_filters( 'of_absolute_relative_urls_save_filters', $save_filters );
			foreach( $save_filters as $filter ) {
				add_filter( $filter, array( __CLASS__, 'relative_url' ) );
			}
		} // set_filters
		
		// Options filters (Both directions)
		private static function set_option_filters() {
			$enable_all = apply_filters( 'of_absolute_relative_urls_enable_all', false );

			// Add specific option filters if the 'all' filter is not enabled
			if ( ! $enable_all ) {
				$option_filters = array( // defaults
					'theme_mods_' . get_option('template'),
					'theme_mods_' . get_option('stylesheet'),
					'text',
					'widget_black-studio-tinymce',
					'widget_sow-editor',
				);
				$option_filters = apply_filters( 'of_absolute_relative_urls_option_filters', $option_filters );
				foreach( $option_filters as $filter ) {
					add_filter( 'pre_update_option_' . $filter, array( __CLASS__, 'relative_url' ) );
					add_filter( 'option_' . $filter, array( __CLASS__, 'absolute_url' ) );
				}
				return;
			}
			
			// identify options to exclude from 'all'
			self::$exclude_options = array(
				'siteurl',
				'home',
				'blogname',
				'blogdescription',
				'users_can_register',
				'admin_email',
				'start_of_week',
				'use_balanceTags',
				'use_smilies',
				'require_name_email',
				'comments_notify',
				'posts_per_rss',
				'rss_use_excerpt',
				'mailserver_url',
				'mailserver_login',
				'mailserver_pass',
				'mailserver_port',
				'default_category',
				'default_comment_status',
				'default_ping_status',
				'default_pingback_flag',
				'posts_per_page',
				'date_format',
				'time_format',
				'links_updated_date_format',
				'comment_moderation',
				'moderation_notify',
				'permalink_structure',
				'rewrite_rules',
				'hack_file',
				'blog_charset',
				'moderation_keys',
				'active_plugins',
				'category_base',
				'ping_sites',
				'comment_max_links',
				'gmt_offset',

				// 1.5
				'default_email_category',
				'recently_edited',
				'template',
				'stylesheet',
				'comment_whitelist',
				'blacklist_keys',
				'comment_registration',
				'html_type',

				// 1.5.1
				'use_trackback',

				// 2.0
				'default_role',
				'db_version',

				// 2.0.1
				'uploads_use_yearmonth_folders',
				'upload_path',

				// 2.1
				'blog_public',
				'default_link_category',
				'show_on_front',

				// 2.2
				'tag_base',

				// 2.5
				'show_avatars',
				'avatar_rating',
				'upload_url_path',
				'thumbnail_size_w',
				'thumbnail_size_h',
				'thumbnail_crop',
				'medium_size_w',
				'medium_size_h',

				// 2.6
				'avatar_default',

				// 2.7
				'large_size_w',
				'large_size_h',
				'image_default_link_type',
				'image_default_size',
				'image_default_align',
				'close_comments_for_old_posts',
				'close_comments_days_old',
				'thread_comments',
				'thread_comments_depth',
				'page_comments',
				'comments_per_page',
				'default_comments_page',
				'comment_order',
				'sticky_posts',
				'widget_categories',
				'widget_text',
				'widget_rss',
				'uninstall_plugins',

				// 2.8
				'timezone_string',

				// 3.0
				'page_for_posts',
				'page_on_front',

				// 3.1
				'default_post_format',

				// 3.5
				'link_manager_enabled',

				// 4.3.0
				'finished_splitting_shared_terms',
				'site_icon',

				// 4.4.0
				'medium_large_size_w',
				'medium_large_size_h',
			);
			add_action( 'all', array( __CLASS__, 'filter_all_options' ) );
		} // set_option_filters

		// dynamically add option filters
		public static function filter_all_options( $filter ) {
			// view i.e. get_option filters
			if ( 0 === strpos( $filter, 'option_' ) ) {
				if ( ! has_filter( $filter, array( __CLASS__, 'absolute_url' ) ) ) {
					$option = substr( $filter, 7 );
					if ( ! in_array( $option, self::$exclude_options ) && false === strpos( $option, 'transient' ) ) {
						add_filter( $filter, array( __CLASS__, 'absolute_url' ) );
					}
				}
		    }
			// save i.e update_option filters
		    if ( 0 === strpos( $filter, 'pre_update_option_' ) ) {
				if ( ! has_filter( $filter, array( __CLASS__, 'relative_url' ) ) ) {
					$option = substr( $filter, 18 );
					if ( ! in_array( $option, self::$exclude_options ) && false === strpos( $option, 'transient' ) ) {
			    		add_filter( $filter, array( __CLASS__, 'relative_url' ) );
					}
				}
			}
		} // filter_all_options

		// Remove domain from urls when saving content
		public static function relative_url( $content ) {
			if ( is_array( $content ) ) {
				foreach ( $content as $key => $value ) {
					$content[ $key ] = self::relative_url( $value );
				}
			} elseif ( is_object( $content ) ) {
				foreach ( $content as $key => $value ) {
					$content->$key = self::relative_url( $value );
				}
			} elseif ( is_string( $content ) ) {
				if ( self::$wpurl === self::$url ) { // doesn't matter which url gets used
					$content = str_replace( self::$url, '/', $content );
				} elseif ( 0 === strpos( self::$wpurl, self::$url ) ) { // replace wp url first
					$content = str_replace( self::$wpurl, '/', $content );
					$content = str_replace( self::$url, '/', $content );
				} else { // replace site url first
					$content = str_replace( self::$url, '/', $content );
					$content = str_replace( self::$wpurl, '/', $content );
				}
			}
			return $content;
		} // relative_url

		// Add domain to urls when displaying/editing content
		public static function absolute_url( $content ) {
			if ( is_array( $content ) ) {
				foreach ( $content as $key => $value ) {
					$content[ $key ] = self::absolute_url( $value );
				}
			} elseif ( is_object( $content ) ) {
				foreach ( $content as $key => $value ) {
					$content->$key = self::absolute_url( $value );
				}
			} elseif ( is_string( $content ) ) {
				$delim = chr(127);
				// content is a url field, not prefixed with 'src' or 'href'
				if ( 0 === strpos( $content, '/' . self::$upload_path ) ) {
					$content = self::$wpurl . substr( $content, 1 );
				} else { // regular content
					// do wpurl first, look for 'src', 'href', 'srcset' and ', ' followed by upload path
					$content = preg_replace( $delim . '(src="|href="|srcset="|, )/' . self::$upload_path . $delim, '${1}' . self::$wpurl . self::$upload_path, $content );
					// now do url, just 'href' not followed by upload path
					$content = str_replace( 'href="/', 'href="' . self::$url, $content );
				}
			}
			return $content;
		} // absolute_url

	}
	of_absolute_relative_urls::init();
}
