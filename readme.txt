=== VW Translate WordPress with Original Word ===
Contributors: vendweave, webexpertrabbi
Donate link: https://vendweave.com/
Tags: translate, translation, multilingual, language switcher, localization
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Translate your entire WordPress website — including hardcoded theme & plugin strings — with a powerful scanner, real-time string replacement, and a beautiful language switcher.

== Description ==

**VW Translate WordPress with Original Word** is a next-generation translation plugin built for developers and site owners who need complete control over every piece of text on their WordPress website.

Unlike traditional translation plugins that only handle post/page content, VW Translate **digs deep into your active theme and all active plugins** to discover every string — whether it is wrapped in WordPress i18n functions (`__()`, `_e()`, `esc_html__()`, etc.) or written as plain hardcoded HTML text. Once discovered, you can translate each string directly from a clean, professional admin panel and let the plugin replace them site-wide using smart output buffering — **no file modifications, no code changes**.

---

= ✨ Key Features =

**🔍 Deep String Scanner**
Recursively scans all PHP files in your active theme and every active plugin. Picks up i18n function calls, inline HTML text, and more — strings that other translation plugins completely miss.

**✏️ Full Translation Control**
Translate every discovered string directly from the admin. Inline editing, per-language, per-string — all in one view with search, filter, and pagination.

**🌐 Site-wide Smart Replacement**
Translations automatically replace original strings everywhere on the frontend using PHP output buffering. Only visible text is replaced — HTML structure, scripts, and stylesheets are never touched.

**🚩 Real Country Flag Images**
Language switchers display real country flag images (via flagcdn.com) — not just text codes or broken emoji on Windows. Crisp, responsive flags with srcset 2× support.

**🎨 10 Switcher Styles**
Choose the perfect look for your site:
`Dropdown` · `Pills` · `Minimal` · `Cards` · `Elegant` · `Flag + Code` · `Flag Only` · `List` · `Flags` · `Floating`

**🔢 Unlimited Languages**
Add as many languages as you need from 65+ presets or define completely custom languages. Each language stores its own code, name, native name, and flag.

**📌 Language Switcher — 3 Ways**
* **Floating Button** — configurable corner position (Bottom Right / Left, Top Right / Left)
* **Widget** — drag the VW Language Switcher to any sidebar
* **Shortcode** — `[vw_translate_switcher]` with optional `style="pills"` override

**➕ Manual String Addition**
Manually add any word, phrase, or sentence to translate — perfect for dynamically generated text the scanner cannot reach.

**⚡ Performance Optimized**
Built-in translation caching with configurable cache duration. Reduces DB queries to near-zero on cached pages.

**🔒 Safe & Secure**
Proper nonce verification on all AJAX calls, `current_user_can()` capability checks, `sanitize_*` / `esc_*` on every input and output, prepared SQL statements. Built fully compliant with WordPress.org plugin guidelines.

**🗑️ Clean Uninstall**
On plugin deletion, all database tables and all stored options are completely removed — no residue left behind.

---

= 🚀 How It Works =

1. **Install & Activate** — Upload and activate the plugin from your WordPress admin.
2. **Add Languages** — Go to **VW Translate → Languages** and add the languages your site needs. Choose from 65+ presets with real flag images.
3. **Scan Strings** — Go to **VW Translate → Scan Strings** and run a scan. The plugin will discover every translatable string from your active theme and plugins.
4. **Translate** — Go to **VW Translate → All Strings**, search for any string, and click **Translate** to enter translations for each language.
5. **Enable Switcher** — Configure the floating switcher, add the widget to a sidebar, or use the shortcode. Your visitors can switch languages instantly.

---

= 🌍 Supported Languages (65+ Presets) =

Bengali · Hindi · Arabic · Urdu · Persian · Turkish · French · German · Spanish · Portuguese · Italian · Dutch · Polish · Russian · Ukrainian · Swedish · Norwegian · Danish · Finnish · Romanian · Czech · Slovak · Hungarian · Greek · Bulgarian · Serbian · Croatian · Slovenian · Albanian · Macedonian · Estonian · Latvian · Lithuanian · Hebrew · Thai · Vietnamese · Indonesian · Malay · Tagalog · Swahili · Afrikaans · Chinese (Simplified) · Chinese (Traditional) · Japanese · Korean · and many more.

You can also define **fully custom languages** with any code, name, native name, and flag.

---

= 🔤 Language Switcher Shortcode =

Use the shortcode anywhere in posts, pages, or widgets:

`[vw_translate_switcher]`

Override the style per block:

`[vw_translate_switcher style="pills"]`
`[vw_translate_switcher style="flag-only"]`
`[vw_translate_switcher style="minimal"]`

Available style values: `dropdown`, `pills`, `minimal`, `cards`, `elegant`, `flag-code`, `flag-only`, `list`, `flags`, `floating`

---

= 🔗 URL-based Switching =

Append `?lang=CODE` to any URL to switch language directly:

`https://example.com/?lang=fr` — switches to French
`https://example.com/?lang=bn` — switches to Bengali

---

== Installation ==

**Automatic (recommended):**

1. In your WordPress admin, go to **Plugins → Add New**.
2. Search for **VW Translate WordPress with Original Word**.
3. Click **Install Now**, then **Activate**.

**Manual:**

1. Download the plugin `.zip` file.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Upload the `.zip` and click **Install Now**, then **Activate**.

**After activation:**

1. Navigate to **VW Translate** in the left admin menu.
2. Add your target languages under **VW Translate → Languages**.
3. Run a string scan under **VW Translate → Scan Strings**.
4. Start translating under **VW Translate → All Strings**.

== Frequently Asked Questions ==

= How is this different from Polylang, WPML, or TranslatePress? =

Polylang and WPML are content translation plugins — they translate your posts, pages, and custom post types. TranslatePress translates rendered HTML visually. **VW Translate** specifically targets **theme and plugin strings** — the text baked into PHP files, theme templates, and plugin output — that those plugins either cannot reach or require complex setup to handle. VW Translate is the right tool when you need to translate an entire theme or plugin's interface text into multiple languages.

= Will this slow down my website? =

No. The plugin uses output buffering with an intelligent caching layer. Translation data is cached after the first lookup, so subsequent page loads read from cache rather than running database queries. Cache duration is configurable in **VW Translate → Settings**.

= Can I add strings that the scanner missed? =

Yes! Use the **+ Add String Manually** feature on the All Strings page. Type the exact text and then provide translations for each of your languages. This is useful for dynamically generated text that appears only at runtime.

= How do visitors switch between languages? =

Visitors have multiple options:
* The **floating language switcher** button (enabled by default, position configurable)
* A **sidebar widget** (VW Language Switcher) placed via Appearance → Widgets
* A **shortcode** `[vw_translate_switcher]` embedded anywhere
* Directly via URL parameter: `?lang=CODE`

The selected language is remembered in a cookie so returning visitors stay in their preferred language.

= Is the plugin safe to use on a production site? =

Yes. The plugin is built to WordPress.org coding standards: all user inputs are sanitized, all outputs are escaped, all AJAX requests use nonce verification, all capability checks use `current_user_can()`, and all database queries use `$wpdb->prepare()`. It does not modify any core, theme, or plugin files.

= Does it modify any theme or plugin files? =

Absolutely not. VW Translate only reads files during scanning. All translation replacements happen at runtime using PHP output buffering — the original files are never changed.

= What happens when I deactivate the plugin? =

On deactivation, temporary runtime caches are cleared. All your translation data and languages remain intact in the database.

= What happens when I delete (uninstall) the plugin? =

On full deletion, the `uninstall.php` file runs and removes all custom database tables and all WordPress options created by the plugin. Your site returns to its original state with zero residue.

= Can I use it with page builders like Elementor or Divi? =

Yes. Because VW Translate works at the output buffer level (replacing text in the final HTML before it is sent to the browser), it is compatible with any page builder, theme, or plugin that outputs standard HTML.

= Is there a limit to how many strings or languages I can have? =

No. The plugin supports unlimited languages and unlimited strings. Performance is maintained through the built-in caching system.

== Screenshots ==

1. **All Strings** — Full list of scanned strings with search, source filter, and translation status badges showing real country flags.
2. **Inline Translation Editor** — Click "Translate" to open a clean modal and add translations for all languages at once.
3. **Languages Management** — Add, edit, and reorder languages. Each language shows its real flag image, name, native name, and code.
4. **String Scanner** — One-click scan with live progress indicator and detailed summary of discovered strings.
5. **Plugin Settings** — Configure Translation Detection, Language Switcher, Shortcode Style, Performance, and more.
6. **Language Switcher Styles** — Preview of all 10 switcher styles with real flag images.
7. **Frontend Floating Switcher** — The floating language switcher as seen by visitors on the frontend.

== Changelog ==

= 1.0.0 — 2026-03-01 =
* 🎉 Initial release.
* Deep PHP file scanner for active theme and all active plugins.
* Supports WordPress i18n functions: `__()`, `_e()`, `esc_html__()`, `esc_attr__()`, `_x()`, `_n()`, and more.
* Full admin interface: All Strings page with search, filter by source, filter by translation status, pagination.
* Inline translation modal — edit translations for all languages in one place.
* Output buffer-based site-wide string replacement.
* Real country flag images via flagcdn.com (replaces broken emoji on Windows).
* 10 language switcher styles: Dropdown, Pills, Minimal, Cards, Elegant, Flag+Code, Flag Only, List, Flags, Floating.
* Floating language switcher with 4 configurable corner positions.
* WordPress sidebar widget (VW Language Switcher).
* Shortcode `[vw_translate_switcher]` with per-instance style override.
* URL parameter language switching (`?lang=CODE`).
* Cookie-based language memory (configurable duration).
* 65+ preset languages with real flags and native names.
* Manual string addition from admin.
* Translation caching with configurable cache duration.
* Custom searchable language preset dropdown with flag images.
* WordPress.org coding standards compliant (sanitization, escaping, nonces, capability checks, prepared SQL).
* Clean uninstall — removes all tables and options on plugin deletion.

== Upgrade Notice ==

= 1.0.0 =
Initial release of VW Translate WordPress with Original Word. No upgrade needed.
