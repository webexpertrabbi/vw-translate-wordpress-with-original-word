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

// Get current options.
$options            = get_option( 'vw_translate_settings', array() );
$enable_url_param   = isset( $options['enable_url_param'] )   ? (bool) $options['enable_url_param']   : true;
$enable_cookie      = isset( $options['enable_cookie'] )      ? (bool) $options['enable_cookie']      : true;
$cookie_duration    = isset( $options['cookie_duration'] )     ? (int) $options['cookie_duration']     : 30;
$enable_switcher    = isset( $options['enable_switcher'] )     ? (bool) $options['enable_switcher']    : true;
$switcher_position  = isset( $options['switcher_position'] )  ? $options['switcher_position']         : 'bottom-right';
$scan_depth         = isset( $options['scan_depth'] )         ? (int) $options['scan_depth']          : 3;
$exclude_admin      = isset( $options['exclude_admin'] )      ? (bool) $options['exclude_admin']      : true;
$cache_translations = isset( $options['cache_translations'] ) ? (bool) $options['cache_translations'] : true;
$cache_duration     = isset( $options['cache_duration'] )     ? (int) $options['cache_duration']      : 12;
?>

<div class="wrap vw-translate-wrap">

	<!-- Page Header -->
	<div class="vwt-page-header">
		<div class="page-title-area">
			<h1><?php esc_html_e( 'Settings', 'vw-translate' ); ?></h1>
			<p><?php esc_html_e( 'Configure how VW Translate behaves on your website.', 'vw-translate' ); ?></p>
		</div>
	</div>

	<!-- Translation Detection -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-admin-network"></span> <?php esc_html_e( 'Translation Detection', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body no-pad">
			<table class="vwt-settings-table">
				<tr>
					<th>
						<label for="vw-translate-enable-url-param"><?php esc_html_e( 'URL Parameter', 'vw-translate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Allow switching language via ?lang=CODE in the URL.', 'vw-translate' ); ?></p>
					</th>
					<td>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-enable-url-param" <?php checked( $enable_url_param ); ?>>
							<span class="slider"></span>
						</label>
					</td>
				</tr>
				<tr>
					<th>
						<label for="vw-translate-enable-cookie"><?php esc_html_e( 'Remember Language', 'vw-translate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Store the visitor\'s language preference in a cookie.', 'vw-translate' ); ?></p>
					</th>
					<td>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-enable-cookie" <?php checked( $enable_cookie ); ?>>
							<span class="slider"></span>
						</label>
					</td>
				</tr>
				<tr>
					<th>
						<label for="vw-translate-cookie-duration"><?php esc_html_e( 'Cookie Duration', 'vw-translate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Number of days to keep the language preference.', 'vw-translate' ); ?></p>
					</th>
					<td>
						<div class="vwt-number-input">
							<input type="number" id="vw-translate-cookie-duration"
								   value="<?php echo esc_attr( $cookie_duration ); ?>"
								   min="1" max="365" step="1">
							<span class="vwt-unit"><?php esc_html_e( 'days', 'vw-translate' ); ?></span>
						</div>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<!-- Language Switcher -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-translation"></span> <?php esc_html_e( 'Language Switcher', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body no-pad">
			<table class="vwt-settings-table">
				<tr>
					<th>
						<label for="vw-translate-enable-switcher"><?php esc_html_e( 'Floating Switcher', 'vw-translate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Display a floating language switcher on the frontend.', 'vw-translate' ); ?></p>
					</th>
					<td>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-enable-switcher" <?php checked( $enable_switcher ); ?>>
							<span class="slider"></span>
						</label>
					</td>
				</tr>
				<tr>
					<th>
						<label for="vw-translate-switcher-position"><?php esc_html_e( 'Switcher Position', 'vw-translate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Where to display the floating switcher.', 'vw-translate' ); ?></p>
					</th>
					<td>
						<select id="vw-translate-switcher-position">
							<option value="bottom-right" <?php selected( $switcher_position, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'vw-translate' ); ?></option>
							<option value="bottom-left" <?php selected( $switcher_position, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'vw-translate' ); ?></option>
							<option value="top-right" <?php selected( $switcher_position, 'top-right' ); ?>><?php esc_html_e( 'Top Right', 'vw-translate' ); ?></option>
							<option value="top-left" <?php selected( $switcher_position, 'top-left' ); ?>><?php esc_html_e( 'Top Left', 'vw-translate' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<!-- Scanner Settings -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Scanner Settings', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body no-pad">
			<table class="vwt-settings-table">
				<tr>
					<th>
						<label for="vw-translate-scan-depth"><?php esc_html_e( 'Scan Depth', 'vw-translate' ); ?></label>
						<p class="description"><?php esc_html_e( 'How many levels deep to scan directories (for theme/plugin scan).', 'vw-translate' ); ?></p>
					</th>
					<td>
						<div class="vwt-number-input">
							<input type="number" id="vw-translate-scan-depth"
								   value="<?php echo esc_attr( $scan_depth ); ?>"
								   min="1" max="10" step="1">
							<span class="vwt-unit"><?php esc_html_e( 'levels', 'vw-translate' ); ?></span>
						</div>
					</td>
				</tr>
				<tr>
					<th>
						<label for="vw-translate-exclude-admin"><?php esc_html_e( 'Exclude Admin', 'vw-translate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Skip admin-only strings during frontend page scan.', 'vw-translate' ); ?></p>
					</th>
					<td>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-exclude-admin" <?php checked( $exclude_admin ); ?>>
							<span class="slider"></span>
						</label>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<!-- Performance / Cache -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Performance', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body no-pad">
			<table class="vwt-settings-table">
				<tr>
					<th>
						<label for="vw-translate-cache-translations"><?php esc_html_e( 'Cache Translations', 'vw-translate' ); ?></label>
						<p class="description"><?php esc_html_e( 'Use WordPress transients to cache translation lookups.', 'vw-translate' ); ?></p>
					</th>
					<td>
						<label class="vwt-switch">
							<input type="checkbox" id="vw-translate-cache-translations" <?php checked( $cache_translations ); ?>>
							<span class="slider"></span>
						</label>
					</td>
				</tr>
				<tr>
					<th>
						<label for="vw-translate-cache-duration"><?php esc_html_e( 'Cache Duration', 'vw-translate' ); ?></label>
						<p class="description"><?php esc_html_e( 'How long to keep cached translations.', 'vw-translate' ); ?></p>
					</th>
					<td>
						<div class="vwt-number-input">
							<input type="number" id="vw-translate-cache-duration"
								   value="<?php echo esc_attr( $cache_duration ); ?>"
								   min="1" max="72" step="1">
							<span class="vwt-unit"><?php esc_html_e( 'hours', 'vw-translate' ); ?></span>
						</div>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<!-- Save Button -->
	<div class="vwt-settings-footer">
		<button type="button" class="vwt-btn vwt-btn-primary vwt-btn-lg" id="vw-translate-save-settings">
			<span class="dashicons dashicons-saved"></span>
			<?php esc_html_e( 'Save Settings', 'vw-translate' ); ?>
		</button>
	</div>

</div>
