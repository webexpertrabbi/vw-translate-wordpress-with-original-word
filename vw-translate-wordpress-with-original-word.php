<?php
/**
 * VW Translate WordPress with Original Word
 *
 * @package           VW_Translate
 * @author            Developer
 * @copyright         2026
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       VW Translate WordPress with Original Word
 * Plugin URI:        https://wordpress.org/plugins/vw-translate-wordpress-with-original-word/
 * Description:       Translate your entire WordPress website by scanning all theme and plugin strings. Replace original words and sentences with your translations across the entire site.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Developer
 * Author URI:        https://developer.com/
 * Text Domain:       vw-translate
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
define( 'VW_TRANSLATE_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 *
 * @since 1.0.0
 */
define( 'VW_TRANSLATE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * @since 1.0.0
 */
define( 'VW_TRANSLATE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin base name.
 *
 * @since 1.0.0
 */
define( 'VW_TRANSLATE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Include required files.
 */
require_once VW_TRANSLATE_PLUGIN_DIR . 'includes/class-vw-translate-db.php';
require_once VW_TRANSLATE_PLUGIN_DIR . 'includes/class-vw-translate-activator.php';
require_once VW_TRANSLATE_PLUGIN_DIR . 'includes/class-vw-translate-scanner.php';
require_once VW_TRANSLATE_PLUGIN_DIR . 'includes/class-vw-translate-frontend.php';
require_once VW_TRANSLATE_PLUGIN_DIR . 'includes/class-vw-translate-widget.php';

if ( is_admin() ) {
	require_once VW_TRANSLATE_PLUGIN_DIR . 'includes/class-vw-translate-admin.php';
}

/**
 * Plugin activation hook.
 *
 * @since 1.0.0
 */
register_activation_hook( __FILE__, array( 'VW_Translate_Activator', 'activate' ) );

/**
 * Plugin deactivation hook.
 *
 * @since 1.0.0
 */
register_deactivation_hook( __FILE__, array( 'VW_Translate_Activator', 'deactivate' ) );

/**
 * Initialize the plugin after all plugins are loaded.
 *
 * @since 1.0.0
 */
function vw_translate_init() {

	// Load plugin text domain for i18n.
	load_plugin_textdomain(
		'vw-translate',
		false,
		dirname( VW_TRANSLATE_PLUGIN_BASENAME ) . '/languages'
	);

	if ( is_admin() ) {
		$admin = new VW_Translate_Admin();
		$admin->init();
	}

	$frontend = new VW_Translate_Frontend();
	$frontend->init();
}
add_action( 'plugins_loaded', 'vw_translate_init' );

/**
 * Register the language switcher widget.
 *
 * @since 1.0.0
 */
function vw_translate_register_widgets() {
	register_widget( 'VW_Translate_Widget' );
}
add_action( 'widgets_init', 'vw_translate_register_widgets' );

/**
 * Language switcher shortcode.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string Language switcher HTML.
 */
function vw_translate_language_switcher_shortcode( $atts ) {

	$atts = shortcode_atts(
		array(
			'style' => 'dropdown',
		),
		$atts,
		'vw_translate_switcher'
	);

	return VW_Translate_Frontend::get_language_switcher( sanitize_text_field( $atts['style'] ) );
}
add_shortcode( 'vw_translate_switcher', 'vw_translate_language_switcher_shortcode' );

/**
 * Get the current translation language.
 *
 * @since 1.0.0
 * @return string Language code.
 */
function vw_translate_get_current_language() {
	return VW_Translate_Frontend::get_current_language();
}

/**
 * Add settings link on plugin list page.
 *
 * @since 1.0.0
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function vw_translate_settings_link( $links ) {

	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=vw-translate' ) ),
		esc_html__( 'Settings', 'vw-translate' )
	);

	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . VW_TRANSLATE_PLUGIN_BASENAME, 'vw_translate_settings_link' );
