<?php

/*
Plugin Name: Absolute &lt;&gt; Relative URLs
Plugin URI: https://www.oxfordframework.com/absolute-relative-urls
Description: Saves relative URLs to database. Displays absolute URLs.
Author: Andrew Patterson
Author URI: http://www.pattersonresearch.ca
Tags: relative, absolute, url, seo, portable, multi-site, network
Version: 1.6.0
Date: 10 March 2019
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'of_absolute_relative_urls' ) ) {

	class of_absolute_relative_urls {

		private static $upload_path; // path only, not url
		private static $sites_path; // '/sites/<n>' part of upload path, current site
		private static $sites_pattern; // '/sites/<n>' preg_replace pattern, unknown site
		private static $wpurl; // wp url (upload url)
		private static $url; // site url (domain, or domain/folder)
		private static $urls; // urls to replace when making relative urls
		private static $del; // delimiter for preg_replace
		private static $pattern; // pattern to match

		// initialize
		public static function init() {
			self::set_vars();
			self::set_filters();
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
				$content = preg_replace(
					array(
						self::$del . self::$pattern . self::$urls . '(/?)' . self::$del,
						self::$del . self::$pattern . self::$upload_path . self::$sites_pattern .  self::$del,
						),
					array(
						'${1}/',
						'${1}' . self::$upload_path,
						),
					$content
				);
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
				if ( apply_filters( 'of_absolute_relative_urls_enable_related_sites_existing_content', false ) ) {
					$content = self::relative_url( $content );
				}
				$content = preg_replace(
					array(
						self::$del . self::$pattern . self::$upload_path . self::$del,
						self::$del . self::$pattern . '(/[^/])' . self::$del
					),
					array(
						'${1}' . self::$wpurl . self::$upload_path . self::$sites_path,
						'${1}' . self::$url . '${2}'
					),
					$content
				);
			}
			return $content;
		} // absolute_url

		// set vars
		private static function set_vars() {

			// initialize class variables/constants
			self::$del = chr(127);
			self::$pattern = '(^|href=\\\\?"|src=\\\\?"|srcset=\\\\?"|data-link=\\\\?"|[0-9]+w, )';
			self::$wpurl = untrailingslashit( get_bloginfo( 'wpurl' ) );
			self::$url = untrailingslashit( get_bloginfo( 'url' ) );

			// local urls to look for
			$related_sites[] = array( 'wpurl' => self::$wpurl, 'url' => self::$url );
			$related_sites = array_merge( $related_sites, apply_filters( 'of_absolute_relative_urls_related_sites', array() ) );
			foreach( $related_sites as $sites ) {
				if ( empty( $sites['url'] ) && !empty( $sites['wpurl'] ) ) { // only wp url specified
					$urls[] = $sites['wpurl'];
				} elseif ( !empty( $sites['url'] ) && empty( $sites['wpurl'] ) ) { // only site url specified
					$urls[] = $sites['url'];
				} elseif ( $sites['wpurl'] === $sites['url'] ) { // urls are equal, use site url
					$urls[] = $sites['url'];
				} elseif ( 0 === strpos( $sites['wpurl'], $sites['url'] ) ) { // wp url first
					$urls[] = $sites['wpurl'];
					$urls[] = $sites['url'];
				} else { // site url first
					$urls[] = $sites['url'];
					$urls[] = $sites['wpurl'];
				}
			}
			self::$urls = '(' . implode( '|', $urls ) . ')';

			// upload path
			$wp_upload = wp_upload_dir();
			if ( ! $wp_upload[ 'error' ] && ( 0 === strpos( $wp_upload[ 'baseurl' ], self::$wpurl ) ) ) {
				$upload_path = substr( $wp_upload[ 'baseurl' ], strlen( self::$wpurl ) );
			} else { // fallback
				$upload_path = '/wp-content/uploads';
			}

			// split $sites_path and $upload_path if desired
			if ( apply_filters( 'of_absolute_relative_urls_parse_sites_path', false ) ) {
				self::$sites_path = strstr( $upload_path, '/sites/' );
				self::$sites_pattern = '(/sites/\d+)';
				self::$upload_path = strstr( $upload_path, '/sites/', true );
			} else {
				self::$sites_path = '';
				self::$sites_pattern = '()';
				self::$upload_path = $upload_path;
			}

		} // set_vars

		// set view and save filters
		private static function set_filters() {

			// initialize defaults
			$view_filters = array(
				'the_editor_content',
				'the_content',
				'get_the_excerpt',
				'the_excerpt_rss',
				'excerpt_edit_pre',
			);
			$save_filters = array(
				'content_save_pre',
				'excerpt_save_pre',
			);
			$option_filters = array( // view and save filters
				'theme_mods_' . get_option('template'),
				'theme_mods_' . get_option('stylesheet'),
				'text',
				'widget_black-studio-tinymce',
				'widget_sow-editor',
			);

			// Option filters
			$option_filters = apply_filters( 'of_absolute_relative_urls_option_filters', $option_filters );
			foreach( $option_filters as $filter ) {
				$view_filters[] = 'option_' . $filter;
				$save_filters[] = 'pre_update_option_' . $filter;
			}

			// View filters (Relative to Absolute)
			if ( apply_filters( 'of_absolute_relative_urls_enable_absolute', true ) ) {
				$view_filters = apply_filters( 'of_absolute_relative_urls_view_filters', $view_filters );
				foreach( $view_filters as $filter ) {
					add_filter( $filter, array( __CLASS__, 'absolute_url' ) );
				}
				// Filter $post for block editor, see ~/wp-admin/includes/post.php
				if ( apply_filters( 'of_absolute_relative_urls_use_block_editor', true ) ) {
					add_filter( 'use_block_editor_for_post', array( __CLASS__, 'filter_post_content' ), 2, 100 );
				}
			}

			// Save filters (Absolute to Relative)
			if ( apply_filters( 'of_absolute_relative_urls_enable_relative', true ) ) {
				$save_filters = apply_filters( 'of_absolute_relative_urls_save_filters', $save_filters );
				foreach( $save_filters as $filter ) {
					add_filter( $filter, array( __CLASS__, 'relative_url' ) );
				}
			}
		} // set_filters
		
		// Special filter/action to filter $post and update cache
		public static function filter_post_content( $filter = false, $post = '' ) {
			if ( $filter ) {
				// default to global $post if none received
				if ( empty( $post ) ) {
					global $post;
				}
				$post->post_content = self::absolute_url( $post->post_content );
				$post->post_excerpt = self::absolute_url( $post->post_excerpt );
				// need to update the cache so the front end gets the filtered content
				wp_cache_replace( $post->ID, $post, 'posts' );
			}
			return $filter;
		} // filter_post_content

	} // class of_absolute_relative_urls
	add_action( 'init', array( 'of_absolute_relative_urls', 'init' ) );
}


/*
 * Summary of actions/filters supported by this plugin
 * Copy example to your functions.php and customize
 *
 */

// override on/off settings
//add_filter( 'of_absolute_relative_urls_enable_relative', function() { return false; } );
//add_filter( 'of_absolute_relative_urls_enable_absolute', function() { return false; } );
//add_filter( 'of_absolute_relative_urls_enable_related_sites_existing_content', function() { return true; } );
//add_filter( 'of_absolute_relative_urls_parse_sites_path', function() { return true; } );
//add_filter( 'of_absolute_relative_urls_use_block_editor', function() { return false; } );

// adjust other inputs

//add_filter( 'of_absolute_relative_urls_related_sites',
//	function( $related_sites ) {
//		$related_sites[]['url'] = "http://multifolder.apatterson.org/site2";
//		return $related_sites;
//	}
//);

//add_filter( 'of_absolute_relative_urls_option_filters',
//	function ( $option_filters ) {
//		$option_filters[] = 'custom_option_filter';
//		return $option_filters;
//	}
//);

//add_filter( 'of_absolute_relative_urls_view_filters',
//	function ( $view_filters ) {
//		$view_filters[] = 'custom_view_filter';
//		return $view_filters;
//	}
//);

//add_filter( 'of_absolute_relative_urls_save_filters',
//	function ($save_filters ) {
//		$view_filters[] = 'custom_save_filter';
//		return $save_filters;
//	}
//);
