=== VW Translate WordPress with Original Word ===
Contributors: developer
Tags: translate, translation, multilingual, language, localization
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Translate your entire WordPress website by scanning all theme and plugin strings. Replace original words and sentences with your translations across the entire site.

== Description ==

**VW Translate WordPress with Original Word** is a powerful translation plugin that goes beyond traditional translation plugins. It scans your active theme and all active plugin files to discover every translatable string, including hardcoded text that other translation plugins miss.

= Key Features =

* **Deep String Scanner** – Scans all PHP files in your active theme and plugins to find translatable strings, including those in WordPress i18n functions (`__()`, `_e()`, `esc_html__()`, etc.) and visible HTML text.
* **Complete Translation Control** – Translate every discovered word and sentence directly from your WordPress admin panel.
* **Site-wide Replacement** – Translations automatically replace original strings everywhere they appear on your website using smart output buffering.
* **Smart Replacement** – Only replaces visible text content, leaving HTML tags, scripts, and styles untouched.
* **Multiple Languages** – Support for unlimited languages with easy management.
* **Language Switcher** – Built-in floating language switcher, widget, and shortcode.
* **Manual String Addition** – Manually add any string you want to translate.
* **Performance Optimized** – Built-in caching system for fast page loading.
* **Safe & Secure** – Follows WordPress coding standards with proper sanitization, escaping, nonces, and capability checks.

= How It Works =

1. **Install & Activate** – Install the plugin and activate it.
2. **Add Languages** – Go to VW Translate → Languages and add the languages you need.
3. **Scan Strings** – Go to VW Translate → Scan to discover all translatable strings from your theme and plugins.
4. **Translate** – Go to VW Translate → All Strings and click "Translate" on any string to add translations.
5. **Switch Languages** – Visitors can use the language switcher or add `?lang=CODE` to any URL.

= Language Switcher Options =

* **Floating Button** – A floating language switcher appears on your site (configurable position).
* **Widget** – Add the VW Language Switcher widget to any sidebar.
* **Shortcode** – Use `[vw_translate_switcher]` anywhere. Supports styles: dropdown, list, flags.

= Supported Languages =

Comes with 65+ preset languages including Bengali, Hindi, Arabic, French, German, Spanish, Chinese, Japanese, Korean, and many more. You can also add custom languages.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **VW Translate** in the admin menu to start configuring.
4. Add your target languages under **VW Translate → Languages**.
5. Run a string scan under **VW Translate → Scan Strings**.
6. Start translating strings under **VW Translate → All Strings**.

== Frequently Asked Questions ==

= How does this plugin differ from Polylang or WPML? =

Unlike Polylang or WPML which focus on content translation (posts, pages), VW Translate specifically targets theme and plugin strings that are hardcoded or use WordPress translation functions. It finds strings that those plugins cannot translate, such as default text in themes and plugins.

= Will this slow down my website? =

The plugin uses output buffering with caching to minimize performance impact. Translation lookups are cached, and the replacement process is optimized to handle strings efficiently.

= Can I add strings manually? =

Yes! If the scanner doesn't catch a specific string, you can manually add it from the All Strings page and provide translations for it.

= How do visitors switch languages? =

Visitors can use the floating language switcher (enabled by default), a widget you place in a sidebar, a shortcode, or by adding `?lang=CODE` to any URL.

= Is it safe for production sites? =

Yes. The plugin follows WordPress coding standards, uses proper sanitization and escaping, nonces for all forms, capability checks, and prepared SQL statements. It's built to be submitted to WordPress.org.

= Does it modify theme or plugin files? =

No. The plugin never modifies any files. It uses output buffering to replace strings in the rendered HTML before it reaches the visitor's browser.

= What happens when I deactivate or uninstall? =

On deactivation, temporary caches are cleared. On uninstall (deletion), all plugin data including database tables and options are completely removed.

== Screenshots ==

1. All Strings page with search and filter
2. Inline translation editor
3. Languages management
4. String scanner
5. Plugin settings
6. Frontend floating language switcher

== Changelog ==

= 1.0.0 =
* Initial release.
* String scanner for active theme and plugins.
* Admin interface for managing translations.
* Output buffer-based string replacement.
* Language switcher widget, shortcode, and floating button.
* 65+ preset languages.
* Translation caching for performance.
* Manual string addition.
* WordPress.org guidelines compliant.

== Upgrade Notice ==

= 1.0.0 =
Initial release of VW Translate WordPress with Original Word.
