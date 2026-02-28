<?php
/**
 * Admin strings management page — Professional Design.
 *
 * @package VW_Translate
 * @since   1.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get query parameters.
$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$source_type = isset( $_GET['source_type'] ) ? sanitize_text_field( wp_unslash( $_GET['source_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filter      = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$paged       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page    = 20;
$offset      = ( $paged - 1 ) * $per_page;

// Get strings.
$args = array(
	'status'      => 'active',
	'source_type' => $source_type,
	'filter'      => $filter,
	'search'      => $search,
	'per_page'    => $per_page,
	'offset'      => $offset,
	'orderby'     => 'id',
	'order'       => 'DESC',
);

$strings     = VW_Translate_DB::get_strings( $args );
$total_items = VW_Translate_DB::get_strings_count( $args );
$total_pages = ceil( $total_items / $per_page );

// Get languages for translation badges.
$languages = VW_Translate_DB::get_languages( true );
$stats     = VW_Translate_DB::get_stats();
?>

<div class="wrap vw-translate-wrap">

	<!-- Page Header -->
	<div class="vwt-page-header">
		<div class="page-title-area">
			<h1>
				<?php
				if ( 'translated' === $filter ) {
					esc_html_e( 'Translated Strings', 'vw-translate' );
				} elseif ( 'untranslated' === $filter ) {
					esc_html_e( 'Untranslated Strings', 'vw-translate' );
				} else {
					esc_html_e( 'All Strings', 'vw-translate' );
				}
				?>
			</h1>
			<p>
				<?php
				if ( 'translated' === $filter ) {
					esc_html_e( 'Showing only strings that have at least one translation.', 'vw-translate' );
				} elseif ( 'untranslated' === $filter ) {
					esc_html_e( 'Showing only strings without any translation.', 'vw-translate' );
				} else {
					esc_html_e( 'Manage and translate all discovered strings from your last scan.', 'vw-translate' );
				}
				?>
			</p>
			<?php if ( ! empty( $filter ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate' ) ); ?>" class="vwt-btn vwt-btn-outline vwt-btn-sm" style="margin-top:6px;">
					<?php esc_html_e( '← Show All Strings', 'vw-translate' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<div class="header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate-scan' ) ); ?>" class="btn-header">
				<span class="dashicons dashicons-search"></span>
				<?php esc_html_e( 'Scan Strings', 'vw-translate' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate-languages' ) ); ?>" class="btn-header">
				<span class="dashicons dashicons-admin-site-alt3"></span>
				<?php esc_html_e( 'Languages', 'vw-translate' ); ?>
			</a>
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

	<!-- Add Manual String -->
	<div class="vwt-card">
		<div class="vwt-card-header">
			<h3><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add String Manually', 'vw-translate' ); ?></h3>
		</div>
		<div class="vwt-card-body">
			<div class="vwt-add-string-area">
				<textarea id="vw-translate-manual-string" placeholder="<?php esc_attr_e( 'Enter a word or sentence to translate...', 'vw-translate' ); ?>"></textarea>
				<button type="button" class="vwt-btn vwt-btn-primary" id="vw-translate-add-string-btn">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add String', 'vw-translate' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Strings Table Card -->
	<div class="vwt-card">

		<!-- Table Toolbar -->
		<div class="vwt-table-toolbar">
			<div class="vwt-search-group">
				<input type="search" id="vw-translate-search-input" value="<?php echo esc_attr( $search ); ?>"
					   placeholder="<?php esc_attr_e( 'Search strings...', 'vw-translate' ); ?>">
				<button type="button" class="vwt-btn vwt-btn-outline" id="vw-translate-search-btn">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Search', 'vw-translate' ); ?>
				</button>
			</div>
			<div class="vwt-filter-group">
				<label for="vw-translate-filter-source"><?php esc_html_e( 'Source:', 'vw-translate' ); ?></label>
				<select id="vw-translate-filter-source">
					<option value=""><?php esc_html_e( 'All Sources', 'vw-translate' ); ?></option>
					<option value="theme" <?php selected( $source_type, 'theme' ); ?>><?php esc_html_e( 'Theme', 'vw-translate' ); ?></option>
					<option value="plugin" <?php selected( $source_type, 'plugin' ); ?>><?php esc_html_e( 'Plugin', 'vw-translate' ); ?></option>
					<option value="frontend" <?php selected( $source_type, 'frontend' ); ?>><?php esc_html_e( 'Frontend', 'vw-translate' ); ?></option>
					<option value="manual" <?php selected( $source_type, 'manual' ); ?>><?php esc_html_e( 'Manual', 'vw-translate' ); ?></option>
				</select>
			</div>
		</div>

		<?php if ( ! empty( $strings ) ) : ?>

			<table class="vwt-data-table">
				<thead>
					<tr>
						<th class="col-string"><?php esc_html_e( 'Original String', 'vw-translate' ); ?></th>
						<th class="col-source"><?php esc_html_e( 'Source', 'vw-translate' ); ?></th>
						<th class="col-translations"><?php esc_html_e( 'Translations', 'vw-translate' ); ?></th>
						<th class="col-actions"><?php esc_html_e( 'Actions', 'vw-translate' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $strings as $string ) : ?>
						<?php
						$string_translations = VW_Translate_DB::get_translations_for_string( $string->id );
						$trans_map           = array();
						foreach ( $string_translations as $trans ) {
							$trans_map[ $trans->language_code ] = $trans->translated_string;
						}
						?>
						<tr data-string-row="<?php echo esc_attr( $string->id ); ?>">
							<td class="col-string">
								<div class="string-text"><?php echo esc_html( $string->original_string ); ?></div>
							</td>
							<td class="col-source">
								<span class="vwt-badge vwt-badge-<?php echo esc_attr( $string->source_type ); ?>">
									<?php echo esc_html( $string->source_type ); ?>
								</span>
								<span class="vwt-source-name" title="<?php echo esc_attr( $string->source_name ); ?>"><?php echo esc_html( $string->source_name ); ?></span>
							</td>
							<td class="col-translations">
								<div class="vwt-lang-badges">
									<?php foreach ( $languages as $lang ) : ?>
										<?php $has_trans = ! empty( $trans_map[ $lang->language_code ] ); ?>
										<span class="vwt-lang-badge <?php echo $has_trans ? 'translated' : ''; ?>"
											  data-lang="<?php echo esc_attr( $lang->language_code ); ?>"
											  title="<?php echo esc_attr( $lang->language_name ); ?>">
											<?php
											if ( ! empty( $lang->flag ) ) {
												echo esc_html( $lang->flag ) . ' ';
											}
											echo esc_html( strtoupper( $lang->language_code ) );
											?>
										</span>
									<?php endforeach; ?>
								</div>
							</td>
							<td class="col-actions">
								<div class="vwt-row-actions">
									<button type="button" class="vwt-btn vwt-btn-primary vwt-btn-sm vw-translate-btn-translate"
											data-string-id="<?php echo esc_attr( $string->id ); ?>">
										<span class="dashicons dashicons-edit"></span>
										<?php esc_html_e( 'Translate', 'vw-translate' ); ?>
									</button>
									<button type="button" class="vwt-btn-icon vw-translate-btn-delete"
											data-string-id="<?php echo esc_attr( $string->id ); ?>"
											title="<?php esc_attr_e( 'Delete', 'vw-translate' ); ?>">
										<span class="dashicons dashicons-trash"></span>
									</button>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="vwt-pagination">
					<div class="pag-info">
						<?php
						printf(
							/* translators: 1: first item number, 2: last item number, 3: total items */
							esc_html__( 'Showing %1$d–%2$d of %3$d strings', 'vw-translate' ),
							$offset + 1,
							min( $offset + $per_page, $total_items ),
							$total_items
						);
						?>
					</div>
					<div class="pag-links">
						<?php
						$base_url = admin_url( 'admin.php?page=vw-translate' );
						if ( $search ) {
							$base_url = add_query_arg( 's', $search, $base_url );
						}
						if ( $source_type ) {
							$base_url = add_query_arg( 'source_type', $source_type, $base_url );
						}
						if ( $filter ) {
							$base_url = add_query_arg( 'filter', $filter, $base_url );
						}

						if ( $paged > 1 ) :
							?>
							<a href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1, $base_url ) ); ?>">&laquo; <?php esc_html_e( 'Prev', 'vw-translate' ); ?></a>
						<?php endif; ?>

						<?php
						$start_page = max( 1, $paged - 2 );
						$end_page   = min( $total_pages, $paged + 2 );

						for ( $i = $start_page; $i <= $end_page; $i++ ) :
							if ( $i === $paged ) :
								?>
								<span class="current"><?php echo esc_html( $i ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( add_query_arg( 'paged', $i, $base_url ) ); ?>"><?php echo esc_html( $i ); ?></a>
								<?php
							endif;
						endfor;

						if ( $paged < $total_pages ) :
							?>
							<a href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1, $base_url ) ); ?>"><?php esc_html_e( 'Next', 'vw-translate' ); ?> &raquo;</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

		<?php else : ?>

			<div class="vwt-empty-state">
				<div class="empty-icon">
					<span class="dashicons dashicons-translation"></span>
				</div>
				<?php if ( ! empty( $search ) ) : ?>
					<h3><?php esc_html_e( 'No matching strings', 'vw-translate' ); ?></h3>
					<p><?php esc_html_e( 'No strings found matching your search. Try a different keyword.', 'vw-translate' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate' ) ); ?>" class="vwt-btn vwt-btn-outline">
						<?php esc_html_e( 'Clear Search', 'vw-translate' ); ?>
					</a>
				<?php else : ?>
					<h3><?php esc_html_e( 'No strings found', 'vw-translate' ); ?></h3>
					<p><?php esc_html_e( 'Run a scan to discover translatable strings from your website.', 'vw-translate' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=vw-translate-scan' ) ); ?>" class="vwt-btn vwt-btn-primary">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'Scan Now', 'vw-translate' ); ?>
					</a>
				<?php endif; ?>
			</div>

		<?php endif; ?>

	</div>

</div>
