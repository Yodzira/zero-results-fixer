<?php

use PHPUnit\Framework\TestCase;

/**
 * Layout swap, translit, variants.
 */
class TranslitTest extends TestCase {

	public function test_layout_swap_en_to_ru() {
		$this->assertSame( 'текст', ZRF_Translit::swap_layout( 'ntrcn' ) );
		$this->assertSame( 'привет', ZRF_Translit::swap_layout( 'ghbdtn' ) );
	}

	public function test_layout_swap_ru_to_en() {
		$this->assertSame( 'yandex', ZRF_Translit::swap_layout( 'нфтвуч' ) );
	}

	public function test_layout_swap_preserves_case_and_other_chars() {
		$this->assertSame( 'Nj', ZRF_Translit::swap_layout( 'То' ) ); // Т->N (upper), о->j.
		$this->assertSame( '12 3!', ZRF_Translit::swap_layout( '12 3!' ) );
	}

	public function test_translit_to_latin() {
		$this->assertSame( 'ayfon', ZRF_Translit::to_latin( 'айфон' ) );
		$this->assertSame( 'esche', ZRF_Translit::to_latin( 'ещё' ) );
	}

	public function test_variants_dedup_original_first() {
		$variants = ZRF_Translit::variants( 'айфон' );
		$this->assertSame( 'айфон', $variants[0] );
		$this->assertContains( 'ayfon', $variants );

		// A latin query keeps its ru-layout swap as a harmless extra variant.
		$en = ZRF_Translit::variants( 'iphone' );
		$this->assertSame( 'iphone', $en[0] );
		$this->assertCount( 2, $en );
		$this->assertSame( array(), ZRF_Translit::variants( '   ' ) );
	}
}

/**
 * Mapping/synonym resolution.
 */
class ResolverTest extends TestCase {

	private $rules = array(
		array( 'id' => 1, 'type' => 'mapping', 'query' => 'айфон', 'posts' => '10,11', 'enabled' => 1, 'priority' => 10 ),
		array( 'id' => 2, 'type' => 'mapping', 'query' => 'Айфон', 'posts' => '99', 'enabled' => 1, 'priority' => 20 ),
		array( 'id' => 3, 'type' => 'mapping', 'query' => 'samsung', 'posts' => '20', 'enabled' => 0, 'priority' => 50 ),
		array( 'id' => 4, 'type' => 'synonym', 'query' => 'смартфон', 'posts' => 'phone', 'enabled' => 1, 'priority' => 10 ),
	);

	public function test_highest_priority_enabled_wins() {
		$rule = ZRF_RuleResolver::mapping_for( 'айфон', $this->rules );
		$this->assertSame( 2, (int) $rule['id'] ); // Priority 20 over 10.
		$this->assertSame( '99', $rule['posts'] );
	}

	public function test_case_and_trim_insensitive() {
		$rule = ZRF_RuleResolver::mapping_for( '  САМСУНГ ', $this->rules );
		$this->assertNull( $rule ); // Disabled rules skipped.

		$rule2 = ZRF_RuleResolver::mapping_for( ' Айфон ', $this->rules );
		$this->assertNotNull( $rule2 );
	}

	public function test_synonyms_by_variants() {
		$syn = ZRF_RuleResolver::synonyms_for( 'Смартфон', $this->rules, array( 'смартфон' ) );
		$this->assertSame( array( 'phone' ), $syn );

		$this->assertSame( array(), ZRF_RuleResolver::synonyms_for( 'самсунг', $this->rules, array( 'самсунг' ) ) );
	}
}
