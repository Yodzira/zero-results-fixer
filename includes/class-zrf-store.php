<?php
/**
 * Rules storage (wpdb).
 *
 * @package ZeroResultsFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rules CRUD.
 */
class ZRF_Store {

	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'zrf_rules';
	}

	public static function activate() {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type varchar(10) NOT NULL DEFAULT 'mapping',
			query varchar(191) NOT NULL DEFAULT '',
			posts varchar(500) NOT NULL DEFAULT '',
			priority int(11) NOT NULL DEFAULT 10,
			enabled tinyint(1) NOT NULL DEFAULT 1,
			hits bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY type_enabled (type, enabled)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * All rules as plain arrays.
	 *
	 * @return array[]
	 */
	public static function all() {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->get_results( "SELECT id, type, query, posts, priority, enabled, hits FROM {$table} ORDER BY priority DESC, id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL -- table from prefix.
	}

	/**
	 * Insert/update a rule.
	 *
	 * @param array $data {id?, type, query, posts, priority, enabled}.
	 * @return int
	 */
	public static function save_rule( array $data ) {
		global $wpdb;
		$table = self::table_name();

		$row = array(
			'type'     => 'synonym' === ( $data['type'] ?? '' ) ? 'synonym' : 'mapping',
			'query'    => substr( trim( (string) ( $data['query'] ?? '' ) ), 0, 191 ),
			'posts'    => substr( trim( (string) ( $data['posts'] ?? '' ) ), 0, 500 ),
			'priority' => max( 0, min( 100, (int) ( $data['priority'] ?? 10 ) ) ),
			'enabled'  => empty( $data['enabled'] ) ? 0 : 1,
		);
		$format = array( '%s', '%s', '%s', '%d', '%d' );

		if ( ! empty( $data['id'] ) ) {
			$wpdb->update( $table, $row, array( 'id' => (int) $data['id'] ), $format, array( '%d' ) );

			return (int) $data['id'];
		}

		$wpdb->insert( $table, $row, $format );

		return (int) $wpdb->insert_id;
	}

	public static function hit( $id ) {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hits = hits + 1 WHERE id = %d", (int) $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL -- table from prefix.
	}

	public static function delete_rule( $id ) {
		global $wpdb;
		$table = self::table_name();
		$wpdb->delete( $table, array( 'id' => (int) $id ), array( '%d' ) );
	}

	public static function erase_all() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.
	}

	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL -- identifier derived from $wpdb->prefix.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
