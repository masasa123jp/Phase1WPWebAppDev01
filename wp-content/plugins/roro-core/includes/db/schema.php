<?php
/**
 * RoRo Core – DB schema installer / upgrader
 *
 * Covers:
 *   • Core domain tables (breed, facility, advice …)
 *   • Identity mapping for social login (Google, X, Facebook, Microsoft, Yahoo, LINE)
 *   • Spatial & JSON generated-column indexes, RANGE partitions
 *   • InnoDB / utf8mb4_ja_0900_as_cs
 *
 * @package RoroCore
 */

namespace RoroCore\Db;
defined('ABSPATH') || exit;

use wpdb;

final class Schema {

	const VERSION = '1.3.0';                       // 🔄 bump on every change

	/** register_activation_hook() entry */
	public static function install(): void {
		global $wpdb;
		require_once ABSPATH.'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();    // utf8mb4_ja_0900_as_cs on Xserver
		$p       = $wpdb->prefix;

		/* =============================================================== *
		 * 1) dbDelta() – create/alter tables, columns, indexes (no FK/PART)
		 * =============================================================== */
$schema = <<<SQL
/* ---------- MASTER TABLES ---------- */
CREATE TABLE {$p}roro_dog_breed(
  breed_id     INT UNSIGNED AUTO_INCREMENT,
  name         VARCHAR(64)  NOT NULL,
  category     CHAR(1)      NOT NULL,
  size         VARCHAR(32),
  risk_profile TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (breed_id),
  UNIQUE KEY uk_breed_name(name)
) $charset ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

CREATE TABLE {$p}roro_facility(
  facility_id  INT UNSIGNED AUTO_INCREMENT,
  name         VARCHAR(120) NOT NULL,
  category     ENUM('cafe','hospital','salon','park','hotel','school','store') NOT NULL,
  lat          DECIMAL(10,8) NOT NULL,
  lng          DECIMAL(11,8) NOT NULL,
  address      VARCHAR(191),
  phone        VARCHAR(32),
  facility_pt  POINT SRID 4326 NOT NULL /*!80000 INVISIBLE */,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (facility_id),
  KEY idx_category(category),
  SPATIAL INDEX spx_fac_pt(facility_pt)
) $charset ENGINE=InnoDB;

CREATE TABLE {$p}roro_advice(
  advice_id   INT UNSIGNED AUTO_INCREMENT,
  title       VARCHAR(120) NOT NULL,
  body        MEDIUMTEXT   NOT NULL,
  category    CHAR(1)      NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(advice_id),
  KEY idx_cat(category)
) $charset ENGINE=InnoDB;

/* ---------- CUSTOMER & PREF ---------- */
CREATE TABLE {$p}roro_customer(
  customer_id INT UNSIGNED AUTO_INCREMENT,
  name        VARCHAR(80)  NOT NULL,
  email       VARCHAR(191) NOT NULL,
  phone       VARCHAR(32),
  zipcode     CHAR(8),
  breed_id    INT UNSIGNED NOT NULL,
  birth_date  DATE,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(customer_id),
  UNIQUE KEY uk_email(email),
  KEY idx_zip(zipcode),
  KEY idx_breed(breed_id)
) $charset ENGINE=InnoDB;

CREATE TABLE {$p}roro_notification_pref(
  customer_id INT UNSIGNED PRIMARY KEY,
  email_on    TINYINT(1) DEFAULT 1,
  line_on     TINYINT(1) DEFAULT 1,
  fcm_on      TINYINT(1) DEFAULT 0,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
             ON UPDATE CURRENT_TIMESTAMP
) $charset ENGINE=InnoDB;

/* ---------- SOCIAL IDENTITY ---------- */
CREATE TABLE {$p}roro_identity(
  uid          VARCHAR(128) NOT NULL,
  customer_id  INT UNSIGNED NOT NULL,
  wp_user_id   BIGINT UNSIGNED NOT NULL,
  idp          VARCHAR(40)  NOT NULL,              -- google.com / line / yahoo.com …
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(uid),
  UNIQUE KEY uk_customer(customer_id),
  UNIQUE KEY uk_wpuser(wp_user_id)
) $charset ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

/* ---------- LOG & CONTENT TABLES ---------- */
CREATE TABLE {$p}roro_photo(
  photo_id     BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id  INT UNSIGNED,
  breed_id     INT UNSIGNED,
  attachment_id BIGINT UNSIGNED NOT NULL,
  zipcode      CHAR(8),
  lat          DECIMAL(10,8),
  lng          DECIMAL(11,8),
  photo_pt     POINT SRID 4326 GENERATED ALWAYS AS
               (IF(lat IS NULL OR lng IS NULL,NULL,Point(lng,lat))) STORED,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(photo_id, created_at),
  KEY idx_cust(customer_id),
  KEY idx_breed(breed_id),
  SPATIAL INDEX spx_photo(photo_pt)
) $charset ENGINE=InnoDB;

CREATE TABLE {$p}roro_report(
  report_id    BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id  INT UNSIGNED,
  content      JSON NOT NULL,
  breed_json   VARCHAR(64) GENERATED ALWAYS AS
               (JSON_UNQUOTE(JSON_EXTRACT(content,'$.breed'))) STORED,
  age_month    INT GENERATED ALWAYS AS
               (JSON_EXTRACT(content,'$.age_month')) STORED,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(report_id),
  KEY idx_breed_age(breed_json, age_month),
  KEY idx_cust(customer_id)
) $charset ENGINE=InnoDB;

CREATE TABLE {$p}roro_gacha_log(
  spin_id      BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id  INT UNSIGNED,
  facility_id  INT UNSIGNED,
  advice_id    INT UNSIGNED,
  prize_type   ENUM('facility','advice') NOT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(spin_id, created_at),
  KEY idx_cust_date(customer_id, created_at)
) $charset ENGINE=InnoDB;

CREATE TABLE {$p}roro_facility_review(
  review_id    BIGINT UNSIGNED AUTO_INCREMENT,
  facility_id  INT UNSIGNED,
  customer_id  INT UNSIGNED,
  rating       TINYINT UNSIGNED NOT NULL CHECK(rating BETWEEN 1 AND 5),
  comment      TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(review_id),
  KEY idx_fac_rating(facility_id, rating)
) $charset ENGINE=InnoDB;

CREATE TABLE {$p}roro_revenue(
  rev_id      BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id INT UNSIGNED,
  amount      DECIMAL(10,2) NOT NULL,
  source      ENUM('ad','affiliate','subscr') NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(rev_id, created_at),
  KEY idx_source(source)
) $charset ENGINE=InnoDB;
SQL;

		dbDelta($schema);                              // ⚠ FK/PARTITION later

		/* =============================================================== *
		 * 2)  ADD FOREIGN KEYS  (dbDelta ignores them)
		 * =============================================================== */
		$fk = [
			"ALTER TABLE {$p}roro_customer
			   ADD CONSTRAINT fk_customer_breed
			   FOREIGN KEY(breed_id) REFERENCES {$p}roro_dog_breed(breed_id)
			   ON DELETE RESTRICT",
			"ALTER TABLE {$p}roro_notification_pref
			   ADD CONSTRAINT fk_pref_cust
			   FOREIGN KEY(customer_id) REFERENCES {$p}roro_customer(customer_id)
			   ON DELETE CASCADE",
			"ALTER TABLE {$p}roro_photo
			   ADD CONSTRAINT fk_photo_cust
			   FOREIGN KEY(customer_id) REFERENCES {$p}roro_customer(customer_id)
			   ON DELETE SET NULL",
			"ALTER TABLE {$p}roro_photo
			   ADD CONSTRAINT fk_photo_breed
			   FOREIGN KEY(breed_id) REFERENCES {$p}roro_dog_breed(breed_id)
			   ON DELETE SET NULL",
			"ALTER TABLE {$p}roro_facility_review
			   ADD CONSTRAINT fk_rev_fac FOREIGN KEY(facility_id)
			   REFERENCES {$p}roro_facility(facility_id) ON DELETE CASCADE",
			"ALTER TABLE {$p}roro_facility_review
			   ADD CONSTRAINT fk_rev_cust FOREIGN KEY(customer_id)
			   REFERENCES {$p}roro_customer(customer_id) ON DELETE SET NULL",
			"ALTER TABLE {$p}roro_identity
			   ADD CONSTRAINT fk_ident_cust FOREIGN KEY(customer_id)
			   REFERENCES {$p}roro_customer(customer_id) ON DELETE CASCADE",
			"ALTER TABLE {$p}roro_identity
			   ADD CONSTRAINT fk_ident_user FOREIGN KEY(wp_user_id)
			   REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE",
		];
		foreach ($fk as $q) { $wpdb->query($q); }

		/* =============================================================== *
		 * 3)  PARTITION large logs
		 * =============================================================== */
		$part = [
			"ALTER TABLE {$p}roro_photo
			   PARTITION BY RANGE (YEAR(created_at)) (
			     PARTITION p2025 VALUES LESS THAN (2026),
			     PARTITION pmax  VALUES LESS THAN MAXVALUE
			   )",
			"ALTER TABLE {$p}roro_gacha_log
			   PARTITION BY RANGE (YEAR(created_at)) (
			     PARTITION p2025 VALUES LESS THAN (2026),
			     PARTITION pmax  VALUES LESS THAN MAXVALUE
			   )",
			"ALTER TABLE {$p}roro_revenue
			   PARTITION BY RANGE (YEAR(created_at)) (
			     PARTITION p2025 VALUES LESS THAN (2026),
			     PARTITION pmax  VALUES LESS THAN MAXVALUE
			   )",
		];
		foreach ($part as $q) { $wpdb->query($q); }

		update_option('roro_schema_version', self::VERSION);
		flush_rewrite_rules();
	}
}
