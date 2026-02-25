<?php
/**
 * Plugin activator and deactivator.
 *
 * @package VW_Translate
 * @since   1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VW_Translate_Activator
 *
 * Handles plugin activation and deactivation tasks.
 *
 * @since 1.0.0
 */
class VW_Translate_Activator {

	/**
	 * Plugin activation.
	 *
	 * Creates database tables and sets default options.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {

		// Check minimum PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( VW_TRANSLATE_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'VW Translate requires PHP 7.4 or higher.', 'vw-translate' ),
				esc_html__( 'Plugin Activation Error', 'vw-translate' ),
				array( 'back_link' => true )
			);
		}

		// Check minimum WordPress version.
		if ( version_compare( get_bloginfo( 'version' ), '5.6', '<' ) ) {
			deactivate_plugins( VW_TRANSLATE_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'VW Translate requires WordPress 5.6 or higher.', 'vw-translate' ),
				esc_html__( 'Plugin Activation Error', 'vw-translate' ),
				array( 'back_link' => true )
			);
		}

		// Create database tables.
		VW_Translate_DB::create_tables();

		// Set default options.
		$default_options = array(
			'default_language'     => 'en',
			'enable_url_param'     => 1,
			'enable_cookie'        => 1,
			'cookie_duration'      => 30,
			'enable_switcher'      => 1,
			'switcher_position'    => 'bottom-right',
			'scan_depth'           => 5,
			'exclude_admin'        => 1,
			'replace_method'       => 'output_buffer',
			'cache_translations'   => 1,
			'cache_duration'       => 12,
		);

		foreach ( $default_options as $key => $value ) {
			if ( false === get_option( 'vw_translate_' . $key ) ) {
				add_option( 'vw_translate_' . $key, $value );
			}
		}

		// Auto-detect WordPress site language and add it as default.
		$languages = VW_Translate_DB::get_languages();
		if ( empty( $languages ) ) {
			$wp_locale         = get_locale(); // e.g. 'en_US', 'bn_BD', 'fr_FR'.
			$lang_code         = strtolower( substr( $wp_locale, 0, 2 ) ); // 'en', 'bn', 'fr'.
			$available         = VW_Translate_DB::get_available_languages();
			$detected_name     = 'English';
			$detected_native   = 'English';
			$detected_flag     = '🇺🇸';
			$detected_code     = 'en';

			// Try to find exact locale match first (e.g., 'pt-br' for 'pt_BR').
			$locale_key = str_replace( '_', '-', strtolower( $wp_locale ) );
			if ( isset( $available[ $locale_key ] ) ) {
				$detected_code   = $locale_key;
				$detected_name   = $available[ $locale_key ]['name'];
				$detected_native = $available[ $locale_key ]['native'];
				$detected_flag   = $available[ $locale_key ]['flag'];
			} elseif ( isset( $available[ $lang_code ] ) ) {
				$detected_code   = $lang_code;
				$detected_name   = $available[ $lang_code ]['name'];
				$detected_native = $available[ $lang_code ]['native'];
				$detected_flag   = $available[ $lang_code ]['flag'];
			}

			VW_Translate_DB::add_language(
				array(
					'language_code' => $detected_code,
					'language_name' => $detected_name,
					'native_name'   => $detected_native,
					'flag'          => $detected_flag,
					'is_default'    => 1,
					'is_active'     => 1,
					'sort_order'    => 0,
				)
			);

			// Store the site original language so the frontend knows which is the untranslated source.
			update_option( 'vw_translate_site_original_language', $detected_code );
		}

		// Store plugin version.
		update_option( 'vw_translate_version', VW_TRANSLATE_VERSION );

		// Clear any cached data.
		wp_cache_flush();
	}

	/**
	 * Plugin deactivation.
	 *
	 * Cleans up temporary data.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {

		// Clear transients.
		delete_transient( 'vw_translate_translations_cache' );
		delete_transient( 'vw_translate_scan_progress' );

		// Clear any cached data.
		wp_cache_flush();
	}
}
