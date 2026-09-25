=== HandballProspects – Följ spelarna ===
Contributors: codeorama
Tags: handball, sports, players
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Link the players in an article to their HandballProspects profiles and show them as small cards below the article.

== Description ==

A box in the Classic Editor lets the writer search HandballProspects by name and pick the players the article mentions. Each pick also adds a tag with the player's name. Below the article, a "Follow the players" row shows one small card per player – name, age and club – that links to the player's profile on HandballProspects in the site's language.

Cards are fetched from HandballProspects and cached for six hours, so a transfer shows up without touching the article. If HandballProspects cannot be reached, the cards fall back to what was known when the players were picked.

== Installation ==

1. Install and activate the plugin.
2. Add the site's partner token to `wp-config.php`:

    define( 'HANDBALLPROSPECTS_TOKEN', '…' );

The token never leaves the server: the editor searches through the site's own REST route.

== Changelog ==

= 0.1.0 =
* First version: player search in the Classic Editor, automatic tags and the "Follow the players" cards.
