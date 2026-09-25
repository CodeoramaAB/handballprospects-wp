<?php

use Codeorama\HandballProspects\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase {

	public function test_a_pasted_token_is_saved_trimmed_and_without_stray_characters(): void {
		$this->assertSame( 'abc123', Settings::resolveToken( "  abc123\n", '', false ) );
		$this->assertSame( 'abc123', Settings::resolveToken( '"abc123"', 'old', false ) );
	}

	public function test_an_empty_field_keeps_the_saved_token(): void {
		// The saved token is never printed back, so the field is always empty.
		$this->assertSame( 'saved', Settings::resolveToken( '', 'saved', false ) );
		$this->assertSame( 'saved', Settings::resolveToken( '   ', 'saved', false ) );
	}

	public function test_the_checkbox_removes_the_token(): void {
		$this->assertSame( '', Settings::resolveToken( 'new', 'saved', true ) );
	}

	public function test_the_hint_shows_only_the_last_characters(): void {
		$this->assertSame( '…a5b3', Settings::hint( '828948c6dee89b8fbadeccc8b2f9feb0978c2a9d2fcc940c0ae2d8c6b91ca5b3' ) );
		$this->assertSame( '', Settings::hint( '' ) );
	}
}
