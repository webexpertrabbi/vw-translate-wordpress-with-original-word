<?php
/**
 * Admin languages management page — Professional Design.
 *
 * @package VW_Translate
 * @since   1.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current languages.
$languages           = VW_Translate_DB::get_languages();
$available_languages = VW_Translate_DB::get_available_languages();

// Remove already added languages from available list.
$added_codes = array();
foreach ( $languages as $lang ) {
	$added_codes[] = $lang->language_code;
}
?>

<div class="wrap vw-translate-wrap">

	<!-- Page Header -->
	<div class="vwt-page-header">
		<div class="page-title-area">
			<h1><?php esc_html_e( 'Languages', 'vw-translate' ); ?></h1>
			<p><?php esc_html_e( 'Manage the languages you want to translate your website into.', 'vw-translate' ); ?></p>
		</div>
		<div class="header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate' ) ); ?>" class="btn-header">
				<span class="dashicons dashicons-list-view"></span>
				<?php esc_html_e( 'All Strings', 'vw-translate' ); ?>
			</a>
		</div>
	</div>

	<div class="vwt-languages-grid">

		<!-- Active Languages Card -->
		<div class="vwt-card">
			<div class="vwt-card-header">
				<h3><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e( 'Active Languages', 'vw-translate' ); ?></h3>
			</div>
			<div class="vwt-card-body no-pad">
				<?php if ( ! empty( $languages ) ) : ?>
					<ul class="vwt-lang-list">
						<?php foreach ( $languages as $lang ) : ?>
							<li>
								<div class="lang-info">
									<span class="lang-flag-icon"><?php echo esc_html( $lang->flag ); ?></span>
									<div class="lang-details">
										<span class="lang-name"><?php echo esc_html( $lang->language_name ); ?></span>
										<div class="lang-meta">
											<?php if ( ! empty( $lang->native_name ) && $lang->native_name !== $lang->language_name ) : ?>
												<span class="lang-native-text"><?php echo esc_html( $lang->native_name ); ?></span>
											<?php endif; ?>
											<span class="lang-code-tag"><?php echo esc_html( $lang->language_code ); ?></span>
										</div>
									</div>
								</div>
								<div class="lang-actions">
									<?php if ( $lang->is_default ) : ?>
										<span class="vwt-default-badge"><?php esc_html_e( 'Default', 'vw-translate' ); ?></span>
									<?php else : ?>
										<button type="button" class="vwt-btn vwt-btn-outline vwt-btn-sm vw-translate-set-default"
												data-lang-id="<?php echo esc_attr( $lang->id ); ?>">
											<?php esc_html_e( 'Set Default', 'vw-translate' ); ?>
										</button>
										<button type="button" class="vwt-btn-icon vw-translate-delete-lang"
												data-lang-id="<?php echo esc_attr( $lang->id ); ?>"
												title="<?php esc_attr_e( 'Delete language', 'vw-translate' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<div class="vwt-empty-state" style="padding: 30px 20px;">
						<div class="empty-icon"><span class="dashicons dashicons-admin-site-alt3"></span></div>
						<h3><?php esc_html_e( 'No languages added', 'vw-translate' ); ?></h3>
						<p><?php esc_html_e( 'Add your first language to start translating.', 'vw-translate' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Add New Language Card -->
		<div class="vwt-card">
			<div class="vwt-card-header">
				<h3><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Language', 'vw-translate' ); ?></h3>
			</div>
			<div class="vwt-card-body">

				<!-- Preset Selector -->
				<div class="vwt-form-group">
					<label for="vw-translate-lang-preset">
						<?php esc_html_e( 'Choose from preset', 'vw-translate' ); ?>
					</label>
					<select id="vw-translate-lang-preset">
						<option value=""><?php esc_html_e( '— Select a language —', 'vw-translate' ); ?></option>
						<?php foreach ( $available_languages as $code => $lang_data ) : ?>
							<?php if ( ! in_array( $code, $added_codes, true ) ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>"
										data-name="<?php echo esc_attr( $lang_data['name'] ); ?>"
										data-native="<?php echo esc_attr( $lang_data['native'] ); ?>"
										data-flag="<?php echo esc_attr( $lang_data['flag'] ); ?>">
									<?php echo esc_html( $lang_data['flag'] . ' ' . $lang_data['name'] . ' (' . $lang_data['native'] . ')' ); ?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				</div>

				<hr class="vwt-form-divider">

				<div class="vwt-form-group">
					<label for="vw-translate-lang-code">
						<?php esc_html_e( 'Language Code', 'vw-translate' ); ?> <span class="required">*</span>
					</label>
					<input type="text" id="vw-translate-lang-code" placeholder="<?php esc_attr_e( 'e.g., bn, fr, de', 'vw-translate' ); ?>"
						   maxlength="10" style="max-width: 160px;">
				</div>

				<div class="vwt-form-group">
					<label for="vw-translate-lang-name">
						<?php esc_html_e( 'Language Name (English)', 'vw-translate' ); ?> <span class="required">*</span>
					</label>
					<input type="text" id="vw-translate-lang-name" placeholder="<?php esc_attr_e( 'e.g., Bengali, French', 'vw-translate' ); ?>">
				</div>

				<div class="vwt-form-group">
					<label for="vw-translate-lang-native">
						<?php esc_html_e( 'Native Name', 'vw-translate' ); ?>
					</label>
					<input type="text" id="vw-translate-lang-native" placeholder="<?php esc_attr_e( 'e.g., বাংলা, Français', 'vw-translate' ); ?>">
				</div>

				<div class="vwt-form-group">
					<label for="vw-translate-lang-flag">
						<?php esc_html_e( 'Flag Emoji', 'vw-translate' ); ?>
					</label>
					<input type="text" id="vw-translate-lang-flag" placeholder="<?php esc_attr_e( 'e.g., 🇧🇩, 🇫🇷', 'vw-translate' ); ?>"
						   style="max-width: 120px;">
				</div>

				<div class="vwt-checkbox-row">
					<input type="checkbox" id="vw-translate-lang-default">
					<label for="vw-translate-lang-default">
						<?php esc_html_e( 'Set as default language', 'vw-translate' ); ?>
					</label>
				</div>

			</div>
			<div class="vwt-card-footer">
				<button type="button" class="vwt-btn vwt-btn-primary" id="vw-translate-add-lang-btn">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add Language', 'vw-translate' ); ?>
				</button>
			</div>
		</div>

	</div>

	<!-- Language Switcher Usage Card -->
	<div class="vwt-card vwt-info-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'Language Switcher Usage', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">

			<div class="info-block">
				<h4><?php esc_html_e( 'Shortcode', 'vw-translate' ); ?></h4>
				<p><?php esc_html_e( 'Use the following shortcode to display a language switcher anywhere:', 'vw-translate' ); ?></p>
				<span class="vwt-code-snippet">[vw_translate_switcher]</span>
				<br><br>
				<p><?php esc_html_e( 'Available styles: dropdown, list, flags', 'vw-translate' ); ?></p>
				<span class="vwt-code-snippet">[vw_translate_switcher style="list"]</span>
			</div>

			<div class="info-block">
				<h4><?php esc_html_e( 'Widget', 'vw-translate' ); ?></h4>
				<p>
					<?php
					printf(
						/* translators: %s: link to widgets page */
						esc_html__( 'Go to %s to add the VW Language Switcher widget to your sidebar.', 'vw-translate' ),
						'<a href="' . esc_url( admin_url( 'widgets.php' ) ) . '">' . esc_html__( 'Appearance → Widgets', 'vw-translate' ) . '</a>'
					);
					?>
				</p>
			</div>

			<div class="info-block">
				<h4><?php esc_html_e( 'URL Parameter', 'vw-translate' ); ?></h4>
				<p><?php esc_html_e( 'Switch languages by adding ?lang=CODE to any URL:', 'vw-translate' ); ?></p>
				<span class="vwt-code-snippet"><?php echo esc_html( home_url( '/?lang=bn' ) ); ?></span>
			</div>

		</div>
	</div>

</div>
