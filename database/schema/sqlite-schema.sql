CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "procedures"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "procedures_slug_unique" on "procedures"("slug");
CREATE TABLE IF NOT EXISTS "venue_procedure"(
  "id" integer primary key autoincrement not null,
  "venue_id" integer not null,
  "procedure_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("venue_id") references "venues_old"("id") on delete cascade,
  foreign key("procedure_id") references "procedures"("id") on delete cascade
);
CREATE UNIQUE INDEX "venue_procedure_venue_id_procedure_id_unique" on "venue_procedure"(
  "venue_id",
  "procedure_id"
);
CREATE TABLE IF NOT EXISTS "locations"(
  "id" integer primary key autoincrement not null,
  "venue_id" integer not null,
  "city_id" integer,
  "postal_code" varchar,
  "address_line1" text,
  "address_line2" text,
  "latitude" numeric,
  "longitude" numeric,
  "map_zoom" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("venue_id") references "venues_old"("id"),
  foreign key("city_id") references "cities"("id")
);
CREATE TABLE IF NOT EXISTS "images"(
  "id" integer primary key autoincrement not null,
  "venue_id" integer not null,
  "external_id" varchar,
  "uri_small" varchar,
  "uri_medium" varchar,
  "uri_large" varchar,
  "uri_xlarge" varchar,
  "is_primary" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("venue_id") references "venues_old"("id")
);
CREATE TABLE IF NOT EXISTS "opening_hours"(
  "id" integer primary key autoincrement not null,
  "venue_id" integer not null,
  "day_of_week" varchar not null,
  "opening_time" time,
  "closing_time" time,
  "is_open" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("venue_id") references "venues_old"("id")
);
CREATE TABLE IF NOT EXISTS "ratings"(
  "id" integer primary key autoincrement not null,
  "venue_id" integer not null,
  "weighted_average" numeric,
  "count" integer not null default '0',
  "cleanliness_avg" numeric,
  "cleanliness_count" integer not null default '0',
  "staff_avg" numeric,
  "staff_count" integer not null default '0',
  "atmosphere_avg" numeric,
  "atmosphere_count" integer not null default '0',
  "display_average" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("venue_id") references "venues_old"("id")
);
CREATE TABLE IF NOT EXISTS "countries"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "code" varchar not null,
  "normalised_name" varchar,
  "active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "countries_code_unique" on "countries"("code");
CREATE TABLE IF NOT EXISTS "cities"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "subregion" varchar,
  "is_main_city" tinyint(1) not null default '0',
  "main_city_id" integer,
  foreign key("main_city_id") references "cities"("id")
);
CREATE UNIQUE INDEX "cities_slug_unique" on "cities"("slug");
CREATE TABLE IF NOT EXISTS "venues"(
  "id" integer primary key autoincrement not null,
  "name" varchar,
  "slug" varchar not null,
  "url" varchar not null,
  "source" varchar not null default 'sitemap2',
  "created_at" datetime,
  "updated_at" datetime,
  "description" text,
  "external_id" varchar,
  "raw_data" text,
  "type_id" varchar,
  "type_name" varchar,
  "normalised_name" varchar,
  "desktop_uri" varchar,
  "mobile_uri" varchar,
  "app_uri" varchar,
  "is_new_venue" tinyint(1) not null default '0'
);
CREATE TABLE IF NOT EXISTS "city_venue"(
  "id" integer primary key autoincrement not null,
  "city_id" integer not null,
  "venue_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "city_venue_city_id_venue_id_unique" on "city_venue"(
  "city_id",
  "venue_id"
);
CREATE TABLE IF NOT EXISTS "procedure_venue"(
  "id" integer primary key autoincrement not null,
  "procedure_id" integer not null,
  "venue_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "procedure_venue_procedure_id_venue_id_unique" on "procedure_venue"(
  "procedure_id",
  "venue_id"
);
CREATE TABLE IF NOT EXISTS "city_procedure"(
  "id" integer primary key autoincrement not null,
  "city_id" integer not null,
  "procedure_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("city_id") references "cities"("id") on delete cascade,
  foreign key("procedure_id") references "procedures"("id") on delete cascade
);
CREATE UNIQUE INDEX "city_procedure_city_id_procedure_id_unique" on "city_procedure"(
  "city_id",
  "procedure_id"
);
CREATE TABLE IF NOT EXISTS "sitemap_urls"(
  "id" integer primary key autoincrement not null,
  "original_url" varchar not null,
  "path" varchar,
  "browse_uri" varchar,
  "treatment_slug" varchar,
  "treatment_name" varchar,
  "offer_type_slug" varchar,
  "offer_type_name" varchar,
  "location_slug" varchar,
  "location_name" varchar,
  "is_processed" tinyint(1) not null default '0',
  "is_valid" tinyint(1) not null default '1',
  "venues_found" integer not null default '0',
  "api_requests" integer not null default '0',
  "pages_processed" integer not null default '0',
  "last_processed_at" datetime,
  "downloaded_at" datetime,
  "api_response" text,
  "error_message" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "sitemap_urls_is_processed_index" on "sitemap_urls"(
  "is_processed"
);
CREATE INDEX "sitemap_urls_is_valid_index" on "sitemap_urls"("is_valid");
CREATE INDEX "sitemap_urls_treatment_slug_location_slug_index" on "sitemap_urls"(
  "treatment_slug",
  "location_slug"
);
CREATE UNIQUE INDEX "sitemap_urls_original_url_unique" on "sitemap_urls"(
  "original_url"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2023_11_15_000001_create_venues_table',1);
INSERT INTO migrations VALUES(5,'2023_11_15_000002_create_procedures_table',1);
INSERT INTO migrations VALUES(6,'2023_11_15_000003_create_cities_table',1);
INSERT INTO migrations VALUES(7,'2023_11_15_000004_create_relationships_tables',1);
INSERT INTO migrations VALUES(8,'2025_04_23_121849_create_locations_table',1);
INSERT INTO migrations VALUES(9,'2025_04_23_121851_create_images_table',1);
INSERT INTO migrations VALUES(10,'2025_04_23_121852_create_opening_hours_table',1);
INSERT INTO migrations VALUES(11,'2025_04_23_121853_create_treatments_table',1);
INSERT INTO migrations VALUES(12,'2025_04_23_121854_create_ratings_table',1);
INSERT INTO migrations VALUES(13,'2025_04_23_121857_create_countries_table',1);
INSERT INTO migrations VALUES(14,'2025_04_23_143144_add_subregion_to_cities_table',1);
INSERT INTO migrations VALUES(15,'2025_04_23_143647_add_subregion_to_cities_table',1);
INSERT INTO migrations VALUES(16,'2025_04_24_124146_add_missing_columns_to_venues_table',1);
INSERT INTO migrations VALUES(17,'2025_04_24_124303_modify_source_column_in_venues_table',1);
INSERT INTO migrations VALUES(18,'2025_04_24_125131_create_complete_venue_related_tables',2);
INSERT INTO migrations VALUES(19,'2025_04_24_124344_create_city_venue_table',3);
INSERT INTO migrations VALUES(20,'2025_04_24_124429_fix_venue_city_relationship',3);
INSERT INTO migrations VALUES(21,'2025_04_24_124554_fix_missing_tables',3);
INSERT INTO migrations VALUES(22,'2025_04_24_131059_create_venue_relationships_tables',3);
INSERT INTO migrations VALUES(23,'2025_04_25_000001_create_treatwell_relationships_tables',3);
INSERT INTO migrations VALUES(24,'2025_04_25_000002_create_procedures_table',3);
INSERT INTO migrations VALUES(25,'2025_04_25_000003_add_slug_to_cities_table',3);
INSERT INTO migrations VALUES(26,'2025_04_24_150110_create_sitemap_urls_table',4);
