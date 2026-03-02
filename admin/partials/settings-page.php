<?php
/**
 * Admin settings page — Professional Design.
 *
 * @package VW_Translate
 * @since   1.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current options — each setting is stored as its own wp_option key.
$enable_url_param   = (bool) get_option( 'vw_translate_enable_url_param',   1 );
$enable_cookie      = (bool) get_option( 'vw_translate_enable_cookie',      1 );
$cookie_duration    = (int)  get_option( 'vw_translate_cookie_duration',    30 );
$enable_switcher    = (bool) get_option( 'vw_translate_enable_switcher',    1 );
$switcher_position  =        get_option( 'vw_translate_switcher_position',  'bottom-right' );
$scan_depth         = (int)  get_option( 'vw_translate_scan_depth',         3 );
$exclude_admin      = (bool) get_option( 'vw_translate_exclude_admin',      1 );
$cache_translations = (bool) get_option( 'vw_translate_cache_translations', 1 );
$cache_duration     = (int)  get_option( 'vw_translate_cache_duration',     12 );
$shortcode_style    =        get_option( 'vw_translate_shortcode_style',    'dropdown' );
$stats              = VW_Translate_DB::get_stats();
?>

<div class="wrap vw-translate-wrap">

	<!-- Page Header -->
	<div class="vwt-page-header">
		<div class="page-title-area">
			<h1><?php esc_html_e( 'Settings', 'vw-translate' ); ?></h1>
			<p><?php esc_html_e( 'Configure how VW Translate behaves on your website.', 'vw-translate' ); ?></p>
		</div>
	</div>

	<!-- Stats -->
	<div class="vwt-stats-grid">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate' ) ); ?>" class="vwt-stat-card-link">
			<div class="vwt-stat-card clr-purple">
				<div class="stat-icon"><span class="dashicons dashicons-editor-textcolor"></span></div>
				<span class="stat-number"><?php echo esc_html( $stats['total_strings'] ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Total Strings', 'vw-translate' ); ?></span>
			</div>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate&filter=translated' ) ); ?>" class="vwt-stat-card-link">
			<div class="vwt-stat-card clr-green">
				<div class="stat-icon"><span class="dashicons dashicons-translation"></span></div>
				<span class="stat-number"><?php echo esc_html( $stats['total_translations'] ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Translations', 'vw-translate' ); ?></span>
			</div>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate-languages' ) ); ?>" class="vwt-stat-card-link">
			<div class="vwt-stat-card clr-blue">
				<div class="stat-icon"><span class="dashicons dashicons-admin-site-alt3"></span></div>
				<span class="stat-number"><?php echo esc_html( $stats['total_languages'] ); ?></span>
				<span class="stat-label"><?php esc_html_e( 'Languages', 'vw-translate' ); ?></span>
			</div>
		</a>
	</div>

	<!-- Translation Detection -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-admin-network"></span> <?php esc_html_e( 'Translation Detection', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<div class="vwt-settings-inline-grid col-3">

				<!-- URL Parameter -->
				<div class="vwt-inline-field">
					<div class="vwt-inline-field-header">
						<div>
							<label for="vw-translate-enable-url-param"><?php esc_html_e( 'URL Parameter', 'vw-translate' ); ?></label>
							<p class="description"><?php esc_html_e( 'Allow switching language via ?lang=CODE in the URL.', 'vw-translate' ); ?></p>
						</div>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-enable-url-param" <?php checked( $enable_url_param ); ?>>
							<span class="slider"></span>
						</label>
					</div>
				</div>

				<!-- Remember Language -->
				<div class="vwt-inline-field">
					<div class="vwt-inline-field-header">
						<div>
							<label for="vw-translate-enable-cookie"><?php esc_html_e( 'Remember Language', 'vw-translate' ); ?></label>
							<p class="description"><?php esc_html_e( 'Store the visitor\'s language preference in a cookie.', 'vw-translate' ); ?></p>
						</div>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-enable-cookie" <?php checked( $enable_cookie ); ?>>
							<span class="slider"></span>
						</label>
					</div>
				</div>

				<!-- Cookie Duration -->
				<div class="vwt-inline-field">
					<label for="vw-translate-cookie-duration"><?php esc_html_e( 'Cookie Duration', 'vw-translate' ); ?></label>
					<p class="description"><?php esc_html_e( 'Number of days to keep the language preference.', 'vw-translate' ); ?></p>
					<div class="vwt-number-input" style="margin-top:10px;">
						<input type="number" id="vw-translate-cookie-duration"
							   value="<?php echo esc_attr( $cookie_duration ); ?>"
							   min="1" max="365" step="1">
						<span class="vwt-unit"><?php esc_html_e( 'days', 'vw-translate' ); ?></span>
					</div>
				</div>

			</div>
		</div>
	</div>

	<!-- Language Switcher -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-translation"></span> <?php esc_html_e( 'Language Switcher', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<div class="vwt-settings-inline-grid col-2">

				<!-- Floating Switcher -->
				<div class="vwt-inline-field">
					<div class="vwt-inline-field-header">
						<div>
							<label for="vw-translate-enable-switcher"><?php esc_html_e( 'Floating Switcher', 'vw-translate' ); ?></label>
							<p class="description"><?php esc_html_e( 'Display a floating language switcher on the frontend.', 'vw-translate' ); ?></p>
						</div>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-enable-switcher" <?php checked( $enable_switcher ); ?>>
							<span class="slider"></span>
						</label>
					</div>
				</div>

				<!-- Switcher Position -->
				<div class="vwt-inline-field">
					<label for="vw-translate-switcher-position"><?php esc_html_e( 'Switcher Position', 'vw-translate' ); ?></label>
					<p class="description"><?php esc_html_e( 'Where to display the floating switcher.', 'vw-translate' ); ?></p>
					<select id="vw-translate-switcher-position" style="margin-top:10px; width:100%;">
						<option value="bottom-right" <?php selected( $switcher_position, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'vw-translate' ); ?></option>
						<option value="bottom-left" <?php selected( $switcher_position, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'vw-translate' ); ?></option>
						<option value="top-right" <?php selected( $switcher_position, 'top-right' ); ?>><?php esc_html_e( 'Top Right', 'vw-translate' ); ?></option>
						<option value="top-left" <?php selected( $switcher_position, 'top-left' ); ?>><?php esc_html_e( 'Top Left', 'vw-translate' ); ?></option>
					</select>
				</div>

			</div>
		</div>
	</div>

	<!-- Shortcode Switcher Style -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e( 'Shortcode Switcher Style', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<p class="description" style="margin:0 0 16px;">
				<?php esc_html_e( 'Choose the default design for the', 'vw-translate' ); ?>
				<code>[vw_translate_switcher]</code>
				<?php esc_html_e( 'shortcode. You can still override per-block with', 'vw-translate' ); ?>
				<code>[vw_translate_switcher style="pills"]</code>.
			</p>
			<div class="vwt-style-picker">

				<!-- Dropdown -->
				<label class="vwt-style-option">
					<input type="radio" name="shortcode_style" value="dropdown" <?php checked( $shortcode_style, 'dropdown' ); ?>>
					<div class="vwt-style-card">
						<div class="vwt-style-preview">
						<div class="vwt-prev-dropdown"><img src="https://flagcdn.com/w20/us.png" width="18" height="13" style="border-radius:2px;vertical-align:middle;margin-right:4px;"> English</div>
						</div>
						<span class="vwt-style-label-text"><?php esc_html_e( 'Dropdown', 'vw-translate' ); ?></span>
					</div>
				</label>

				<!-- Pills -->
				<label class="vwt-style-option">
					<input type="radio" name="shortcode_style" value="pills" <?php checked( $shortcode_style, 'pills' ); ?>>
					<div class="vwt-style-card">
						<div class="vwt-style-preview">
							<div class="vwt-prev-pills">
							<span class="act"><img src="https://flagcdn.com/w20/us.png" width="18" height="13" style="border-radius:2px;vertical-align:middle;margin-right:3px;"> EN</span>
							<span><img src="https://flagcdn.com/w20/pl.png" width="18" height="13" style="border-radius:2px;vertical-align:middle;margin-right:3px;"> PL</span>
							</div>
						</div>
						<span class="vwt-style-label-text"><?php esc_html_e( 'Pills', 'vw-translate' ); ?></span>
					</div>
				</label>

				<!-- Minimal -->
				<label class="vwt-style-option">
					<input type="radio" name="shortcode_style" value="minimal" <?php checked( $shortcode_style, 'minimal' ); ?>>
					<div class="vwt-style-card">
						<div class="vwt-style-preview">
							<div class="vwt-prev-minimal">
							<span class="act"><img src="https://flagcdn.com/w20/us.png" width="18" height="13" style="border-radius:2px;vertical-align:middle;margin-right:3px;"> EN</span>
							<span class="sep"></span>
							<span><img src="https://flagcdn.com/w20/pl.png" width="18" height="13" style="border-radius:2px;vertical-align:middle;margin-right:3px;"> PL</span>
							</div>
						</div>
						<span class="vwt-style-label-text"><?php esc_html_e( 'Minimal', 'vw-translate' ); ?></span>
					</div>
				</label>

				<!-- Cards -->
				<label class="vwt-style-option">
					<input type="radio" name="shortcode_style" value="cards" <?php checked( $shortcode_style, 'cards' ); ?>>
					<div class="vwt-style-card">
						<div class="vwt-style-preview">
							<div class="vwt-prev-cards">
							<span class="act"><img src="https://flagcdn.com/w20/us.png" width="20" height="15" style="border-radius:3px;vertical-align:middle;"><em>EN</em></span>
							<span><img src="https://flagcdn.com/w20/pl.png" width="20" height="15" style="border-radius:3px;vertical-align:middle;"><em>PL</em></span>
							</div>
						</div>
						<span class="vwt-style-label-text"><?php esc_html_e( 'Cards', 'vw-translate' ); ?></span>
					</div>
				</label>

				<!-- Elegant -->
				<label class="vwt-style-option">
					<input type="radio" name="shortcode_style" value="elegant" <?php checked( $shortcode_style, 'elegant' ); ?>>
					<div class="vwt-style-card">
						<div class="vwt-style-preview">
						<div class="vwt-prev-elegant"><img src="https://flagcdn.com/w20/us.png" width="18" height="13" style="border-radius:2px;vertical-align:middle;margin-right:4px;"> English</div>
						</div>
						<span class="vwt-style-label-text"><?php esc_html_e( 'Elegant', 'vw-translate' ); ?></span>
					</div>
				</label>

				<!-- Flag + Code -->
				<label class="vwt-style-option">
					<input type="radio" name="shortcode_style" value="flag-code" <?php checked( $shortcode_style, 'flag-code' ); ?>>
					<div class="vwt-style-card">
						<div class="vwt-style-preview">
							<div class="vwt-prev-pills" style="background:none;padding:0;gap:8px;">
								<span class="act" style="border:1.5px solid #667eea;padding:4px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:5px;"><img src="https://flagcdn.com/w20/us.png" width="18" height="13" style="border-radius:2px;"> EN</span>
								<span style="border:1.5px solid #e4e7ec;padding:4px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:5px;"><img src="https://flagcdn.com/w20/pl.png" width="18" height="13" style="border-radius:2px;"> PL</span>
							</div>
						</div>
						<span class="vwt-style-label-text"><?php esc_html_e( 'Flag + Code', 'vw-translate' ); ?></span>
					</div>
				</label>

				<!-- Flag Only -->
				<label class="vwt-style-option">
					<input type="radio" name="shortcode_style" value="flag-only" <?php checked( $shortcode_style, 'flag-only' ); ?>>
					<div class="vwt-style-card">
						<div class="vwt-style-preview">
							<div style="display:inline-flex;align-items:center;gap:8px;">
								<span style="border:2.5px solid #667eea;padding:3px;border-radius:5px;display:inline-flex;"><img src="https://flagcdn.com/w40/us.png" width="28" height="21" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,.2);"></span>
								<span style="border:2.5px solid transparent;padding:3px;border-radius:5px;display:inline-flex;opacity:.65;"><img src="https://flagcdn.com/w40/pl.png" width="28" height="21" style="border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,.2);"></span>
							</div>
						</div>
						<span class="vwt-style-label-text"><?php esc_html_e( 'Flag Only', 'vw-translate' ); ?></span>
					</div>
				</label>

			</div>
		</div>
	</div>

	<!-- Scanner Settings -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Scanner Settings', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<div class="vwt-settings-inline-grid col-2">

				<!-- Scan Depth -->
				<div class="vwt-inline-field">
					<label for="vw-translate-scan-depth"><?php esc_html_e( 'Scan Depth', 'vw-translate' ); ?></label>
					<p class="description"><?php esc_html_e( 'How many levels deep to scan directories (for theme/plugin scan).', 'vw-translate' ); ?></p>
					<div class="vwt-number-input" style="margin-top:10px;">
						<input type="number" id="vw-translate-scan-depth"
							   value="<?php echo esc_attr( $scan_depth ); ?>"
							   min="1" max="10" step="1">
						<span class="vwt-unit"><?php esc_html_e( 'levels', 'vw-translate' ); ?></span>
					</div>
				</div>

				<!-- Exclude Admin -->
				<div class="vwt-inline-field">
					<div class="vwt-inline-field-header">
						<div>
							<label for="vw-translate-exclude-admin"><?php esc_html_e( 'Exclude Admin', 'vw-translate' ); ?></label>
							<p class="description"><?php esc_html_e( 'Skip admin-only strings during frontend page scan.', 'vw-translate' ); ?></p>
						</div>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-exclude-admin" <?php checked( $exclude_admin ); ?>>
							<span class="slider"></span>
						</label>
					</div>
				</div>

			</div>
		</div>
	</div>

	<!-- Performance / Cache -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Performance', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<div class="vwt-settings-inline-grid col-2">

				<!-- Cache Translations -->
				<div class="vwt-inline-field">
					<div class="vwt-inline-field-header">
						<div>
							<label for="vw-translate-cache-translations"><?php esc_html_e( 'Cache Translations', 'vw-translate' ); ?></label>
							<p class="description"><?php esc_html_e( 'Use WordPress transients to cache translation lookups.', 'vw-translate' ); ?></p>
						</div>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-cache-translations" <?php checked( $cache_translations ); ?>>
							<span class="slider"></span>
						</label>
					</div>
				</div>

				<!-- Cache Duration -->
				<div class="vwt-inline-field">
					<label for="vw-translate-cache-duration"><?php esc_html_e( 'Cache Duration', 'vw-translate' ); ?></label>
					<p class="description"><?php esc_html_e( 'How long to keep cached translations.', 'vw-translate' ); ?></p>
					<div class="vwt-number-input" style="margin-top:10px;">
						<input type="number" id="vw-translate-cache-duration"
							   value="<?php echo esc_attr( $cache_duration ); ?>"
							   min="1" max="72" step="1">
						<span class="vwt-unit"><?php esc_html_e( 'hours', 'vw-translate' ); ?></span>
					</div>
				</div>

			</div>
		</div>
	</div>

	<!-- Save Button -->
	<div class="vwt-settings-footer">
		<button type="button" class="vwt-btn vwt-btn-primary vwt-btn-lg" id="vw-translate-save-settings">
			<span class="dashicons dashicons-saved"></span>
			<?php esc_html_e( 'Save Settings', 'vw-translate' ); ?>
		</button>
		<button type="button" class="vwt-btn vwt-btn-outline vwt-btn-lg" id="vw-translate-clear-cache" style="margin-left: 10px;">
			<span class="dashicons dashicons-trash"></span>
			<?php esc_html_e( 'Clear Translation Cache', 'vw-translate' ); ?>
		</button>
	</div>

	<!-- Cookie Reset Note -->
	<div class="vwt-notice info" style="margin-top: 16px;">
		<span class="dashicons dashicons-info"></span>
		<?php
		$default_lang = VW_Translate_DB::get_default_language();
		$default_code = $default_lang ? $default_lang->language_code : 'en';
		printf(
			/* translators: %s: reset URL */
			esc_html__( 'If your browser is showing a non-default language, visit %s to reset the language cookie and return to the default language.', 'vw-translate' ),
			'<a href="' . esc_url( home_url( '/?lang=' . $default_code ) ) . '" target="_blank">' . esc_url( home_url( '/?lang=' . $default_code ) ) . '</a>'
		);
		?>
	</div>

</div>
