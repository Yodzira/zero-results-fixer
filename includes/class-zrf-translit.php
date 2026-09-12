<?php
/**
 * Keyboard-layout and translit variants (pure).
 *
 * @package ZeroResultsFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZRF_Translit {

	/** QWERTY -> ЙЦУКЕН (per key). */
	const LAYOUT_EN_RU = array(
		'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г',
		'i' => 'ш', 'o' => 'щ', 'p' => 'з', 'a' => 'ф', 's' => 'ы', 'd' => 'в', 'f' => 'а',
		'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л', 'l' => 'д', 'z' => 'я', 'x' => 'ч',
		'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь',
	);

	/** Cyrillic -> latin transliteration. */
	const TRANSLIT_RU_EN = array(
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
		'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
		'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
		'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
	);

	/**
	 * Case-preserving layout swap for a single string.
	 *
	 * @param string $text Input.
	 * @return string Swapped (unchanged chars stay).
	 */
	public static function swap_layout( $text ) {
		$en_ru = self::LAYOUT_EN_RU;
		$ru_en = array_flip( $en_ru );
		$out   = '';
		$chars = preg_split( '//u', (string) $text, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( (array) $chars as $ch ) {
			$lower = A11Z_lower( $ch );
			if ( isset( $en_ru[ $lower ] ) ) {
				$out .= A11Z_is_upper( $ch ) ? A11Z_upper( $en_ru[ $lower ] ) : $en_ru[ $lower ];
				continue;
			}
			if ( isset( $ru_en[ $lower ] ) ) {
				$out .= A11Z_is_upper( $ch ) ? strtoupper( $ru_en[ $lower ] ) : $ru_en[ $lower ];
				continue;
			}
			$out .= $ch;
		}

		return $out;
	}

	/**
	 * Transliterate Cyrillic to latin.
	 *
	 * @param string $text Input.
	 * @return string
	 */
	public static function to_latin( $text ) {
		$out = '';
		$chars = preg_split( '//u', A11Z_lower( (string) $text ), -1, PREG_SPLIT_NO_EMPTY );
		foreach ( (array) $chars as $ch ) {
			$out .= isset( self::TRANSLIT_RU_EN[ $ch ] ) ? self::TRANSLIT_RU_EN[ $ch ] : $ch;
		}

		return $out;
	}

	/**
	 * All useful search variants of a query (deduped, original first).
	 *
	 * @param string $query Raw search query.
	 * @return string[]
	 */
	public static function variants( $query ) {
		$query = trim( (string) $query );
		if ( '' === $query ) {
			return array();
		}

		$variants = array( $query );
		$swapped  = self::swap_layout( $query );
		if ( '' !== $swapped && 0 !== strcasecmp( $swapped, $query ) ) {
			$variants[] = $swapped;
		}
		$has_cyrillic = preg_match( '/[а-яё]/iu', $query );
		if ( $has_cyrillic ) {
			$latin = self::to_latin( $query );
			if ( '' !== $latin && ! in_array( $latin, $variants, true ) ) {
				$variants[] = $latin;
			}
		}

		return array_values( array_unique( $variants ) );
	}
}

/**
 * Local helpers avoiding mbstring dependency (pure).
 */
function A11Z_lower( $s ) {
	if ( function_exists( 'mb_strtolower' ) ) {
		return mb_strtolower( (string) $s, 'UTF-8' );
	}
	$s = strtolower( (string) $s );

	return strtr( $s, 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя' );
}

function A11Z_upper( $s ) {
	if ( function_exists( 'mb_strtoupper' ) ) {
		return mb_strtoupper( (string) $s, 'UTF-8' );
	}
	$s = strtoupper( (string) $s );

	return strtr( $s, 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя', 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ' );
}

function A11Z_is_upper( $ch ) {
	return A11Z_lower( $ch ) !== $ch;
}
