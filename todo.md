# TODO

## High Priority
- [ ] Gate the admin routes behind authentication or authorization so only trusted operators can trigger scraping commands. 【F:routes/web.php†L18-L35】
- [ ] Replace the raw `exec` background execution with queued Artisan jobs or Symfony Process to prevent command injection and capture failures when launching scrape commands. 【F:app/Http/Controllers/AdminController.php†L34-L83】【F:app/Services/CommandService.php†L5-L16】
- [ ] Ensure the `scrape:all-cities` command forwards the `--limit-pages` option to `scrape:treatwell-all` so page limits are respected. 【F:app/Console/Commands/ScrapeAllCities.php†L15-L116】

## Medium Priority
- [ ] Move recurring database lookups out of Blade templates (e.g., navbar city dropdown) into controllers or view composers to avoid running queries during view rendering. 【F:resources/views/layouts/app.blade.php†L20-L40】
- [ ] Populate the admin city's select list from the database instead of hard-coded options to keep the UI in sync with stored locations. 【F:resources/views/admin/index.blade.php†L22-L53】
- [ ] Consolidate setup documentation into a root README that references the specialized venue guides and clarifies environment prerequisites. 【F:README_VENUES.md†L1-L75】【F:README_VENUES_COMMANDS.md†L1-L92】【F:README_VENUES_UNLIMITED.md†L1-L74】

## Low Priority
- [ ] Surface background scraping status (e.g., queued job progress or log links) in the admin UI so operators can tell when tasks finish or fail. 【F:resources/views/admin/index.blade.php†L22-L143】【F:app/Http/Controllers/AdminController.php†L34-L83】
- [ ] Audit the front-end asset pipeline—currently Bootstrap CDN plus Tailwind/Vite dev dependencies—to remove unused packages or document the expected styling workflow. 【F:resources/views/layouts/app.blade.php†L6-L71】【F:package.json†L1-L15】
