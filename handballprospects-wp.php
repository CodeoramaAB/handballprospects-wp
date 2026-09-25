<?php
/**
 * Plugin Name:       HandballProspects – Följ spelarna
 * Plugin URI:        https://github.com/CodeoramaAB/handballprospects-wp
 * Description:       Koppla spelarna i en artikel till deras profiler på HandballProspects och visa dem som små kort under artikeln.
 * Version:           0.1.2
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Codeorama AB
 * Author URI:        https://handballprospects.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       handballprospects
 * Domain Path:       /languages
 *
 * Sajtens partnertoken fylls i under Inställningar → HandballProspects.
 * Valfritt i wp-config.php (vinner över inställningssidan):
 *   define( 'HANDBALLPROSPECTS_TOKEN', '…' );
 *   define( 'HANDBALLPROSPECTS_API_URL', '…' );   // förval https://handballprospects.com/api/v1
 */

defined( 'ABSPATH' ) || exit;

define( 'HANDBALLPROSPECTS_VERSION', '0.1.2' );
define( 'HANDBALLPROSPECTS_FILE', __FILE__ );

require_once __DIR__ . '/src/Settings.php';
require_once __DIR__ . '/src/Api.php';
require_once __DIR__ . '/src/Players.php';
require_once __DIR__ . '/src/Card.php';
require_once __DIR__ . '/src/Rest.php';
require_once __DIR__ . '/src/MetaBox.php';
require_once __DIR__ . '/src/Frontend.php';

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'handballprospects', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

Codeorama\HandballProspects\Settings::register();
Codeorama\HandballProspects\Players::register();
Codeorama\HandballProspects\Rest::register();
Codeorama\HandballProspects\MetaBox::register();
Codeorama\HandballProspects\Frontend::register();

// Uppdateringar direkt från GitHub-releaserna (publikt repo, ingen token).
require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/CodeoramaAB/handballprospects-wp/',
	__FILE__,
	'handballprospects-wp'
);
