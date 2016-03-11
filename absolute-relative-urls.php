<?php

/*
Plugin Name: Absolute &lt;&gt; Relative URLs
Plugin URI: https://www.oxfordframework.com/absolute-relative-urls
Description: Saves relative URLs to database. Displays absolute URLs.
Author: Andrew Patterson
Author URI: http://www.pattersonresearch.ca
Tags: relative, absolute, url, seo, portable
Version: 1.4.1
Date: 11 Mar 2016
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// View filters (Relative to Absolute)
$view_filters = array(
	'the_editor_content',
	'the_content',
	'get_the_excerpt',
	'the_excerpt_rss',
	'excerpt_edit_pre'
);
// Save filters (Absolute to Relative)
$save_filters = array(
	'content_save_pre',
	'excerpt_save_pre'
);
// Options filters (Both directions)
$options_filters = array(
	'theme_mods_' . get_option('template'),
	'theme_mods_' . get_option('stylesheet'),
	'widget_black-studio-tinymce'
);


// Remove domain from urls when saving content
function pri_relative_url( $content ) {
	if ( is_array( $content ) ) {
		foreach ( $content as $key => $value ) {
			$content[ $key ] = pri_relative_url( $value );
		}
	} elseif ( is_object( $content ) ) {
		foreach ( $content as $key => $value ) {
			$content->$key = pri_relative_url( $value );
		}
	} elseif ( is_string( $content ) ) {
		$wpurl = trailingslashit( get_bloginfo( 'wpurl' ) );
		$url = trailingslashit( get_bloginfo( 'url' ) );
		if ( $wpurl === $url ) { // doesn't matter which url gets used
			$content = str_replace( $url, '/', $content );
		} elseif ( 0 === strpos( $wpurl, $url ) ) { // replace wp url first
			$content = str_replace( $wpurl, '/', $content );
			$content = str_replace( $url, '/', $content );
		} else { // replace site url first
			$content = str_replace( $url, '/', $content );
			$content = str_replace( $wpurl, '/', $content );
		}
	}
	return $content;
}

// Save filters
foreach( $save_filters as $filter ) {
	add_filter( $filter, 'pri_relative_url' );
}
foreach( $options_filters as $filter ) {
	add_filter( 'pre_update_option_' . $filter, 'pri_relative_url' );
}


// Add domain to urls when displaying/editing content
function pri_absolute_url( $content ) {
	if ( is_array( $content ) ) {
		foreach ( $content as $key => $value ) {
			$content[ $key ] = pri_absolute_url( $value );
		}
	} elseif ( is_object( $content ) ) {
		foreach ( $content as $key => $value ) {
			$content->$key = pri_absolute_url( $value );
		}
	} elseif ( is_string( $content ) ) {
		$upload_path = pri_upload_path();
		$wpurl = trailingslashit( get_bloginfo( 'wpurl' ) );
		$url = trailingslashit( get_bloginfo( 'url' ) );
		$delim = chr(127);
		// content is a url field, not prefixed with 'src' or 'href'
		if ( 0 === strpos( $content, '/' . $upload_path ) ) {
			$content = $wpurl . substr( $content, 1 );
		} else { // regular content, look for 'src' and 'href', do wpurl first
			$content = preg_replace( $delim . '(src="|href=")/' . $upload_path . $delim, '${1}' . $wpurl . $upload_path, $content );
			$content = str_replace( 'href="/', 'href="' . $url, $content );
		}
	}
	return $content;
}

// View filters
foreach( $view_filters as $filter ) {
	add_filter( $filter, 'pri_absolute_url' );
}
foreach( $options_filters as $filter ) {
	add_filter( 'option_' . $filter, 'pri_absolute_url' );
}


// get upload path from 'baseurl'
function pri_upload_path() {
	$wpurl = trailingslashit( get_bloginfo( 'wpurl' ) );
	$wp_upload = wp_upload_dir();
	if ( ! $wp_upload[ 'error' ] && ( 0 === strpos( $wp_upload[ 'baseurl' ], $wpurl ) ) ) {
		return substr( $wp_upload[ 'baseurl' ], strlen( $wpurl ) );
	} else { // fallback
		return 'wp-content/uploads';
	}
}
