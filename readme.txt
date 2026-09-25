=== HandballProspects – Följ spelarna ===
Contributors: codeorama
Tags: handball, sports, players
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.1.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Link the players in an article to their HandballProspects profiles and show them as small cards below the article.

== Description ==

A box in the Classic Editor lets the writer search HandballProspects by name and pick the players the article mentions. Each pick also adds a tag with the player's name. Below the article, a "Follow the players" row shows one small card per player – name, age and club – that links to the player's profile on HandballProspects in the site's language.

Cards are fetched from HandballProspects and cached for six hours, so a transfer shows up without touching the article. If HandballProspects cannot be reached, the cards fall back to what was known when the players were picked.

== Installation ==

1. Install and activate the plugin.
2. Go to Settings → HandballProspects and paste the site's partner token. The page shows whether HandballProspects accepts it.

Until a token is saved, writers do not see the player box; administrators see a reminder with a link to the settings. The token can also be set in `wp-config.php` with `define( 'HANDBALLPROSPECTS_TOKEN', '…' );`, which takes precedence over the settings page.

The token never leaves the server: the editor searches through the site's own REST route.

== Changelog ==

= 0.1.2 =
* Profile links always open in the site's language (`lang=`), also for cards saved before HandballProspects added the language itself.

= 0.1.1 =
* Settings page for the site token (Settings → HandballProspects) with a connection check; the player box stays hidden from writers until a token is saved.

= 0.1.0 =
* First version: player search in the Classic Editor, automatic tags and the "Follow the players" cards.
