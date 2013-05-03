<?php

/*
Plugin Name: Custom Dynamic Sidebars
Plugin URI: http://wordpress.org/extend/plugins/custom-dynamic-sidebars
Version: 0.2
Description: Create custom sidebars for some posts, pages and categories and manage them through the admin Widgets page.
Author: lightningspirit
Author URI: http://profiles.wordpress.org/lightningspirit
Text Domain: custom-dynamic-sidebars
Domain Path: /languages/
Tags: plugin, sidebars, custom sidebars, new sidebars, admin, sidebar, simple sidebars, template sidebars, cms
License: GPLv2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/


/*
 * @package Custom Dynamic Sidebars
 * @author Vitor Carvalho
 * @copyright lightningspirit 2012
 * This code is released under the GPL licence version 2 or later
 * http://www.gnu.org/licenses/gpl.txt
 */



// Checks if it is accessed from Wordpress' index.php
if ( ! function_exists( 'add_action' ) ) {
	die( 'I\'m just a plugin. I must not do anything when called directly!' );

}




if ( ! class_exists ( 'WP_Custom_Dynamic_Sidebars' ) ) :
/**
 * WP_Custom_Dynamic_Sidebars
 *
 * @package WordPress
 * @subpackage Simple Sidebars
 * @since 0.1
 */
class WP_Custom_Dynamic_Sidebars {
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'init' ) );

	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */
	public static function init() {
		// Load the text domain to support translations
		load_plugin_textdomain( 'custom-dynamic-sidebars', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		

		// Just to be parsed by gettext
		$plugin_headers = array(
			__( 'Custom Dynamic Sidebars', 'custom-dynamic-sidebars' ).
			__( 'Create custom sidebars for some posts, pages and categories and manage them through the admin Widgets page.', 'custom-dynamic-sidebars' )
		);


		// if new upgrade
		if ( version_compare( (int) get_option( 'custom_dynamic_sidebars_version' ), '0.2', '<' ) )
			add_action( 'admin_init', array( __CLASS__, 'do_upgrade' ) );
		
		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
		
		/** Register actions for admin pages */
		if ( is_admin() ) {
			/** Add admin menu */
			add_action( 'admin_menu', array( __CLASS__, 'add_page_to_menu' ) );
			add_filter( 'parent_file', array( __CLASS__, 'move_taxonomy_menu' ) );
			
			/** widgets.php */
			add_action( 'widgets_admin_page', array( __CLASS__, 'manage_sidebars_link' ), 1 );
			add_action( 'sidebar_admin_setup', array( __CLASS__, 'register_custom_sidebars' ), 1 );
			
			/** Add form field (position) */
			add_action( 'admin_print_scripts-edit-tags.php', array( __CLASS__, 'taxonomy_sidebar_scripts' ) );
			add_action( 'admin_print_styles-edit-tags.php', array( __CLASS__, 'taxonomy_sidebar_styles' ) );
			add_action( 'sidebar_add_form_fields', array( __CLASS__, 'taxonomy_sidebar_add_form_field' ), 10, 2);
			add_action( 'sidebar_edit_form_fields', array( __CLASS__, 'taxonomy_sidebar_edit_form_field' ), 10, 2);
			add_action( 'edited_sidebar', array( __CLASS__, 'taxonomy_sidebar_fields_save' ), 10, 2);
			add_action( 'created_sidebar', array( __CLASS__, 'taxonomy_sidebar_fields_save' ), 10, 2);
			
			/** Sidebar taxonomy UI tweaks */
			add_action( 'load-edit-tags.php', array( __CLASS__, 'load_taxonomy_sidebar_screen' ) );
			add_action( 'manage_edit-sidebar_columns', array( __CLASS__, 'manage_taxonomy_sidebar_columns' ) );
			add_action( 'manage_sidebar_custom_column', array( __CLASS__, 'manage_taxonomy_sidebar_custom_columns' ), 10, 3 );
			add_action( 'manage_edit-sidebar_sortable_columns', array( __CLASS__, 'manage_taxonomy_sidebar_sortable_columns' ) );
			add_action( 'sidebar_row_actions', array( __CLASS__, 'sidebar_filter_row_actions' ) );
			
			/** Custom metabox for sidebars */
			add_action( 'add_meta_boxes', array( __CLASS__, 'sidebar_meta_box' ) );
			add_action( 'save_post', array( __CLASS__, 'sidebar_save_post' ), 10, 2 );
			
			/** Add custom form fields to taxonomies (general) that are public and show_ui */
			foreach ( get_taxonomies( array( 'show_ui' => true, 'public' => true ) ) as $taxonomy ) {
				add_action( "{$taxonomy}_edit_form_fields", array( __CLASS__, 'taxonomy_general_edit_form_field' ), 10, 2);
				add_action( "edited_{$taxonomy}", array( __CLASS__, 'taxonomy_general_fields_save' ), 10, 2);
				add_action( "{$taxonomy}_edit_form_fields", array( __CLASS__, 'taxonomy_general_edit_form_field' ), 10, 2);
				add_action( "edited_{$taxonomy}", array( __CLASS__, 'taxonomy_general_fields_save' ), 10, 2);
				
			}
			
			
		}
		
		/** Register, create and load custom sidebars */
		add_action( 'init', array( __CLASS__, 'register_taxonomy_for_sidebars' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'load_custom_sidebars' ) );
		add_filter( 'sidebars_widgets', array( __CLASS__, 'replace_sidebars' ) );

	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */
	public static function do_upgrade() {
		update_option( 'custom_dynamic_sidebars_version', '0.2' );
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @param string $links
	 * @param string $file
	 * @return void
	 */
	public static function plugin_action_links( $links, $file ) {
		if ( $file != plugin_basename( __FILE__ ) )
			return $links;

		$settings_link = '<a href="edit-tags.php?taxonomy=sidebar">' . __( 'Manage Sidebars', 'custom-dynamic-sidebars' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;

	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */
	public static function add_page_to_menu() {
		$sidebar = get_taxonomy( 'sidebar' );
		add_submenu_page( 'themes.php', $sidebar->labels->name, $sidebar->labels->menu_name, $sidebar->cap->manage_terms, 'edit-tags.php?taxonomy=sidebar' );
	 	
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */
	public static function move_taxonomy_menu( $parent_file ) {
		global $current_screen;
		
		if ( 'edit-sidebar' == $current_screen->id ) 
			return get_taxonomy( 'sidebar' )->show_in_menu;
		
		return $parent_file;
	 	
	}
	 
	 /** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return void
	 */
	public static function manage_sidebars_link() {
		?>
		<div style="text-align: right; margin-right: 7px;"">
			<a href="edit-tags.php?taxonomy=sidebar" class="button button-secondary"><?php _e( 'Manage Sidebars' ); ?></a>
		</div>
		<?php
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function register_custom_sidebars() {
		global $wp_taxonomies, $wp_sidebar_params;
		
		// Now, we have everything registered, just register the new sidebars
		$args = array( 'hide_empty' => false, 'order_by' => 'id' );
		$args = apply_filters( 'custom_dynamic_sidebar_register_params', $args );
		$sidebars = get_terms( 'sidebar', $args );
		
		if ( is_wp_error( $sidebars ) )
			return true;

		// Regists all simple sidebars
		foreach ( $sidebars as $sidebar ) {

			register_sidebar( array(
				'name' => $sidebar->name,
				'id' => $sidebar->slug,
				'description' => $sidebar->description,
				)
			);

		}

		return true;

	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function taxonomy_sidebar_scripts() {
		if ( isset( $_GET['taxonomy'] ) && 'sidebar' == $_GET['taxonomy'] ) {
			wp_enqueue_script( 'taxonomy-sidebar', plugin_dir_url( __FILE__ ) . 'js/admin-taxonomy-sidebar.min.js' );
			
		}
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function taxonomy_sidebar_styles() {
		if ( isset( $_GET['taxonomy'] ) && 'sidebar' == $_GET['taxonomy'] ) {
			wp_enqueue_style( 'taxonomy-sidebar', plugin_dir_url( __FILE__ ) . 'css/admin-taxonomy-sidebar.css' );
			
		}
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function taxonomy_sidebar_add_form_field() {
		global $wp_registered_sidebars;
		?>
		
		<div class="form-field form-required">
			<label for="tag-position">
				<?php _e( 'Position' ); ?>
			</label>
			<select name="tag-position" id="tag-position" aria-required="true">
				<option value="-1"><?php _e( '&mdash; Select a position &mdash;' ); ?></option>
				<?php foreach ( $wp_registered_sidebars as $sidebar ) : ?>
				<option value="<?php echo esc_attr( $sidebar['id'] ); ?>">
					<?php echo esc_attr( $sidebar['name'] ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php _e( 'Position is the actual theme registered sidebar.' ); ?>
			</p>
		</div>
		
		<?php
	}

	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function taxonomy_sidebar_edit_form_field( $tag, $taxonomy ) {
		global $wp_registered_sidebars;
		
		$position = get_option( "sidebar_{$tag->term_id}_position" );
		
		?>
		
		<tr class="form-field form-required">
			<th scope="row" valign="top">
				<label for="tag-position">
					<?php _e( 'Position' ); ?>
				</label>
			</th>
			<td>
				<select name="tag-position" id="tag-position" aria-required="true">
					<option value="-1"><?php _e( '&mdash; Select a position &mdash;' ); ?></option>
					<?php foreach ( $wp_registered_sidebars as $sidebar ) : ?>
					<option value="<?php echo esc_attr( $sidebar['id'] ); ?>"<?php selected( $position, $sidebar['id'] ); ?>>
						<?php echo esc_attr( $sidebar['name'] ); ?>
					</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php _e( 'Position is the actual theme registered sidebar.' ); ?>
				</p>
			</td>
		</tr>
		
		<?php
	}
	
	/** 
	 * TODO: {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function taxonomy_sidebar_fields_save( $term_id ) {
		if ( isset( $_REQUEST['tag-position'] ) ) {
			update_option( "sidebar_{$term_id}_position", esc_html( $_REQUEST['tag-position'] ) );
			
		}
		
		return $term_id;
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function load_taxonomy_sidebar_screen() {
		
		// Add screen help
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'	=> 'general_custom_sidebar',
			'title'	=> __( 'Overview' ),
			'content'	=> '<p>' . __( 'This screen provides access to all user defined custom sidebars. You can add, edit or remove custom dynamic sidebars here. A dynamic sidebar area (or just sidebar) is a holder where you can drop widgets. You are able to create your own custom sidebars and dislaying them in some your selected posts, pages or categories.' ) . '</p>',
			)
		);
		
		$screen->add_help_tab( array(
			'id'	=> 'manage_custom_sidebar',
			'title'	=> __( 'Managing Sidebars' ),
			'content'	=> 
				'<p>' . __( 'To create a custom sidebar just name it, add a brief description and select a position provided by the current theme, using the form in the left of this screen. Note: sidebar positions are theme specific and you are not able to create or remove any from the dashboard.' ) . '</p>' .
				'<p>' . sprintf( __( 'After theme activation you can go to <a href="%s">Widgets</a> screen, thus you can add widgets to you custom sidebars.' ), 'widgets.php' ) . '</p>' .
				'<p>' . __( 'To edit or remove a custom sidebar just use the row actions provided.' ) . '</p>'
			)
		);
		
		$screen->add_help_tab( array(
			'id'	=> 'associate_custom_sidebar',
			'title'	=> __( 'Associating with content' ),
			'content'	=> 
				'<p>' . __( 'Associating sidebars to posts, pages and categories is simple! You have to visit the edit screen of the desired post/page/category and check the custom sidebars you want to display, inside the «Sidebars» metabox on the right side of the screen.' ) . '</p>'
			)
		);

		$screen->add_help_tab( array(
			'id'	=> 'contributing',
			'title'	=> __( 'Contributing' ),
			'content'	=> 
				'<p>' . sprintf( __( 'You can contribute with code, patches, translations and documentation. You can add new features or replace the existing ones. Just visit the <a href="%s" target="_blank">plugin official page</a> and create a new issue.' ), 'https://github.com/lightningspirit/WordPress-Custom-Dynamic-Sidebars' ) . '</p>'
			)
		);
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function manage_taxonomy_sidebar_columns( $columns ) {
		return array(
			'cb'			=> $columns['cb'],
			'name' 			=> $columns['name'],
			'description' 	=> $columns['description'],
			'position' 		=> __( 'Position' ),
			'widgets' 		=> __( 'Widgets' ),
			'posts' 		=> $columns['posts'],
			'taxonomies' 	=> __( 'Taxonomies' ),
		
		);
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function manage_taxonomy_sidebar_custom_columns( $empty, $column, $term_id ) {
		global $wp_registered_sidebars;
		
		if ( 'position' == $column ) {
			$position = get_option( "sidebar_{$term_id}_position" );
			
			if ( array_key_exists( $position, $wp_registered_sidebars ) ) {
				echo $wp_registered_sidebars[ $position ]['name'];
				
			}
						
		}
		
		if ( 'widgets' == $column ) {
			$sidebars_widgets = wp_get_sidebars_widgets();
			$sidebar = get_term( $term_id, 'sidebar' );
			
			if ( array_key_exists( $sidebar->slug, $sidebars_widgets ) )
				$count = count( $sidebars_widgets[ $sidebar->slug ] );
			else
				$count = 0;
			
			echo '<a href="widgets.php">' . (int) $count . '</a>';
						
		}

		if ( 'taxonomies' == $column ) {
			if ( $count = get_option( "sidebar_{$term_id}_taxonomies" ) )
				$count = count( $count );
			
			echo '<a href="edit-tags.php?sidebar=' . $term_id . '">' . (int) $count . '</a>';
						
		}
		
	}

	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function manage_taxonomy_sidebar_sortable_columns( $columns ) {
		$columns['widgets']		= 'widgets';
		$columns['position']	= 'position';
		$columns['taxonomies']	= 'taxonomies';
		return $columns;
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function sidebar_filter_row_actions( $actions ) {
		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['view'] );
		return $actions;
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function sidebar_meta_box( $post_type ) {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = apply_filters( 'custom_dynamic_sidebars_post_types', $post_types );
		
		if ( in_array( $post_type, $post_types ) ) 
			add_meta_box( 'custom-dynamic-sidebars', __( 'Sidebars' ), array( __CLASS__, 'sidebar_meta_box_content' ), $post_type, 'side', 'default' );
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function sidebar_meta_box_content( $post, $metabox ) {
		
		// Get all ther terms
		$all = (array) get_terms( 'sidebar', array(
				'hide_empty' => false,
				'order_by' => 'id',
			)
		);
		
		// Get the terms associated to the post
		$assoc = (array) wp_get_object_terms( $post->ID, 'sidebar', array( 'fields' => 'ids' ) );
		
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
			<div class="entry-term" style="margin-bottom: 10px">
				<input type="checkbox" id="custom-sidebar-<?php echo $term->slug; ?>" name="custom-sidebar[]" value="<?php echo $term->slug ?>"<?php checked( $term->checked, true ); ?> />&nbsp;&nbsp;
				<label class="entry-term-name" for="custom-sidebar-<?php echo $term->slug; ?>"><?php echo $term->name; ?></label>
				<div class="description">
					<p class="description" style="margin-left: 22px;"><?php echo $term->description; ?></p>
				</div>
			</div>
			<?php
				endforeach; else :
			?>
			<p class="description"><?php _e( 'There are no Custom Sidebars defined' ); ?></p>
			<?php 
			
		
		endif;
		
		wp_nonce_field( 'manage_sidebar_to_post', 'sidebar_post_nonce' );
		
	}

	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function sidebar_save_post( $post_id, $post ) {
		
		/* Check if there is any post of a dynamic sidebar */
		if ( ! isset( $_POST['sidebar_post_nonce'] ) )
			return $post_id;
		
		/** Check admin referer */
		check_admin_referer( 'manage_sidebar_to_post', 'sidebar_post_nonce' );
		
		/* Check if the current user has permission to edit the post meta. */
		$post_type_object = get_post_type_object( $post->post_type ); 
		if ( !current_user_can( $post_type_object->cap->edit_post, $post_id ) )
			return $post_id;
		
		if ( isset( $_POST['custom-sidebar'] ) )
			wp_set_post_terms( $post_id, $_POST['custom-sidebar'], 'sidebar', true );
		else
			wp_set_object_terms( $post_id, '', 'sidebar' );
		
		return $post_id;
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function taxonomy_general_edit_form_field( $term, $taxonomy ) {	
		$taxonomy = get_taxonomy( $taxonomy );
		$sidebars = WP_Custom_Dynamic_Sidebars::_get_sidebars_term_relationship( $term->term_id );
		
		?>
		
		<tr class="form-field form-required">
			<th scope="row" valign="top">
				<label for="tag-sidebar">
					<?php _e( 'Associated Sidebars' ); ?>
				</label>
			</th>
			<td>
				<?php foreach ( (array) get_terms( 'sidebar', array( 'hide_empty' => false ) ) as $sidebar ) : ?>
				<label for="sidebar_<?php echo $sidebar->term_id; ?>">
					<input id="sidebar_<?php echo $sidebar->term_id; ?>" type="checkbox" name="sidebars[<?php echo $sidebar->term_id; ?>]" value="<?php echo $sidebar->slug; ?>" style="width:20px;" <?php checked( $sidebar->term_id, array_search( $sidebar->slug, $sidebars ) ); ?>>&nbsp;
					<?php echo $sidebar->name; ?>
				</label>
				<p class="description"><?php echo $sidebar->description; ?></p>
				<br>
				<?php endforeach; ?>
			</td>
		</tr>
		
		<?php
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function taxonomy_general_fields_save( $term_id ) {
		
		// Remove sidebars not present
		$actual_sidebars = WP_Custom_Dynamic_Sidebars::_get_sidebars_term_relationship( $term_id );
		
		if ( $actual_sidebars ) {
			$post_sidebars = isset( $_REQUEST['sidebars'] ) ? $_REQUEST['sidebars'] : array();
			foreach ( $actual_sidebars as $id => $slug ) {
				if ( ! array_key_exists( $id, $post_sidebars ) )
					$sidebars[] = $slug;
				
			}
			WP_Custom_Dynamic_Sidebars::_remove_sidebar_term_relationship( $term_id, $sidebars );
			
		
		}
		
		// Add Sidebars
		if ( isset( $_REQUEST['sidebars'] ) ) {
			WP_Custom_Dynamic_Sidebars::_save_sidebars_term_relationship( $term_id, $_REQUEST['sidebars'] );
			
		}
		
		return $term_id;
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function _get_sidebars_term_relationship( $term_id ) {
		$custom_sidebars = get_option( "sidebar_terms_relationships" );
		
		foreach ( (array) $custom_sidebars as $sidebar => $terms ) {
			if ( in_array( $term_id, (array) $terms ) ) {
				$s = get_term_by( 'slug', $sidebar, 'sidebar' );
				$return_custom_sidebars[ $s->term_id ] = $s->slug;
				
			}
			
		}
		
		return isset( $return_custom_sidebars ) ? $return_custom_sidebars : array();
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function _remove_sidebar_term_relationship( $term_id, $sidebars ) {
		$custom_sidebars = get_option( "sidebar_terms_relationships" );
		
		foreach ( $sidebars as $sidebar ) {
			$key = array_search( $term_id, $custom_sidebars[ $sidebar ] );
			if ( false !== $key ) {
				unset( $custom_sidebars[ $sidebar ][ $key ] );
					
			}
		}
		update_option( "sidebar_terms_relationships", $custom_sidebars );
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function _save_sidebars_term_relationship( $term_id, $sidebars ) {
		$custom_sidebars = get_option( "sidebar_terms_relationships" );
		foreach ( $sidebars as $sidebar ) {
			if ( ! in_array( $term_id, $custom_sidebars[ $sidebar ] ) )
				$custom_sidebars[ $sidebar ][] = $term_id;
			
		}
		
		update_option( "sidebar_terms_relationships", $custom_sidebars );
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function register_taxonomy_for_sidebars() {
		
		// Get all already registered post types
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types = apply_filters( 'custom_dynamic_sidebars_post_types', $post_types );

		// Register the sidebars taxonomy
		$args = apply_filters( 'custom_dynamic_sidebars_register_args', array(
				'label'						=> __( 'Sidebars' , 'simple-sidebars'),
				'labels' 					=> array(
					'name' 						=> _x( 'Sidebars', 'taxonomy general name' , 'simple-sidebars'),
					'singular_name' 			=> _x( 'Sidebar', 'taxonomy singular name' , 'simple-sidebars'),
					'search_items' 				=> __( 'Search Sidebar' , 'simple-sidebars'),
					'popular_items'				=> null,
					'all_items' 				=> __( 'All Sidebars' , 'simple-sidebars'),
					'parent_item' 				=> null,
					'parent_item_colon'			=> null,
					'edit_item' 				=> __( 'Edit Sidebar' , 'simple-sidebars'),
					'update_item' 				=> __( 'Update Sidebar' , 'simple-sidebars'),
					'add_new_item' 				=> __( 'Add New Sidebar' , 'simple-sidebars'),
					'new_item_name' 			=> __( 'New Sidebar Name' , 'simple-sidebars'),
					'separate_items_with_commas'=> __( 'Separate Sidebar names with commas' , 'simple-sidebars'),
					'add_or_remove_items'		=> __( 'Add or Remove Sidebars' , 'simple-sidebars'),
					'choose_from_most_used'		=> __( 'Choose from the Most used' , 'simple-sidebars'),
					'menu_name' 				=> __( 'Sidebars' , 'simple-sidebars'),
	
				),
				'public'					=> true,
				'show_ui' 					=> false,
				'show_in_nav_menus' 		=> false,
				'show_tagcloud'				=> false,
				'hierarchical'				=> false,
				'query_var' 				=> false,
				'rewrite' 					=> false,
				'capabilities'				=> array(
					'manage_terms'				=> 'edit_theme_options',
					'edit_terms' 				=> 'edit_theme_options',
					'delete_terms' 				=> 'edit_theme_options',
					'assign_terms' 				=> 'edit_theme_options',
				),
				'show_in_menu'				=> 'themes.php',
			)
		);
		register_taxonomy( 'sidebar', null, $args );

		// Associate sidebars to all public post types
		foreach ( $post_types as $post_type ) {
			register_taxonomy_for_object_type( 'sidebar', $post_type );

		}
		
	}
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function load_custom_sidebars() {
		global $wp_query, $wp_registered_sidebars, $wp_sidebars_positions;
		
		if ( is_singular() || ( is_front_page() && isset( $wp_query->queried_object_id ) ) ) {
			$custom_sidebars = wp_get_post_terms( $wp_query->queried_object_id, 'sidebar' );
			
		} elseif ( isset( $wp_query->queried_object_id ) && ( is_category() || is_tag() || is_tax() ) ) {
			$sidebar_terms_relationships = get_option( "sidebar_terms_relationships" );
			foreach ( (array) $sidebar_terms_relationships as $sidebar => $terms ) {
				if ( in_array( $wp_query->queried_object_id, $terms ) ) {
					$custom_sidebars[] = get_term_by( 'slug', $sidebar, 'sidebar' );
					
				}

			}
			
		}
		
		// Determine and replace sidebar args
		if ( isset( $custom_sidebars ) && is_array( $custom_sidebars ) && !empty( $custom_sidebars ) ) {
			
			foreach ( $custom_sidebars as $custom_sidebar ) {
				$replacing = get_option( "sidebar_{$custom_sidebar->term_id}_position" );
				$wp_sidebars_positions[ $replacing ] = $custom_sidebar->slug;
				
			}
			
		}

	}
	
	
	/** 
	 * {@internal Missing Short Description}}
	 * 
	 * @since 0.1
	 * 
	 * @return boolean
	 */
	public static function replace_sidebars( $sidebars_widgets ) {
		global $wp_sidebars_positions, $wp_registered_sidebars;
		
		if ( is_array( $wp_sidebars_positions ) && ! empty( $wp_sidebars_positions ) ) {
			/** Replace the sidebars */
			foreach ( $wp_sidebars_positions as $position => $custom_sidebar ) {
				$sidebars_widgets[ $position ] = $sidebars_widgets[ $custom_sidebar ];
				unset( $sidebars_widgets[ $custom_sidebar ] );
				
			}
				
		}
		
		return $sidebars_widgets;
		
	}




}

new WP_Custom_Dynamic_Sidebars;

endif;


/**
 * custom_dyamic_sidebars_activation_hook
 *
 * Register activation hook for plugin
 *
 * @since 0.1
 */
function custom_dyamic_sidebars_activation_hook() {
	// Wordpress version control. No compatibility with older versions. ( wp_die )
	if ( version_compare( get_bloginfo( 'version' ), '3.4', '<' ) ) {
		wp_die( 'Hide Comments is not compatible with versions prior to 3.4' );

	}

	// Update to last version in
	update_option( 'custom_dyamic_sidebars_plugin_version', '0.1' );

}
register_activation_hook( __FILE__, 'custom_dyamic_sidebars_activation_hook' );
