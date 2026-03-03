<?php
/**
 * Admin functionality for VW Translate.
 *
 * @package VW_Translate
 * @since   1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VW_Translate_Admin
 *
 * Handles all admin panel functionality including menus,
 * settings pages, and AJAX handlers.
 *
 * @since 1.0.0
 */
class VW_Translate_Admin {

	/**
	 * Initialize admin hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Ensure site original language option exists (migration for existing installs).
		if ( false === get_option( 'vw_translate_site_original_language' ) ) {
			$lang_code = strtolower( substr( get_locale(), 0, 2 ) );
			update_option( 'vw_translate_site_original_language', $lang_code );
		}

		// AJAX handlers.
		add_action( 'wp_ajax_vw_translate_scan', array( $this, 'ajax_scan_strings' ) );
		add_action( 'wp_ajax_vw_translate_save_translation', array( $this, 'ajax_save_translation' ) );
		add_action( 'wp_ajax_vw_translate_get_translations', array( $this, 'ajax_get_translations' ) );
		add_action( 'wp_ajax_vw_translate_delete_string', array( $this, 'ajax_delete_string' ) );
		add_action( 'wp_ajax_vw_translate_add_language', array( $this, 'ajax_add_language' ) );
		add_action( 'wp_ajax_vw_translate_delete_language', array( $this, 'ajax_delete_language' ) );
		add_action( 'wp_ajax_vw_translate_set_default_language', array( $this, 'ajax_set_default_language' ) );
		add_action( 'wp_ajax_vw_translate_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_vw_translate_add_manual_string', array( $this, 'ajax_add_manual_string' ) );
		add_action( 'wp_ajax_vw_translate_clear_cache', array( $this, 'ajax_clear_cache' ) );
	}

	/**
	 * Add admin menu pages.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {

		// Main menu.
		add_menu_page(
			__( 'VW Translate', 'vw-translate' ),
			__( 'VW Translate', 'vw-translate' ),
			'manage_options',
			'vw-translate',
			array( $this, 'render_strings_page' ),
			'dashicons-translation',
			80
		);

		// Strings submenu.
		add_submenu_page(
			'vw-translate',
			__( 'All Strings', 'vw-translate' ),
			__( 'All Strings', 'vw-translate' ),
			'manage_options',
			'vw-translate',
			array( $this, 'render_strings_page' )
		);

		// Languages submenu.
		add_submenu_page(
			'vw-translate',
			__( 'Languages', 'vw-translate' ),
			__( 'Languages', 'vw-translate' ),
			'manage_options',
			'vw-translate-languages',
			array( $this, 'render_languages_page' )
		);

		// Scan submenu.
		add_submenu_page(
			'vw-translate',
			__( 'Scan Strings', 'vw-translate' ),
			__( 'Scan Strings', 'vw-translate' ),
			'manage_options',
			'vw-translate-scan',
			array( $this, 'render_scan_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'vw-translate',
			__( 'Settings', 'vw-translate' ),
			__( 'Settings', 'vw-translate' ),
			'manage_options',
			'vw-translate-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin CSS and JavaScript.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {

		// Only load on our plugin pages.
		$plugin_pages = array(
			'toplevel_page_vw-translate',
			'vw-translate_page_vw-translate-languages',
			'vw-translate_page_vw-translate-scan',
			'vw-translate_page_vw-translate-settings',
		);

		if ( ! in_array( $hook, $plugin_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'vw-translate-admin',
			VW_TRANSLATE_PLUGIN_URL . 'admin/css/vw-translate-admin.css',
			array(),
			VW_TRANSLATE_VERSION
		);

		wp_enqueue_script(
			'vw-translate-admin',
			VW_TRANSLATE_PLUGIN_URL . 'admin/js/vw-translate-admin.js',
			array( 'jquery' ),
			VW_TRANSLATE_VERSION,
			true
		);

		wp_localize_script(
			'vw-translate-admin',
			'vwTranslate',
			array(
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'vw_translate_nonce' ),
				'siteOriginalLanguage' => get_option( 'vw_translate_site_original_language', strtolower( substr( get_locale(), 0, 2 ) ) ),
				'strings'              => array(
					'scanning'      => __( 'Scanning files...', 'vw-translate' ),
					'scanComplete'  => __( 'Scan complete!', 'vw-translate' ),
					'scanError'     => __( 'Scan failed. Please try again.', 'vw-translate' ),
					'saving'        => __( 'Saving...', 'vw-translate' ),
					'saved'         => __( 'Saved successfully!', 'vw-translate' ),
					'saveError'     => __( 'Save failed. Please try again.', 'vw-translate' ),
					'confirmDelete' => __( 'Are you sure you want to delete this?', 'vw-translate' ),
					'deleted'       => __( 'Deleted successfully!', 'vw-translate' ),
					'deleteError'   => __( 'Delete failed. Please try again.', 'vw-translate' ),
					'loading'       => __( 'Loading...', 'vw-translate' ),
					'noResults'     => __( 'No strings found.', 'vw-translate' ),
				),
			)
		);
	}

	/**
	 * Render the strings management page.
	 *
	 * @since 1.0.0
	 */
	public function render_strings_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vw-translate' ) );
		}

		include VW_TRANSLATE_PLUGIN_DIR . 'admin/partials/strings-page.php';
	}

	/**
	 * Render the languages management page.
	 *
	 * @since 1.0.0
	 */
	public function render_languages_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vw-translate' ) );
		}

		include VW_TRANSLATE_PLUGIN_DIR . 'admin/partials/languages-page.php';
	}

	/**
	 * Render the scan page.
	 *
	 * @since 1.0.0
	 */
	public function render_scan_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vw-translate' ) );
		}

		include VW_TRANSLATE_PLUGIN_DIR . 'admin/partials/scan-page.php';
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'vw-translate' ) );
		}

		include VW_TRANSLATE_PLUGIN_DIR . 'admin/partials/settings-page.php';
	}

	// -------------------------------------------------------------------------
	// AJAX Handlers
	// -------------------------------------------------------------------------

	/**
	 * AJAX: Scan strings from theme and plugins.
	 *
	 * @since 1.0.0
	 */
	public function ajax_scan_strings() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		$scan_type = isset( $_POST['scan_type'] ) ? sanitize_text_field( wp_unslash( $_POST['scan_type'] ) ) : 'all';

		if ( ! in_array( $scan_type, array( 'all', 'theme', 'plugins', 'frontend' ), true ) ) {
			$scan_type = 'all';
		}

		// Increase time limit for scanning.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$scanner = new VW_Translate_Scanner();
		$results = $scanner->run_scan( $scan_type );

		wp_send_json_success(
			array(
				'message'        => sprintf(
					/* translators: 1: total strings found, 2: new strings, 3: existing strings */
					__( 'Scan complete! Found %1$d strings total. %2$d new strings added, %3$d already existed.', 'vw-translate' ),
					$results['total_found'],
					$results['total_new'],
					$results['total_existing']
				),
				'total_found'    => $results['total_found'],
				'total_new'      => $results['total_new'],
				'total_existing' => $results['total_existing'],
			)
		);
	}

	/**
	 * AJAX: Save a translation.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_translation() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		$string_id   = isset( $_POST['string_id'] ) ? absint( $_POST['string_id'] ) : 0;
		$lang_code   = isset( $_POST['language_code'] ) ? sanitize_text_field( wp_unslash( $_POST['language_code'] ) ) : '';
		$translation = isset( $_POST['translation'] ) ? sanitize_textarea_field( wp_unslash( $_POST['translation'] ) ) : '';

		if ( empty( $string_id ) || empty( $lang_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data provided.', 'vw-translate' ) ) );
		}

		// Verify the string exists.
		$string = VW_Translate_DB::get_string( $string_id );
		if ( ! $string ) {
			wp_send_json_error( array( 'message' => __( 'String not found.', 'vw-translate' ) ) );
		}

		$result = VW_Translate_DB::save_translation( $string_id, $lang_code, $translation );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save translation.', 'vw-translate' ) ) );
		}

		// Clear ALL translation caches (not just this language) so frontend
		// picks up changes immediately for every language.
		VW_Translate_Frontend::clear_all_translation_caches();

		wp_send_json_success(
			array(
				'message' => __( 'Translation saved successfully.', 'vw-translate' ),
			)
		);
	}

	/**
	 * AJAX: Get translations for a string.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_translations() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		$string_id = isset( $_POST['string_id'] ) ? absint( $_POST['string_id'] ) : 0;

		if ( empty( $string_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid string ID.', 'vw-translate' ) ) );
		}

		$string       = VW_Translate_DB::get_string( $string_id );
		$translations = VW_Translate_DB::get_translations_for_string( $string_id );
		$languages    = VW_Translate_DB::get_languages( true );

		// Build translations map.
		$trans_map = array();
		foreach ( $translations as $trans ) {
			$trans_map[ $trans->language_code ] = $trans->translated_string;
		}

		wp_send_json_success(
			array(
				'string'       => $string,
				'translations' => $trans_map,
				'languages'    => $languages,
			)
		);
	}

	/**
	 * AJAX: Delete a string.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_string() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		$string_id = isset( $_POST['string_id'] ) ? absint( $_POST['string_id'] ) : 0;

		if ( empty( $string_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid string ID.', 'vw-translate' ) ) );
		}

		$result = VW_Translate_DB::delete_string( $string_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete string.', 'vw-translate' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'String deleted successfully.', 'vw-translate' ),
			)
		);
	}

	/**
	 * AJAX: Add a new language.
	 *
	 * @since 1.0.0
	 */
	public function ajax_add_language() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		$lang_code   = isset( $_POST['language_code'] ) ? sanitize_text_field( wp_unslash( $_POST['language_code'] ) ) : '';
		$lang_name   = isset( $_POST['language_name'] ) ? sanitize_text_field( wp_unslash( $_POST['language_name'] ) ) : '';
		$native_name = isset( $_POST['native_name'] ) ? sanitize_text_field( wp_unslash( $_POST['native_name'] ) ) : '';
		$flag        = isset( $_POST['flag'] ) ? sanitize_text_field( wp_unslash( $_POST['flag'] ) ) : '';
		$is_default  = isset( $_POST['is_default'] ) ? absint( $_POST['is_default'] ) : 0;

		if ( empty( $lang_code ) || empty( $lang_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Language code and name are required.', 'vw-translate' ) ) );
		}

		// Validate language code format.
		if ( ! preg_match( '/^[a-z]{2,3}(-[a-z]{2,3})?$/i', $lang_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid language code format.', 'vw-translate' ) ) );
		}

		$result = VW_Translate_DB::add_language(
			array(
				'language_code' => strtolower( $lang_code ),
				'language_name' => $lang_name,
				'native_name'   => $native_name,
				'flag'          => $flag,
				'is_default'    => $is_default,
			)
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to add language.', 'vw-translate' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %s: language name */
					__( '%s added successfully.', 'vw-translate' ),
					$lang_name
				),
			)
		);
	}

	/**
	 * AJAX: Delete a language.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_language() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		$lang_id = isset( $_POST['language_id'] ) ? absint( $_POST['language_id'] ) : 0;

		if ( empty( $lang_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid language ID.', 'vw-translate' ) ) );
		}

		$result = VW_Translate_DB::delete_language( $lang_id );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete language.', 'vw-translate' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Language deleted successfully.', 'vw-translate' ),
			)
		);
	}

	/**
	 * AJAX: Set default language.
	 *
	 * @since 1.0.0
	 */
	public function ajax_set_default_language() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		$lang_id = isset( $_POST['language_id'] ) ? absint( $_POST['language_id'] ) : 0;

		if ( empty( $lang_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid language ID.', 'vw-translate' ) ) );
		}

		$result = VW_Translate_DB::update_language( $lang_id, array( 'is_default' => 1 ) );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to set default language.', 'vw-translate' ) ) );
		}

		// Clear all translation caches since default language changed.
		VW_Translate_Frontend::clear_all_translation_caches();

		wp_send_json_success(
			array(
				'message' => __( 'Default language updated.', 'vw-translate' ),
			)
		);
	}

	/**
	 * AJAX: Save plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_settings() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		$settings = array(
			'enable_url_param'   => isset( $_POST['enable_url_param'] ) ? absint( $_POST['enable_url_param'] ) : 0,
			'enable_cookie'      => isset( $_POST['enable_cookie'] ) ? absint( $_POST['enable_cookie'] ) : 0,
			'cookie_duration'    => isset( $_POST['cookie_duration'] ) ? absint( $_POST['cookie_duration'] ) : 30,
			'enable_switcher'    => isset( $_POST['enable_switcher'] ) ? absint( $_POST['enable_switcher'] ) : 0,
			'switcher_position'  => isset( $_POST['switcher_position'] ) ? sanitize_text_field( wp_unslash( $_POST['switcher_position'] ) ) : 'bottom-right',
			'shortcode_style'    => isset( $_POST['shortcode_style'] ) ? sanitize_text_field( wp_unslash( $_POST['shortcode_style'] ) ) : 'dropdown',
			'size_dropdown'      => isset( $_POST['size_dropdown'] )  ? sanitize_text_field( wp_unslash( $_POST['size_dropdown'] ) )  : 'md',
			'size_pills'         => isset( $_POST['size_pills'] )     ? sanitize_text_field( wp_unslash( $_POST['size_pills'] ) )     : 'md',
			'size_minimal'       => isset( $_POST['size_minimal'] )   ? sanitize_text_field( wp_unslash( $_POST['size_minimal'] ) )   : 'md',
			'size_cards'         => isset( $_POST['size_cards'] )     ? sanitize_text_field( wp_unslash( $_POST['size_cards'] ) )     : 'md',
			'size_elegant'       => isset( $_POST['size_elegant'] )   ? sanitize_text_field( wp_unslash( $_POST['size_elegant'] ) )   : 'md',
			'size_flag_code'     => isset( $_POST['size_flag_code'] ) ? sanitize_text_field( wp_unslash( $_POST['size_flag_code'] ) ) : 'md',
			'size_flag_only'     => isset( $_POST['size_flag_only'] ) ? sanitize_text_field( wp_unslash( $_POST['size_flag_only'] ) ) : 'md',
			'scan_depth'         => isset( $_POST['scan_depth'] ) ? absint( $_POST['scan_depth'] ) : 5,
			'exclude_admin'      => isset( $_POST['exclude_admin'] ) ? absint( $_POST['exclude_admin'] ) : 1,
			'cache_translations' => isset( $_POST['cache_translations'] ) ? absint( $_POST['cache_translations'] ) : 1,
			'cache_duration'     => isset( $_POST['cache_duration'] ) ? absint( $_POST['cache_duration'] ) : 12,
		);

		// Validate switcher position.
		$allowed_positions = array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
		if ( ! in_array( $settings['switcher_position'], $allowed_positions, true ) ) {
			$settings['switcher_position'] = 'bottom-right';
		}

		// Validate shortcode style.
		$allowed_styles = array( 'dropdown', 'pills', 'minimal', 'cards', 'elegant', 'flag-code', 'flag-only' );
		if ( ! in_array( $settings['shortcode_style'], $allowed_styles, true ) ) {
			$settings['shortcode_style'] = 'dropdown';
		}

		// Validate per-style sizes.
		$allowed_sizes = array( 'sm', 'md', 'lg' );
		foreach ( array( 'size_dropdown', 'size_pills', 'size_minimal', 'size_cards', 'size_elegant', 'size_flag_code', 'size_flag_only' ) as $size_key ) {
			if ( ! in_array( $settings[ $size_key ], $allowed_sizes, true ) ) {
				$settings[ $size_key ] = 'md';
			}
		}

		// Validate scan depth (1-10).
		$settings['scan_depth'] = max( 1, min( 10, $settings['scan_depth'] ) );

		// Validate cookie duration (1-365).
		$settings['cookie_duration'] = max( 1, min( 365, $settings['cookie_duration'] ) );

		// Validate cache duration (1-72 hours).
		$settings['cache_duration'] = max( 1, min( 72, $settings['cache_duration'] ) );

		foreach ( $settings as $key => $value ) {
			update_option( 'vw_translate_' . $key, $value );
		}

		// Clear ALL translation caches.
		VW_Translate_Frontend::clear_all_translation_caches();

		wp_send_json_success(
			array(
				'message' => __( 'Settings saved successfully.', 'vw-translate' ),
			)
		);
	}

	/**
	 * AJAX: Add a manual string.
	 *
	 * @since 1.0.0
	 */
	public function ajax_add_manual_string() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		$original_string = isset( $_POST['original_string'] ) ? sanitize_textarea_field( wp_unslash( $_POST['original_string'] ) ) : '';

		if ( empty( $original_string ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a string.', 'vw-translate' ) ) );
		}

		$result = VW_Translate_DB::insert_string(
			array(
				'original_string' => $original_string,
				'string_hash'     => md5( $original_string ),
				'source_type'     => 'manual',
				'source_name'     => __( 'Manually Added', 'vw-translate' ),
				'source_file'     => '',
			)
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to add string.', 'vw-translate' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'String added successfully.', 'vw-translate' ),
			)
		);
	}

	/**
	 * AJAX: Clear all translation caches.
	 *
	 * @since 1.1.0
	 */
	public function ajax_clear_cache() {

		check_ajax_referer( 'vw_translate_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'vw-translate' ) ) );
		}

		VW_Translate_Frontend::clear_all_translation_caches();

		wp_send_json_success(
			array(
				'message' => __( 'All translation caches cleared successfully.', 'vw-translate' ),
			)
		);
	}
}
