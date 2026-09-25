<?php

use Codeorama\HandballProspects\Card;
use PHPUnit\Framework\TestCase;

final class CardTest extends TestCase {

	protected function tearDown(): void {
		unset( $GLOBALS['hp_test_translations'] );
	}

	public function test_age_and_club(): void {
		$this->assertSame( '18 yrs · Skjern Handball', Card::metaLine( array( 'age' => 18, 'birth_year' => 2008, 'club' => 'Skjern Handball' ) ) );
	}

	public function test_birth_year_when_the_age_is_unknown(): void {
		$this->assertSame( 'b. 2009 · HK Malmö', Card::metaLine( array( 'age' => null, 'birth_year' => 2009, 'club' => 'HK Malmö' ) ) );
	}

	public function test_only_what_is_known(): void {
		$this->assertSame( 'Lugi HF', Card::metaLine( array( 'club' => 'Lugi HF' ) ) );
		$this->assertSame( '24 yrs', Card::metaLine( array( 'age' => 24 ) ) );
		$this->assertSame( '', Card::metaLine( array() ) );
	}

	public function test_follows_the_site_language(): void {
		$GLOBALS['hp_test_translations'] = array( '%d yrs' => '%d år' );

		$this->assertSame( '18 år · Skjern Handball', Card::metaLine( array( 'age' => 18, 'club' => 'Skjern Handball' ) ) );
	}
}
