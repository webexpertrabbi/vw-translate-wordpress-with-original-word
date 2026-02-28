<?php
/**
 * String scanner for VW Translate.
 *
 * @package VW_Translate
 * @since   1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VW_Translate_Scanner
 *
 * Scans active theme and plugin files to extract translatable strings.
 *
 * @since 1.0.0
 */
class VW_Translate_Scanner {

	/**
	 * Maximum file scanning depth.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $max_depth = 5;

	/**
	 * File extensions to scan.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $scan_extensions = array( 'php' );

	/**
	 * Directories to skip during scanning.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $skip_dirs = array(
		'node_modules',
		'vendor',
		'.git',
		'.svn',
		'tests',
		'test',
		'cache',
	);

	/**
	 * Regex patterns for WordPress i18n functions.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $i18n_patterns = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->max_depth = (int) get_option( 'vw_translate_scan_depth', 5 );

		// Build regex patterns for i18n functions.
		$this->build_patterns();
	}

	/**
	 * Build regex patterns for extracting translatable strings.
	 *
	 * @since 1.0.0
	 */
	private function build_patterns() {

		// WordPress i18n functions that take a single string + text domain.
		$single_arg_functions = array(
			'__',
			'_e',
			'esc_html__',
			'esc_html_e',
			'esc_attr__',
			'esc_attr_e',
		);

		// Functions with context: _x('string', 'context', 'domain').
		$context_functions = array(
			'_x',
			'_ex',
			'esc_html_x',
			'esc_attr_x',
		);

		foreach ( $single_arg_functions as $func ) {
			// Match single-quoted strings.
			$this->i18n_patterns[] = '/' . preg_quote( $func, '/' ) . '\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*(?:,\s*[\'"][^\'"]*[\'"])?\s*\)/s';
			// Match double-quoted strings.
			$this->i18n_patterns[] = '/' . preg_quote( $func, '/' ) . '\(\s*"((?:[^"\\\\]|\\\\.)*)"\s*(?:,\s*[\'"][^\'"]*[\'"])?\s*\)/s';
		}

		foreach ( $context_functions as $func ) {
			// Match _x('string', 'context', 'domain') - single quotes.
			$this->i18n_patterns[] = '/' . preg_quote( $func, '/' ) . '\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*,\s*\'(?:[^\'\\\\]|\\\\.)*\'\s*(?:,\s*[\'"][^\'"]*[\'"])?\s*\)/s';
			// Match _x("string", "context", "domain") - double quotes.
			$this->i18n_patterns[] = '/' . preg_quote( $func, '/' ) . '\(\s*"((?:[^"\\\\]|\\\\.)*)"\s*,\s*"(?:[^"\\\\]|\\\\.)*"\s*(?:,\s*[\'"][^\'"]*[\'"])?\s*\)/s';
		}

		// Also scan for visible text in HTML templates.
		// Matches text between HTML tags like >Some Text<.
		$this->i18n_patterns[] = '/>\s*([A-Z][^<>{}\$\n]{2,80})\s*</';
	}

	/**
	 * Scan the active theme for translatable strings.
	 *
	 * @since 1.0.0
	 * @return array Array of found strings.
	 */
	public function scan_theme() {

		$theme     = wp_get_theme();
		$theme_dir = get_template_directory();
		$strings   = array();

		// Scan parent theme.
		$theme_strings = $this->scan_directory( $theme_dir );

		foreach ( $theme_strings as $string ) {
			$string['source_type'] = 'theme';
			$string['source_name'] = $theme->get( 'Name' );
			$strings[]             = $string;
		}

		// Scan child theme if active.
		if ( is_child_theme() ) {
			$child_dir     = get_stylesheet_directory();
			$child_strings = $this->scan_directory( $child_dir );

			foreach ( $child_strings as $string ) {
				$string['source_type'] = 'theme';
				$string['source_name'] = wp_get_theme()->get( 'Name' ) . ' (Child)';
				$strings[]             = $string;
			}
		}

		return $strings;
	}

	/**
	 * Scan active plugins for translatable strings.
	 *
	 * @since 1.0.0
	 * @return array Array of found strings.
	 */
	public function scan_plugins() {

		$active_plugins = get_option( 'active_plugins', array() );
		$strings        = array();

		foreach ( $active_plugins as $plugin ) {
			$plugin_dir  = WP_PLUGIN_DIR . '/' . dirname( $plugin );
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, false );
			$plugin_name = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : dirname( $plugin );

			// Skip our own plugin.
			if ( strpos( $plugin, 'vw-translate-wordpress-with-original-word' ) !== false ) {
				continue;
			}

			if ( is_dir( $plugin_dir ) ) {
				$plugin_strings = $this->scan_directory( $plugin_dir );

				foreach ( $plugin_strings as $string ) {
					$string['source_type'] = 'plugin';
					$string['source_name'] = $plugin_name;
					$strings[]             = $string;
				}
			}
		}

		return $strings;
	}

	/**
	 * Scan a directory recursively for translatable strings.
	 *
	 * @since 1.0.0
	 * @param string $dir   Directory path.
	 * @param int    $depth Current depth.
	 * @return array Array of found strings.
	 */
	public function scan_directory( $dir, $depth = 0 ) {

		$strings = array();

		if ( $depth >= $this->max_depth ) {
			return $strings;
		}

		if ( ! is_dir( $dir ) || ! is_readable( $dir ) ) {
			return $strings;
		}

		$iterator = new DirectoryIterator( $dir );

		foreach ( $iterator as $file ) {
			if ( $file->isDot() ) {
				continue;
			}

			$filename = $file->getFilename();

			// Skip hidden files and directories.
			if ( strpos( $filename, '.' ) === 0 ) {
				continue;
			}

			if ( $file->isDir() ) {
				// Skip excluded directories.
				if ( in_array( strtolower( $filename ), $this->skip_dirs, true ) ) {
					continue;
				}

				$sub_strings = $this->scan_directory( $file->getPathname(), $depth + 1 );
				$strings     = array_merge( $strings, $sub_strings );
			} elseif ( $file->isFile() ) {
				$extension = strtolower( $file->getExtension() );

				if ( in_array( $extension, $this->scan_extensions, true ) ) {
					$file_strings = $this->scan_file( $file->getPathname() );
					$strings      = array_merge( $strings, $file_strings );
				}
			}
		}

		return $strings;
	}

	/**
	 * Scan a single file for translatable strings.
	 *
	 * @since 1.0.0
	 * @param string $file_path Full file path.
	 * @return array Array of found strings.
	 */
	public function scan_file( $file_path ) {

		$strings = array();

		if ( ! is_file( $file_path ) || ! is_readable( $file_path ) ) {
			return $strings;
		}

		// Limit file size to 1MB.
		$file_size = filesize( $file_path );
		if ( $file_size > 1048576 ) {
			return $strings;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $file_path );

		if ( empty( $content ) ) {
			return $strings;
		}

		$relative_path = str_replace(
			array( WP_CONTENT_DIR . '/', WP_CONTENT_DIR . '\\' ),
			'',
			$file_path
		);

		$found_hashes = array();

		foreach ( $this->i18n_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $match ) {
					$original = isset( $match[1] ) ? $match[1] : '';

					// Clean up the string.
					$original = $this->clean_string( $original );

					// Skip empty or too short strings.
					if ( empty( $original ) || mb_strlen( $original ) < 2 ) {
						continue;
					}

					// Skip strings that look like code or variables.
					if ( $this->is_code_string( $original ) ) {
						continue;
					}

					$hash = md5( $original );

					// Skip duplicates within this file.
					if ( in_array( $hash, $found_hashes, true ) ) {
						continue;
					}

					$found_hashes[] = $hash;

					$strings[] = array(
						'original_string' => $original,
						'string_hash'     => $hash,
						'source_file'     => $relative_path,
					);
				}
			}
		}

		return $strings;
	}

	/**
	 * Clean up a found string.
	 *
	 * @since 1.0.0
	 * @param string $string Raw string.
	 * @return string Cleaned string.
	 */
	private function clean_string( $string ) {

		// Unescape PHP escape sequences.
		$string = stripcslashes( $string );

		// Trim whitespace.
		$string = trim( $string );

		return $string;
	}

	/**
	 * Check if a string looks like code rather than translatable text.
	 *
	 * @since 1.0.0
	 * @param string $string String to check.
	 * @return bool True if the string appears to be code.
	 */
	private function is_code_string( $string ) {

		// Check for common code patterns.
		$code_patterns = array(
			'/^\$/',                     // Starts with $.
			'/^[a-z_]+\(/',              // Function call.
			'/^https?:\/\//',            // URL.
			'/^[a-z0-9_\-]+\.[a-z]+$/i', // Filename.
			'/^[#\.]/',                  // CSS selector.
			'/^\{/',                     // JSON/object.
			'/^</',                      // HTML tag.
			'/^[0-9]+$/',               // Only numbers.
			'/^[a-z0-9_\-]+$/i',        // Single word no spaces (likely a key/id).
			'/^\s*$/',                   // Whitespace only.
			'/[{}()\[\]$]/',            // Contains code characters.
			'/^%[sdf]/',                // Printf format.
		);

		foreach ( $code_patterns as $pattern ) {
			if ( preg_match( $pattern, $string ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Perform a full scan of theme and plugins.
	 *
	 * @since 1.0.0
	 * @param string $scan_type What to scan: 'all', 'theme', 'plugins', 'frontend'.
	 * @return array Results summary.
	 */
	public function run_scan( $scan_type = 'all' ) {

		$total_found    = 0;
		$total_new      = 0;
		$total_existing = 0;

		$all_strings = array();

		if ( 'frontend' === $scan_type ) {
			$frontend_strings = $this->scan_frontend_pages();
			$all_strings      = array_merge( $all_strings, $frontend_strings );
		}

		if ( 'all' === $scan_type || 'theme' === $scan_type ) {
			$theme_strings = $this->scan_theme();
			$all_strings   = array_merge( $all_strings, $theme_strings );
		}

		if ( 'all' === $scan_type || 'plugins' === $scan_type ) {
			$plugin_strings = $this->scan_plugins();
			$all_strings    = array_merge( $all_strings, $plugin_strings );
		}

		$total_found = count( $all_strings );

		// Insert new strings into database while preserving existing strings and their translations.
		foreach ( $all_strings as $string_data ) {
			$hash = ! empty( $string_data['string_hash'] ) ? $string_data['string_hash'] : md5( $string_data['original_string'] );
			$existing = VW_Translate_DB::get_string_by_hash( $hash );

			if ( $existing ) {
				++$total_existing;
			} else {
				$result = VW_Translate_DB::insert_string( $string_data );
				if ( $result ) {
					++$total_new;
				}
			}
		}

		// Clear translation cache.
		delete_transient( 'vw_translate_translations_cache' );

		return array(
			'total_found'    => $total_found,
			'total_new'      => $total_new,
			'total_existing' => $total_existing,
		);
	}

	// -------------------------------------------------------------------------
	// Frontend Page Scanning
	// -------------------------------------------------------------------------

	/**
	 * Scan frontend pages for visible strings.
	 *
	 * Fetches all published pages and posts via HTTP requests
	 * and extracts visible text strings from the rendered HTML.
	 *
	 * @since 1.0.0
	 * @return array Array of found strings.
	 */
	public function scan_frontend_pages() {

		$urls         = $this->get_frontend_urls();
		$all_strings  = array();
		$found_hashes = array();

		foreach ( $urls as $url_data ) {
			$html = $this->fetch_page_content( $url_data['url'] );

			if ( empty( $html ) ) {
				continue;
			}

			$page_strings = $this->extract_visible_strings( $html );

			foreach ( $page_strings as $text ) {
				$hash = md5( $text );

				if ( in_array( $hash, $found_hashes, true ) ) {
					continue;
				}

				$found_hashes[] = $hash;

				$all_strings[] = array(
					'original_string' => $text,
					'string_hash'     => $hash,
					'source_type'     => 'frontend',
					'source_name'     => $url_data['title'],
					'source_file'     => $url_data['url'],
				);
			}
		}

		return $all_strings;
	}

	/**
	 * Get all frontend URLs to scan.
	 *
	 * Collects URLs for the homepage, all published pages,
	 * recent posts, and WooCommerce pages if active.
	 *
	 * @since 1.0.0
	 * @return array Array of URL data with 'url' and 'title' keys.
	 */
	private function get_frontend_urls() {

		$urls = array();

		// Home page.
		$urls[] = array(
			'url'   => home_url( '/' ),
			'title' => get_bloginfo( 'name' ) . ' - Home',
		);

		// All published pages.
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		foreach ( $pages as $page ) {
			$permalink = get_permalink( $page->ID );
			if ( $permalink ) {
				$urls[] = array(
					'url'   => $permalink,
					'title' => $page->post_title,
				);
			}
		}

		// Recent published posts (limit 50 for performance).
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $posts as $post_item ) {
			$permalink = get_permalink( $post_item->ID );
			if ( $permalink ) {
				$urls[] = array(
					'url'   => $permalink,
					'title' => $post_item->post_title,
				);
			}
		}

		// WooCommerce pages if active.
		if ( function_exists( 'wc_get_page_id' ) ) {
			$woo_pages = array( 'shop', 'cart', 'checkout', 'myaccount' );
			foreach ( $woo_pages as $woo_page ) {
				$page_id = wc_get_page_id( $woo_page );
				if ( $page_id > 0 ) {
					$permalink = get_permalink( $page_id );
					if ( $permalink ) {
						$urls[] = array(
							'url'   => $permalink,
							'title' => 'WooCommerce - ' . ucfirst( $woo_page ),
						);
					}
				}
			}
		}

		// Remove duplicate URLs.
		$seen        = array();
		$unique_urls = array();
		foreach ( $urls as $url_data ) {
			if ( ! in_array( $url_data['url'], $seen, true ) ) {
				$seen[]        = $url_data['url'];
				$unique_urls[] = $url_data;
			}
		}

		return $unique_urls;
	}

	/**
	 * Fetch the HTML content of a page.
	 *
	 * @since 1.0.0
	 * @param string $url Page URL.
	 * @return string HTML content or empty string on failure.
	 */
	private function fetch_page_content( $url ) {

		// Add bypass parameter so our translation doesn't interfere.
		$url = add_query_arg( 'vw_translate_bypass', '1', $url );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 30,
				'sslverify'  => false,
				'user-agent' => 'VW Translate Scanner/' . VW_TRANSLATE_VERSION,
				'headers'    => array(
					'Accept' => 'text/html',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return '';
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Extract visible text strings from HTML content.
	 *
	 * Parses the HTML and extracts text that visitors can actually see,
	 * excluding scripts, styles, and code content.
	 *
	 * @since 1.0.0
	 * @param string $html Full HTML page content.
	 * @return array Array of unique visible strings.
	 */
	private function extract_visible_strings( $html ) {

		$strings = array();

		// Remove non-visible content blocks.
		$html = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $html );
		$html = preg_replace( '/<style\b[^>]*>.*?<\/style>/is', '', $html );
		$html = preg_replace( '/<noscript\b[^>]*>.*?<\/noscript>/is', '', $html );
		$html = preg_replace( '/<!--.*?-->/s', '', $html );
		$html = preg_replace( '/<svg\b[^>]*>.*?<\/svg>/is', '', $html );
		$html = preg_replace( '/<code\b[^>]*>.*?<\/code>/is', '', $html );
		$html = preg_replace( '/<pre\b[^>]*>.*?<\/pre>/is', '', $html );

		// 1. Extract from <title> tag.
		if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $title_match ) ) {
			$title_text = trim( html_entity_decode( $title_match[1], ENT_QUOTES, 'UTF-8' ) );
			if ( $this->is_valid_visible_string( $title_text ) ) {
				$strings[] = $title_text;
			}
		}

		// 2. Extract from meta description and OG tags.
		preg_match_all(
			'/<meta[^>]+(?:name|property)=["\'](?:description|og:title|og:description|og:site_name)["\'][^>]+content=["\']([^"\']+)["\']/',
			$html,
			$meta_matches
		);
		if ( ! empty( $meta_matches[1] ) ) {
			foreach ( $meta_matches[1] as $meta_text ) {
				$meta_text = trim( html_entity_decode( $meta_text, ENT_QUOTES, 'UTF-8' ) );
				if ( $this->is_valid_visible_string( $meta_text ) ) {
					$strings[] = $meta_text;
				}
			}
		}

		// 3. Extract from visible HTML attributes.
		preg_match_all(
			'/(?:title|alt|placeholder|aria-label|data-tooltip)=["\']([^"\']{2,})["\']/i',
			$html,
			$attr_matches
		);
		if ( ! empty( $attr_matches[1] ) ) {
			foreach ( $attr_matches[1] as $attr_text ) {
				$attr_text = trim( html_entity_decode( $attr_text, ENT_QUOTES, 'UTF-8' ) );
				if ( $this->is_valid_visible_string( $attr_text ) ) {
					$strings[] = $attr_text;
				}
			}
		}

		// 4. Extract from input/button values.
		preg_match_all(
			'/<(?:input|button)[^>]+value=["\']([^"\']{2,})["\']/i',
			$html,
			$button_matches
		);
		if ( ! empty( $button_matches[1] ) ) {
			foreach ( $button_matches[1] as $btn_text ) {
				$btn_text = trim( html_entity_decode( $btn_text, ENT_QUOTES, 'UTF-8' ) );
				if ( $this->is_valid_visible_string( $btn_text ) ) {
					$strings[] = $btn_text;
				}
			}
		}

		// 5. Extract text content from body.
		$body = $html;
		if ( preg_match( '/<body[^>]*>(.*)<\/body>/is', $html, $body_match ) ) {
			$body = $body_match[1];
		}

		// Remove form elements from body text extraction.
		$body = preg_replace( '/<input[^>]*>/is', '', $body );
		$body = preg_replace( '/<textarea[^>]*>.*?<\/textarea>/is', '', $body );

		// Remove WordPress admin bar if present.
		$body = preg_replace( '/<div[^>]+id=["\']wpadminbar["\'][^>]*>.*?<!--\s*\/?wpadminbar\s*-->/is', '', $body );

		// Extract text between HTML tags.
		preg_match_all( '/>([^<]+)</', $body, $text_matches );
		if ( ! empty( $text_matches[1] ) ) {
			foreach ( $text_matches[1] as $text ) {
				$text = trim( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );

				if ( ! $this->is_valid_visible_string( $text ) ) {
					continue;
				}

				// Split very long text into sentences.
				if ( mb_strlen( $text ) > 300 ) {
					$sentences = preg_split( '/(?<=[.!?\x{0964}])\s+/u', $text );
					foreach ( $sentences as $sentence ) {
						$sentence = trim( $sentence );
						if ( $this->is_valid_visible_string( $sentence ) ) {
							$strings[] = $sentence;
						}
					}
				} else {
					$strings[] = $text;
				}
			}
		}

		// 6. Extract select option text.
		preg_match_all( '/<option[^>]*>([^<]{2,})<\/option>/i', $body, $option_matches );
		if ( ! empty( $option_matches[1] ) ) {
			foreach ( $option_matches[1] as $opt_text ) {
				$opt_text = trim( html_entity_decode( $opt_text, ENT_QUOTES, 'UTF-8' ) );
				if ( $this->is_valid_visible_string( $opt_text ) ) {
					$strings[] = $opt_text;
				}
			}
		}

		return array_unique( $strings );
	}

	/**
	 * Check if a string is valid visible text for translation.
	 *
	 * This is a more lenient check than is_code_string() because
	 * frontend-visible strings can include single words like
	 * "Home", "Cart", "Search", etc.
	 *
	 * @since 1.0.0
	 * @param string $string String to check.
	 * @return bool True if the string is valid visible text.
	 */
	private function is_valid_visible_string( $string ) {

		if ( empty( $string ) ) {
			return false;
		}

		$string = trim( $string );

		// Skip too short strings.
		if ( mb_strlen( $string ) < 2 ) {
			return false;
		}

		// Must contain at least one letter (any script/language).
		if ( ! preg_match( '/\pL/u', $string ) ) {
			return false;
		}

		// Skip URLs.
		if ( preg_match( '/^https?:\/\//i', $string ) ) {
			return false;
		}

		// Skip email addresses.
		if ( preg_match( '/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $string ) ) {
			return false;
		}

		// Skip file paths.
		if ( preg_match( '/^\/[\w\-\/]+\.\w+$/', $string ) ) {
			return false;
		}

		// Skip JSON-like strings.
		if ( preg_match( '/^\s*[\{\[]/', $string ) ) {
			return false;
		}

		// Skip PHP/JS variables.
		if ( preg_match( '/^\$\w/', $string ) ) {
			return false;
		}

		// Skip CSS selectors.
		if ( preg_match( '/^[.#][\w\-]+$/', $string ) ) {
			return false;
		}

		// Skip very long strings (over 500 chars).
		if ( mb_strlen( $string ) > 500 ) {
			return false;
		}

		// Skip only special characters and numbers.
		if ( preg_match( '/^[^a-zA-Z\x{0080}-\x{FFFF}]+$/u', $string ) ) {
			return false;
		}

		// Skip HTML entity-only strings.
		if ( preg_match( '/^&[a-z]+;$/i', $string ) ) {
			return false;
		}

		// Skip CSS values like 100px, 12em.
		if ( preg_match( '/^\d+(\.\d+)?\s*(px|em|rem|%|pt|vh|vw)$/i', $string ) ) {
			return false;
		}

		return true;
	}
}
