<?php
/**
 * Applies rules to the native search query.
 *
 * @package ZeroResultsFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZRF_Applier {

	/** @var bool Inside our own result injection — skip nested queries. */
	private static $in_filter = false;

	public static function boot() {
		add_filter( 'posts_search', array( __CLASS__, 'expand_synonyms' ), 100, 2 );
		add_filter( 'the_posts', array( __CLASS__, 'inject_mapping' ), 10, 2 );
	}

	private static function rules() {
		return ZRF_Store::all();
	}

	/**
	 * Only front-end main search queries pass through here.
	 *
	 * @param WP_Query $query Query.
	 * @return bool
	 */
	private static function applies( $query ) {
		return ! self::$in_filter && $query->is_search() && ! is_admin();
	}

	/**
	 * Append synonym/variant OR-terms to the search SQL.
	 *
	 * @param string   $search Search WHERE fragment.
	 * @param WP_Query $query  Query.
	 * @return string
	 */
	public static function expand_synonyms( $search, $query ) {
		if ( '' === trim( (string) $search ) || ! self::applies( $query ) ) {
			return $search;
		}

		$raw      = $query->get( 's' );
		$variants = ZRF_Translit::variants( $raw );
		// Layout/translit variants (everything after the original) + rule synonyms.
		$words = array_slice( $variants, 1 );
		$synonyms = ZRF_RuleResolver::synonyms_for( $raw, self::rules(), $variants );
		$words = array_values( array_unique( array_merge( $words, $synonyms ) ) );
		if ( ! $words ) {
			return $search;
		}

		global $wpdb;
		$or = '';
		foreach ( $words as $word ) {
			$like = '%' . $wpdb->esc_like( $word ) . '%';
			$or  .= $wpdb->prepare( " OR {$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s", $like, $like ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->posts is a safe identifier.
		}
		if ( '' === $or ) {
			return $search;
		}

		// The native fragment looks like " AND ((post_title LIKE ..) OR (post_content LIKE ..))".
		$closing = strrpos( $search, '))' );
		if ( false === $closing ) {
			return $search;
		}

		return substr( $search, 0, $closing ) . $or . substr( $search, $closing );
	}

	/**
	 * Mapping rules: prepend the mapped posts, dedupe, count hits.
	 *
	 * @param WP_Post[] $posts Current results.
	 * @param WP_Query  $query Query.
	 * @return WP_Post[]
	 */
	public static function inject_mapping( $posts, $query ) {
		if ( ! self::applies( $query ) ) {
			return $posts;
		}

		$raw  = $query->get( 's' );
		$rule = ZRF_RuleResolver::mapping_for( $raw, self::rules() );
		if ( ! $rule ) {
			// Fallback: empty results -> category fallback (when configured).
			if ( ! $posts ) {
				return self::fallback_posts( $query );
			}

			return $posts;
		}

		$ids = array_filter( array_map( 'absint', explode( ',', (string) $rule['posts'] ) ) );
		if ( ! $ids ) {
			return $posts;
		}

		self::$in_filter = true;
		$extra = get_posts(
			array(
				'post_type'      => 'any',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count( $ids ),
			)
		);
		self::$in_filter = false;

		if ( $extra ) {
			ZRF_Store::hit( (int) $rule['id'] );
			$seen = array();
			foreach ( (array) $posts as $p ) {
				$seen[ $p->ID ] = true;
			}
			foreach ( $extra as $p ) {
				if ( empty( $seen[ $p->ID ] ) ) {
					$posts[] = $p;
					$seen[ $p->ID ] = true;
				}
			}
		}

		// the_posts runs AFTER WP_Query set post_count/found_posts: with zero
		// native results the loop would still render "nothing found" unless
		// the counters are synced with the injected set.
		$query->posts       = $posts;
		$query->post_count  = count( $posts );
		$query->found_posts = max( (int) $query->found_posts, count( $posts ) );
		if ( $posts ) {
			$query->post = $posts[0];
		}

		return $posts;
	}

	/**
	 * Zero-results fallback: latest posts of the configured category.
	 *
	 * @param WP_Query $query Query.
	 * @return WP_Post[]
	 */
	private static function fallback_posts( $query ) {
		$category_id = (int) get_option( 'zrf_fallback_category', 0 );
		if ( ! $category_id || ! get_option( 'zrf_fallback_enabled', false ) ) {
			return $query->posts ?? array();
		}

		self::$in_filter = true;
		$posts = get_posts(
			array(
				'category'    => $category_id,
				'numberposts' => 5,
			)
		);
		self::$in_filter = false;

		return $posts;
	}
}
