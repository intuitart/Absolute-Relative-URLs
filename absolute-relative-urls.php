<?php

/*
Plugin Name: Absolute &lt;&gt; Relative URLs
Plugin URI: https://www.oxfordframework.com/absolute-relative-urls
Description: Saves relative URLs to database. Displays absolute URLs.
Author: Andrew Patterson
Author URI: http://www.pattersonresearch.ca
Tags: relative, absolute, url, seo, portable
Version: 1.5.3
Date: 19 Jun 2017
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'of_absolute_relative_urls' ) ) {

	class of_absolute_relative_urls {

		private static $upload_path; // path only
		private static $wpurl; // wp url (upload url)
		private static $url; // site url
		private static $url1; // first url when making relative urls
		private static $url2; // second url when making relative urls
		private static $delim; // delimiter for preg_replace
		private static $exclude_options = array(); // exclusions when doing 'all' options
		private static $pattern; // pattern to match
		
		// initialize
		public static function init() {
			self::set_vars();
			self::set_filters();
			self::set_option_filters();
		} // init
		
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
				$content = preg_replace( self::$delim . self::$pattern .  self::$url1 . '(/?)' . self::$delim, '${1}/', $content);
				if ( self::$url2 ) {
					$content = preg_replace( self::$delim . self::$pattern .  self::$url2 . '(/?)' . self::$delim, '${1}/', $content);
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
			} elseif ( is_string( $content ) ) { // wp url, then site url
				$content = preg_replace( self::$delim . self::$pattern . '(/[^/])' . self::$upload_path . self::$delim, '${1}' . self::$wpurl . '${2}' . self::$upload_path, $content );
				$content = preg_replace( self::$delim . self::$pattern . '(/[^/])' . self::$delim, '${1}' . self::$url . '${2}' , $content );
			}
			return $content;
		} // absolute_url

		// set vars
		private static function set_vars() {
			self::$delim = chr(127);
			self::$pattern = '(src=\\\\?"|href=\\\\?"|srcset=\\\\?"|[0-9]+w, )';
			self::$wpurl = untrailingslashit( get_bloginfo( 'wpurl' ) );
			self::$url = untrailingslashit( get_bloginfo( 'url' ) );
			if ( self::$wpurl === self::$url ) { // doesn't matter which url gets used
				self::$url1 = self::$wpurl;
				self::$url2 = false;
			} elseif ( 0 === strpos( self::$wpurl, self::$url ) ) { // wp url first
				self::$url1 = self::$wpurl;
				self::$url2 = self::$url;
			} else { // site url first
				self::$url1 = self::$url;
				self::$url2 = self::$wpurl;
			}
			
			// upload path
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
			
			if ( $enable_all ) {
				// Exclude specific option filters if the 'all' filter is enabled
				$exclude_options = array();
				$exclude_options = apply_filters( 'of_absolute_relative_urls_exclude_option_filters', $exclude_options );
				include( plugin_dir_path( __FILE__ ) . 'includes/wp-options.php' );
				self::$exclude_options = array_merge( $exclude_options, $wp_options );
				add_action( 'all', array( __CLASS__, 'filter_all_options' ) );
			} else {
				// Add specific option filters if the 'all' filter is not enabled
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
			}
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

	} // class of_absolute_relative_urls
	add_action( 'init', array( 'of_absolute_relative_urls', 'init' ) );
}
