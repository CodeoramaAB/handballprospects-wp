<?php

use Codeorama\HandballProspects\Api;
use PHPUnit\Framework\TestCase;

final class ApiTest extends TestCase {

	public function test_the_site_locale_becomes_a_handballprospects_language(): void {
		$this->assertSame( 'sv', Api::languageFor( 'sv_SE' ) );
		$this->assertSame( 'en', Api::languageFor( 'en_US' ) );
		$this->assertSame( 'en', Api::languageFor( 'en_GB' ) );
		$this->assertSame( 'de', Api::languageFor( 'de_DE_formal' ) );
		$this->assertSame( 'no', Api::languageFor( 'nb_NO' ) );
		$this->assertSame( 'da', Api::languageFor( 'da_DK' ) );
	}

	public function test_a_language_handballprospects_lacks_leaves_it_to_the_partner_default(): void {
		$this->assertNull( Api::languageFor( 'fr_FR' ) );
		$this->assertNull( Api::languageFor( '' ) );
	}
}
