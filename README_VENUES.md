# Treatwell Venues Fetcher

This tool allows you to fetch venue data from Treatwell Lithuania using their API and XML sitemaps.

## Features

- Fetches venues from the Treatwell API with customizable city and procedure filters
- Extracts venue data from XML sitemaps
- Processes treatment location data to extract additional venue information
- Combines data from multiple sources, merging duplicates
- Provides detailed statistics about the fetching process
- Saves venue data as JSON files with summary reports

## Installation

The venues fetcher is part of the Laravel application. Make sure you have set up the Laravel environment correctly.

## Usage

Use the `venues:fetch` Artisan command to fetch venue data. The command has the following options:

```bash
php artisan venues:fetch [options]
```

### Options

- `--fetch-api`: Fetch venues from the Treatwell API
- `--fetch-sitemap`: Fetch venues from Treatwell XML sitemaps
- `--city=CITY`: Filter by specific city (default: all cities)
- `--procedure=PROCEDURE`: Filter by specific procedure (default: all procedures)
- `--debug`: Show detailed debug output
- `--max-pages=N`: Maximum number of pages to fetch from API (default: 50)
- `--sitemap-url=URL`: Base URL for sitemap (default: https://www.treatwell.lt/site-map-venues-treatment-location-%d.xml)
- `--max-sitemaps=N`: Maximum number of sitemaps to fetch (default: 3)

### Examples

Fetch venues from API for all cities and procedures:
```bash
php artisan venues:fetch --fetch-api
```

Fetch venues from sitemap:
```bash
php artisan venues:fetch --fetch-sitemap
```

Fetch venues from both API and sitemap:
```bash
php artisan venues:fetch --fetch-api --fetch-sitemap
```

Fetch venues for a specific city:
```bash
php artisan venues:fetch --fetch-api --city=vilnius-lt
```

Fetch venues for a specific procedure:
```bash
php artisan venues:fetch --fetch-api --procedure=masazas
```

## Output

The command will save venue data to the `storage/app/venues` directory as JSON files with timestamps:

- `venues_YYYY-MM-DD_HHmmss.json`: Contains all the venue data
- `summary_YYYY-MM-DD_HHmmss.json`: Contains summary statistics

## Available Cities

- `vilnius-lt` (Vilnius)
- `kaunas-lt` (Kaunas)
- `klaipeda-lt` (Klaipėda)
- `siauliai-lt` (Šiauliai)
- `panevezys-lt` (Panevėžys)

## Available Procedures

- `kirpimas` (Haircut)
- `balayage-dazymas` (Balayage coloring)
- `plauku-gydymas` (Hair treatment)
- `masazas` (Massage)
- `veido-valymas` (Facial cleansing)
- `depiliacija-vaskavimas` (Waxing)
- `pedikiuras` (Pedicure)
- `manikiuras` (Manicure)

## Troubleshooting

If you encounter issues with the command:

1. Check that your internet connection is working
2. Verify that the Treatwell API and sitemaps are accessible
3. Use the `--debug` option to see more detailed information
4. Check the Laravel logs for additional error details

## Contributing

To contribute to this tool, add new features or fix bugs, please follow the standard Laravel development workflow. 