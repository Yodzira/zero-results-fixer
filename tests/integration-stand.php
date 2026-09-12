<?php
/**
 * Zero Results Fixer integration — inside QA container:
 *   docker exec infra-wordpress-1 wp eval-file /tmp/zrf-integration.php --allow-root
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function check( $label, $cond ) {
	if ( $cond ) {
		$GLOBALS['pass']++;
		echo "  ok   {$label}\n";
	} else {
		$GLOBALS['fail']++;
		echo "  FAIL {$label}\n";
	}
}

echo "== Zero Results Fixer integration ==\n";

check( 'plugin active', is_plugin_active( 'zero-results-fixer/zero-results-fixer.php' ) );
check( 'classes loaded', class_exists( 'ZRF_Applier' ) && class_exists( 'ZRF_Store' ) );

global $wpdb;
$table     = ZRF_Store::table_name();
$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
if ( $has_table !== $table ) {
	ZRF_Store::activate(); // install --force does not re-run the activation hook.
	$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
}
check( 'rules table present', $has_table === $table );

// Fixture posts.
$p_iphone = wp_insert_post( array( 'post_title' => 'iPhone 15 Pro', 'post_name' => 'iphone-15-pro', 'post_content' => 'Apple flagship phone', 'post_status' => 'publish' ) );
$p_sam    = wp_insert_post( array( 'post_title' => 'Samsung Galaxy S25', 'post_name' => 'samsung-galaxy', 'post_content' => 'Android flagship', 'post_status' => 'publish' ) );
$p_text   = wp_insert_post( array( 'post_title' => 'Текстовый редактор', 'post_name' => 'text-editor', 'post_content' => 'Простой текст', 'post_status' => 'publish' ) );
check( 'fixture posts created', (bool) $p_iphone && (bool) $p_sam && (bool) $p_text );

ZRF_Store::erase_all();
// Mapping: «гаджет» -> iPhone post. Synonym: «самсунг» also matches «samsung».
ZRF_Store::save_rule( array( 'type' => 'mapping', 'query' => 'гаджет', 'posts' => (string) $p_iphone, 'priority' => 10, 'enabled' => 1 ) );
ZRF_Store::save_rule( array( 'type' => 'synonym', 'query' => 'самсунг', 'posts' => 'Samsung', 'priority' => 10, 'enabled' => 1 ) );

$search = static function ( $s ) {
	$q = new WP_Query( array( 's' => $s, 'posts_per_page' => 10 ) );

	return wp_list_pluck( $q->posts, 'ID' );
};

// 1. Native search works untouched.
check( 'native search finds iphone', in_array( (int) $p_iphone, $search( 'iPhone' ), true ) );

// 2. Synonym: «самсунг» must find the Samsung post via the synonym word.
check( 'synonym finds samsung', in_array( (int) $p_sam, $search( 'самсунг' ), true ) );

// 3. Layout tolerance: «ntrcn» -> «текст» variant finds the editor post.
check( 'layout variant finds text post', in_array( (int) $p_text, $search( 'ntrcn' ), true ) );

// 4. Mapping: «гаджет» shows the mapped post even though the word never matches.
$mapping_hits = $search( 'совершенно_несуществующее_слово' );
check( 'unmapped query stays empty', ! in_array( (int) $p_iphone, $mapping_hits, true ) );
$mapped = $search( 'гаджет' );
check( 'mapping shows the mapped post', in_array( (int) $p_iphone, $mapped, true ) );

// 5. Hit counter incremented by the applied mapping.
$rules   = ZRF_Store::all();
$mapping = null;
foreach ( $rules as $rule ) {
	if ( 'mapping' === $rule['type'] ) {
		$mapping = $rule;
	}
}
check( 'hit counter incremented', $mapping && (int) $mapping['hits'] >= 1 );

// 6. Disabled rule stops applying.
ZRF_Store::save_rule( array( 'id' => (int) $mapping['id'], 'type' => 'mapping', 'query' => 'гаджет', 'posts' => (string) $p_iphone, 'priority' => 10, 'enabled' => 0 ) );
check( 'disabled rule stops applying', ! in_array( (int) $p_iphone, $search( 'гаджет' ), true ) );

// Cleanup.
wp_delete_post( $p_iphone, true );
wp_delete_post( $p_sam, true );
wp_delete_post( $p_text, true );
ZRF_Store::erase_all();
check( 'rules erased', array() === ZRF_Store::all() );

printf( "\n== Zero Results Fixer integration: %d pass, %d fail ==\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
