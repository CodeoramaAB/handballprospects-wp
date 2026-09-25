<?php

use Codeorama\HandballProspects\Players;
use PHPUnit\Framework\TestCase;

final class PlayersTest extends TestCase {

	public function test_keeps_order_drops_invalid_rows_and_duplicates(): void {
		$players = Players::sanitize(
			array(
				array( 'id' => 12, 'name' => ' Axel Månsson ', 'age' => '18', 'club' => 'Skjern Handball', 'url' => 'https://handballprospects.com/spelare/12' ),
				array( 'id' => 0, 'name' => 'Nobody' ),
				array( 'id' => 7, 'name' => '' ),
				'not a row',
				array( 'id' => 12, 'name' => 'Axel again' ),
				array( 'id' => '34', 'name' => '<b>Jack</b> Månsson', 'birth_year' => 1998, 'url' => 'javascript:alert(1)' ),
			)
		);

		$this->assertSame(
			array(
				array( 'id' => 12, 'name' => 'Axel Månsson', 'age' => 18, 'birth_year' => null, 'club' => 'Skjern Handball', 'url' => 'https://handballprospects.com/spelare/12' ),
				array( 'id' => 34, 'name' => 'Jack Månsson', 'age' => null, 'birth_year' => 1998, 'club' => null, 'url' => '' ),
			),
			$players
		);
	}

	public function test_anything_but_a_list_is_nothing(): void {
		$this->assertSame( array(), Players::sanitize( '' ) );
		$this->assertSame( array(), Players::sanitize( null ) );
		$this->assertSame( array(), Players::sanitize( 'a:1:{}' ) );
	}

	public function test_caps_the_number_of_players(): void {
		$rows = array_map( static fn ( int $id ): array => array( 'id' => $id, 'name' => "Player $id" ), range( 1, 30 ) );

		$this->assertCount( Players::MAX, Players::sanitize( $rows ) );
	}

	public function test_an_api_card_becomes_a_snapshot_with_the_club_name(): void {
		$snapshot = Players::fromCard(
			array(
				'requested_id' => 555,
				'id'           => 12,
				'name'         => 'Axel Månsson',
				'age'          => 18,
				'birth_year'   => 2008,
				'position'     => 'M9',
				'club'         => array( 'id' => 3404, 'name' => 'Skjern Handball', 'country' => 'DK' ),
				'url'          => 'https://handballprospects.com/spelare/12?lang=en',
			)
		);

		$this->assertSame(
			array( 'id' => 12, 'name' => 'Axel Månsson', 'age' => 18, 'birth_year' => 2008, 'club' => 'Skjern Handball', 'url' => 'https://handballprospects.com/spelare/12?lang=en' ),
			$snapshot
		);
		$this->assertSame( array(), Players::fromCard( array( 'id' => 0 ) ) );
	}
}
