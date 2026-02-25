<?php
/**
 * Database handler for VW Translate.
 *
 * @package VW_Translate
 * @since   1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VW_Translate_DB
 *
 * Handles all database operations including table creation,
 * CRUD operations for strings, translations, and languages.
 *
 * @since 1.0.0
 */
class VW_Translate_DB {

	/**
	 * Strings table name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private static $strings_table = 'vw_translate_strings';

	/**
	 * Translations table name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private static $translations_table = 'vw_translate_translations';

	/**
	 * Languages table name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private static $languages_table = 'vw_translate_languages';

	/**
	 * Get the full table name with prefix.
	 *
	 * @since 1.0.0
	 * @param string $table Short table name.
	 * @return string Full table name with prefix.
	 */
	public static function get_table_name( $table ) {
		global $wpdb;
		return $wpdb->prefix . $table;
	}

	/**
	 * Create all plugin database tables.
	 *
	 * @since 1.0.0
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$strings_table      = self::get_table_name( self::$strings_table );
		$translations_table = self::get_table_name( self::$translations_table );
		$languages_table    = self::get_table_name( self::$languages_table );

		$sql = "CREATE TABLE {$strings_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			original_string text NOT NULL,
			string_hash varchar(32) NOT NULL,
			source_type varchar(20) NOT NULL DEFAULT 'theme',
			source_name varchar(255) NOT NULL DEFAULT '',
			source_file varchar(500) NOT NULL DEFAULT '',
			string_context varchar(255) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			date_added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY string_hash (string_hash),
			KEY source_type (source_type),
			KEY status (status)
		) {$charset_collate};

		CREATE TABLE {$translations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			string_id bigint(20) unsigned NOT NULL,
			language_code varchar(10) NOT NULL,
			translated_string text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'published',
			date_added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY string_lang (string_id, language_code),
			KEY language_code (language_code),
			KEY status (status)
		) {$charset_collate};

		CREATE TABLE {$languages_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			language_code varchar(10) NOT NULL,
			language_name varchar(100) NOT NULL,
			native_name varchar(100) NOT NULL DEFAULT '',
			flag varchar(10) NOT NULL DEFAULT '',
			is_default tinyint(1) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			sort_order int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY language_code (language_code)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop all plugin database tables.
	 *
	 * @since 1.0.0
	 */
	public static function drop_tables() {
		global $wpdb;

		$strings_table      = self::get_table_name( self::$strings_table );
		$translations_table = self::get_table_name( self::$translations_table );
		$languages_table    = self::get_table_name( self::$languages_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$translations_table}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$strings_table}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$languages_table}" );
	}

	// -------------------------------------------------------------------------
	// String Methods
	// -------------------------------------------------------------------------

	/**
	 * Insert a new string into the database.
	 *
	 * @since 1.0.0
	 * @param array $data String data.
	 * @return int|false Insert ID or false on failure.
	 */
	public static function insert_string( $data ) {
		global $wpdb;

		$table = self::get_table_name( self::$strings_table );

		$defaults = array(
			'original_string' => '',
			'string_hash'     => '',
			'source_type'     => 'theme',
			'source_name'     => '',
			'source_file'     => '',
			'string_context'  => '',
			'status'          => 'active',
			'date_added'      => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['string_hash'] ) ) {
			$data['string_hash'] = md5( $data['original_string'] );
		}

		// Check if string already exists.
		$existing = self::get_string_by_hash( $data['string_hash'] );
		if ( $existing ) {
			return $existing->id;
		}

		$result = $wpdb->insert(
			$table,
			array(
				'original_string' => $data['original_string'],
				'string_hash'     => $data['string_hash'],
				'source_type'     => $data['source_type'],
				'source_name'     => $data['source_name'],
				'source_file'     => $data['source_file'],
				'string_context'  => $data['string_context'],
				'status'          => $data['status'],
				'date_added'      => $data['date_added'],
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get a string by its hash.
	 *
	 * @since 1.0.0
	 * @param string $hash MD5 hash of the string.
	 * @return object|null String object or null.
	 */
	public static function get_string_by_hash( $hash ) {
		global $wpdb;

		$table = self::get_table_name( self::$strings_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE string_hash = %s LIMIT 1",
				$hash
			)
		);
	}

	/**
	 * Get a string by ID.
	 *
	 * @since 1.0.0
	 * @param int $id String ID.
	 * @return object|null String object or null.
	 */
	public static function get_string( $id ) {
		global $wpdb;

		$table = self::get_table_name( self::$strings_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$id
			)
		);
	}

	/**
	 * Get all strings with optional filters.
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return array Array of string objects.
	 */
	public static function get_strings( $args = array() ) {
		global $wpdb;

		$table = self::get_table_name( self::$strings_table );

		$defaults = array(
			'status'      => 'active',
			'source_type' => '',
			'search'      => '',
			'orderby'     => 'id',
			'order'       => 'DESC',
			'per_page'    => 20,
			'offset'      => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where   = array( '1=1' );
		$prepare = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]   = 'status = %s';
			$prepare[] = $args['status'];
		}

		if ( ! empty( $args['source_type'] ) ) {
			$where[]   = 'source_type = %s';
			$prepare[] = $args['source_type'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]   = 'original_string LIKE %s';
			$prepare[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_clause = implode( ' AND ', $where );

		// Sanitize orderby.
		$allowed_orderby = array( 'id', 'original_string', 'source_type', 'source_name', 'date_added' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
		$order           = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		$per_page = absint( $args['per_page'] );
		$offset   = absint( $args['offset'] );

		$sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$prepare[] = $per_page;
		$prepare[] = $offset;

		if ( ! empty( $prepare ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results(
				$wpdb->prepare( $sql, $prepare ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get total count of strings.
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return int Total count.
	 */
	public static function get_strings_count( $args = array() ) {
		global $wpdb;

		$table = self::get_table_name( self::$strings_table );

		$defaults = array(
			'status'      => 'active',
			'source_type' => '',
			'search'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where   = array( '1=1' );
		$prepare = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]   = 'status = %s';
			$prepare[] = $args['status'];
		}

		if ( ! empty( $args['source_type'] ) ) {
			$where[]   = 'source_type = %s';
			$prepare[] = $args['source_type'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]   = 'original_string LIKE %s';
			$prepare[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_clause = implode( ' AND ', $where );

		$sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";

		if ( ! empty( $prepare ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare( $sql, $prepare ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Delete a string and its translations.
	 *
	 * @since 1.0.0
	 * @param int $id String ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_string( $id ) {
		global $wpdb;

		$strings_table      = self::get_table_name( self::$strings_table );
		$translations_table = self::get_table_name( self::$translations_table );

		// Delete translations first.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $translations_table, array( 'string_id' => $id ), array( '%d' ) );

		// Delete the string.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $strings_table, array( 'id' => $id ), array( '%d' ) );

		return false !== $result;
	}

	/**
	 * Delete all strings and their translations.
	 *
	 * Completely clears the strings and translations tables
	 * so only new scan results remain.
	 *
	 * @since 1.0.0
	 * @return bool True on success.
	 */
	public static function delete_all_strings() {
		global $wpdb;

		$strings_table      = self::get_table_name( self::$strings_table );
		$translations_table = self::get_table_name( self::$translations_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE {$translations_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE {$strings_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return true;
	}

	/**
	 * Clear all strings by source type.
	 *
	 * @since 1.0.0
	 * @param string $source_type Source type (theme or plugin).
	 * @param string $source_name Source name.
	 * @return int|false Number of rows deleted or false on failure.
	 */
	public static function clear_strings( $source_type = '', $source_name = '' ) {
		global $wpdb;

		$strings_table = self::get_table_name( self::$strings_table );

		$where   = array();
		$prepare = array();
		$formats = array();

		if ( ! empty( $source_type ) ) {
			$where['source_type'] = $source_type;
			$formats[]            = '%s';
		}

		if ( ! empty( $source_name ) ) {
			$where['source_name'] = $source_name;
			$formats[]            = '%s';
		}

		if ( empty( $where ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->query( "TRUNCATE TABLE {$strings_table}" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete( $strings_table, $where, $formats );
	}

	// -------------------------------------------------------------------------
	// Translation Methods
	// -------------------------------------------------------------------------

	/**
	 * Save a translation.
	 *
	 * @since 1.0.0
	 * @param int    $string_id        String ID.
	 * @param string $language_code    Language code.
	 * @param string $translated_string Translated text.
	 * @return int|false Insert/update ID or false on failure.
	 */
	public static function save_translation( $string_id, $language_code, $translated_string ) {
		global $wpdb;

		$table = self::get_table_name( self::$translations_table );

		// Check if translation exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE string_id = %d AND language_code = %s",
				$string_id,
				$language_code
			)
		);

		if ( $existing ) {
			// Update existing translation.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$table,
				array(
					'translated_string' => $translated_string,
					'date_modified'     => current_time( 'mysql' ),
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			return false !== $result ? $existing->id : false;
		}

		// Insert new translation.
		$result = $wpdb->insert(
			$table,
			array(
				'string_id'         => $string_id,
				'language_code'     => $language_code,
				'translated_string' => $translated_string,
				'status'            => 'published',
				'date_added'        => current_time( 'mysql' ),
				'date_modified'     => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get translations for a string.
	 *
	 * @since 1.0.0
	 * @param int $string_id String ID.
	 * @return array Array of translation objects.
	 */
	public static function get_translations_for_string( $string_id ) {
		global $wpdb;

		$table = self::get_table_name( self::$translations_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE string_id = %d ORDER BY language_code ASC",
				$string_id
			)
		);
	}

	/**
	 * Get all translations for a specific language.
	 *
	 * @since 1.0.0
	 * @param string $language_code Language code.
	 * @return array Array of objects with original_string and translated_string.
	 */
	public static function get_all_translations_for_language( $language_code ) {
		global $wpdb;

		$strings_table      = self::get_table_name( self::$strings_table );
		$translations_table = self::get_table_name( self::$translations_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.original_string, t.translated_string
				FROM {$translations_table} t
				INNER JOIN {$strings_table} s ON t.string_id = s.id
				WHERE t.language_code = %s
				AND t.status = 'published'
				AND s.status = 'active'
				AND t.translated_string != ''
				ORDER BY CHAR_LENGTH(s.original_string) DESC",
				$language_code
			)
		);
	}

	/**
	 * Delete a translation.
	 *
	 * @since 1.0.0
	 * @param int $translation_id Translation ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_translation( $translation_id ) {
		global $wpdb;

		$table = self::get_table_name( self::$translations_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $table, array( 'id' => $translation_id ), array( '%d' ) );

		return false !== $result;
	}

	// -------------------------------------------------------------------------
	// Language Methods
	// -------------------------------------------------------------------------

	/**
	 * Add a new language.
	 *
	 * @since 1.0.0
	 * @param array $data Language data.
	 * @return int|false Insert ID or false on failure.
	 */
	public static function add_language( $data ) {
		global $wpdb;

		$table = self::get_table_name( self::$languages_table );

		$defaults = array(
			'language_code' => '',
			'language_name' => '',
			'native_name'   => '',
			'flag'          => '',
			'is_default'    => 0,
			'is_active'     => 1,
			'sort_order'    => 0,
		);

		$data = wp_parse_args( $data, $defaults );

		// Check if language already exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE language_code = %s",
				$data['language_code']
			)
		);

		if ( $existing ) {
			return $existing->id;
		}

		// If this is set as default, unset others.
		if ( $data['is_default'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table, array( 'is_default' => 0 ), array( 'is_default' => 1 ), array( '%d' ), array( '%d' ) );
		}

		$result = $wpdb->insert(
			$table,
			array(
				'language_code' => $data['language_code'],
				'language_name' => $data['language_name'],
				'native_name'   => $data['native_name'],
				'flag'          => $data['flag'],
				'is_default'    => $data['is_default'],
				'is_active'     => $data['is_active'],
				'sort_order'    => $data['sort_order'],
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%d' )
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get all languages.
	 *
	 * @since 1.0.0
	 * @param bool $active_only Only return active languages.
	 * @return array Array of language objects.
	 */
	public static function get_languages( $active_only = false ) {
		global $wpdb;

		$table = self::get_table_name( self::$languages_table );

		$where = '';
		if ( $active_only ) {
			$where = 'WHERE is_active = 1';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			"SELECT * FROM {$table} {$where} ORDER BY sort_order ASC, language_name ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Get the default language.
	 *
	 * @since 1.0.0
	 * @return object|null Language object or null.
	 */
	public static function get_default_language() {
		global $wpdb;

		$table = self::get_table_name( self::$languages_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			"SELECT * FROM {$table} WHERE is_default = 1 LIMIT 1" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Get a language by code.
	 *
	 * @since 1.0.0
	 * @param string $code Language code.
	 * @return object|null Language object or null.
	 */
	public static function get_language( $code ) {
		global $wpdb;

		$table = self::get_table_name( self::$languages_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE language_code = %s LIMIT 1",
				$code
			)
		);
	}

	/**
	 * Update a language.
	 *
	 * @since 1.0.0
	 * @param int   $id   Language ID.
	 * @param array $data Language data to update.
	 * @return bool True on success, false on failure.
	 */
	public static function update_language( $id, $data ) {
		global $wpdb;

		$table = self::get_table_name( self::$languages_table );

		// If setting as default, unset others first.
		if ( isset( $data['is_default'] ) && $data['is_default'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table, array( 'is_default' => 0 ), array( 'is_default' => 1 ), array( '%d' ), array( '%d' ) );
		}

		$formats = array();
		foreach ( $data as $key => $value ) {
			if ( in_array( $key, array( 'is_default', 'is_active', 'sort_order' ), true ) ) {
				$formats[] = '%d';
			} else {
				$formats[] = '%s';
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete a language and its translations.
	 *
	 * @since 1.0.0
	 * @param int $id Language ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_language( $id ) {
		global $wpdb;

		$languages_table    = self::get_table_name( self::$languages_table );
		$translations_table = self::get_table_name( self::$translations_table );

		// Get language code first.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$language = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT language_code FROM {$languages_table} WHERE id = %d",
				$id
			)
		);

		if ( ! $language ) {
			return false;
		}

		// Delete all translations for this language.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$translations_table,
			array( 'language_code' => $language->language_code ),
			array( '%s' )
		);

		// Delete the language.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$languages_table,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get translation statistics.
	 *
	 * @since 1.0.0
	 * @return array Statistics data.
	 */
	public static function get_stats() {
		global $wpdb;

		$strings_table      = self::get_table_name( self::$strings_table );
		$translations_table = self::get_table_name( self::$translations_table );
		$languages_table    = self::get_table_name( self::$languages_table );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_strings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$strings_table} WHERE status = 'active'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_translations = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$translations_table} WHERE translated_string != ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_languages = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$languages_table} WHERE is_active = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$theme_strings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$strings_table} WHERE source_type = 'theme' AND status = 'active'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$plugin_strings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$strings_table} WHERE source_type = 'plugin' AND status = 'active'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'total_strings'      => $total_strings,
			'total_translations' => $total_translations,
			'total_languages'    => $total_languages,
			'theme_strings'      => $theme_strings,
			'plugin_strings'     => $plugin_strings,
		);
	}

	/**
	 * Get available languages list.
	 *
	 * @since 1.0.0
	 * @return array Predefined languages.
	 */
	public static function get_available_languages() {
		return array(
			// A
			'aa'    => array( 'name' => 'Afar', 'native' => 'Afaraf', 'flag' => '🇩🇯' ),
			'ab'    => array( 'name' => 'Abkhaz', 'native' => 'Аҧсуа', 'flag' => '🇬🇪' ),
			'af'    => array( 'name' => 'Afrikaans', 'native' => 'Afrikaans', 'flag' => '🇿🇦' ),
			'ak'    => array( 'name' => 'Akan', 'native' => 'Akan', 'flag' => '🇬🇭' ),
			'am'    => array( 'name' => 'Amharic', 'native' => 'አማርኛ', 'flag' => '🇪🇹' ),
			'an'    => array( 'name' => 'Aragonese', 'native' => 'Aragonés', 'flag' => '🇪🇸' ),
			'ar'    => array( 'name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦' ),
			'as'    => array( 'name' => 'Assamese', 'native' => 'অসমীয়া', 'flag' => '🇮🇳' ),
			'av'    => array( 'name' => 'Avaric', 'native' => 'Авар', 'flag' => '🇷🇺' ),
			'ay'    => array( 'name' => 'Aymara', 'native' => 'Aymar', 'flag' => '🇧🇴' ),
			'az'    => array( 'name' => 'Azerbaijani', 'native' => 'Azərbaycan', 'flag' => '🇦🇿' ),

			// B
			'ba'    => array( 'name' => 'Bashkir', 'native' => 'Башҡорт', 'flag' => '🇷🇺' ),
			'be'    => array( 'name' => 'Belarusian', 'native' => 'Беларуская', 'flag' => '🇧🇾' ),
			'bg'    => array( 'name' => 'Bulgarian', 'native' => 'Български', 'flag' => '🇧🇬' ),
			'bh'    => array( 'name' => 'Bihari', 'native' => 'भोजपुरी', 'flag' => '🇮🇳' ),
			'bi'    => array( 'name' => 'Bislama', 'native' => 'Bislama', 'flag' => '🇻🇺' ),
			'bm'    => array( 'name' => 'Bambara', 'native' => 'Bamanankan', 'flag' => '🇲🇱' ),
			'bn'    => array( 'name' => 'Bengali', 'native' => 'বাংলা', 'flag' => '🇧🇩' ),
			'bo'    => array( 'name' => 'Tibetan', 'native' => 'བོད་སྐད', 'flag' => '🇨🇳' ),
			'br'    => array( 'name' => 'Breton', 'native' => 'Brezhoneg', 'flag' => '🇫🇷' ),
			'bs'    => array( 'name' => 'Bosnian', 'native' => 'Bosanski', 'flag' => '🇧🇦' ),

			// C
			'ca'    => array( 'name' => 'Catalan', 'native' => 'Català', 'flag' => '🇪🇸' ),
			'ce'    => array( 'name' => 'Chechen', 'native' => 'Нохчийн', 'flag' => '🇷🇺' ),
			'ch'    => array( 'name' => 'Chamorro', 'native' => 'Chamoru', 'flag' => '🇬🇺' ),
			'co'    => array( 'name' => 'Corsican', 'native' => 'Corsu', 'flag' => '🇫🇷' ),
			'cr'    => array( 'name' => 'Cree', 'native' => 'ᓀᐦᐃᔭᐍᐏᐣ', 'flag' => '🇨🇦' ),
			'cs'    => array( 'name' => 'Czech', 'native' => 'Čeština', 'flag' => '🇨🇿' ),
			'cu'    => array( 'name' => 'Church Slavonic', 'native' => 'Словѣньскъ', 'flag' => '🇧🇬' ),
			'cv'    => array( 'name' => 'Chuvash', 'native' => 'Чӑваш', 'flag' => '🇷🇺' ),
			'cy'    => array( 'name' => 'Welsh', 'native' => 'Cymraeg', 'flag' => '🏴' ),

			// D
			'da'    => array( 'name' => 'Danish', 'native' => 'Dansk', 'flag' => '🇩🇰' ),
			'de'    => array( 'name' => 'German', 'native' => 'Deutsch', 'flag' => '🇩🇪' ),
			'de-at' => array( 'name' => 'German (Austria)', 'native' => 'Deutsch (Österreich)', 'flag' => '🇦🇹' ),
			'de-ch' => array( 'name' => 'German (Switzerland)', 'native' => 'Deutsch (Schweiz)', 'flag' => '🇨🇭' ),
			'dv'    => array( 'name' => 'Divehi', 'native' => 'ދިވެހި', 'flag' => '🇲🇻' ),
			'dz'    => array( 'name' => 'Dzongkha', 'native' => 'རྫོང་ཁ', 'flag' => '🇧🇹' ),

			// E
			'ee'    => array( 'name' => 'Ewe', 'native' => 'Eʋegbe', 'flag' => '🇬🇭' ),
			'el'    => array( 'name' => 'Greek', 'native' => 'Ελληνικά', 'flag' => '🇬🇷' ),
			'en'    => array( 'name' => 'English', 'native' => 'English', 'flag' => '🇺🇸' ),
			'en-au' => array( 'name' => 'English (Australia)', 'native' => 'English (Australia)', 'flag' => '🇦🇺' ),
			'en-ca' => array( 'name' => 'English (Canada)', 'native' => 'English (Canada)', 'flag' => '🇨🇦' ),
			'en-gb' => array( 'name' => 'English (UK)', 'native' => 'English (UK)', 'flag' => '🇬🇧' ),
			'en-in' => array( 'name' => 'English (India)', 'native' => 'English (India)', 'flag' => '🇮🇳' ),
			'en-nz' => array( 'name' => 'English (New Zealand)', 'native' => 'English (New Zealand)', 'flag' => '🇳🇿' ),
			'en-za' => array( 'name' => 'English (South Africa)', 'native' => 'English (South Africa)', 'flag' => '🇿🇦' ),
			'eo'    => array( 'name' => 'Esperanto', 'native' => 'Esperanto', 'flag' => '🌍' ),
			'es'    => array( 'name' => 'Spanish', 'native' => 'Español', 'flag' => '🇪🇸' ),
			'es-ar' => array( 'name' => 'Spanish (Argentina)', 'native' => 'Español (Argentina)', 'flag' => '🇦🇷' ),
			'es-cl' => array( 'name' => 'Spanish (Chile)', 'native' => 'Español (Chile)', 'flag' => '🇨🇱' ),
			'es-co' => array( 'name' => 'Spanish (Colombia)', 'native' => 'Español (Colombia)', 'flag' => '🇨🇴' ),
			'es-mx' => array( 'name' => 'Spanish (Mexico)', 'native' => 'Español (México)', 'flag' => '🇲🇽' ),
			'es-pe' => array( 'name' => 'Spanish (Peru)', 'native' => 'Español (Perú)', 'flag' => '🇵🇪' ),
			'es-ve' => array( 'name' => 'Spanish (Venezuela)', 'native' => 'Español (Venezuela)', 'flag' => '🇻🇪' ),
			'et'    => array( 'name' => 'Estonian', 'native' => 'Eesti', 'flag' => '🇪🇪' ),
			'eu'    => array( 'name' => 'Basque', 'native' => 'Euskara', 'flag' => '🇪🇸' ),

			// F
			'fa'    => array( 'name' => 'Persian', 'native' => 'فارسی', 'flag' => '🇮🇷' ),
			'fa-af' => array( 'name' => 'Dari', 'native' => 'دری', 'flag' => '🇦🇫' ),
			'ff'    => array( 'name' => 'Fula', 'native' => 'Fulfulde', 'flag' => '🇸🇳' ),
			'fi'    => array( 'name' => 'Finnish', 'native' => 'Suomi', 'flag' => '🇫🇮' ),
			'fj'    => array( 'name' => 'Fijian', 'native' => 'Vosa Vakaviti', 'flag' => '🇫🇯' ),
			'fo'    => array( 'name' => 'Faroese', 'native' => 'Føroyskt', 'flag' => '🇫🇴' ),
			'fr'    => array( 'name' => 'French', 'native' => 'Français', 'flag' => '🇫🇷' ),
			'fr-be' => array( 'name' => 'French (Belgium)', 'native' => 'Français (Belgique)', 'flag' => '🇧🇪' ),
			'fr-ca' => array( 'name' => 'French (Canada)', 'native' => 'Français (Canada)', 'flag' => '🇨🇦' ),
			'fr-ch' => array( 'name' => 'French (Switzerland)', 'native' => 'Français (Suisse)', 'flag' => '🇨🇭' ),
			'fy'    => array( 'name' => 'Western Frisian', 'native' => 'Frysk', 'flag' => '🇳🇱' ),

			// G
			'ga'    => array( 'name' => 'Irish', 'native' => 'Gaeilge', 'flag' => '🇮🇪' ),
			'gd'    => array( 'name' => 'Scottish Gaelic', 'native' => 'Gàidhlig', 'flag' => '🏴' ),
			'gl'    => array( 'name' => 'Galician', 'native' => 'Galego', 'flag' => '🇪🇸' ),
			'gn'    => array( 'name' => 'Guarani', 'native' => "Avañe'ẽ", 'flag' => '🇵🇾' ),
			'gu'    => array( 'name' => 'Gujarati', 'native' => 'ગુજરાતી', 'flag' => '🇮🇳' ),
			'gv'    => array( 'name' => 'Manx', 'native' => 'Gaelg', 'flag' => '🇮🇲' ),

			// H
			'ha'    => array( 'name' => 'Hausa', 'native' => 'هَوُسَ', 'flag' => '🇳🇬' ),
			'he'    => array( 'name' => 'Hebrew', 'native' => 'עברית', 'flag' => '🇮🇱' ),
			'hi'    => array( 'name' => 'Hindi', 'native' => 'हिन्दी', 'flag' => '🇮🇳' ),
			'ho'    => array( 'name' => 'Hiri Motu', 'native' => 'Hiri Motu', 'flag' => '🇵🇬' ),
			'hr'    => array( 'name' => 'Croatian', 'native' => 'Hrvatski', 'flag' => '🇭🇷' ),
			'ht'    => array( 'name' => 'Haitian Creole', 'native' => 'Kreyòl Ayisyen', 'flag' => '🇭🇹' ),
			'hu'    => array( 'name' => 'Hungarian', 'native' => 'Magyar', 'flag' => '🇭🇺' ),
			'hy'    => array( 'name' => 'Armenian', 'native' => 'Հայերեն', 'flag' => '🇦🇲' ),
			'hz'    => array( 'name' => 'Herero', 'native' => 'Otjiherero', 'flag' => '🇳🇦' ),

			// I
			'ia'    => array( 'name' => 'Interlingua', 'native' => 'Interlingua', 'flag' => '🌍' ),
			'id'    => array( 'name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩' ),
			'ig'    => array( 'name' => 'Igbo', 'native' => 'Igbo', 'flag' => '🇳🇬' ),
			'ii'    => array( 'name' => 'Nuosu', 'native' => 'ꆈꌠ꒿', 'flag' => '🇨🇳' ),
			'ik'    => array( 'name' => 'Inupiaq', 'native' => 'Iñupiaq', 'flag' => '🇺🇸' ),
			'io'    => array( 'name' => 'Ido', 'native' => 'Ido', 'flag' => '🌍' ),
			'is'    => array( 'name' => 'Icelandic', 'native' => 'Íslenska', 'flag' => '🇮🇸' ),
			'it'    => array( 'name' => 'Italian', 'native' => 'Italiano', 'flag' => '🇮🇹' ),
			'iu'    => array( 'name' => 'Inuktitut', 'native' => 'ᐃᓄᒃᑎᑐᑦ', 'flag' => '🇨🇦' ),

			// J
			'ja'    => array( 'name' => 'Japanese', 'native' => '日本語', 'flag' => '🇯🇵' ),
			'jv'    => array( 'name' => 'Javanese', 'native' => 'Basa Jawa', 'flag' => '🇮🇩' ),

			// K
			'ka'    => array( 'name' => 'Georgian', 'native' => 'ქართული', 'flag' => '🇬🇪' ),
			'kg'    => array( 'name' => 'Kongo', 'native' => 'Kikongo', 'flag' => '🇨🇩' ),
			'ki'    => array( 'name' => 'Kikuyu', 'native' => 'Gĩkũyũ', 'flag' => '🇰🇪' ),
			'kj'    => array( 'name' => 'Kwanyama', 'native' => 'Kuanyama', 'flag' => '🇳🇦' ),
			'kk'    => array( 'name' => 'Kazakh', 'native' => 'Қазақ', 'flag' => '🇰🇿' ),
			'kl'    => array( 'name' => 'Kalaallisut', 'native' => 'Kalaallisut', 'flag' => '🇬🇱' ),
			'km'    => array( 'name' => 'Khmer', 'native' => 'ភាសាខ្មែរ', 'flag' => '🇰🇭' ),
			'kn'    => array( 'name' => 'Kannada', 'native' => 'ಕನ್ನಡ', 'flag' => '🇮🇳' ),
			'ko'    => array( 'name' => 'Korean', 'native' => '한국어', 'flag' => '🇰🇷' ),
			'kr'    => array( 'name' => 'Kanuri', 'native' => 'Kanuri', 'flag' => '🇳🇬' ),
			'ks'    => array( 'name' => 'Kashmiri', 'native' => 'कश्मीरी', 'flag' => '🇮🇳' ),
			'ku'    => array( 'name' => 'Kurdish', 'native' => 'Kurdî', 'flag' => '🇮🇶' ),
			'kv'    => array( 'name' => 'Komi', 'native' => 'Коми', 'flag' => '🇷🇺' ),
			'kw'    => array( 'name' => 'Cornish', 'native' => 'Kernewek', 'flag' => '🇬🇧' ),
			'ky'    => array( 'name' => 'Kyrgyz', 'native' => 'Кыргызча', 'flag' => '🇰🇬' ),

			// L
			'la'    => array( 'name' => 'Latin', 'native' => 'Latina', 'flag' => '🇻🇦' ),
			'lb'    => array( 'name' => 'Luxembourgish', 'native' => 'Lëtzebuergesch', 'flag' => '🇱🇺' ),
			'lg'    => array( 'name' => 'Luganda', 'native' => 'Luganda', 'flag' => '🇺🇬' ),
			'li'    => array( 'name' => 'Limburgish', 'native' => 'Limburgs', 'flag' => '🇳🇱' ),
			'ln'    => array( 'name' => 'Lingala', 'native' => 'Lingála', 'flag' => '🇨🇩' ),
			'lo'    => array( 'name' => 'Lao', 'native' => 'ພາສາລາວ', 'flag' => '🇱🇦' ),
			'lt'    => array( 'name' => 'Lithuanian', 'native' => 'Lietuvių', 'flag' => '🇱🇹' ),
			'lu'    => array( 'name' => 'Luba-Katanga', 'native' => 'Tshiluba', 'flag' => '🇨🇩' ),
			'lv'    => array( 'name' => 'Latvian', 'native' => 'Latviešu', 'flag' => '🇱🇻' ),

			// M
			'mg'    => array( 'name' => 'Malagasy', 'native' => 'Malagasy', 'flag' => '🇲🇬' ),
			'mh'    => array( 'name' => 'Marshallese', 'native' => 'Kajin M̧ajeļ', 'flag' => '🇲🇭' ),
			'mi'    => array( 'name' => 'Maori', 'native' => 'Te Reo Māori', 'flag' => '🇳🇿' ),
			'mk'    => array( 'name' => 'Macedonian', 'native' => 'Македонски', 'flag' => '🇲🇰' ),
			'ml'    => array( 'name' => 'Malayalam', 'native' => 'മലയാളം', 'flag' => '🇮🇳' ),
			'mn'    => array( 'name' => 'Mongolian', 'native' => 'Монгол', 'flag' => '🇲🇳' ),
			'mr'    => array( 'name' => 'Marathi', 'native' => 'मराठी', 'flag' => '🇮🇳' ),
			'ms'    => array( 'name' => 'Malay', 'native' => 'Bahasa Melayu', 'flag' => '🇲🇾' ),
			'mt'    => array( 'name' => 'Maltese', 'native' => 'Malti', 'flag' => '🇲🇹' ),
			'my'    => array( 'name' => 'Burmese', 'native' => 'မြန်မာ', 'flag' => '🇲🇲' ),

			// N
			'na'    => array( 'name' => 'Nauru', 'native' => 'Dorerin Naoero', 'flag' => '🇳🇷' ),
			'nb'    => array( 'name' => 'Norwegian Bokmål', 'native' => 'Norsk Bokmål', 'flag' => '🇳🇴' ),
			'nd'    => array( 'name' => 'Northern Ndebele', 'native' => 'isiNdebele', 'flag' => '🇿🇼' ),
			'ne'    => array( 'name' => 'Nepali', 'native' => 'नेपाली', 'flag' => '🇳🇵' ),
			'ng'    => array( 'name' => 'Ndonga', 'native' => 'Owambo', 'flag' => '🇳🇦' ),
			'nl'    => array( 'name' => 'Dutch', 'native' => 'Nederlands', 'flag' => '🇳🇱' ),
			'nl-be' => array( 'name' => 'Dutch (Belgium)', 'native' => 'Nederlands (België)', 'flag' => '🇧🇪' ),
			'nn'    => array( 'name' => 'Norwegian Nynorsk', 'native' => 'Norsk Nynorsk', 'flag' => '🇳🇴' ),
			'no'    => array( 'name' => 'Norwegian', 'native' => 'Norsk', 'flag' => '🇳🇴' ),
			'nr'    => array( 'name' => 'Southern Ndebele', 'native' => 'isiNdebele', 'flag' => '🇿🇦' ),
			'nv'    => array( 'name' => 'Navajo', 'native' => 'Diné Bizaad', 'flag' => '🇺🇸' ),
			'ny'    => array( 'name' => 'Chichewa', 'native' => 'Chichewa', 'flag' => '🇲🇼' ),

			// O
			'oc'    => array( 'name' => 'Occitan', 'native' => 'Occitan', 'flag' => '🇫🇷' ),
			'oj'    => array( 'name' => 'Ojibwe', 'native' => 'ᐊᓂᔑᓈᐯᒧᐎᓐ', 'flag' => '🇨🇦' ),
			'om'    => array( 'name' => 'Oromo', 'native' => 'Afaan Oromoo', 'flag' => '🇪🇹' ),
			'or'    => array( 'name' => 'Odia', 'native' => 'ଓଡ଼ିଆ', 'flag' => '🇮🇳' ),
			'os'    => array( 'name' => 'Ossetian', 'native' => 'Ирон', 'flag' => '🇬🇪' ),

			// P
			'pa'    => array( 'name' => 'Punjabi', 'native' => 'ਪੰਜਾਬੀ', 'flag' => '🇮🇳' ),
			'pi'    => array( 'name' => 'Pali', 'native' => 'पाऴि', 'flag' => '🇮🇳' ),
			'pl'    => array( 'name' => 'Polish', 'native' => 'Polski', 'flag' => '🇵🇱' ),
			'ps'    => array( 'name' => 'Pashto', 'native' => 'پښتو', 'flag' => '🇦🇫' ),
			'pt'    => array( 'name' => 'Portuguese', 'native' => 'Português', 'flag' => '🇵🇹' ),
			'pt-br' => array( 'name' => 'Portuguese (Brazil)', 'native' => 'Português (Brasil)', 'flag' => '🇧🇷' ),

			// Q
			'qu'    => array( 'name' => 'Quechua', 'native' => 'Runa Simi', 'flag' => '🇵🇪' ),

			// R
			'rm'    => array( 'name' => 'Romansh', 'native' => 'Rumantsch', 'flag' => '🇨🇭' ),
			'rn'    => array( 'name' => 'Kirundi', 'native' => 'Ikirundi', 'flag' => '🇧🇮' ),
			'ro'    => array( 'name' => 'Romanian', 'native' => 'Română', 'flag' => '🇷🇴' ),
			'ru'    => array( 'name' => 'Russian', 'native' => 'Русский', 'flag' => '🇷🇺' ),
			'rw'    => array( 'name' => 'Kinyarwanda', 'native' => 'Ikinyarwanda', 'flag' => '🇷🇼' ),

			// S
			'sa'    => array( 'name' => 'Sanskrit', 'native' => 'संस्कृतम्', 'flag' => '🇮🇳' ),
			'sc'    => array( 'name' => 'Sardinian', 'native' => 'Sardu', 'flag' => '🇮🇹' ),
			'sd'    => array( 'name' => 'Sindhi', 'native' => 'سنڌي', 'flag' => '🇵🇰' ),
			'se'    => array( 'name' => 'Northern Sami', 'native' => 'Davvisámegiella', 'flag' => '🇳🇴' ),
			'sg'    => array( 'name' => 'Sango', 'native' => 'Yângâ tî Sängö', 'flag' => '🇨🇫' ),
			'si'    => array( 'name' => 'Sinhala', 'native' => 'සිංහල', 'flag' => '🇱🇰' ),
			'sk'    => array( 'name' => 'Slovak', 'native' => 'Slovenčina', 'flag' => '🇸🇰' ),
			'sl'    => array( 'name' => 'Slovenian', 'native' => 'Slovenščina', 'flag' => '🇸🇮' ),
			'sm'    => array( 'name' => 'Samoan', 'native' => 'Gagana Sāmoa', 'flag' => '🇼🇸' ),
			'sn'    => array( 'name' => 'Shona', 'native' => 'chiShona', 'flag' => '🇿🇼' ),
			'so'    => array( 'name' => 'Somali', 'native' => 'Soomaaliga', 'flag' => '🇸🇴' ),
			'sq'    => array( 'name' => 'Albanian', 'native' => 'Shqip', 'flag' => '🇦🇱' ),
			'sr'    => array( 'name' => 'Serbian', 'native' => 'Српски', 'flag' => '🇷🇸' ),
			'ss'    => array( 'name' => 'Swati', 'native' => 'SiSwati', 'flag' => '🇸🇿' ),
			'st'    => array( 'name' => 'Southern Sotho', 'native' => 'Sesotho', 'flag' => '🇱🇸' ),
			'su'    => array( 'name' => 'Sundanese', 'native' => 'Basa Sunda', 'flag' => '🇮🇩' ),
			'sv'    => array( 'name' => 'Swedish', 'native' => 'Svenska', 'flag' => '🇸🇪' ),
			'sw'    => array( 'name' => 'Swahili', 'native' => 'Kiswahili', 'flag' => '🇰🇪' ),

			// T
			'ta'    => array( 'name' => 'Tamil', 'native' => 'தமிழ்', 'flag' => '🇮🇳' ),
			'te'    => array( 'name' => 'Telugu', 'native' => 'తెలుగు', 'flag' => '🇮🇳' ),
			'tg'    => array( 'name' => 'Tajik', 'native' => 'Тоҷикӣ', 'flag' => '🇹🇯' ),
			'th'    => array( 'name' => 'Thai', 'native' => 'ไทย', 'flag' => '🇹🇭' ),
			'ti'    => array( 'name' => 'Tigrinya', 'native' => 'ትግርኛ', 'flag' => '🇪🇷' ),
			'tk'    => array( 'name' => 'Turkmen', 'native' => 'Türkmen', 'flag' => '🇹🇲' ),
			'tl'    => array( 'name' => 'Filipino', 'native' => 'Filipino', 'flag' => '🇵🇭' ),
			'tn'    => array( 'name' => 'Tswana', 'native' => 'Setswana', 'flag' => '🇧🇼' ),
			'to'    => array( 'name' => 'Tonga', 'native' => 'Lea Faka-Tonga', 'flag' => '🇹🇴' ),
			'tr'    => array( 'name' => 'Turkish', 'native' => 'Türkçe', 'flag' => '🇹🇷' ),
			'ts'    => array( 'name' => 'Tsonga', 'native' => 'Xitsonga', 'flag' => '🇿🇦' ),
			'tt'    => array( 'name' => 'Tatar', 'native' => 'Татар', 'flag' => '🇷🇺' ),
			'tw'    => array( 'name' => 'Twi', 'native' => 'Twi', 'flag' => '🇬🇭' ),
			'ty'    => array( 'name' => 'Tahitian', 'native' => 'Reo Tahiti', 'flag' => '🇵🇫' ),

			// U
			'ug'    => array( 'name' => 'Uyghur', 'native' => 'ئۇيغۇرچە', 'flag' => '🇨🇳' ),
			'uk'    => array( 'name' => 'Ukrainian', 'native' => 'Українська', 'flag' => '🇺🇦' ),
			'ur'    => array( 'name' => 'Urdu', 'native' => 'اردو', 'flag' => '🇵🇰' ),
			'uz'    => array( 'name' => 'Uzbek', 'native' => 'Oʻzbek', 'flag' => '🇺🇿' ),

			// V
			've'    => array( 'name' => 'Venda', 'native' => 'Tshivenḓa', 'flag' => '🇿🇦' ),
			'vi'    => array( 'name' => 'Vietnamese', 'native' => 'Tiếng Việt', 'flag' => '🇻🇳' ),
			'vo'    => array( 'name' => 'Volapük', 'native' => 'Volapük', 'flag' => '🌍' ),

			// W
			'wa'    => array( 'name' => 'Walloon', 'native' => 'Walon', 'flag' => '🇧🇪' ),
			'wo'    => array( 'name' => 'Wolof', 'native' => 'Wollof', 'flag' => '🇸🇳' ),

			// X
			'xh'    => array( 'name' => 'Xhosa', 'native' => 'isiXhosa', 'flag' => '🇿🇦' ),

			// Y
			'yi'    => array( 'name' => 'Yiddish', 'native' => 'ייִדיש', 'flag' => '🇮🇱' ),
			'yo'    => array( 'name' => 'Yoruba', 'native' => 'Yorùbá', 'flag' => '🇳🇬' ),

			// Z
			'za'    => array( 'name' => 'Zhuang', 'native' => 'Saɯ cueŋƅ', 'flag' => '🇨🇳' ),
			'zh'    => array( 'name' => 'Chinese (Simplified)', 'native' => '中文(简体)', 'flag' => '🇨🇳' ),
			'zh-hk' => array( 'name' => 'Chinese (Hong Kong)', 'native' => '中文(香港)', 'flag' => '🇭🇰' ),
			'zh-tw' => array( 'name' => 'Chinese (Traditional)', 'native' => '中文(繁體)', 'flag' => '🇹🇼' ),
			'zu'    => array( 'name' => 'Zulu', 'native' => 'isiZulu', 'flag' => '🇿🇦' ),
		);
	}
}
