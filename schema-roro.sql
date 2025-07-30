/* -------------------------------------------------------------------------
 * 0. データベース（WP とは別 DB を使う場合のみ）
 * ---------------------------------------------------------------------- */
CREATE DATABASE IF NOT EXISTS `wp_roro_log`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_ja_0900_as_cs;
USE `wp_roro_log`;


/* -------------------------------------------------------------------------
 * 1. マスタ系
 * ---------------------------------------------------------------------- */

/* 1-1. 犬種マスタ */
CREATE TABLE IF NOT EXISTS roro_dog_breed (
  breed_id      INT UNSIGNED AUTO_INCREMENT,
  name          VARCHAR(64)  NOT NULL,
  category      CHAR(1)      NOT NULL,          -- A〜H
  size          VARCHAR(32),
  risk_profile  TEXT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (breed_id),
  UNIQUE KEY uk_name (name)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

/* 1-2. 施設（スーパータイプ） */
CREATE TABLE IF NOT EXISTS roro_facility (
  facility_id INT UNSIGNED AUTO_INCREMENT,
  name        VARCHAR(120) NOT NULL,
  category    ENUM('cafe','hospital','salon','park','hotel','school','store') NOT NULL,
  lat         DECIMAL(10,8) NOT NULL,
  lng         DECIMAL(11,8) NOT NULL,
  facility_pt POINT SRID 4326 NOT NULL /*!80000 INVISIBLE */,
  address     VARCHAR(191),
  phone       VARCHAR(32),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (facility_id),
  KEY idx_cat (category),
  SPATIAL INDEX spx (facility_pt)
) ENGINE=InnoDB;

/* 1-3. スポンサーマスタ（広告主） */
CREATE TABLE IF NOT EXISTS roro_sponsor (
  sponsor_id   INT UNSIGNED AUTO_INCREMENT,
  name         VARCHAR(120) NOT NULL,
  logo_url     VARCHAR(255) DEFAULT NULL,
  website_url  VARCHAR(255) DEFAULT NULL,
  status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (sponsor_id)
) ENGINE=InnoDB;

/* -------------------------------------------------------------------------
 * 2. 顧客＆認証関連
 * ---------------------------------------------------------------------- */

/* 2-1. 顧客 */
CREATE TABLE IF NOT EXISTS roro_customer (
  customer_id    INT UNSIGNED AUTO_INCREMENT,
  name           VARCHAR(80)  NOT NULL,
  email          VARCHAR(191) NOT NULL,
  auth_provider  ENUM('local','firebase','line','google','facebook') NOT NULL DEFAULT 'local',
  user_type      ENUM('free','premium','admin')                      NOT NULL DEFAULT 'free',
  consent_status ENUM('unknown','agreed','revoked')                  NOT NULL DEFAULT 'unknown',
  phone          VARCHAR(32),
  zipcode        CHAR(8),
  breed_id       INT UNSIGNED NOT NULL,
  birth_date     DATE,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id),
  UNIQUE KEY uk_email (email),
  KEY idx_zip (zipcode),
  KEY idx_auth_provider (auth_provider),
  CONSTRAINT fk_customer_breed
      FOREIGN KEY (breed_id) REFERENCES roro_dog_breed(breed_id)
) ENGINE=InnoDB;

/* 2-2. Firebase／ソーシャルログイン ID ↔ 顧客 ↔ WP ユーザー */
CREATE TABLE IF NOT EXISTS roro_identity (
  uid          VARCHAR(128) NOT NULL,
  customer_id  INT UNSIGNED NOT NULL,
  wp_user_id   BIGINT UNSIGNED NOT NULL,
  provider     ENUM('firebase','line','google','facebook') NOT NULL DEFAULT 'firebase',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (uid),
  UNIQUE KEY uk_customer (customer_id),
  UNIQUE KEY uk_user     (wp_user_id),
  CONSTRAINT fk_ident_customer
      FOREIGN KEY (customer_id) REFERENCES roro_customer(customer_id)
) ENGINE=InnoDB
  CHARACTER SET utf8mb4 COLLATE utf8mb4_ja_0900_as_cs;

/* 2-3. 通知設定（Phase 1.6 拡張） */
CREATE TABLE IF NOT EXISTS roro_notification_pref (
  customer_id        INT UNSIGNED PRIMARY KEY,
  email_on           TINYINT(1) DEFAULT 1,
  line_on            TINYINT(1) DEFAULT 1,
  fcm_on             TINYINT(1) DEFAULT 0,
  category_email_on  TINYINT(1) DEFAULT 1,
  category_push_on   TINYINT(1) DEFAULT 1,
  token_expires_at   TIMESTAMP NULL DEFAULT NULL,
  updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                     ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pref_customer
      FOREIGN KEY (customer_id) REFERENCES roro_customer(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB;


/* -------------------------------------------------------------------------
 * 3. コンテンツ系
 * ---------------------------------------------------------------------- */

/* 3-1. アドバイス */
CREATE TABLE IF NOT EXISTS roro_advice (
  advice_id  INT UNSIGNED AUTO_INCREMENT,
  title      VARCHAR(120) NOT NULL,
  body       MEDIUMTEXT   NOT NULL,
  category   CHAR(1)      NOT NULL,  -- A〜H
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (advice_id),
  KEY idx_cat (category)
) ENGINE=InnoDB;

/* 3-2. 施設サブタイプ（例：カフェ／病院） */
/* カフェ */
CREATE TABLE IF NOT EXISTS roro_facility_cafe (
  facility_id    INT UNSIGNED PRIMARY KEY,
  opening_hours  VARCHAR(191),
  pet_menu       TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_cafe_fac
      FOREIGN KEY (facility_id) REFERENCES roro_facility(facility_id) ON DELETE CASCADE
) ENGINE=InnoDB;
/* 病院 */
CREATE TABLE IF NOT EXISTS roro_facility_hospital (
  facility_id          INT UNSIGNED PRIMARY KEY,
  treatment_speciality VARCHAR(191),
  emergency            TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_hosp_fac
      FOREIGN KEY (facility_id) REFERENCES roro_facility(facility_id) ON DELETE CASCADE
) ENGINE=InnoDB;
/* ※ salon / park / hotel / school / store も同様に必要ならコピー */

/* 3-3. 施設レビュー */
CREATE TABLE IF NOT EXISTS roro_facility_review (
  review_id    BIGINT UNSIGNED AUTO_INCREMENT,
  facility_id  INT UNSIGNED,
  customer_id  INT UNSIGNED,
  rating       TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment      TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  KEY idx_fac (facility_id),
  CONSTRAINT fk_rev_fac
      FOREIGN KEY (facility_id) REFERENCES roro_facility(facility_id) ON DELETE CASCADE
) ENGINE=InnoDB;


/* -------------------------------------------------------------------------
 * 4. 投稿／レポート／写真
 * ---------------------------------------------------------------------- */

/* 4-1. 写真投稿（四半期パーティション） */
CREATE TABLE IF NOT EXISTS roro_photo (
  photo_id      BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id   INT UNSIGNED,
  breed_id      INT UNSIGNED,
  facility_id   INT UNSIGNED DEFAULT NULL,
  attachment_id BIGINT UNSIGNED NOT NULL,
  zipcode       CHAR(8),
  lat           DECIMAL(10,8),
  lng           DECIMAL(11,8),
  photo_pt      POINT SRID 4326 GENERATED ALWAYS AS (Point(lng,lat)) STORED,
  analysis_json JSON NULL,             -- AI 解析結果
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (photo_id, created_at),
  KEY idx_cust    (customer_id),
  KEY idx_breed   (breed_id),
  KEY idx_facility(facility_id),
  SPATIAL INDEX spx_photo (photo_pt),
  CONSTRAINT fk_photo_cust
      FOREIGN KEY (customer_id) REFERENCES roro_customer(customer_id) ON DELETE SET NULL,
  CONSTRAINT fk_photo_facility
      FOREIGN KEY (facility_id) REFERENCES roro_facility(facility_id) ON DELETE SET NULL
) ENGINE=InnoDB
PARTITION BY RANGE (TO_DAYS(created_at)) (
  PARTITION p2025q3 VALUES LESS THAN (TO_DAYS('2025-10-01')),
  PARTITION p2025q4 VALUES LESS THAN (TO_DAYS('2026-01-01')),
  PARTITION pFuture VALUES LESS THAN MAXVALUE
);

/* 4-2. レポート（JSON 列） */
CREATE TABLE IF NOT EXISTS roro_report (
  report_id   BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id INT UNSIGNED,
  content     JSON NOT NULL,
  breed_json  VARCHAR(64) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(content,'$.breed'))) STORED,
  age_month   INT         GENERATED ALWAYS AS (JSON_EXTRACT(content,'$.age_month')) STORED,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (report_id),
  KEY idx_breed_age (breed_json, age_month),
  CONSTRAINT fk_report_cust
      FOREIGN KEY (customer_id) REFERENCES roro_customer(customer_id) ON DELETE SET NULL
) ENGINE=InnoDB;


/* -------------------------------------------------------------------------
 * 5. ガチャ／広告／課金系
 * ---------------------------------------------------------------------- */

/* 5-1. 広告案件 */
CREATE TABLE IF NOT EXISTS roro_ad (
  ad_id      INT UNSIGNED AUTO_INCREMENT,
  sponsor_id INT UNSIGNED NOT NULL,
  title      VARCHAR(120) NOT NULL,
  content    TEXT,
  image_url  VARCHAR(255) DEFAULT NULL,
  start_date DATE,
  end_date   DATE,
  price      DECIMAL(10,2) DEFAULT 0,
  status     ENUM('draft','active','expired') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ad_id),
  KEY idx_sponsor (sponsor_id),
  CONSTRAINT fk_ad_sponsor
      FOREIGN KEY (sponsor_id) REFERENCES roro_sponsor(sponsor_id) ON DELETE CASCADE
) ENGINE=InnoDB;

/* 5-2. 広告クリック */
CREATE TABLE IF NOT EXISTS roro_ad_click (
  click_id    BIGINT UNSIGNED AUTO_INCREMENT,
  ad_id       INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NULL,
  clicked_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (click_id),
  KEY idx_ad_id (ad_id),
  CONSTRAINT fk_click_ad
      FOREIGN KEY (ad_id) REFERENCES roro_ad(ad_id) ON DELETE CASCADE,
  CONSTRAINT fk_click_customer
      FOREIGN KEY (customer_id) REFERENCES roro_customer(customer_id) ON DELETE SET NULL
) ENGINE=InnoDB;

/* 5-3. ガチャ履歴（月パーティション） */
CREATE TABLE IF NOT EXISTS roro_gacha_log (
  spin_id     BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id INT UNSIGNED,
  facility_id INT UNSIGNED,
  advice_id   INT UNSIGNED,
  prize_type  ENUM('facility','advice','ad') NOT NULL,
  price       DECIMAL(10,2) DEFAULT 0,
  sponsor_id  INT UNSIGNED DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (spin_id, created_at),
  KEY idx_cust_date (customer_id, created_at),
  CONSTRAINT fk_gacha_sponsor
      FOREIGN KEY (sponsor_id) REFERENCES roro_sponsor(sponsor_id) ON DELETE SET NULL
) ENGINE=InnoDB
PARTITION BY RANGE (TO_DAYS(created_at)) (
  PARTITION p2025q3 VALUES LESS THAN (TO_DAYS('2025-10-01')),
  PARTITION p2025q4 VALUES LESS THAN (TO_DAYS('2026-01-01')),
  PARTITION pFuture VALUES LESS THAN MAXVALUE
);

/* 5-4. 収益ログ */
CREATE TABLE IF NOT EXISTS roro_revenue (
  rev_id     BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id INT UNSIGNED,
  amount      DECIMAL(10,2) NOT NULL,
  source      ENUM('ad','affiliate','subscr') NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rev_id, created_at),
  KEY idx_src_date (source, created_at)
) ENGINE=InnoDB
PARTITION BY RANGE (TO_DAYS(created_at)) (
  PARTITION p2025q3 VALUES LESS THAN (TO_DAYS('2025-10-01')),
  PARTITION p2025q4 VALUES LESS THAN (TO_DAYS('2026-01-01')),
  PARTITION pMax  VALUES LESS THAN MAXVALUE
);

/* 5-5. 決済履歴 */
CREATE TABLE IF NOT EXISTS roro_payment (
  payment_id   BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id  INT UNSIGNED NULL,
  sponsor_id   INT UNSIGNED NULL,
  method       ENUM('credit','paypal','stripe','applepay','googlepay') NOT NULL,
  amount       DECIMAL(10,2) NOT NULL,
  status       ENUM('pending','succeeded','failed','refunded') NOT NULL DEFAULT 'pending',
  transaction_id VARCHAR(191) DEFAULT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (payment_id),
  KEY idx_customer_status (customer_id, status),
  CONSTRAINT fk_payment_customer
      FOREIGN KEY (customer_id) REFERENCES roro_customer(customer_id) ON DELETE CASCADE,
  CONSTRAINT fk_payment_sponsor
      FOREIGN KEY (sponsor_id) REFERENCES roro_sponsor(sponsor_id) ON DELETE CASCADE
) ENGINE=InnoDB;


/* -------------------------------------------------------------------------
 * 6. サポート系
 * ---------------------------------------------------------------------- */

/* 6-1. 課題マスタ */
CREATE TABLE IF NOT EXISTS roro_issue (
  issue_id    INT UNSIGNED AUTO_INCREMENT,
  name        VARCHAR(80) NOT NULL,
  description TEXT,
  priority    TINYINT UNSIGNED DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (issue_id),
  KEY idx_priority (priority)
) ENGINE=InnoDB;

/* 6-2. お問い合わせ */
CREATE TABLE IF NOT EXISTS roro_contact (
  contact_id  BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id INT UNSIGNED NULL,
  name        VARCHAR(120) NOT NULL,
  email       VARCHAR(191) NOT NULL,
  subject     VARCHAR(191) DEFAULT NULL,
  message     TEXT NOT NULL,
  status      ENUM('new','processing','closed') NOT NULL DEFAULT 'new',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (contact_id),
  KEY idx_contact_customer (customer_id),
  CONSTRAINT fk_contact_customer
      FOREIGN KEY (customer_id) REFERENCES roro_customer(customer_id) ON DELETE SET NULL
) ENGINE=InnoDB;

/* -------------------------------------------------------------------------
 * 7. イベント管理
 *  上記報告書で設計したイベント情報のデータモデルに基づき、イベント関連テーブルを追加する。
 *  各テーブルは独立した識別子を持ち、外部キーで関連付けられる。
 * ---------------------------------------------------------------------- */

/* 7-1. 情報源マスタ */
CREATE TABLE IF NOT EXISTS roro_event_source (
  source_id   INT UNSIGNED AUTO_INCREMENT,
  name        VARCHAR(50) NOT NULL,
  description TEXT,
  base_url    VARCHAR(255),
  notes       TEXT,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (source_id),
  UNIQUE KEY uk_event_source_name (name)
) ENGINE=InnoDB;

/* 7-2. 開催地（都道府県・市区町村など） */
CREATE TABLE IF NOT EXISTS roro_event_location (
  location_id   INT UNSIGNED AUTO_INCREMENT,
  prefecture    VARCHAR(50) DEFAULT NULL,
  city          VARCHAR(100) DEFAULT NULL,
  full_address  VARCHAR(255) DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (location_id),
  KEY idx_event_location (prefecture, city)
) ENGINE=InnoDB;

/* 7-3. 会場マスタ */
CREATE TABLE IF NOT EXISTS roro_event_venue (
  venue_id    INT UNSIGNED AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  location_id INT UNSIGNED DEFAULT NULL,
  address     VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (venue_id),
  KEY idx_event_venue_name (name),
  KEY idx_event_venue_location (location_id),
  CONSTRAINT fk_event_venue_location
      FOREIGN KEY (location_id) REFERENCES roro_event_location(location_id) ON DELETE SET NULL
) ENGINE=InnoDB;

/* 7-4. 主催者マスタ */
CREATE TABLE IF NOT EXISTS roro_event_organizer (
  organizer_id INT UNSIGNED AUTO_INCREMENT,
  name         VARCHAR(100) NOT NULL,
  description  TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (organizer_id),
  KEY idx_event_organizer_name (name)
) ENGINE=InnoDB;

/* 7-5. イベント */
CREATE TABLE IF NOT EXISTS roro_event (
  event_id     BIGINT UNSIGNED AUTO_INCREMENT,
  source_id    INT UNSIGNED NOT NULL,
  organizer_id INT UNSIGNED DEFAULT NULL,
  name         VARCHAR(255) NOT NULL,
  date_start   DATE DEFAULT NULL,
  date_end     DATE DEFAULT NULL,
  date_text    VARCHAR(50) DEFAULT NULL,
  location_id  INT UNSIGNED DEFAULT NULL,
  venue_id     INT UNSIGNED DEFAULT NULL,
  description  TEXT,
  url          VARCHAR(255) DEFAULT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id),
  KEY idx_event_source_date (source_id, date_start),
  KEY idx_event_date_range (date_start, date_end),
  KEY idx_event_location (location_id),
  KEY idx_event_venue (venue_id),
  CONSTRAINT fk_event_source
      FOREIGN KEY (source_id) REFERENCES roro_event_source(source_id) ON DELETE CASCADE,
  CONSTRAINT fk_event_organizer
      FOREIGN KEY (organizer_id) REFERENCES roro_event_organizer(organizer_id) ON DELETE SET NULL,
  CONSTRAINT fk_event_location
      FOREIGN KEY (location_id) REFERENCES roro_event_location(location_id) ON DELETE SET NULL,
  CONSTRAINT fk_event_venue
      FOREIGN KEY (venue_id) REFERENCES roro_event_venue(venue_id) ON DELETE SET NULL
) ENGINE=InnoDB;
