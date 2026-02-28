<?php
/**
 * Admin scan page — Professional Design.
 *
 * @package VW_Translate
 * @since   1.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$theme          = wp_get_theme();
$active_plugins = get_option( 'active_plugins', array() );
$stats          = VW_Translate_DB::get_stats();
?>

<div class="wrap vw-translate-wrap">

	<!-- Page Header -->
	<div class="vwt-page-header">
		<div class="page-title-area">
			<h1><?php esc_html_e( 'Scan Strings', 'vw-translate' ); ?></h1>
			<p><?php esc_html_e( 'Discover translatable strings from your website by scanning frontend pages or source files.', 'vw-translate' ); ?></p>
		</div>
		<div class="header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate' ) ); ?>" class="btn-header">
				<span class="dashicons dashicons-list-view"></span>
				<?php esc_html_e( 'View Strings', 'vw-translate' ); ?>
			</a>
		</div>
	</div>

	<!-- Current Stats -->
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

	<!-- Info Notice -->
	<div class="vwt-notice info">
		<span class="dashicons dashicons-info"></span>
		<?php esc_html_e( 'Scanning will add new strings without removing existing ones. Your translations are always preserved.', 'vw-translate' ); ?>
	</div>

	<!-- Scan Options Card -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Choose Scan Method', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<div class="vwt-scan-options-grid">

				<!-- Frontend Pages (Recommended) -->
				<label class="vwt-scan-option recommended selected">
					<span class="rec-tag"><?php esc_html_e( 'Recommended', 'vw-translate' ); ?></span>
					<input type="radio" name="scan_type" value="frontend" checked>
					<div class="option-content">
						<div class="option-title"><?php esc_html_e( 'Scan Frontend Pages', 'vw-translate' ); ?></div>
						<div class="option-desc">
							<?php esc_html_e( 'Fetches your actual published pages and extracts only visible text. Produces fewer, more relevant strings and keeps your site fast.', 'vw-translate' ); ?>
						</div>
					</div>
				</label>

				<!-- All Source Files -->
				<label class="vwt-scan-option">
					<input type="radio" name="scan_type" value="all">
					<div class="option-content">
						<div class="option-title"><?php esc_html_e( 'Scan All Source Files (Theme + Plugins)', 'vw-translate' ); ?></div>
						<div class="option-desc">
							<?php esc_html_e( 'Scans all PHP files for translatable strings. May produce thousands of strings including backend-only ones.', 'vw-translate' ); ?>
						</div>
					</div>
				</label>

				<!-- Theme Only -->
				<label class="vwt-scan-option">
					<input type="radio" name="scan_type" value="theme">
					<div class="option-content">
						<div class="option-title">
							<?php
							printf(
								/* translators: %s: theme name */
								esc_html__( 'Scan Theme Only — %s', 'vw-translate' ),
								esc_html( $theme->get( 'Name' ) )
							);
							?>
						</div>
						<div class="option-desc">
							<?php esc_html_e( 'Only scan the active theme files for translatable strings.', 'vw-translate' ); ?>
						</div>
					</div>
				</label>

				<!-- Plugins Only -->
				<label class="vwt-scan-option">
					<input type="radio" name="scan_type" value="plugins">
					<div class="option-content">
						<div class="option-title">
							<?php
							printf(
								/* translators: %d: number of active plugins */
								esc_html__( 'Scan Plugins Only — %d active plugins', 'vw-translate' ),
								count( $active_plugins )
							);
							?>
						</div>
						<div class="option-desc">
							<?php esc_html_e( 'Only scan active plugin files for translatable strings.', 'vw-translate' ); ?>
						</div>
					</div>
				</label>

			</div>
		</div>
	</div>

	<!-- Environment Info Card -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'Environment Info', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<div class="vwt-env-info">
				<div class="env-block">
					<h4><?php esc_html_e( 'Active Theme', 'vw-translate' ); ?></h4>
					<p>
						<strong><?php echo esc_html( $theme->get( 'Name' ) ); ?></strong>
						<?php if ( is_child_theme() ) : ?>
							<em>(<?php esc_html_e( 'Child Theme', 'vw-translate' ); ?>)</em>
						<?php endif; ?>
					</p>
				</div>
				<div class="env-block">
					<h4><?php esc_html_e( 'Active Plugins', 'vw-translate' ); ?></h4>
					<ul>
						<?php foreach ( $active_plugins as $plugin ) : ?>
							<?php
							$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
							$plugin_name = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : dirname( $plugin );
							if ( strpos( $plugin, 'vw-translate-wordpress-with-original-word' ) !== false ) {
								continue;
							}
							?>
							<li><?php echo esc_html( $plugin_name ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
		<div class="vwt-card-footer">
			<div class="vwt-scan-trigger">
				<button type="button" class="vwt-btn vwt-btn-primary vwt-btn-lg" id="vw-translate-scan-btn"
						data-label="<?php esc_attr_e( 'Start Scan', 'vw-translate' ); ?>">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Start Scan', 'vw-translate' ); ?>
				</button>
				<p class="scan-help"><?php esc_html_e( 'Scanning may take a moment depending on the number of pages and files.', 'vw-translate' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Progress Card -->
	<div class="vwt-card vwt-scan-progress">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Scanning...', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<div class="progress-track">
				<div class="progress-fill" style="width: 0%;"></div>
			</div>
			<div class="progress-label">
				<span class="progress-status"><?php esc_html_e( 'Preparing...', 'vw-translate' ); ?></span>
				<span class="progress-pct">0%</span>
			</div>
		</div>
	</div>

	<!-- Results Card -->
	<div class="vwt-card vwt-scan-results">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Scan Complete', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<div class="results-grid"></div>
		</div>
		<div class="vwt-card-footer">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate' ) ); ?>" class="vwt-btn vwt-btn-primary">
				<span class="dashicons dashicons-list-view"></span>
				<?php esc_html_e( 'View All Strings', 'vw-translate' ); ?>
			</a>
		</div>
	</div>

</div>
