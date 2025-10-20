# Treatwell Venue Commands Documentation

This document provides comprehensive documentation for all commands related to venue management, import, export, and validation in the Treatwell system.

## Table of Contents

1. [Data Import Commands](#data-import-commands)
    - [Fetching Venues](#fetching-venues)
    - [Processing JSON Files](#processing-json-files)
    - [XML Conversion](#xml-conversion)
    - [Single Venue Processing](#single-venue-processing)
2. [Data Export Commands](#data-export-commands)
    - [Exporting to JSON](#exporting-to-json)
3. [Data Validation Commands](#data-validation-commands)
    - [Validating Venue Data](#validating-venue-data)
4. [Scraping Commands](#scraping-commands)
    - [Treatwell API Scraping](#treatwell-api-scraping)
    - [City Scraping](#city-scraping)
5. [Scheduling Commands](#scheduling-commands)

## Data Import Commands

### Fetching Venues

#### `venues:fetch` - Fetch venues from Treatwell API

This command fetches venue data from the Treatwell API and sitemap.

```bash
php artisan venues:fetch [options]
```

**Options:**
- `--fetch-api` - Fetch venues from the API
- `--fetch-sitemap` - Fetch venues from the sitemap
- `--save-to-db` - Save fetched venues to the database
- `--save-raw` - Save raw API response for later processing
- `--limit=N` - Limit the number of venues to fetch (default: 0, all)
- `--debug` - Display additional debug information

**Examples:**
```bash
php artisan venues:fetch --fetch-api --save-to-db
php artisan venues:fetch --fetch-sitemap --save-raw --limit=100
```

### Processing JSON Files

#### `json:process` - Process JSON files and import into database

This command processes JSON files containing venue data and imports them into the database.

```bash
php artisan json:process [directory] [options]
```

**Arguments:**
- `directory` - Directory containing JSON files to process (default: app/json)

**Options:**
- `--max-files=N` - Maximum number of files to process (default: 0, all)
- `--batch-size=N` - Number of files to process in a batch (default: 20)
- `--force` - Force processing even if files were already processed
- `--dry-run` - Run without saving to database
- `--debug` - Show debug output

**Examples:**
```bash
php artisan json:process storage/app/json --batch-size=50
php artisan json:process --max-files=10 --dry-run --debug
```

#### `json:process-all` - Process all JSON files in XML directory

This command processes all JSON files in the storage/app/xml directory.

```bash
php artisan json:process-all [options]
```

**Options:**
- `--batch-size=N` - Number of files to process in each batch (default: 25)
- `--max-files=N` - Maximum number of files to process (default: 0, all)
- `--clear-existing` - Clear existing data before importing
- `--force` - Force the operation without confirmation prompts

**Examples:**
```bash
php artisan json:process-all --batch-size=50
php artisan json:process-all --clear-existing --force
```

### XML Conversion

#### `venues:convert-xml` - Convert XML files to JSON format

This command converts XML files from the Treatwell API to JSON format, which can then be processed by the JSON processing commands.

```bash
php artisan venues:convert-xml [options]
```

**Options:**
- `--input=DIR` - Directory containing XML files (default: storage/app/xml)
- `--output=DIR` - Directory to save JSON files (default: storage/app/json)
- `--batch-size=N` - Number of files to process in a batch (default: 20)
- `--max-files=N` - Maximum number of files to process (default: 0, all)
- `--force` - Process files even if JSON output already exists

**Examples:**
```bash
php artisan venues:convert-xml
php artisan venues:convert-xml --input=storage/imports/xml --output=storage/imports/json
```

### Single Venue Processing

#### `venues:parse-url` - Parse a specific Treatwell API URL

This command parses a specific Treatwell API URL and saves all information.

```bash
php artisan venues:parse-url <url> [options]
```

**Arguments:**
- `url` - The API URL to parse

**Options:**
- `--debug` - Show debug output

**Examples:**
```bash
php artisan venues:parse-url "https://www.treatwell.lt/api/browse/salonai/kur-vilnius-lietuva/"
```

#### `venues:save-single` - Save a single venue from JSON data

This command saves a single venue from JSON data.

```bash
php artisan venues:save-single [options]
```

**Options:**
- `--file=FILE` - Path to JSON file
- `--venue-id=ID` - External ID of the venue to save

**Examples:**
```bash
php artisan venues:save-single --file=storage/app/json/venue_123.json
```

## Data Export Commands

### Exporting to JSON

#### `venues:export-json` - Export venues from database to JSON files

This command exports venues from the database to JSON files.

```bash
php artisan venues:export-json [options]
```

**Options:**
- `--output=DIR` - Directory to save exported JSON files (default: storage/app/json/exports)
- `--batch-size=N` - Number of venues to process in each batch (default: 50)
- `--max=N` - Maximum number of venues to export (default: 0, all)
- `--city=SLUG` - Filter venues by city slug
- `--procedure=SLUG` - Filter venues by procedure slug
- `--format=FORMAT` - Export format (individual = one file per venue, single = all venues in one file)
- `--pretty` - Format JSON output with indentation

**Examples:**
```bash
php artisan venues:export-json --format=single --pretty
php artisan venues:export-json --city=vilnius --procedure=haircut --max=100
```

## Data Validation Commands

### Validating Venue Data

#### `venues:validate` - Validate venue data and generate reports

This command validates venue data in the database and generates reports on potential issues.

```bash
php artisan venues:validate [options]
```

**Options:**
- `--report=FORMAT` - Report output format (console, json, or csv) (default: console)
- `--output=DIR` - Directory to save reports (default: storage/app/reports)
- `--batch-size=N` - Number of venues to process in each batch (default: 100)
- `--max=N` - Maximum number of venues to validate (default: 0, all)
- `--fix` - Attempt to fix common issues automatically

**Examples:**
```bash
php artisan venues:validate
php artisan venues:validate --report=json --fix
php artisan venues:validate -v --max=100 # Verbose output with venue details
```

## Scraping Commands

### Treatwell API Scraping

#### `scrape:treatwell-all` - Scrape all data from Treatwell API

This command scrapes all data from the Treatwell API.

```bash
php artisan scrape:treatwell-all [options]
```

**Options:**
- Various options to control the scraping process

### City Scraping

#### `scrape:all-cities` - Scrape all cities from Treatwell

This command scrapes all cities from Treatwell.

```bash
php artisan scrape:all-cities
```

#### `update:city-relationships` - Update city relationships

This command updates city relationships based on scraped data.

```bash
php artisan update:city-relationships
```

## Scheduling Commands

The following commands are scheduled to run automatically:

1. `venues:fetch --fetch-api --fetch-sitemap` - Weekly on Sundays at 01:00
2. `venues:validate --report=json --fix` - Weekly on Mondays at 03:00
3. `venues:export-json --format=single --pretty` - Monthly at 05:00

You can modify the schedule in `app/Console/Kernel.php` if needed.

## Common Workflows

### Complete Import Workflow

1. Fetch venues from API and sitemap:
   ```bash
   php artisan venues:fetch --fetch-api --fetch-sitemap --save-raw
   ```

2. Convert any XML files to JSON:
   ```bash
   php artisan venues:convert-xml --force
   ```

3. Process and import the JSON files:
   ```bash
   php artisan json:process-all
   ```

4. Validate and fix any issues:
   ```bash
   php artisan venues:validate --fix
   ```

### Export and Backup Workflow

1. Export all venues to a single JSON file:
   ```bash
   php artisan venues:export-json --format=single --pretty
   ```

2. Validate the data and generate a report:
   ```bash
   php artisan venues:validate --report=json
   ``` 