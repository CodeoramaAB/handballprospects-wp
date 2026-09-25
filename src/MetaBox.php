<?php

namespace Codeorama\HandballProspects;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Rutan under texten i Classic Editor: sök på namn → välj → klart. Valen
 * reser som JSON i ett dolt fält och sparas med inlägget. En nyvald spelare
 * får också en tagg med sitt namn: editorns skript lägger den i taggrutan
 * direkt, och sparningen lägger den om skriptet inte hann.
 */
final class MetaBox {

	private const FIELD = 'handballprospects_players';

	private const NONCE = 'handballprospects_players_nonce';

	public static function register(): void {
		add_action( 'add_meta_boxes_post', array( self::class, 'add' ) );
		add_action( 'save_post_post', array( self::class, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	public static function add(): void {
		add_meta_box(
			'handballprospects-players',
			__( 'Follow the players – HandballProspects', 'handballprospects' ),
			array( self::class, 'render' ),
			'post',
			'normal',
			'high'
		);
	}

	public static function render( WP_Post $post ): void {
		wp_nonce_field( self::FIELD, self::NONCE );
		$players = Players::get( $post->ID );

		if ( null === Api::fromConfig() ) {
			echo '<p class="hp-box__notice">' . esc_html__( 'HandballProspects is not configured: define HANDBALLPROSPECTS_TOKEN in wp-config.php.', 'handballprospects' ) . '</p>';
		}
		?>
		<div class="hp-box" data-hp-box>
			<input type="hidden" name="<?php echo esc_attr( self::FIELD ); ?>" value="<?php echo esc_attr( (string) wp_json_encode( $players ) ); ?>" data-hp-field>
			<label class="screen-reader-text" for="hp-box-search"><?php esc_html_e( 'Search player', 'handballprospects' ); ?></label>
			<input type="search" id="hp-box-search" class="hp-box__search" autocomplete="off" placeholder="<?php esc_attr_e( 'Search player by name…', 'handballprospects' ); ?>" data-hp-search>
			<ul class="hp-box__results" role="listbox" data-hp-results hidden></ul>
			<p class="hp-box__status" aria-live="polite" data-hp-status></p>
			<ol class="hp-box__selected" data-hp-selected></ol>
			<p class="description"><?php esc_html_e( 'Shown as small cards at the end of the article. Each player also gets a tag with their name.', 'handballprospects' ); ?></p>
		</div>
		<?php
	}

	public static function save( int $postId, WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::FIELD ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $postId ) || ! current_user_can( 'edit_post', $postId ) ) {
			return;
		}

		$previous = array_column( Players::get( $postId ), 'id' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON; Players::sanitize() is the sanitizer.
		$players = Players::sanitize( json_decode( (string) wp_unslash( $_POST[ self::FIELD ] ?? '' ), true ) );
		Players::save( $postId, $players );

		// Bara de nyvalda: en tagg skribenten tagit bort ska inte komma tillbaka.
		$added = array_filter( $players, static fn ( array $player ): bool => ! in_array( $player['id'], $previous, true ) );
		if ( array() !== $added && is_object_in_taxonomy( $post->post_type, 'post_tag' ) ) {
			wp_set_post_tags( $postId, array_column( $added, 'name' ), true );
		}
	}

	public static function enqueue( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'post' !== get_current_screen()?->post_type ) {
			return;
		}

		$base = plugin_dir_url( HANDBALLPROSPECTS_FILE ) . 'assets/';
		wp_enqueue_style( 'handballprospects-editor', $base . 'editor.css', array(), HANDBALLPROSPECTS_VERSION );
		wp_enqueue_script( 'handballprospects-editor', $base . 'editor.js', array( 'jquery', 'tags-box' ), HANDBALLPROSPECTS_VERSION, true );
		wp_localize_script(
			'handballprospects-editor',
			'HandballProspectsEditor',
			array(
				'searchUrl' => rest_url( Rest::NAMESPACE . '/search' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'max'       => Players::MAX,
				'i18n'      => array(
					'searching' => __( 'Searching…', 'handballprospects' ),
					'noResults' => __( 'No players found.', 'handballprospects' ),
					'error'     => __( 'The search failed. Try again.', 'handballprospects' ),
					'full'      => __( 'The article already has the maximum number of players.', 'handballprospects' ),
					'moveUp'    => __( 'Move up', 'handballprospects' ),
					'moveDown'  => __( 'Move down', 'handballprospects' ),
					'remove'    => __( 'Remove', 'handballprospects' ),
					'empty'     => __( 'No players selected.', 'handballprospects' ),
				),
			)
		);
	}
}
