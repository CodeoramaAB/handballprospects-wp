<?php

namespace Codeorama\HandballProspects;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Editorns sökning går via sajtens egen REST-väg, så att token stannar på
 * servern och HandballProspects aldrig behöver svara en webbläsare (ingen
 * CORS). Bara den som får redigera inlägg når den.
 */
final class Rest {

	public const NAMESPACE = 'handballprospects/v1';

	public static function register(): void {
		add_action(
			'rest_api_init',
			static function (): void {
				register_rest_route(
					self::NAMESPACE,
					'/search',
					array(
						'methods'             => 'GET',
						'callback'            => array( self::class, 'search' ),
						'permission_callback' => static fn (): bool => current_user_can( 'edit_posts' ),
						'args'                => array(
							'q' => array(
								'type'              => 'string',
								'required'          => true,
								'minLength'         => 2,
								'maxLength'         => 100,
								'sanitize_callback' => 'sanitize_text_field',
							),
						),
					)
				);
			}
		);
	}

	public static function search( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$api = Api::fromConfig();
		if ( null === $api ) {
			return new WP_Error( 'handballprospects_not_configured', __( 'HandballProspects is not configured on this site.', 'handballprospects' ), array( 'status' => 503 ) );
		}

		$results = $api->search( (string) $request->get_param( 'q' ) );
		if ( is_wp_error( $results ) ) {
			return new WP_Error( 'handballprospects_unavailable', __( 'HandballProspects could not be reached. Try again in a moment.', 'handballprospects' ), array( 'status' => 502 ) );
		}

		$players = array();
		foreach ( $results as $card ) {
			$snapshot = Players::fromCard( (array) $card );
			if ( array() === $snapshot ) {
				continue;
			}

			$snapshot['meta']     = Card::metaLine( $snapshot );
			$snapshot['position'] = isset( $card['position'] ) ? sanitize_text_field( (string) $card['position'] ) : null;
			$players[]            = $snapshot;
		}

		return new WP_REST_Response( $players );
	}
}
