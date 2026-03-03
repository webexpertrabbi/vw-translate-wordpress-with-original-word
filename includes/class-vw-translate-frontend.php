<?php
/**
 * Frontend functionality for VW Translate.
 *
 * @package VW_Translate
 * @since   1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VW_Translate_Frontend
 *
 * Handles frontend string replacement using output buffering
 * and provides the language switcher.
 *
 * @since 1.0.0
 */
class VW_Translate_Frontend {

	/**
	 * Current language code.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private static $current_language = '';

	/**
	 * Cached translations.
	 *
	 * @since 1.0.0
	 * @var array|null
	 */
	private static $translations_cache = null;

	/**
	 * Initialize frontend hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Don't run in admin unless specifically enabled.
		if ( is_admin() && get_option( 'vw_translate_exclude_admin', 1 ) ) {
			return;
		}

		// Detect and set current language.
		add_action( 'init', array( $this, 'detect_language' ), 1 );

		// Redirect to ?lang=default when no lang param is present.
		add_action( 'template_redirect', array( $this, 'maybe_redirect_to_default_language' ), 0 );

		// Start output buffering for string replacement.
		add_action( 'template_redirect', array( $this, 'start_output_buffer' ), 1 );

		// Add language switcher to frontend.
		add_action( 'wp_footer', array( $this, 'render_floating_switcher' ) );

		// Enqueue frontend styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Set HTML lang attribute.
		add_filter( 'language_attributes', array( $this, 'filter_language_attributes' ) );
	}

	/**
	 * Detect the current language from URL parameter or cookie.
	 *
	 * @since 1.0.0
	 */
	public function detect_language() {

		$default_lang = self::get_default_language_code();

		// 1. Check URL parameter.
		if ( get_option( 'vw_translate_enable_url_param', 1 ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['lang'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$lang = sanitize_text_field( wp_unslash( $_GET['lang'] ) );

				// If the visitor explicitly selects the DEFAULT language, clear any
				// non-default override cookie so the site resets to its default state.
				if ( $lang === $default_lang ) {
					$this->clear_language_cookie();
					self::$current_language = $default_lang;
					return;
				}

				if ( self::is_valid_language( $lang ) ) {
					self::$current_language = $lang;
					$this->set_language_cookie( $lang );
					return;
				}
			}
		}

		// 2. Check cookie. Only respect a cookie that stores a NON-DEFAULT language.
		// Cookies for the default language are never stored, so this always
		// represents an explicit visitor override.
		if ( get_option( 'vw_translate_enable_cookie', 1 ) ) {
			if ( isset( $_COOKIE['vw_translate_lang'] ) ) {
				$lang = sanitize_text_field( wp_unslash( $_COOKIE['vw_translate_lang'] ) );
				// Ignore cookie if it equals the default language (cleanup stale cookies).
				if ( $lang === $default_lang ) {
					$this->clear_language_cookie();
				} elseif ( self::is_valid_language( $lang ) ) {
					self::$current_language = $lang;
					return;
				}
			}
		}

		// 3. Default language.
		self::$current_language = $default_lang;
	}

	/**
	 * Set a language cookie.
	 *
	 * We NEVER store the default language in the cookie. The cookie exists only
	 * to remember a visitor's explicit choice of a NON-default language. This
	 * ensures that when the default language is changed in the admin, existing
	 * visitors automatically see the new default (unless they explicitly switched).
	 *
	 * @since 1.0.0
	 * @param string $lang Language code.
	 */
	private function set_language_cookie( $lang ) {

		if ( ! get_option( 'vw_translate_enable_cookie', 1 ) ) {
			return;
		}

		// If the language IS the default, clear any stale cookie instead of setting one.
		if ( $lang === self::get_default_language_code() ) {
			$this->clear_language_cookie();
			return;
		}

		$duration = (int) get_option( 'vw_translate_cookie_duration', 30 );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.setcookie_setcookie
		setcookie(
			'vw_translate_lang',
			$lang,
			array(
				'expires'  => time() + ( DAY_IN_SECONDS * $duration ),
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly'  => false,
				'samesite' => 'Lax',
			)
		);
	}

	/**
	 * Clear / expire the language cookie.
	 *
	 * @since 1.1.0
	 */
	private function clear_language_cookie() {

		if ( ! isset( $_COOKIE['vw_translate_lang'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.setcookie_setcookie
		setcookie(
			'vw_translate_lang',
			'',
			array(
				'expires'  => time() - HOUR_IN_SECONDS,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly'  => false,
				'samesite' => 'Lax',
			)
		);

		// Immediately clear from the current request too.
		unset( $_COOKIE['vw_translate_lang'] );
	}

	/**
	 * Check if a language code is valid (exists and is active).
	 *
	 * @since 1.0.0
	 * @param string $lang Language code.
	 * @return bool True if valid.
	 */
	private static function is_valid_language( $lang ) {

		$language = VW_Translate_DB::get_language( $lang );
		return $language && $language->is_active;
	}

	/**
	 * Get the current language code.
	 *
	 * @since 1.0.0
	 * @return string Language code.
	 */
	public static function get_current_language() {

		if ( empty( self::$current_language ) ) {
			self::$current_language = self::get_default_language_code();
		}

		return self::$current_language;
	}

	/**
	 * Get the default language code.
	 *
	 * @since 1.0.0
	 * @return string Default language code.
	 */
	public static function get_default_language_code() {

		$default = VW_Translate_DB::get_default_language();
		return $default ? $default->language_code : 'en';
	}

	/**
	 * Redirect to the URL with the default ?lang= param when none is present.
	 *
	 * This ensures every page load has an explicit lang parameter so the
	 * language is always unambiguous. When a visitor hits the site without
	 * any ?lang= in the URL, they are transparently redirected to the same
	 * URL with ?lang=<default_code> appended (302, no SEO penalty since
	 * the URL is otherwise identical).
	 *
	 * @since 1.1.0
	 */
	public function maybe_redirect_to_default_language() {

		// Skip if URL parameter feature is disabled.
		if ( ! get_option( 'vw_translate_enable_url_param', 1 ) ) {
			return;
		}

		// Skip if ?lang= is already present.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['lang'] ) ) {
			return;
		}

		// Skip for AJAX, REST, cron, and feed requests.
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
			return;
		}

		// Skip during frontend scanning.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['vw_translate_bypass'] ) ) {
			return;
		}

		$default_lang = self::get_default_language_code();

		// Determine which language to redirect to:
		// If the visitor has a valid non-default cookie, preserve their choice;
		// otherwise redirect to the default language.
		$redirect_lang = $default_lang;

		if ( get_option( 'vw_translate_enable_cookie', 1 ) && isset( $_COOKIE['vw_translate_lang'] ) ) {
			$cookie_lang = sanitize_text_field( wp_unslash( $_COOKIE['vw_translate_lang'] ) );
			if ( $cookie_lang !== $default_lang && self::is_valid_language( $cookie_lang ) ) {
				$redirect_lang = $cookie_lang;
			}
		}

		$current_url  = home_url( add_query_arg( null, null ) );
		$redirect_url = add_query_arg( 'lang', $redirect_lang, $current_url );

		wp_redirect( $redirect_url, 302 );
		exit;
	}

	/**
	 * Start output buffering.
	 *
	 * @since 1.0.0
	 */
	public function start_output_buffer() {

		// Bypass during frontend scanning to get original strings.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['vw_translate_bypass'] ) ) {
			return;
		}

		// Always start the output buffer. The page content may be written in a
		// language that differs from the current/default language (e.g., Polish
		// content with English set as default). process_output_buffer() will
		// look up translations for the current language and replace matching
		// original strings. If no translations exist, the buffer is returned
		// unchanged — so there is no harm in always buffering.
		ob_start( array( $this, 'process_output_buffer' ) );
	}

	/**
	 * Process the output buffer and replace strings.
	 *
	 * @since 1.0.0
	 * @param string $buffer HTML output buffer.
	 * @return string Modified HTML output.
	 */
	public function process_output_buffer( $buffer ) {

		if ( empty( $buffer ) ) {
			return $buffer;
		}

		$current_lang = self::get_current_language();
		$translations = self::get_translations( $current_lang );
		$total_trans  = is_array( $translations ) ? count( $translations ) : 0;
		$replaced     = 0;

		if ( ! empty( $translations ) ) {
			// Replace strings - translations are already sorted by length (longest first)
			// to prevent partial replacements.
			foreach ( $translations as $translation ) {
				$original   = $translation->original_string;
				$translated = $translation->translated_string;

				if ( empty( $translated ) || $original === $translated ) {
					continue;
				}

				// Quick check: skip if original string is not present in this page.
				if ( false === strpos( $buffer, $original ) ) {
					continue;
				}

				// Replace in text content (not in HTML attributes, tags, or scripts).
				$buffer = $this->safe_replace( $buffer, $original, $translated );
				$replaced++;
			}
		}

		// Add debug comment (visible in View Source) so admins can verify the plugin is active.
		$cookie_val = isset( $_COOKIE['vw_translate_lang'] )
			? sanitize_text_field( wp_unslash( $_COOKIE['vw_translate_lang'] ) )
			: 'none';

		$debug = sprintf(
			'<!-- VW Translate: lang=%s, cookie=%s, translations=%d, applied=%d -->',
			esc_attr( $current_lang ),
			esc_attr( $cookie_val ),
			$total_trans,
			$replaced
		);

		// Insert before closing </body> tag if present, otherwise append.
		if ( false !== stripos( $buffer, '</body>' ) ) {
			$buffer = str_ireplace( '</body>', $debug . "\n</body>", $buffer );
		} else {
			$buffer .= $debug;
		}

		return $buffer;
	}

	/**
	 * Safely replace strings in HTML content.
	 *
	 * Avoids replacing strings inside HTML tags, attributes,
	 * script blocks, and style blocks.
	 *
	 * @since 1.0.0
	 * @param string $html     HTML content.
	 * @param string $search   String to search for.
	 * @param string $replace  Replacement string.
	 * @return string Modified HTML.
	 */
	private function safe_replace( $html, $search, $replace ) {

		// Split HTML into parts: tags and text.
		$parts  = preg_split( '/(<[^>]*>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		$result = '';

		$in_script = false;
		$in_style  = false;

		if ( false === $parts ) {
			// If regex fails, do simple replacement.
			return str_replace( $search, $replace, $html );
		}

		foreach ( $parts as $part ) {
			// Check for script/style tags.
			if ( preg_match( '/<script[\s>]/i', $part ) ) {
				$in_script = true;
				$result   .= $part;
				continue;
			}
			if ( preg_match( '/<\/script>/i', $part ) ) {
				$in_script = false;
				$result   .= $part;
				continue;
			}
			if ( preg_match( '/<style[\s>]/i', $part ) ) {
				$in_style = true;
				$result  .= $part;
				continue;
			}
			if ( preg_match( '/<\/style>/i', $part ) ) {
				$in_style = false;
				$result  .= $part;
				continue;
			}

			// Skip if inside script or style.
			if ( $in_script || $in_style ) {
				$result .= $part;
				continue;
			}

			// Skip HTML tags.
			if ( strpos( $part, '<' ) === 0 ) {
				// But do replace in title, alt, placeholder, and value attributes.
				if ( preg_match( '/(title|alt|placeholder|value|aria-label|content)=["\']/', $part ) ) {
					$part = str_replace( $search, $replace, $part );
				}
				$result .= $part;
				continue;
			}

			// Replace in text content.
			$result .= str_replace( $search, $replace, $part );
		}

		return $result;
	}

	/**
	 * Get all translations for a language (with caching).
	 *
	 * @since 1.0.0
	 * @param string $language_code Language code.
	 * @return array Array of translation objects.
	 */
	public static function get_translations( $language_code ) {

		if ( null !== self::$translations_cache ) {
			return self::$translations_cache;
		}

		$cache_enabled = get_option( 'vw_translate_cache_translations', 1 );
		$cache_key     = 'vw_translate_cache_' . $language_code;

		if ( $cache_enabled ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached && is_array( $cached ) ) {
				self::$translations_cache = $cached;
				return $cached;
			}
		}

		$translations = VW_Translate_DB::get_all_translations_for_language( $language_code );

		if ( $cache_enabled && ! empty( $translations ) ) {
			$cache_hours = (int) get_option( 'vw_translate_cache_duration', 12 );
			set_transient( $cache_key, $translations, $cache_hours * HOUR_IN_SECONDS );
		}

		self::$translations_cache = $translations;
		return $translations;
	}

	/**
	 * Clear ALL translation caches for every active language.
	 *
	 * @since 1.1.0
	 */
	public static function clear_all_translation_caches() {
		$languages = VW_Translate_DB::get_languages( true );
		if ( ! empty( $languages ) ) {
			foreach ( $languages as $lang ) {
				delete_transient( 'vw_translate_cache_' . $lang->language_code );
			}
		}
		// Also clear the legacy key.
		delete_transient( 'vw_translate_translations_cache' );
		self::$translations_cache = null;
	}

	/**
	 * Attempt to read the active theme's primary colour from multiple sources.
	 *
	 * Resolution order:
	 *  1. WordPress theme.json colour palette (slug: primary, accent, foreground)
	 *  2. Common Customizer theme-mod keys
	 *  3. Empty string — JS fallback takes over
	 *
	 * @since 1.3.0
	 * @return string Hex colour string or empty string.
	 */
	public static function get_site_primary_color() {

		// 1. theme.json (block / hybrid themes — WordPress 5.8+).
		if ( function_exists( 'wp_get_global_settings' ) ) {
			$settings = wp_get_global_settings();
			$palette  = ! empty( $settings['color']['palette']['theme'] ) ? $settings['color']['palette']['theme'] : array();
			$priority = array( 'primary', 'accent', 'foreground', 'vivid-cyan-blue' );
			foreach ( $priority as $slug ) {
				foreach ( $palette as $color ) {
					if ( strtolower( $color['slug'] ) === $slug && ! empty( $color['color'] ) ) {
						return sanitize_hex_color( $color['color'] );
					}
				}
			}
			// Fall back to first theme colour.
			if ( ! empty( $palette[0]['color'] ) ) {
				return sanitize_hex_color( $palette[0]['color'] );
			}
		}

		// 2. Common Customizer theme-mod keys.
		foreach ( array( 'primary_color', 'accent_color', 'button_color', 'link_color', 'theme_color' ) as $key ) {
			$val = get_theme_mod( $key, '' );
			if ( ! empty( $val ) ) {
				return sanitize_hex_color( $val );
			}
		}

		return '';
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_assets() {

		wp_enqueue_style(
			'vw-translate-frontend',
			VW_TRANSLATE_PLUGIN_URL . 'admin/css/vw-translate-frontend.css',
			array(),
			VW_TRANSLATE_VERSION
		);

		// Inline CSS: override --vwt-primary when PHP can detect the theme's primary colour.
		$primary = self::get_site_primary_color();
		if ( $primary ) {
			wp_add_inline_style(
				'vw-translate-frontend',
				':root { --vwt-primary: ' . esc_attr( $primary ) . '; }'
			);
		}

		// Inline JS fallback: detect primary colour from common CSS custom properties
		// (covers themes that expose variables but not customizer/theme.json values).
		wp_register_script( 'vw-translate-color-detect', false, array(), VW_TRANSLATE_VERSION, array( 'in_footer' => false ) );
		wp_enqueue_script( 'vw-translate-color-detect' );
		wp_add_inline_script(
			'vw-translate-color-detect',
			'(function(){
				var cs=getComputedStyle(document.documentElement);
				var p=(
					cs.getPropertyValue("--wp--preset--color--primary").trim()||
					cs.getPropertyValue("--color-primary").trim()||
					cs.getPropertyValue("--primary-color").trim()||
					cs.getPropertyValue("--primary").trim()||
					cs.getPropertyValue("--accent-color").trim()||
					cs.getPropertyValue("--color-accent").trim()||
					cs.getPropertyValue("--theme-color").trim()||
					cs.getPropertyValue("--wp--preset--color--vivid-cyan-blue").trim()
				);
				if(p){document.documentElement.style.setProperty("--vwt-primary",p);}
			})();'
		);
	}

	/**
	 * Render the floating language switcher.
	 *
	 * @since 1.0.0
	 */
	public function render_floating_switcher() {

		if ( ! get_option( 'vw_translate_enable_switcher', 1 ) ) {
			return;
		}

		$position = get_option( 'vw_translate_switcher_position', 'bottom-right' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::get_language_switcher( 'floating', $position );
	}

	/**
	 * Filter the language attributes for the HTML tag.
	 *
	 * @since 1.0.0
	 * @param string $output Language attributes string.
	 * @return string Modified language attributes.
	 */
	public function filter_language_attributes( $output ) {

		$current_lang = self::get_current_language();
		$default_lang = self::get_default_language_code();

		if ( $current_lang !== $default_lang ) {
			$output = preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $current_lang ) . '"', $output );
		}

		return $output;
	}

	/**
	 * Convert a flag emoji (e.g. 🇺🇸) to its ISO 3166-1 alpha-2 country code (e.g. "us").
	 *
	 * Unicode Regional Indicator Symbols A–Z start at code point 0x1F1E6.
	 * Windows does not render Unicode flag emoji as actual flag images, so we
	 * derive the ISO code and serve a real PNG from flagcdn.com instead.
	 *
	 * @since  1.2.0
	 * @param  string $emoji Flag emoji (e.g. 🇺🇸).
	 * @return string Lowercase 2-letter country code, or empty string on failure.
	 */
	private static function flag_emoji_to_iso( $emoji ) {
		if ( empty( $emoji ) ) {
			return '';
		}
		$code = '';
		$len  = mb_strlen( $emoji );
		for ( $i = 0; $i < $len; $i++ ) {
			$ord = mb_ord( mb_substr( $emoji, $i, 1 ) );
			if ( $ord >= 0x1F1E6 && $ord <= 0x1F1FF ) {
				$code .= chr( $ord - 0x1F1E6 + ord( 'A' ) );
			}
		}
		return strtolower( $code );
	}

	/**
	 * Return a flag <img> HTML string for the given flag emoji.
	 *
	 * Serves a real PNG image from flagcdn.com instead of relying on Unicode
	 * emoji (which Windows does not display as flag images). Falls back to the
	 * raw emoji wrapped in a <span> when the ISO code cannot be extracted.
	 *
	 * The returned string is already safely escaped and can be echoed directly.
	 *
	 * @since  1.2.0
	 * @param  string $flag_emoji Flag emoji stored in the DB (e.g. 🇺🇸).
	 * @param  string $alt_text   Accessible alt text (language name).
	 * @return string HTML <img> tag or fallback <span>.
	 */
	public static function get_flag_img_html( $flag_emoji, $alt_text = '' ) {
		$iso = self::flag_emoji_to_iso( $flag_emoji );
		if ( empty( $iso ) ) {
			return ! empty( $flag_emoji )
				? '<span class="vwt-flag-emoji" aria-hidden="true">' . esc_html( $flag_emoji ) . '</span>'
				: '<span class="vwt-flag-globe" aria-hidden="true">&#127760;</span>';
		}
		return '<img class="vwt-flag-img"'
			. ' src="https://flagcdn.com/w40/' . esc_attr( $iso ) . '.png"'
			. ' srcset="https://flagcdn.com/w80/' . esc_attr( $iso ) . '.png 2x"'
			. ' width="20" height="15"'
			. ' alt="' . esc_attr( $alt_text ) . '"'
			. ' loading="lazy">';
	}

	/**
	 * Generate language switcher HTML.
	 *
	 * @since 1.0.0
	 * @param string $style    Switcher style: 'dropdown', 'list', 'flags', 'floating'.
	 * @param string $position Position for floating style.
	 * @return string Switcher HTML.
	 */
	public static function get_language_switcher( $style = '', $position = '' ) {

		// If no explicit style is passed, use the admin-saved shortcode style.
		if ( empty( $style ) || 'default' === $style ) {
			$style = get_option( 'vw_translate_shortcode_style', 'dropdown' );
		}

		$languages    = VW_Translate_DB::get_languages( true );
		$current_lang = self::get_current_language();

		if ( empty( $languages ) || count( $languages ) < 2 ) {
			return '';
		}

		$current_url = home_url( add_query_arg( null, null ) );

		ob_start();

		switch ( $style ) {
			case 'pills':
				self::render_pills_switcher( $languages, $current_lang, $current_url );
				break;

			case 'minimal':
				self::render_minimal_switcher( $languages, $current_lang, $current_url );
				break;

			case 'cards':
				self::render_cards_switcher( $languages, $current_lang, $current_url );
				break;

			case 'elegant':
				self::render_elegant_switcher( $languages, $current_lang, $current_url );
				break;

			case 'list':
				self::render_list_switcher( $languages, $current_lang, $current_url );
				break;

			case 'flags':
				self::render_flags_switcher( $languages, $current_lang, $current_url );
				break;

			case 'flag-code':
				self::render_flag_code_switcher( $languages, $current_lang, $current_url );
				break;

			case 'flag-only':
				self::render_flag_only_switcher( $languages, $current_lang, $current_url );
				break;

			case 'floating':
				self::render_floating_switcher_html( $languages, $current_lang, $current_url, $position );
				break;

			case 'dropdown':
			default:
				self::render_dropdown_switcher( $languages, $current_lang, $current_url );
				break;
		}

		$html = ob_get_clean();

		// Apply size modifier class (skip for floating — it has its own fixed styling).
		if ( 'floating' !== $style && ! empty( $html ) ) {
			$size_opt = get_option( 'vw_translate_size_' . str_replace( '-', '_', $style ), 'md' );
			if ( 'md' !== $size_opt ) {
				// Prepend vwt-size-{sm|lg} to the first class attribute in the output.
				$html = preg_replace( '/class="/', 'class="vwt-size-' . esc_attr( $size_opt ) . ' ', $html, 1 );
			}
		}

		return $html;
	}

	/**
	 * Build a language switch URL.
	 *
	 * @since 1.0.0
	 * @param string $current_url Current page URL.
	 * @param string $lang_code   Target language code.
	 * @return string URL with language parameter.
	 */
	private static function build_lang_url( $current_url, $lang_code ) {
		return esc_url( add_query_arg( 'lang', $lang_code, $current_url ) );
	}

	/**
	 * Render dropdown style switcher.
	 *
	 * @since 1.0.0
	 * @param array  $languages    Available languages.
	 * @param string $current_lang Current language code.
	 * @param string $current_url  Current page URL.
	 */
	private static function render_dropdown_switcher( $languages, $current_lang, $current_url ) {
		?>
		<div class="vwt-ls vwt-style-dropdown">
			<select onchange="if(this.value) window.location.href=this.value;" aria-label="<?php esc_attr_e( 'Select Language', 'vw-translate' ); ?>">
				<?php foreach ( $languages as $lang ) : ?>
					<option value="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
						<?php selected( $current_lang, $lang->language_code ); ?>>
						<?php
						if ( ! empty( $lang->flag ) ) {
							echo esc_html( $lang->flag . ' ' );
						}
						echo esc_html( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name );
						?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render pills style switcher.
	 *
	 * @since 1.1.0
	 */
	private static function render_pills_switcher( $languages, $current_lang, $current_url ) {
		?>
		<div class="vwt-ls vwt-style-pills">
			<?php foreach ( $languages as $lang ) : ?>
				<a href="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
				   hreflang="<?php echo esc_attr( $lang->language_code ); ?>"
				   class="<?php echo $current_lang === $lang->language_code ? 'vwt-active' : ''; ?>">
					<?php if ( ! empty( $lang->flag ) ) : ?>
						<span class="vwt-flag"><?php echo self::get_flag_img_html( $lang->flag, $lang->language_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php endif; ?>
					<?php echo esc_html( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name ); ?>
					<span class="vwt-code"><?php echo esc_html( strtoupper( $lang->language_code ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render minimal style switcher.
	 *
	 * @since 1.1.0
	 */
	private static function render_minimal_switcher( $languages, $current_lang, $current_url ) {
		?>
		<div class="vwt-ls vwt-style-minimal">
			<?php foreach ( $languages as $i => $lang ) : ?>
				<?php if ( $i > 0 ) : ?>
					<span class="vwt-sep" aria-hidden="true"></span>
				<?php endif; ?>
				<a href="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
				   hreflang="<?php echo esc_attr( $lang->language_code ); ?>"
				   class="<?php echo $current_lang === $lang->language_code ? 'vwt-active' : ''; ?>">
					<?php if ( ! empty( $lang->flag ) ) : ?>
						<span class="vwt-flag"><?php echo self::get_flag_img_html( $lang->flag, $lang->language_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php endif; ?>
					<?php echo esc_html( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render cards style switcher.
	 *
	 * @since 1.1.0
	 */
	private static function render_cards_switcher( $languages, $current_lang, $current_url ) {
		?>
		<div class="vwt-ls vwt-style-cards">
			<?php foreach ( $languages as $lang ) : ?>
				<a href="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
				   hreflang="<?php echo esc_attr( $lang->language_code ); ?>"
				   class="<?php echo $current_lang === $lang->language_code ? 'vwt-active' : ''; ?>">
					<?php if ( ! empty( $lang->flag ) ) : ?>
						<span class="vwt-flag"><?php echo self::get_flag_img_html( $lang->flag, $lang->language_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php endif; ?>
					<span class="vwt-name"><?php echo esc_html( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name ); ?></span>
					<span class="vwt-code"><?php echo esc_html( strtoupper( $lang->language_code ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render elegant style switcher (custom gradient dropdown).
	 *
	 * @since 1.1.0
	 */
	private static function render_elegant_switcher( $languages, $current_lang, $current_url ) {

		$current_lang_obj = null;
		foreach ( $languages as $lang ) {
			if ( $lang->language_code === $current_lang ) {
				$current_lang_obj = $lang;
				break;
			}
		}

		$uid = 'vwt-elegant-' . wp_rand( 1000, 9999 );
		?>
		<div class="vwt-ls vwt-style-elegant" id="<?php echo esc_attr( $uid ); ?>">
			<button type="button" class="vwt-elegant-toggle" aria-expanded="false" aria-haspopup="listbox">
				<span class="vwt-flag">
					<?php
					if ( $current_lang_obj && ! empty( $current_lang_obj->flag ) ) {
						echo self::get_flag_img_html( $current_lang_obj->flag, $current_lang_obj->language_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						echo '<span class="vwt-flag-globe" aria-hidden="true">&#127760;</span>';
					}
					?>
				</span>
				<span class="vwt-name">
					<?php
					if ( $current_lang_obj ) {
						echo esc_html( ! empty( $current_lang_obj->native_name ) ? $current_lang_obj->native_name : $current_lang_obj->language_name );
					}
					?>
				</span>
				<span class="vwt-chevron" aria-hidden="true">&#9660;</span>
			</button>
			<div class="vwt-elegant-dropdown" role="listbox">
				<?php foreach ( $languages as $lang ) : ?>
					<a href="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
					   hreflang="<?php echo esc_attr( $lang->language_code ); ?>"
					   class="vwt-elegant-item <?php echo $current_lang === $lang->language_code ? 'vwt-active' : ''; ?>"
					   role="option" aria-selected="<?php echo $current_lang === $lang->language_code ? 'true' : 'false'; ?>">
						<?php if ( ! empty( $lang->flag ) ) : ?>
						<span class="vwt-flag"><?php echo self::get_flag_img_html( $lang->flag, $lang->language_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<span><?php echo esc_html( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name ); ?></span>
						<span class="vwt-check" aria-hidden="true">&#10003;</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<script>
		(function(){
			var w=document.getElementById('<?php echo esc_js( $uid ); ?>');
			if(!w)return;
			var b=w.querySelector('.vwt-elegant-toggle');
			var d=w.querySelector('.vwt-elegant-dropdown');
			b.addEventListener('click',function(e){
				e.stopPropagation();
				var o=d.classList.contains('vwt-open');
				d.classList.toggle('vwt-open',!o);
				b.setAttribute('aria-expanded',String(!o));
			});
			document.addEventListener('click',function(e){
				if(!w.contains(e.target)){
					d.classList.remove('vwt-open');
					b.setAttribute('aria-expanded','false');
				}
			});
		})();
		</script>
		<?php
	}

	/**
	 * Render list style switcher.
	 *
	 * @since 1.0.0
	 * @param array  $languages    Available languages.
	 * @param string $current_lang Current language code.
	 * @param string $current_url  Current page URL.
	 */
	private static function render_list_switcher( $languages, $current_lang, $current_url ) {
		?>
		<ul class="vw-translate-switcher vw-translate-list">
			<?php foreach ( $languages as $lang ) : ?>
				<li class="<?php echo $current_lang === $lang->language_code ? 'vw-translate-active' : ''; ?>">
					<a href="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
					   hreflang="<?php echo esc_attr( $lang->language_code ); ?>">
						<?php
						if ( ! empty( $lang->flag ) ) {
							echo '<span class="vw-translate-flag">' . self::get_flag_img_html( $lang->flag, $lang->language_name ) . '</span> '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						echo esc_html( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name );
						?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Render flags-only style switcher.
	 *
	 * @since 1.0.0
	 * @param array  $languages    Available languages.
	 * @param string $current_lang Current language code.
	 * @param string $current_url  Current page URL.
	 */
	private static function render_flags_switcher( $languages, $current_lang, $current_url ) {
		?>
		<div class="vw-translate-switcher vw-translate-flags">
			<?php foreach ( $languages as $lang ) : ?>
				<a href="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
				   hreflang="<?php echo esc_attr( $lang->language_code ); ?>"
				   title="<?php echo esc_attr( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name ); ?>"
				   class="vw-translate-flag-link <?php echo $current_lang === $lang->language_code ? 'vw-translate-active' : ''; ?>">
					<?php
					if ( ! empty( $lang->flag ) ) {
						echo self::get_flag_img_html( $lang->flag, $lang->language_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						echo esc_html( strtoupper( $lang->language_code ) );
					}
					?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render floating style switcher.
	 *
	 * @since 1.0.0
	 * @param array  $languages    Available languages.
	 * @param string $current_lang Current language code.
	 * @param string $current_url  Current page URL.
	 * @param string $position     Position class.
	 */
	private static function render_floating_switcher_html( $languages, $current_lang, $current_url, $position = 'bottom-right' ) {

		$current_lang_obj = null;
		foreach ( $languages as $lang ) {
			if ( $lang->language_code === $current_lang ) {
				$current_lang_obj = $lang;
				break;
			}
		}
		?>
		<div class="vw-translate-floating vw-translate-position-<?php echo esc_attr( $position ); ?>" id="vw-translate-floating">
			<button type="button" class="vw-translate-floating-toggle" aria-expanded="false" aria-label="<?php esc_attr_e( 'Switch Language', 'vw-translate' ); ?>">
				<span class="vw-translate-current-flag">
					<?php
					if ( $current_lang_obj && ! empty( $current_lang_obj->flag ) ) {
						echo self::get_flag_img_html( $current_lang_obj->flag, $current_lang_obj->language_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						echo '<span class="vwt-flag-globe" aria-hidden="true">&#127760;</span>';
					}
					?>
				</span>
				<span class="vw-translate-current-name">
					<?php
					if ( $current_lang_obj ) {
						echo esc_html( ! empty( $current_lang_obj->native_name ) ? $current_lang_obj->native_name : $current_lang_obj->language_name );
					}
					?>
				</span>
			</button>
			<div class="vw-translate-floating-dropdown" style="display:none;">
				<?php foreach ( $languages as $lang ) : ?>
					<a href="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
					   hreflang="<?php echo esc_attr( $lang->language_code ); ?>"
					   class="vw-translate-floating-item <?php echo $current_lang === $lang->language_code ? 'vw-translate-active' : ''; ?>">
						<?php
						if ( ! empty( $lang->flag ) ) {
							echo '<span class="vw-translate-flag">' . self::get_flag_img_html( $lang->flag, $lang->language_name ) . '</span> '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						echo esc_html( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name );
						?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<script>
		(function(){
			var toggle = document.querySelector('.vw-translate-floating-toggle');
			var dropdown = document.querySelector('.vw-translate-floating-dropdown');
			if(toggle && dropdown){
				toggle.addEventListener('click', function(e){
					e.stopPropagation();
					var isOpen = dropdown.style.display !== 'none';
					dropdown.style.display = isOpen ? 'none' : 'block';
					toggle.setAttribute('aria-expanded', !isOpen);
				});
				document.addEventListener('click', function(){
					dropdown.style.display = 'none';
					toggle.setAttribute('aria-expanded', 'false');
				});
			}
		})();
		</script>
		<?php
	}

	/**
	 * Render flag+code style switcher (flag image + uppercase language code).
	 *
	 * @since 1.2.0
	 * @param array  $languages    Available languages.
	 * @param string $current_lang Current language code.
	 * @param string $current_url  Current page URL.
	 */
	private static function render_flag_code_switcher( $languages, $current_lang, $current_url ) {
		?>
		<div class="vwt-ls vwt-style-flag-code">
			<?php foreach ( $languages as $lang ) : ?>
				<a href="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
				   hreflang="<?php echo esc_attr( $lang->language_code ); ?>"
				   title="<?php echo esc_attr( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name ); ?>"
				   class="<?php echo $current_lang === $lang->language_code ? 'vwt-active' : ''; ?>">
					<?php if ( ! empty( $lang->flag ) ) : ?>
						<span class="vwt-flag"><?php echo self::get_flag_img_html( $lang->flag, $lang->language_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php endif; ?>
					<span class="vwt-code"><?php echo esc_html( strtoupper( $lang->language_code ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render flag-only style switcher (flag images, no text).
	 *
	 * @since 1.2.0
	 * @param array  $languages    Available languages.
	 * @param string $current_lang Current language code.
	 * @param string $current_url  Current page URL.
	 */
	private static function render_flag_only_switcher( $languages, $current_lang, $current_url ) {
		?>
		<div class="vwt-ls vwt-style-flag-only">
			<?php foreach ( $languages as $lang ) : ?>
				<a href="<?php echo esc_url( self::build_lang_url( $current_url, $lang->language_code ) ); ?>"
				   hreflang="<?php echo esc_attr( $lang->language_code ); ?>"
				   title="<?php echo esc_attr( ! empty( $lang->native_name ) ? $lang->native_name : $lang->language_name ); ?>"
				   class="<?php echo $current_lang === $lang->language_code ? 'vwt-active' : ''; ?>">
					<?php
					if ( ! empty( $lang->flag ) ) {
						echo self::get_flag_img_html( $lang->flag, $lang->language_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						echo '<span class="vwt-flag-globe" aria-hidden="true">&#127760;</span>';
					}
					?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
