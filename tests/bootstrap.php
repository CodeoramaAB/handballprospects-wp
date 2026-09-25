<?php
/**
 * Enhetstesterna kör pluginets rena logik utan WordPress: de få
 * WP-funktioner den använder stubbas här med WordPress eget beteende
 * (översättning = källsträngen, sanering = trimmad text utan taggar).
 */

define( 'ABSPATH', __DIR__ . '/' );

function __( string $text, string $domain = 'default' ): string {
	return $GLOBALS['hp_test_translations'][ $text ] ?? $text;
}

function sanitize_text_field( string $text ): string {
	return trim( preg_replace( '/\s+/', ' ', strip_tags( $text ) ) );
}

function esc_url_raw( string $url ): string {
	return preg_match( '#^https?://#i', $url ) === 1 ? $url : '';
}

function untrailingslashit( string $value ): string {
	return rtrim( $value, '/\\' );
}

require __DIR__ . '/../src/Api.php';
require __DIR__ . '/../src/Players.php';
require __DIR__ . '/../src/Card.php';
