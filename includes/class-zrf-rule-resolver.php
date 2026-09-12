<?php
/**
 * Pure rule resolution.
 *
 * @package ZeroResultsFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure resolver: which rule matches a query.
 */
class ZRF_RuleResolver {

	/**
	 * Find the mapping rule for a query (case-insensitive, trimmed).
	 *
	 * @param string   $query Raw query.
	 * @param array[]  $rules Rules: {id, type, query, posts, enabled, priority}.
	 * @return array|null
	 */
	public static function mapping_for( $query, array $rules ) {
		$needle = ZRF_RuleResolver::key( $query );
		$best   = null;
		foreach ( $rules as $rule ) {
			if ( empty( $rule['enabled'] ) || 'mapping' !== $rule['type'] ) {
				continue;
			}
			if ( ZRF_RuleResolver::key( isset( $rule['query'] ) ? $rule['query'] : '' ) !== $needle ) {
				continue;
			}
			if ( null === $best || (int) $rule['priority'] > (int) $best['priority'] ) {
				$best = $rule;
			}
		}

		return $best;
	}

	/**
	 * All enabled synonym words for a query's variants.
	 *
	 * @param string   $query Raw query.
	 * @param array[]  $rules Rules.
	 * @param string[] $variants Layout/translit variants of the query.
	 * @return string[] Synonym words to OR into the search.
	 */
	public static function synonyms_for( $query, array $rules, array $variants ) {
		$needle = ZRF_RuleResolver::key( $query );
		$keys   = array();
		foreach ( $variants as $variant ) {
			$keys[ ZRF_RuleResolver::key( $variant ) ] = true;
		}

		$words = array();
		foreach ( $rules as $rule ) {
			if ( empty( $rule['enabled'] ) || 'synonym' !== $rule['type'] ) {
				continue;
			}
			$from = ZRF_RuleResolver::key( isset( $rule['query'] ) ? $rule['query'] : '' );
			if ( isset( $keys[ $from ] ) && '' !== (string) ( isset( $rule['posts'] ) ? $rule['posts'] : '' ) ) {
				$words[] = (string) $rule['posts']; // Synonym rules reuse the posts column as the word.
			}
		}

		return array_values( array_unique( $words ) );
	}

	/**
	 * Normalized lookup key.
	 *
	 * @param string $value Raw.
	 * @return string
	 */
	public static function key( $value ) {
		$value = trim( (string) $value );
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $value, 'UTF-8' );
		}
		$value = strtolower( $value );

		return strtr( $value, 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя' );
	}
}
