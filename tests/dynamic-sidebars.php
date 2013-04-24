<?php

/*
Plugin Name: Dynamic Sidebars
Plugin URI: http://www.vcarvalho.com/wordpress-plugins/dynamic-sidebars
Version: 0.1
Text Domain: dynamic-sidebars
Domain Path: /languages/
Author: lightningspirit
Author URI: http://www.vcarvalho.com
Description: Create dynamically new sidebars for each post, page or custom post type and manage them through the admin Widgets page.
License: GPL3
*/


/*
 * Dynamic Sidebars
 * 
 * Copyright 2011 Vitor Carvalho <email@vcarvalho.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */



/**
 * Dynamic Sidebars
 *
 * @package Dynamic Sidebars
 * @since 0.1
 * @author lightningspirit
 * @copyright 2011, lightningspirit.NET
 * This code is released under the GPL licence version 3 or later
 * http://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * 
 */


/**
 * Checks if it is accessed from Wordpress' index.php
 * @since 0.1
 */
if ( ! function_exists( 'add_action' ) ) {
	die( 'I\'m just a plugin. I must not do anything when called directly!' );
	
}


/**
 * Add actions and filters
 * @since 0.1
 */

/** register the dynamic_sidebar taxonomy type **/
add_action( 'init', 'sfep_register_dynamic_sidebar_taxonomy', 9999 ); // register post type after all post types

/** register terms in the dynamic_sidebar taxonomy **/
add_action( 'init', 'sfep_register_current_sidebars', 10000 );

/** Create a shortcode [load_sidebar] **/
add_action( 'init', 'sfep_add_shortcode_load_sidebar', 10000 );

/** Adds an entry 'Sidebars' in theme.php menu **/
add_action( 'admin_menu', 'sfep_admin_menu_tweak' );

/** Add metexbox for each custom post type **/
add_action( 'add_meta_boxes', 'sfep_add_meta_boxes' );

/** When save post, save dynamic-sidebars too **/
add_action( 'save_post', 'sfep_save_post', 10, 2 );

/** Enqueue styles **/
add_action( 'admin_print_styles', 'sfep_edit_post_print_styles' );
add_action( 'admin_print_scripts', 'sfep_edit_tag_print_scripts', 100 );

/** Edit the dynamic_sidebars screen columns **/
add_filter( 'manage_edit-dynamic_sidebar_columns', 'sfep_edit_page_columns' );

/* Filter the params of a dynamic sidebar on-the-fly */
add_filter( 'dynamic_sidebar_params', 'sfep_filter_dynamic_sidebar_params', 10, 1 );

/* Filter the row actions */
add_filter( 'dynamic_sidebar_row_actions', 'sfep_filter_row_actions' );


/**
 * Regist a new taxonomy called dynamic_sidebar that holds all
 * dynamically created sidebars, their description, names,
 * and all stuff.
 * The 9999 parameter in the add_action tries to grant this function
 * a calling after all registered objects...
 * @since 0.1
 */
function sfep_register_dynamic_sidebar_taxonomy() {

	load_plugin_textdomain( 'dynamic-sidebars', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	
	// Get all already registered post types
	$post_types = apply_filters( 'dynamic_sidebars_post_types', get_post_types( array(
				'publicly_queryable' => true,
				'show_ui' => true
			), 'names'
		)
	);
	// Regist this taxanomy for all returned post types
	register_taxonomy( 'dynamic_sidebar', null, array(
			'label'				=> __( 'Sidebars' , 'dynamic-sidebars'),
			'labels' 			=> array(
				'name' 				=> _x( 'Sidebars', 'taxonomy general name' , 'dynamic-sidebars'),
				'singular_name' 		=> _x( 'Sidebar', 'taxonomy singular name' , 'dynamic-sidebars'),
				'search_items' 			=> __( 'Search Sidebar' , 'dynamic-sidebars'),
				'popular_items'			=> null,
				'all_items' 			=> __( 'All Sidebars' , 'dynamic-sidebars'),
				'parent_item' 			=> null,
				'parent_item_colon'		=> null,
				'edit_item' 			=> __( 'Edit Sidebar' , 'dynamic-sidebars'), 
				'update_item' 			=> __( 'Update Sidebar' , 'dynamic-sidebars'),
				'add_new_item' 			=> __( 'Add New Sidebar' , 'dynamic-sidebars'),
				'new_item_name' 		=> __( 'New Sidebar Name' , 'dynamic-sidebars'),
				'separate_items_with_commas'	=> __( 'Separate Sidebar names with commas' , 'dynamic-sidebars'),
				'add_or_remove_items'		=> __( 'Add or Remove Sidebars' , 'dynamic-sidebars'),
				'choose_from_most_used'		=> __( 'Choose from the Most used' , 'dynamic-sidebars'),
				'menu_name' 			=> __( 'Sidebars' , 'dynamic-sidebars'),
			
			),
			'public'		=> true,
			'show_ui' 		=> false,
			'show_in_nav_menus' 	=> false,
			'show_tagcloud'		=> false,
			'hierarchical'		=> false,
			'query_var' 		=> false,
			'rewrite' 		=> null,
			'capabilities'		=> array( 'edit_theme_options' ),
		
		)
	);
	
	foreach ( $post_types as $post_type ) {
		register_taxonomy_for_object_type( 'dynamic_sidebar', $post_type );
	}

	global $wp_taxonomies;
	$wp_taxonomies['dynamic_sidebar']->cap->manage_terms = 'edit_theme_options';
	$wp_taxonomies['dynamic_sidebar']->cap->edit_terms = 'edit_theme_options';
	$wp_taxonomies['dynamic_sidebar']->cap->delete_terms = 'edit_theme_options';
	
	return true;
}



/**
 * Regist a Sidebar if the current post have any sidebar selected
 * @since 0.1
 */
function sfep_register_current_sidebars() {
	global $wp_sidebar_params;
	
	$w = get_terms( 'dynamic_sidebar', array(
			'hide_empty' => false,
			'order_by' => 'id',
		)
	);
	
	if ( is_wp_error( $w ) )
		return true;
	
	// Regists all custom sidebars
	foreach ( $w as $x ) {
		$wp_sidebar_params[$x->slug] = array(
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget' => '</li>',
			'before_title' => '<h3 class="widgettitle">',
			'after_title' => '</h3>',
		);
		register_sidebar( array(
			'name' => $x->name,
			'id' => $x->slug,
			'description' => $x->description,
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget' => '</li>',
			'before_title' => '<h3 class="widgettitle">',
			'after_title' => '</h3>',
			)
		);
	}
}


/**
 * Regist the shortcode [load_sidebar]
 * @since 0.1
 */
function sfep_add_shortcode_load_sidebar() {
	add_shortcode( 'load_sidebar', 'sfep_load_sidebar_shortcode_function' );
}


/**
 * Function to process the [load_sidebar] shortcode
 * @since 0.1
 */
function sfep_load_sidebar_shortcode_function( $atts ) {
	extract( shortcode_atts( array(
		'id' => false,
	), $atts ) );
	
	return dynamic_sidebar( $id );
	
}




/**
 * Adds entries for Menu and Submenu
 * and changes the overview of menu
 * @since 0.1
 */
function sfep_admin_menu_tweak() {
	global $menu, $submenu;
	
	add_submenu_page( 'themes.php', __( 'Sidebars' , 'dynamic-sidebars'), __( 'Sidebars' , 'dynamic-sidebars'), 'edit_theme_options', 'edit-tags.php?taxonomy=dynamic_sidebar' );
	
	return true;
}



/**
 * Adds a metabox
 * @since 0.1
 */
function sfep_add_meta_boxes( $post_type ) {
	add_meta_box( 'dynamic-sidebars', __( 'Sidebars' , 'dynamic-sidebars'), 'sfep_dynamic_sidebar_metabox', $post_type, 'side', 'default' );
	return true;
}



/**
 * Dynamic Sidebar Metabox
 * @since 0.1
 */
function sfep_dynamic_sidebar_metabox( $post, $metabox ) {
	
	// Get all ther terms
	$all = (array) get_terms( 'dynamic_sidebar', array(
			'hide_empty' => false,
			'order_by' => 'id',
		)
	);
	
	// Get the terms associated to the post
	$assoc = (array) wp_get_object_terms( $post->ID, 'dynamic_sidebar', array( 'fields' => 'ids' ) );
	
	$keys = array_keys( $all );
	
	foreach( $keys as $k ) {
		if ( in_array( $all[$k]->term_id, $assoc ) )
			$all[$k]->checked = true;
		else
			$all[$k]->checked = false;
	}
	
	// Produce new HTML with it
	if ( ! empty( $all ) ) : foreach ( $all as $term ) :
?>
<div class="entry-term">
	<input type="checkbox" id="dynamic-sidebar-<?php echo $term->slug; ?>" name="dynamic-sidebar[]" value="<?php echo $term->slug ?>"<?php checked( $term->checked, true ); ?> />&nbsp;&nbsp;
	<label class="entry-term-name" for="dynamic-sidebar-<?php echo $term->slug; ?>"><?php echo $term->name; ?></label>
	<div class="description">
		<p class="description"><?php echo $term->description; ?></p>
	</div>
</div>
<?php
	endforeach; else :
?>
<p class="description"><?php _e( 'There are no Dynamic Sidebars defined' , 'dynamic-sidebars'); ?></p>
<?php
	endif;
}



/**
 * Save the dynamic-sidebar post relationship
 * @since 0.1
 */
function sfep_save_post( $post_id, $post ) {
	
	/* Check if there is any post of a dynamic sidebar */
	if ( !isset( $_POST['dynamic-sidebar'] ) )
		return $post_id;
	
	/* Check if the current user has permission to edit the post meta. */
	$post_type_object = get_post_type_object( $post->post_type ); 
	if ( !current_user_can( $post_type_object->cap->edit_post, $post_id ) )
		return $post_id;
	
	wp_set_post_terms( $post_id, $_POST['dynamic-sidebar'], 'dynamic_sidebar' );
	
	return $post_id;
}



/**
 * Add styles
 * @since 0.1
 */
function sfep_edit_post_print_styles() {
	global $current_screen;
	
	$base = array( 'edit', 'post' );
	
	if ( !in_array( $base, (array) $current_screen ) )
		return $current_screen->id;
?>
<style type="text/css">
<!--
div.entry-term { margin-bottom: 5px; }
div.entry-term div.description { border-bottom: 1px solid #DFDFDF; }
div.entry-term:last-child div.description { border-bottom: 0; }
-->
</style>
<?php
}


/**
 * Add styles
 * @since 0.1
 */
function sfep_edit_tag_print_scripts() {
	global $current_screen;
	
	if ( $_SERVER['SCRIPT_NAME'] == '/wp-admin/edit-tags.php' && isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] == 'dynamic_sidebar' ) :
	
?>
<script type="text/javascript">
<!--
jQuery(document).ready(function($){
	$('div.form-field label[for=tag-slug]').text( '<?php _e("ID", 'dynamic-sidebars'); ?>' ).parent().find('p').text( '<?php _e("The ID for this sidebar. Need to be unique. Left blank for automatic fill.", 'dynamic-sidebars'); ?>' );
	$('tr.form-field th label[for=slug]').text( '<?php _e("ID", 'dynamic-sidebars'); ?>' ).parent().parent().find('td p').text( '<?php _e("The ID for this sidebar. Need to be unique. Left blank for automatic fill.", 'dynamic-sidebars'); ?>' );
	$('div.inline-edit-col label span.title').eq(1).text( '<?php _e("ID", 'dynamic-sidebars'); ?>' );
	$('ul#adminmenu li.menu-top').removeClass('wp-has-current-submenu wp-menu-open')
	.addClass('wp-not-current-submenu')
	.children('a').removeClass('wp-has-current-submenu wp-menu-open');
	$('li#menu-appearance').addClass('wp-has-current-submenu wp-menu-open')
	.removeClass('wp-not-current-submenu')
	.children('a').addClass('wp-has-current-submenu wp-menu-open');
});
-->
</script>
<?php

	endif;

}



/**
 * This function edits the columns displayed in the edit screen
 * @since 0.1
 */
function sfep_edit_page_columns( $columns ) {
	
	// Edit the label for this column
	$columns['slug'] = __( 'ID' , 'dynamic-sidebars');
	
	// This column does not make sense here
	unset( $columns['posts'] );
	
	return $columns;
}



/**
 * Display sidebars related to the current post, page, or custom post type.
 *
 * The arguments of $args are:
 * 'sidebars': 'all' or an array of registered sidebars ids
 * 'before_widget': Defaults for '<li id="%1$s" class="widget %2$s">'
 * 'after_widget': Defaults for '</li>'
 * 'before_title': Defaults for '<h2 class="widgettitle">'
 * 'after_title': Defaults for '</h2>'
 * 'limit': The number of sidebars displayed. This is a security measure, since calling dynamic_sidebar is a good resource hunter. Defaults for '5'.
 * 'post_id': The $post->ID. Defaults for the global $post->ID defined.
 *
 * @since 0.1
 * @param array $args An array of arguments
 */
function dynamic_sidebars( $args = array() ) {
	global $post, $wp_sidebar_params;
	
	$defaults = array(
		'sidebars' => 'all',
		'before_widget' => '<li id="%1$s" class="widget %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<h2 class="widgettitle">',
		'after_title' => '</h2>',
		'limit' => 5,
		'post_id' => $post->ID,
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
	
	
	// Get all sidebars associated with this post
	$sidebars = (array) wp_get_object_terms( $post_id, 'dynamic_sidebar', array( 'fields' => 'all' ) );
	
	// Checks if there is no sidebar for the current post
	if ( empty( $sidebars ) )
		return $post_id;
	
	// Check if this variable is a error
	if ( is_wp_error( $sidebars ) )
		return $sidebars;
	
	// Limit sidebars displayed if applicable
	if ( $limit != -1 )
		$sidebars = array_slice( $sidebars, 0, $limit );
	
	
	// All done! Lets display our dynamic sidebars
	foreach ( $sidebars as $sidebar ) {
		$wp_sidebar_params[$sidebar->slug] = $args;
		dynamic_sidebar( $sidebar->slug );
		
	}
	
	
	return $post_id;
}


/**
 * Filter the sidebar params to be displayed correctly with the params given to
 * the dynamic_sidebars funtion.
 *
 * @since 0.1
 * @param array $params The default params given when registered
 * @return array The new params
 */
function sfep_filter_dynamic_sidebar_params( $params ) {
	global $wp_sidebar_params, $wp_registered_widgets;
	
	// Checks if this is the inative widgets sidebar
	if ( 'wp_inactive_widgets' == $params[0]['id'] )
		return $params;
		
	if ( ! isset( $wp_sidebar_params[$params[0]['id']] ) )
		return $params;
	
	$params[0]['before_widget'] = $wp_sidebar_params[$params[0]['id']]['before_widget'];
	$params[0]['after_widget'] = $wp_sidebar_params[$params[0]['id']]['after_widget'];
	$params[0]['before_title'] = $wp_sidebar_params[$params[0]['id']]['before_title'];
	$params[0]['after_title'] = $wp_sidebar_params[$params[0]['id']]['after_title'];
	
	$classname = '';
	foreach ( (array) $wp_registered_widgets[$params[0]['widget_id']]['classname'] as $cn ) {
		if ( is_string($cn) )
			$classname .= '_' . $cn;
		elseif ( is_object($cn) )
			$classname .= '_' . get_class( $cn );
	}

	$classname = ltrim( $classname, '_' );
	$params[0]['before_widget'] = sprintf( $params[0]['before_widget'], $params[0]['widget_id'], $classname );
	
	return $params;

}


/**
 * Filter the row actions to remove View element...
 *
 * @since 0.1
 * @param array $actions Row actions
 * @return array The new row actions
 */
function sfep_filter_row_actions( $actions ) {

	unset( $actions['view'] );

	return $actions;
}



