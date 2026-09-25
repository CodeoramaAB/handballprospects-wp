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

	public function test_a_profile_link_without_a_language_gets_the_sites(): void {
		$this->assertSame(
			'https://handballprospects.com/spelare/12?utm_source=gohandball&utm_medium=referral&lang=en',
			Api::withLanguage( 'https://handballprospects.com/spelare/12?utm_source=gohandball&utm_medium=referral', 'en' )
		);
		$this->assertSame( 'https://handballprospects.com/spelare/12?lang=sv', Api::withLanguage( 'https://handballprospects.com/spelare/12', 'sv' ) );
		$this->assertSame( 'https://handballprospects.com/spelare/12?lang=sv#karriar', Api::withLanguage( 'https://handballprospects.com/spelare/12#karriar', 'sv' ) );
	}

	public function test_a_link_that_already_carries_a_language_is_left_alone(): void {
		$url = 'https://handballprospects.com/spelare/12?lang=en&utm_source=gohandball';

		$this->assertSame( $url, Api::withLanguage( $url, 'sv' ) );
		$this->assertSame( 'https://handballprospects.com/spelare/12', Api::withLanguage( 'https://handballprospects.com/spelare/12', null ) );
		$this->assertSame( '', Api::withLanguage( '', 'sv' ) );
	}
}
