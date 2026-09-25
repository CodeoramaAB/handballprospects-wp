# HandballProspects – Följ spelarna

WordPress-plugin för Handbollskanalen och GoHandball. Skribenten kopplar artikelns spelare till deras profiler på [HandballProspects](https://handballprospects.com), och under artikeln visas de som små kort ("Följ spelarna" / "Follow the players").

HP-sidan (partner-API:t `/api/v1`) bor i `handball-scraper`, se PRO-137.

## Så fungerar det

- **Editorn (Classic Editor):** en ruta under texten. Skribenten söker på namn och väljer spelare. Ordningen går att ändra och spelare går att ta bort. En nyvald spelare läggs också som tagg: taggrutan fylls direkt, och sparningen lägger till taggen om skriptet inte hann. En tagg som skribenten själv tagit bort läggs inte tillbaka.
- **Sökningen** går via sajtens egen REST-väg `handballprospects/v1/search` (kräver `edit_posts`). Token stannar på servern.
- **Lagring:** post meta `_handballprospects_players`, som innehåller HP-id plus en ögonblicksbild av kortet (namn, ålder, födelseår, klubb, länk).
- **Frontend:** `the_content` får "Följ spelarna" sist, med kort på två rader och horisontell scroll-snap. Korten hämtas i ett anrop och cachas 6 h. Om HP inte svarar visas ögonblicksbilderna.
  - En spelare som slagits ihop på HP får sitt nya id i artikeln.
  - En spelare som tagits bort ur HP visas inte.
- **Språk:** sajtens locale styr både pluginets texter (engelska som källspråk, svensk översättning i `languages/`) och profillänkarnas `?lang=`.

## Konfiguration

Inställningar → HandballProspects: klistra in sajtens token. Sidan testar anslutningen och visar "Ansluten", "känner inte igen token" eller "svarar inte".
- En sparad token visas aldrig igen, bara de sista fyra tecknen.
- Ett tomt fält behåller den sparade token, och kryssrutan tar bort den.
- Utan token ser skribenterna ingen spelarruta. Administratörer ser i stället en påminnelse med länk till inställningssidan.

Den som hellre vill ha token i `wp-config.php` kan lägga den där. Den vinner då över inställningssidan:

```php
define( 'HANDBALLPROSPECTS_TOKEN', '…' );
define( 'HANDBALLPROSPECTS_API_URL', 'https://handballprospects.com/api/v1' ); // valfri
```

Anropa alltid `handballprospects.com`, inte Clouds vanity-adress: profillänkarna följer adressen som anropas.

## Utveckling

```sh
composer install
vendor/bin/phpunit                # enhetstester för den rena logiken
npx @wordpress/env start          # WordPress 6.8 + Classic Editor på http://localhost:8888 (admin/password)
```

Token för den lokala miljön läggs in under Inställningar → HandballProspects (http://localhost:8888/wp-admin, admin/password).

## Release

Uppdateringar hämtas från GitHub-releaserna med [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) (v5.7, inkluderad i repot):

1. Höj `Version` i `handballprospects-wp.php`, `HANDBALLPROSPECTS_VERSION` och `Stable tag` i `readme.txt`.
2. Uppdatera `== Changelog ==`.
3. Tagga `vX.Y.Z` och skapa en release på GitHub.

Sajterna ser uppdateringen under Tillägg. Zip-filen är GitHubs egen, och `.gitattributes` håller ute tester och utvecklingsfiler.
