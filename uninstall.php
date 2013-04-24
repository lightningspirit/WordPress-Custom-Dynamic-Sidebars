<?php
/*
Uninstall procedure (Removes the plugin cleanly)
*/


// Checks if it is accessed from Wordpress Admin
if ( ! function_exists( 'add_action' ) ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
	
}


// Make sure that we are uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
	
}



// Delete options from DB
delete_option( 'sidebar_terms_relationships' );
delete_option( 'custom_dynamic_sidebars_version' );

// Remove Categories
$terms = get_terms( 'sidebar' );
if ( ! $terms )
	return;

foreach ( $terms as $term ) {
	delete_option( "sidebar_{$term->term_id}_position" );
	wp_delete_term( $term->term_id, 'sidebar' );
	
}

// Bye! See you soon!
