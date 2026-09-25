<?php

namespace Codeorama\HandballProspects;

defined( 'ABSPATH' ) || exit;

/**
 * Inställningar → HandballProspects: sajtens partnertoken. Den sparas i en
 * option och visas aldrig igen (bara de sista tecknen). En konstant i
 * wp-config.php vinner fortfarande, för den som hellre håller den där.
 */
final class Settings {

	public const PAGE = 'handballprospects';

	public const OPTION = 'handballprospects_token';

	private const GROUP = 'handballprospects';

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( HANDBALLPROSPECTS_FILE ), array( self::class, 'actionLinks' ) );
	}

	/** Den token pluginet använder, eller tom sträng när ingen finns. */
	public static function token(): string {
		if ( self::tokenFromConfig() ) {
			return (string) HANDBALLPROSPECTS_TOKEN;
		}

		return (string) get_option( self::OPTION, '' );
	}

	public static function isConfigured(): bool {
		return '' !== self::token();
	}

	public static function url(): string {
		return admin_url( 'options-general.php?page=' . self::PAGE );
	}

	public static function menu(): void {
		add_options_page(
			__( 'HandballProspects', 'handballprospects' ),
			__( 'HandballProspects', 'handballprospects' ),
			'manage_options',
			self::PAGE,
			array( self::class, 'render' )
		);
	}

	public static function settings(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'string',
				'default'           => '',
				'show_in_rest'      => false,
				'sanitize_callback' => array( self::class, 'sanitize' ),
			)
		);
	}

	/**
	 * Formulärets token → det som sparas. Tomt fält behåller den sparade (den
	 * visas aldrig, så fältet är alltid tomt), kryssrutan tar bort den.
	 */
	public static function sanitize( mixed $input ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- options.php verifies the settings nonce.
		$clear = ! empty( $_POST['handballprospects_clear_token'] );

		return self::resolveToken( is_string( $input ) ? $input : '', (string) get_option( self::OPTION, '' ), $clear );
	}

	public static function resolveToken( string $input, string $existing, bool $clear ): string {
		if ( $clear ) {
			return '';
		}

		$token = preg_replace( '/[^A-Za-z0-9._-]/', '', trim( $input ) ) ?? '';

		return '' === $token ? $existing : $token;
	}

	/** "…a5b3": nog för att känna igen vilken token som sitter, inget mer. */
	public static function hint( string $token ): string {
		return '' === $token ? '' : '…' . substr( $token, -4 );
	}

	/**
	 * @param  array<string, string> $links
	 * @return array<string, string>
	 */
	public static function actionLinks( array $links ): array {
		return array( 'settings' => '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'handballprospects' ) . '</a>' ) + $links;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$token      = self::token();
		$fromConfig = self::tokenFromConfig();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'HandballProspects', 'handballprospects' ); ?></h1>
			<p><?php esc_html_e( 'Link the players in your articles to their profiles on HandballProspects. The token identifies this site; you get it from HandballProspects.', 'handballprospects' ); ?></p>

			<?php self::renderStatus( $token ); ?>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="handballprospects-token"><?php esc_html_e( 'Token', 'handballprospects' ); ?></label></th>
						<td>
							<?php if ( $fromConfig ) : ?>
								<p><?php esc_html_e( 'Set in wp-config.php (HANDBALLPROSPECTS_TOKEN), which takes precedence over this page.', 'handballprospects' ); ?> <code><?php echo esc_html( self::hint( $token ) ); ?></code></p>
							<?php else : ?>
								<input type="password" id="handballprospects-token" name="<?php echo esc_attr( self::OPTION ); ?>" class="regular-text code" autocomplete="off" spellcheck="false"
									placeholder="<?php echo esc_attr( '' === $token ? __( 'Paste the token here', 'handballprospects' ) : sprintf( /* translators: %s: last characters of the saved token */ __( 'Saved (ends with %s) – leave empty to keep', 'handballprospects' ), self::hint( $token ) ) ); ?>">
								<?php if ( '' !== $token ) : ?>
									<p><label><input type="checkbox" name="handballprospects_clear_token" value="1"> <?php esc_html_e( 'Remove the saved token', 'handballprospects' ); ?></label></p>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php
				if ( ! $fromConfig ) {
					submit_button();
				}
				?>
			</form>
		</div>
		<?php
	}

	private static function renderStatus( string $token ): void {
		if ( '' === $token ) {
			$class   = 'notice-warning';
			$message = __( 'No token yet: the player box is hidden in the editor until one is saved.', 'handballprospects' );
		} else {
			$check = Api::fromConfig()?->check();
			if ( true === $check ) {
				$class   = 'notice-success';
				$message = __( 'Connected: HandballProspects accepts the token.', 'handballprospects' );
			} elseif ( is_wp_error( $check ) && 401 === ( $check->get_error_data()['status'] ?? null ) ) {
				$class   = 'notice-error';
				$message = __( 'HandballProspects does not recognise the token. Check that it was pasted in full.', 'handballprospects' );
			} else {
				$class   = 'notice-error';
				$message = __( 'HandballProspects could not be reached. The cards show what was known when the players were picked until it answers again.', 'handballprospects' );
			}
		}

		printf( '<div class="notice inline %s"><p>%s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	private static function tokenFromConfig(): bool {
		return defined( 'HANDBALLPROSPECTS_TOKEN' ) && '' !== (string) HANDBALLPROSPECTS_TOKEN;
	}
}
