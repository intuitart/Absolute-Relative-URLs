<?php

/*
Plugin Name: Absolute Relative URLs
Plugin URI: https://www.oxfordframework.com/absolute-relative-urls
Description: Want to host your Wordpress site from a different domain? This plugin will help!
Author: Andrew Patterson
Author URI: http://www.pattersonresearch.ca
Tags: relative, absolute, url, seo, portable, multi-site, network
Version: 1.6.2
Date: 2 April 2021
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'of_absolute_relative_urls' ) ) {

	class of_absolute_relative_urls {

		private $upload_path; // path only, not url
		private $sites_path; // '/sites/<n>' part of upload path, current site
		private $sites_pattern; // '/sites/<n>' preg_replace pattern, unknown site
		private $wpurl; // wp url (upload url)
		private $url; // site url (domain, or domain/folder)
		private $urls; // urls to replace when making relative urls
		private $del; // delimiter for preg_replace
		private $pattern; // pattern to match

		// Startup, private, create or get instance via of_absolute_relative_urls::instance()
		private function __construct() {
			$this->set_vars();
			$this->set_filters();
		} // __construct

		// return instance, create if one doesn't exist
		public static function instance() {
			static $instance = null;
			if ( $instance == null ) {
				$instance = new of_absolute_relative_urls();
			}
			return $instance;
		} // instance()

		// Remove domain from urls when saving content
		public function relative_url( $content ) {
			if ( is_array( $content ) ) {
				foreach ( $content as $key => $value ) {
					$content[ $key ] = $this->relative_url( $value );
				}
			} elseif ( is_object( $content ) ) {
				foreach ( $content as $key => $value ) {
					$content->$key = $this->relative_url( $value );
				}
			} elseif ( is_string( $content ) ) {
				$content = preg_replace(
					array(
						$this->del . $this->pattern . $this->urls . '(/?)' . $this->del,
						$this->del . $this->pattern . $this->upload_path . $this->sites_pattern .  $this->del,
						),
					array(
						'${1}/',
						'${1}' . $this->upload_path,
						),
					$content
				);
			}
			return $content;
		} // relative_url

		// Add domain to urls when displaying/editing content
		public function absolute_url( $content ) {
			if ( is_array( $content ) ) {
				foreach ( $content as $key => $value ) {
					$content[ $key ] = $this->absolute_url( $value );
				}
			} elseif ( is_object( $content ) ) {
				foreach ( $content as $key => $value ) {
					$content->$key = $this->absolute_url( $value );
				}
			} elseif ( is_string( $content ) ) { // wp url, then site url
				if ( apply_filters( 'of_absolute_relative_urls_enable_related_sites_existing_content', false ) ) {
					$content = $this->relative_url( $content );
				}
				$content = preg_replace(
					array(
						$this->del . $this->pattern . $this->upload_path . $this->del,
						$this->del . $this->pattern . '(/[^/])' . $this->del
					),
					array(
						'${1}' . $this->wpurl . $this->upload_path . $this->sites_path,
						'${1}' . $this->url . '${2}'
					),
					$content
				);
			}
			return $content;
		} // absolute_url

		// set vars
		private function set_vars() {

			// initialize class variables/constants
			$this->del = chr(127);
			$this->pattern = '(^|href=\\\\?"|src=\\\\?"|srcset=\\\\?"|data-link=\\\\?"|[0-9]+w, )';
			$this->wpurl = untrailingslashit( get_bloginfo( 'wpurl' ) );
			$this->url = untrailingslashit( get_bloginfo( 'url' ) );

			// current site and wp urls
			$this->wpurl = apply_filters( 'of_absolute_relative_urls_wpurl', $this->wpurl, $this->url );
			$this->url = apply_filters( 'of_absolute_relative_urls_url', $this->url, $this->wpurl );
			$urls[] = $this->url;
			if ( $this->url !== $this->wpurl ) {
				$urls[] = $this->wpurl;
			}

			// related sites urls
			$related_sites = apply_filters( 'of_absolute_relative_urls_related_sites', array() );
			foreach( $related_sites as $site ) {

				// current structure for specifying related urls, just the url
				if ( is_string ( $site ) ) {
					$urls[] = $site;
				}

				// deprecated structure for related urls, specify whether site or wp url
				elseif ( is_array( $site ) ) {
					if ( !empty( $site['url'] ) ) {
						$urls[] = $site['url'];
					}
					if ( !empty( $site['wpurl'] ) ) {
						$urls[] = $site['wpurl'];
					}
				}
			}

			// ensure longer urls get parsed first when one url is a substring of another
			rsort( $urls );

			// prepare urls for regex search and replace
			$this->urls = '(' . implode( '|', $urls ) . ')';

			// upload path
			$wp_upload = wp_upload_dir();
			if ( ! $wp_upload[ 'error' ] && ( 0 === strpos( $wp_upload[ 'baseurl' ], $this->wpurl ) ) ) {
				$upload_path = substr( $wp_upload[ 'baseurl' ], strlen( $this->wpurl ) );
			} else { // fallback
				$upload_path = '/wp-content/uploads';
			}

			// split $sites_path and $upload_path if desired
			if ( apply_filters( 'of_absolute_relative_urls_parse_sites_path', false ) ) {
				$this->sites_path = strstr( $upload_path, '/sites/' );
				$this->sites_pattern = '(/sites/\d+)';
				$this->upload_path = strstr( $upload_path, '/sites/', true );
			} else {
				$this->sites_path = '';
				$this->sites_pattern = '()';
				$this->upload_path = $upload_path;
			}

		} // set_vars

		// set view and save filters
		public function set_filters() {

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
				// Filter $post for block editor, see ~/wp-admin/includes/post.php
				if ( apply_filters( 'of_absolute_relative_urls_use_block_editor', true ) ) {
					add_filter( 'use_block_editor_for_post', array( $this, 'filter_post_content' ), 100, 2 );
				}
				$view_filters = apply_filters( 'of_absolute_relative_urls_view_filters', $view_filters );
				foreach( $view_filters as $filter ) {
					add_filter( $filter, array( $this, 'absolute_url' ) );
				}
			}

			// Save filters (Absolute to Relative)
			if ( apply_filters( 'of_absolute_relative_urls_enable_relative', true ) ) {
				$save_filters = apply_filters( 'of_absolute_relative_urls_save_filters', $save_filters );
				foreach( $save_filters as $filter ) {
					add_filter( $filter, array( $this, 'relative_url' ) );
				}
			}
		} // set_filters

		// Special filter/action to filter $post and update cache
		public function filter_post_content( $filter = false, $post = '' ) {
			if ( $filter ) {
				// default to global $post if none received
				if ( empty( $post ) ) {
					global $post;
				}
				$post->post_content = $this->absolute_url( $post->post_content );
				$post->post_excerpt = $this->absolute_url( $post->post_excerpt );
				// need to update the cache so the front end gets the filtered content
				wp_cache_replace( $post->ID, $post, 'posts' );
			}
			return $filter;
		} // filter_post_content

	} // class of_absolute_relative_urls

	// initialize on 'init'
	add_action( 'init', array( 'of_absolute_relative_urls', 'instance' ) );
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

// example: set $wpurl to a specific url
//add_filter( 'of_absolute_relative_urls_wpurl', function() { return "https://www.mydomain.com/blog"; } );

// example: WPML Integration, set $url = $wpurl when *Use directory for default language* is enabled in WPML
//add_filter( 'of_absolute_relative_urls_url',
//	function( $url, $wpurl ) {
//		if ( class_exists( 'SitePress' ) ) {
//			$wpml_url_settings = apply_filters( 'wpml_setting', false, 'urls' );
//			if ( $wpml_url_settings['directory_for_default_language'] == true ) {
//				$url = $wpurl;
//			}
//		}
//		return $url;
//	}, 10, 2
//);

//add_filter( 'of_absolute_relative_urls_related_sites',
//	function( $related_sites ) {
//		$related_sites[] = "http://multifolder.apatterson.org/site2";
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
