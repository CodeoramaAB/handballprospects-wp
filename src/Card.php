<?php

namespace Codeorama\HandballProspects;

defined( 'ABSPATH' ) || exit;

/**
 * Kortets andra rad: "18 år · Skjern Handball". Ålder när födelsedatumet
 * är känt, annars födelseåret, annars bara klubben.
 */
final class Card {

	/**
	 * @param array{age?: ?int, birth_year?: ?int, club?: ?string} $player
	 */
	public static function metaLine( array $player ): string {
		$parts = array();

		if ( ! empty( $player['age'] ) ) {
			/* translators: %d: the player's age in years. */
			$parts[] = sprintf( __( '%d yrs', 'handballprospects' ), (int) $player['age'] );
		} elseif ( ! empty( $player['birth_year'] ) ) {
			/* translators: %d: the player's year of birth. */
			$parts[] = sprintf( __( 'b. %d', 'handballprospects' ), (int) $player['birth_year'] );
		}

		if ( ! empty( $player['club'] ) ) {
			$parts[] = (string) $player['club'];
		}

		return implode( ' · ', $parts );
	}
}
