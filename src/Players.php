<?php

namespace Codeorama\HandballProspects;

defined( 'ABSPATH' ) || exit;

/**
 * Artikelns valda spelare, i post meta. Varje rad är HP-id:t plus en
 * ögonblicksbild av kortet (namn, ålder, klubb, länk) från när skribenten
 * valde spelaren — den visas om HandballProspects inte svarar.
 *
 * @phpstan-type Snapshot array{id: int, name: string, age: ?int, birth_year: ?int, club: ?string, url: string}
 */
final class Players {

	public const META_KEY = '_handballprospects_players';

	/** Fler kort än så blir inte "små och kompakta". API:t tar 20 per anrop. */
	public const MAX = 12;

	public static function register(): void {
		register_post_meta(
			'post',
			self::META_KEY,
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'auth_callback'     => static fn (): bool => current_user_can( 'edit_posts' ),
			)
		);
	}

	/**
	 * @return list<Snapshot>
	 */
	public static function get( int $postId ): array {
		return self::sanitize( get_post_meta( $postId, self::META_KEY, true ) );
	}

	/**
	 * @param list<Snapshot> $players
	 */
	public static function save( int $postId, array $players ): void {
		if ( array() === $players ) {
			delete_post_meta( $postId, self::META_KEY );

			return;
		}

		update_post_meta( $postId, self::META_KEY, $players );
	}

	/**
	 * Allt som kommer utifrån — editorns JSON, gammal meta — passerar här:
	 * bara rader med ett positivt id och ett namn, varje id en gång, i
	 * ordning, högst MAX.
	 *
	 * @return list<Snapshot>
	 */
	public static function sanitize( mixed $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$players = array();
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$id   = (int) ( $row['id'] ?? 0 );
			$name = trim( sanitize_text_field( (string) ( $row['name'] ?? '' ) ) );
			if ( $id <= 0 || '' === $name || isset( $players[ $id ] ) ) {
				continue;
			}

			$players[ $id ] = array(
				'id'         => $id,
				'name'       => $name,
				'age'        => self::optionalInt( $row['age'] ?? null ),
				'birth_year' => self::optionalInt( $row['birth_year'] ?? null ),
				'club'       => self::optionalText( $row['club'] ?? null ),
				'url'        => esc_url_raw( (string) ( $row['url'] ?? '' ) ),
			);

			if ( count( $players ) >= self::MAX ) {
				break;
			}
		}

		return array_values( $players );
	}

	/**
	 * Ett kort från API:t som en ögonblicksbild. Klubben sparas som namn:
	 * det är allt kortet visar.
	 *
	 * @param  array<string, mixed> $card
	 * @return Snapshot
	 */
	public static function fromCard( array $card ): array {
		$club = $card['club'] ?? null;

		return self::sanitize(
			array(
				array(
					'id'         => $card['id'] ?? 0,
					'name'       => $card['name'] ?? '',
					'age'        => $card['age'] ?? null,
					'birth_year' => $card['birth_year'] ?? null,
					'club'       => is_array( $club ) ? ( $club['name'] ?? null ) : $club,
					'url'        => $card['url'] ?? '',
				),
			)
		)[0] ?? array();
	}

	private static function optionalInt( mixed $value ): ?int {
		return is_numeric( $value ) && (int) $value > 0 ? (int) $value : null;
	}

	private static function optionalText( mixed $value ): ?string {
		$text = is_scalar( $value ) ? trim( sanitize_text_field( (string) $value ) ) : '';

		return '' === $text ? null : $text;
	}
}
