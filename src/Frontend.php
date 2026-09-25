<?php

namespace Codeorama\HandballProspects;

defined( 'ABSPATH' ) || exit;

/**
 * "Följ spelarna" sist i artikeln. Korten hämtas från HandballProspects och
 * cachas en stund, så att en klubbyte syns utan att artikeln rörs; svarar
 * HP inte visas ögonblicksbilderna från när spelarna valdes.
 */
final class Frontend {

	private const CACHE_SECONDS = 6 * HOUR_IN_SECONDS;

	public static function register(): void {
		add_filter( 'the_content', array( self::class, 'append' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	public static function enqueue(): void {
		if ( is_singular( 'post' ) && array() !== Players::get( (int) get_queried_object_id() ) ) {
			wp_enqueue_style( 'handballprospects', plugin_dir_url( HANDBALLPROSPECTS_FILE ) . 'assets/frontend.css', array(), HANDBALLPROSPECTS_VERSION );
		}
	}

	public static function append( string $content ): string {
		$postId = (int) get_the_ID();
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || $postId !== (int) get_queried_object_id() ) {
			return $content;
		}

		$players = self::cards( $postId );

		return array() === $players ? $content : $content . self::render( $postId, $players );
	}

	/**
	 * @return list<array{id: int, name: string, age: ?int, birth_year: ?int, club: ?string, url: string}>
	 */
	private static function cards( int $postId ): array {
		$chosen = Players::get( $postId );
		$api    = Api::fromConfig();
		if ( array() === $chosen || null === $api ) {
			return $chosen;
		}

		$ids   = array_column( $chosen, 'id' );
		$key   = 'hp_cards_' . md5( implode( ',', $ids ) . '|' . get_locale() );
		$fresh = get_transient( $key );

		if ( ! is_array( $fresh ) ) {
			$response = $api->cards( $ids );
			if ( is_wp_error( $response ) ) {
				return $chosen;
			}

			$fresh = $response;
			set_transient( $key, $fresh, self::CACHE_SECONDS );
		}

		return self::merge( $postId, $chosen, $fresh );
	}

	/**
	 * Lägg de färska korten på artikelns val. En spelare som slagits ihop
	 * på HP får sitt nya id i artikeln; en som försvunnit ur HP visas inte.
	 *
	 * @param  list<array<string, mixed>>                                     $chosen
	 * @param  array{data: list<array<string, mixed>>, missing: list<int>}  $fresh
	 * @return list<array{id: int, name: string, age: ?int, birth_year: ?int, club: ?string, url: string}>
	 */
	private static function merge( int $postId, array $chosen, array $fresh ): array {
		$byRequested = array();
		foreach ( $fresh['data'] as $card ) {
			$byRequested[ (int) ( $card['requested_id'] ?? 0 ) ] = Players::fromCard( (array) $card );
		}

		$players = array();
		$moved   = false;
		foreach ( $chosen as $player ) {
			if ( in_array( $player['id'], $fresh['missing'], true ) ) {
				continue;
			}

			$card = $byRequested[ $player['id'] ] ?? array();
			if ( array() === $card ) {
				$players[] = $player;

				continue;
			}

			$moved     = $moved || $card['id'] !== $player['id'];
			$players[] = $card;
		}

		if ( $moved ) {
			Players::save( $postId, $players );
		}

		return $players;
	}

	/**
	 * @param list<array{id: int, name: string, age: ?int, birth_year: ?int, club: ?string, url: string}> $players
	 */
	private static function render( int $postId, array $players ): string {
		$titleId = 'hp-follow-' . $postId;

		$items = '';
		foreach ( $players as $player ) {
			$meta   = Card::metaLine( $player );
			$items .= sprintf(
				'<li class="hp-follow__item"><a class="hp-card" href="%s" target="_blank" rel="noopener"><span class="hp-card__name">%s</span>%s</a></li>',
				esc_url( $player['url'] ),
				esc_html( $player['name'] ),
				'' === $meta ? '' : '<span class="hp-card__meta">' . esc_html( $meta ) . '</span>'
			);
		}

		return sprintf(
			'<section class="hp-follow" aria-labelledby="%1$s"><div class="hp-follow__head"><div class="hp-follow__title" id="%1$s" role="heading" aria-level="2">%2$s</div><span class="hp-follow__source">%3$s</span></div><ul class="hp-follow__list">%4$s</ul></section>',
			esc_attr( $titleId ),
			esc_html__( 'Follow the players', 'handballprospects' ),
			esc_html__( 'on HandballProspects', 'handballprospects' ),
			$items
		);
	}
}
