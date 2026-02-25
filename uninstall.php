<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package VW_Translate
 * @since   1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check for user capability.
if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

global $wpdb;

// Delete all plugin options.
$options = array(
	'vw_translate_version',
	'vw_translate_default_language',
	'vw_translate_enable_url_param',
	'vw_translate_enable_cookie',
	'vw_translate_cookie_duration',
	'vw_translate_enable_switcher',
	'vw_translate_switcher_position',
	'vw_translate_scan_depth',
	'vw_translate_exclude_admin',
	'vw_translate_replace_method',
	'vw_translate_cache_translations',
	'vw_translate_cache_duration',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete all transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%vw_translate_cache_%'"
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_vw_translate_%'"
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_vw_translate_%'"
);

// Drop custom database tables.
$tables = array(
	$wpdb->prefix . 'vw_translate_translations',
	$wpdb->prefix . 'vw_translate_strings',
	$wpdb->prefix . 'vw_translate_languages',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Clear any cached data.
wp_cache_flush();
