<?php

namespace Codeorama\HandballProspects;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Klienten mot HandballProspects partner-API (/api/v1). Anropen görs alltid
 * från servern: token får aldrig nå en webbläsare.
 */
final class Api {

	public const DEFAULT_URL = 'https://handballprospects.com/api/v1';

	/** HandballProspects språk, nycklade på WordPress-locale utan region. */
	private const LANGUAGES = array(
		'sv' => 'sv',
		'en' => 'en',
		'de' => 'de',
		'da' => 'da',
		'nb' => 'no',
		'nn' => 'no',
		'no' => 'no',
	);

	public function __construct(
		private string $baseUrl,
		private string $token,
		private ?string $lang,
	) {}

	/** Null när sajten saknar token — då är pluginet avstängt. */
	public static function fromConfig(): ?self {
		$token = defined( 'HANDBALLPROSPECTS_TOKEN' ) ? (string) HANDBALLPROSPECTS_TOKEN : '';
		if ( '' === $token ) {
			return null;
		}

		$url = defined( 'HANDBALLPROSPECTS_API_URL' ) ? (string) HANDBALLPROSPECTS_API_URL : self::DEFAULT_URL;

		return new self( untrailingslashit( $url ), $token, self::languageFor( get_locale() ) );
	}

	/**
	 * Sajtens språk som HandballProspects-språk ("sv_SE" → "sv"), så att
	 * profillänkarna öppnas på artikelns språk. Null för ett språk HP inte
	 * har; då gäller partnerns förval på HP-sidan.
	 */
	public static function languageFor( string $locale ): ?string {
		$prefix = strtolower( strtok( $locale, '_-' ) ?: '' );

		return self::LANGUAGES[ $prefix ] ?? null;
	}

	/**
	 * @return list<array<string, mixed>>|WP_Error
	 */
	public function search( string $term ): array|WP_Error {
		$body = $this->get( '/players/search', array( 'q' => $term ) );

		return is_wp_error( $body ) ? $body : (array) ( $body['data'] ?? array() );
	}

	/**
	 * Korten för en artikel, i artikelns ordning. `requested_id` säger vilket
	 * id kortet svarar på — en spelare som slagits ihop på HP kommer tillbaka
	 * under sitt nya id.
	 *
	 * @param  list<int> $ids
	 * @return array{data: list<array<string, mixed>>, missing: list<int>}|WP_Error
	 */
	public function cards( array $ids ): array|WP_Error {
		$body = $this->get( '/players', array( 'ids' => implode( ',', $ids ) ) );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		return array(
			'data'    => (array) ( $body['data'] ?? array() ),
			'missing' => array_map( 'intval', (array) ( $body['missing'] ?? array() ) ),
		);
	}

	/**
	 * @param  array<string, string> $query
	 * @return array<string, mixed>|WP_Error
	 */
	private function get( string $path, array $query ): array|WP_Error {
		if ( null !== $this->lang ) {
			$query['lang'] = $this->lang;
		}

		$response = wp_remote_get(
			$this->baseUrl . $path . '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ),
			array(
				'timeout' => 5,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->token,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status || ! is_array( $body ) ) {
			return new WP_Error(
				'handballprospects_api',
				sprintf( 'HandballProspects answered %d.', $status ),
				array( 'status' => $status )
			);
		}

		return $body;
	}
}
