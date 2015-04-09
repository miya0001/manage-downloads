<?php
/*
encoding: utf-8
vim: ft=php noexpandtab shiftwidth=4 tabstop=4
Plugin Name: Manage Downloads
Version: 0.1
Description: Manages download
Author: Digitalcube Inc.
Author URI: http://digitalcube.jp/
Plugin URI: https://github.com/miya0001/manage-downloads
Text Domain: manage-downloads
Domain Path: /languages
*/

$manage_downloads = new Manage_Downloads();

register_activation_hook( __FILE__, array( $manage_downloads, 'register_activation_hook' ) );

class Manage_Downloads
{
	const rewrite_endpoint = 'download';
	const post_type = 'manage_downloads';

	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function init()
	{
		$labels = array(
			'name' => _x( 'Downloads', 'Post Type General Name', 'manage-downloads' ),
			'singular_name' => _x( 'Download Download', 'Post Type Singular Name', 'manage-downloads' ),
			'menu_name' => __( 'Downloads', 'manage-downloads' ),
			'name_admin_bar' => __( 'Downloads', 'manage-downloads' ),
			'parent_item_colon' => __( 'Parent Download:', 'manage-downloads' ),
			'all_items' => __( 'All Downloads', 'manage-downloads' ),
			'add_new_item' => __( 'Add New Download', 'manage-downloads' ),
			'add_new' => __( 'Add New', 'manage-downloads' ),
			'new_item' => __( 'New Download', 'manage-downloads' ),
			'edit_item' => __( 'Edit Download', 'manage-downloads' ),
			'update_item' => __( 'Update Download', 'manage-downloads' ),
			'view_item' => __( 'View Download', 'manage-downloads' ),
			'search_items' => __( 'Search Download', 'manage-downloads' ),
			'not_found' => __( 'Not found', 'manage-downloads' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'manage-downloads' ),
		);

		$args = array(
			'label' => __( 'Downloads', 'manage-downloads' ),
			'description' => __( 'Downloads', 'manage-downloads' ),
			'labels' => $labels,
			'supports' => array(
				'title',
			),
			'hierarchical' => false,
			'public'  => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 6.5,
			'show_in_admin_bar' => true,
			'show_in_nav_menus' => true,
			'can_export' => true,
			'has_archive' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'capability_type' => 'page',
			'register_meta_box_cb' => array( $this, 'add_meta_box' ),
		);

		register_post_type( self::get_post_type(), $args );
		add_rewrite_endpoint( self::get_rewrite_endpoint(), EP_ROOT );
	}

	public function register_activation_hook()
	{
		add_rewrite_endpoint( self::get_rewrite_endpoint(), EP_ROOT );
		flush_rewrite_rules();
	}

	public function plugins_loaded()
	{
		add_shortcode( 'download_counter', array( $this, 'download_counter' ) );
		add_shortcode( 'download_link', array( $this, 'download_link' ) );

		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 10, 2 );
	}

	public function post_type_link( $permalink, $post )
	{
		if ( $post && self::get_post_type() === $post->post_type ) {
			return esc_url( $this->get_permalink( $post->ID ) );
		}

		return $permalink;
	}

	public function template_redirect()
	{
		global $wp_query;

		if ( isset( $wp_query->query[ $this->get_rewrite_endpoint() ] ) ) {
			$id = get_query_var( $this->get_rewrite_endpoint() );
			if ( isset( $_COOKIE['manage_downloads_' . $id] ) && $_COOKIE['manage_downloads_' . $id] ) {
				header( 'Location: ' . esc_url_raw( Manage_Downloads::get_download_url( $id ) ) );
			} else {
				Manage_Downloads::set_count( $id );
				setcookie( 'manage_downloads_' . $id, 1 );
				header( 'Location: ' . esc_url_raw( Manage_Downloads::get_download_url( $id ) ) );
			}
			exit;
		}
	}

	public function save_post( $id )
	{
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $id;
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'inline-save' ) {
			return $id;
		}

		$p = get_post( $id );

		/**
		 * Save post_meta
		 */
		if ( self::get_post_type() === $p->post_type ) {
			if ( isset( $_POST['download-item-url'] ) && trim( $_POST['download-item-url'] ) ) {
				update_post_meta( $id, 'manage-downloads-item-url', trim( $_POST['download-item-url'] ) );
			} else {
				delete_post_meta( $id, 'manage-downloads-item-url' );
			}
		}
	}

	public function download_counter( $atts )
	{
		$atts = shortcode_atts( array(
			'id' => '',
		), $atts );

		$counter = Manage_Downloads::get_count( $atts['id'] );
		if ( intval( $counter ) ) {
			return intval( $counter );
		} else {
			return "0";
		}
	}

	public function download_link( $atts )
	{
		$atts = shortcode_atts( array(
			'id' => '',
		), $atts );

		if ( get_post( intval( $atts['id'] ) ) ) {
			return sprintf(
				'<a href="%s">%s</a>',
				esc_url( self::get_permalink( $atts['id'] ) ),
				esc_html( get_the_title( $atts['id'] ) )
			);
		}
	}

	public function add_meta_box()
	{
		add_meta_box(
			'manage-downloads-url',
			__( 'URL', 'manage-downloads' ),
			array( $this, 'get_url_meta_box' ),
			self::get_post_type(),
			'normal',
			'low'
		);

		add_meta_box(
			'manage-downloads-counter',
			__( 'Number of download', 'manage-downloads' ),
			function( $post ){
				$count = number_format( Manage_Downloads::get_count( $post->ID ) );
				echo '<div style="font-size: 200%; text-align: right;">' . esc_html( $count ) . '</div>';
			},
			self::get_post_type(),
			'side',
			'high'
		);

		add_meta_box(
			'manage-downloads-examples',
			__( 'Sample Code', 'manage-downloads' ),
			array( $this, 'get_counter_metabox' ),
			self::get_post_type(),
			'normal',
			'low'
		);
	}

	public function get_counter_metabox( $post ){
?>
<dl>
	<dt>Download URL</dt>
	<dd><?php echo esc_url( Manage_Downloads::get_permalink( $post->ID ) ); ?></dd>
	<dt>Link Shortcode</dt>
	<dd>[download_link id="<?php echo esc_html( $post->ID ); ?>"]</dd>
	<dt>Counter Shortcode</dt>
	<dd>[download_counter id="<?php echo esc_html( $post->ID ); ?>"]</dd>
</dl>
<?php

	}

	public function get_url_meta_box( $post )
	{
		$url = Manage_Downloads::get_download_url( $post->ID, true );

		printf(
			'<input type="text" name="download-item-url" value="%s" style="width: 100%%;" placeholder="http://" />',
			esc_url( $url )
		);
	}

	public static function get_permalink( $id )
	{
		if ( get_post( intval( $id ) ) ) {
			return home_url( self::get_rewrite_endpoint() . '/' . intval( $id ) );
		};
	}

	public static function get_rewrite_endpoint()
	{
		return apply_filters( 'manage_downloads_rewrite_endpoint', self::rewrite_endpoint );
	}

	public static function get_post_type()
	{
		return apply_filters( 'manage_downloads_post_type', self::post_type );
	}

	public static function get_download_url( $id )
	{
		if ( get_post( intval( $id ) ) ) {
			return get_post_meta( intval( $id ), 'manage-downloads-item-url', true );
		}
	}

	public static function set_download_url( $id, $url )
	{
		if ( get_post( intval( $id ) ) ) {
			update_post_meta( intval( $id ), 'manage-downloads-item-url', $url );
			return true;
		}
	}

	public static function get_count( $ids )
	{
		$id_array = preg_split( "/,/", $ids );
		$count = 0;
		foreach ( $id_array as $id ) {
			$id = intval( trim( $id ) );
			if ( $id ) {
				$n = intval( get_post_meta( $id, 'download-item-counter', true ) );
				$count = $count + $n;
			}
		}

		return intval( $count );
	}

	public static function set_count( $id )
	{
		$count = intval( Manage_Downloads::get_count( $id ) ) + 1;
		update_post_meta( $id, 'download-item-counter', $count );

		return $count;
	}
}
