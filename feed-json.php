<?php
/*
Plugin Name: Feed JSON
Plugin URI: http://wordpress.org/extend/plugins/feed-json/
Description: Adds a new type of feed you can subscribe to. http://example.com/feed/json or http://example.com/?feed=json to anywhere you get a JSON form.
Author: wokamoto
Version: 1.0.1
Author URI: http://dogmap.jp/

License:
 Released under the GPL license
  http://www.gnu.org/copyleft/gpl.html

  Copyright 2011 (email : wokamoto1973@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define('FEED_JSON_ICON_URL', 'feed-json-icon-url');
define('FEED_JSON_ICON_URL_DEFAULT', 'http://alpha.mixi.co.jp/blog/wp-content/uploads/2011/12/40x40.png');

$feed_json_param = array(FEED_JSON_ICON_URL);

class feed_json {
	function feed_json() {
		global $wp_rewrite;

		add_action('init', array(&$this, 'add_feed_json'));
		add_action('do_feed_json', array(&$this, 'do_feed_json'), 10, 1);
		add_action('admin_menu', array(&$this, 'feed_json_plugin_menu'));

		add_filter('template_include', array(&$this, 'template_json'));
		add_filter('query_vars', array(&$this, 'add_query_vars'));

		$plugin_basename = plugin_basename(__FILE__);
		add_action('activate_' . $plugin_basename, array(&$this, 'add_feed_json_once'));
		add_action('deactivate_' . $plugin_basename, array(&$this, 'remove_feed_json'));
	}

	function add_feed_json_once() {
		global $wp_rewrite;
		$this->add_feed_json();
		$wp_rewrite->flush_rules();
	}

	function remove_feed_json() {
		global $wp_rewrite;
		$feeds = array();
		foreach ( $wp_rewrite->feeds as $feed ) {
			if ( $feed !== 'json' ) {
				$feeds[] = $feed;
			}
		}
		$wp_rewrite->feeds = $feeds;
		$wp_rewrite->flush_rules();
	}

	function add_query_vars($qvars) {
	  $qvars[] = 'callback';
	  $qvars[] = 'limit';
	  return $qvars;
	}

	function add_feed_json() {
		add_feed('json', array(&$this, 'do_feed_json'));
	}

	function do_feed_json() {
		load_template($this->template_json(dirname(__FILE__) . '/feed-json-template.php'));
	}

	function template_json( $template ) {
		$template_file = false;
		if (get_query_var('feed') === 'json') {
			$template_file = '/feed-json.php';
			if (function_exists('get_stylesheet_directory') && file_exists(get_stylesheet_directory() . $template_file)) {
				$template_file = get_stylesheet_directory() . $template_file;
			} elseif (function_exists('get_template_directory') && file_exists(get_template_directory() . $template_file)) {
				$template_file = get_template_directory() . $template_file;
			} elseif (file_exists(dirname(__FILE__) . '/feed-json-template.php')) {
				$template_file = dirname(__FILE__) . '/feed-json-template.php';
			} else {
				$template_file = false;
			}
		}

		return (
			$template_file !== false
			? $template_file
			: $template
			);
	}

	function feed_json_plugin_menu(){
		add_menu_page('Feed JSON', 'Feed JSON', 8, basename(__file__), '', plugins_url('m_icon.png',__FILE__));
		add_submenu_page(basename(__file__), '設定', '設定', 'manage_options', basename(__file__), array(&$this, 'feed_json_plugin_options'));
	}

	function feed_json_plugin_options(){
		global $feed_json_param;

		$html = array(
			      '<form method="post" action="options.php">',
			      '<input type="hidden" name="action" value="update" />',
			      '<input type="hidden" name="page_options" value="'.implode(',', $feed_json_param) . '" />',
			      wp_nonce_field('update-options'),
			      '<h2>Feed JSON icon url</h2>',
			      '<p>default value: ' . FEED_JSON_ICON_URL_DEFAULT . '</p>',
			      '<p><input type="text" name="' . FEED_JSON_ICON_URL . '" value="' . get_option(FEED_JSON_ICON_URL) . '" style="width:300px;" /></p>',
			      '<p><input type="submit" class="button-primary" value="Save Changes" /></p>',
			      '</form>'
			      );
		echo implode('', $html);
	}
}

new feed_json();
?>
