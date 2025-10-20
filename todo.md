# TODO

## High Priority
- [ ] Gate the admin routes behind authentication or authorization so only trusted operators can trigger scraping commands. 【F:routes/web.php†L18-L35】
- [ ] Replace the raw `exec` background execution with queued Artisan jobs or Symfony Process to prevent command injection and capture failures when launching scrape commands. 【F:app/Http/Controllers/AdminController.php†L34-L83】【F:app/Services/CommandService.php†L5-L16】
- [ ] Ensure the `scrape:all-cities` command forwards the `--limit-pages` option to `scrape:treatwell-all` so page limits are respected. 【F:app/Console/Commands/ScrapeAllCities.php†L15-L116】
- [ ] Update the venue detail routing so dashboard and admin "View" links supply the slug expected by `venues.show`, preventing 404s when following those shortcuts. 【F:routes/web.php†L20-L23】【F:resources/views/admin/index.blade.php†L82-L111】【F:resources/views/dashboard.blade.php†L34-L70】
- [ ] Replace `VenueService::matchVenuesWithCities` attaching every venue to every city with matching that uses each venue's real location metadata. 【F:app/Services/VenueService.php†L33-L67】
- [ ] Teach `scrape:treatwell-all` to honor a `--limit-pages` cap so the forwarded option actually short-circuits pagination per city. 【F:app/Console/Commands/ScrapeTreatwellAll.php†L23-L123】

## Medium Priority
- [ ] Move recurring database lookups out of Blade templates (e.g., navbar city dropdown) into controllers or view composers to avoid running queries during view rendering. 【F:resources/views/layouts/app.blade.php†L20-L40】
- [ ] Populate the admin city's select list from the database instead of hard-coded options to keep the UI in sync with stored locations. 【F:resources/views/admin/index.blade.php†L22-L53】
- [ ] Consolidate setup documentation into a root README that references the specialized venue guides and clarifies environment prerequisites. 【F:README_VENUES.md†L1-L75】【F:README_VENUES_COMMANDS.md†L1-L92】【F:README_VENUES_UNLIMITED.md†L1-L74】
- [ ] Fix the rating sort branches in `VenueController::byCity` so ordering happens on joined rating data instead of a no-op `orderBy` inside `whereHas`. 【F:app/Http/Controllers/VenueController.php†L58-L114】
- [ ] Load the scrape city list from persisted `cities` data (with sensible fallbacks) instead of the hard-coded array in `ScrapeAllCities`. 【F:app/Console/Commands/ScrapeAllCities.php†L23-L101】
- [ ] Convert the static `City::scopeWithMostVenues` to a conventional scope and ensure it eager loads counts consistently across drivers. 【F:app/Models/City.php†L55-L86】

## Low Priority
- [ ] Surface background scraping status (e.g., queued job progress or log links) in the admin UI so operators can tell when tasks finish or fail. 【F:resources/views/admin/index.blade.php†L22-L143】【F:app/Http/Controllers/AdminController.php†L34-L83】
- [ ] Audit the front-end asset pipeline—currently Bootstrap CDN plus Tailwind/Vite dev dependencies—to remove unused packages or document the expected styling workflow. 【F:resources/views/layouts/app.blade.php†L6-L71】【F:package.json†L1-L15】
- [ ] Persist any newly discovered cities from Treatwell responses into the database so subsequent scrapes and UIs stay in sync. 【F:app/Console/Commands/ScrapeAllCities.php†L40-L86】
- [ ] Replace the blocking `sleep` calls in scraper commands with configurable rate limiting or queued job pacing so long runs do not stall the worker. 【F:app/Console/Commands/ScrapeTreatwellAll.php†L71-L120】
