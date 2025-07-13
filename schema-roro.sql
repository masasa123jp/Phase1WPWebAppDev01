/* ---- 0. データベース作成（WP とは別 DB を使う場合のみ実行） ---- */
CREATE DATABASE IF NOT EXISTS `wp_roro_log`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_ja_0900_as_cs;
USE `wp_roro_log`;

/* ---- 1. 犬種マスタ ---- */
CREATE TABLE IF NOT EXISTS roro_dog_breed (
  breed_id      INT UNSIGNED AUTO_INCREMENT,
  name          VARCHAR(64)  NOT NULL,
  category      CHAR(1)      NOT NULL,
  size          VARCHAR(32),
  risk_profile  TEXT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (breed_id),
  UNIQUE KEY uk_name (name)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

/* ---- 2. 施設（スーパータイプ） ---- */
CREATE TABLE IF NOT EXISTS roro_facility (
  facility_id INT UNSIGNED AUTO_INCREMENT,
  name        VARCHAR(120) NOT NULL,
  category    VARCHAR(16)  NOT NULL,
  lat         DECIMAL(10,8) NOT NULL,
  lng         DECIMAL(11,8) NOT NULL,
  facility_pt POINT SRID 4326 NOT NULL
             /*!80000 INVISIBLE */,
  address     VARCHAR(191),
  phone       VARCHAR(32),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (facility_id),
  KEY idx_cat (category),
  SPATIAL INDEX spx (facility_pt)
) ENGINE=InnoDB;

/* ---- 3. アドバイス ---- */
CREATE TABLE IF NOT EXISTS roro_advice (
  advice_id  INT UNSIGNED AUTO_INCREMENT,
  title      VARCHAR(120) NOT NULL,
  body       MEDIUMTEXT   NOT NULL,
  category   CHAR(1)      NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(advice_id),
  KEY idx_cat (category)
) ENGINE=InnoDB;

/* ---- 4. 顧客 ---- */
CREATE TABLE IF NOT EXISTS roro_customer (
  customer_id INT UNSIGNED AUTO_INCREMENT,
  name        VARCHAR(80)  NOT NULL,
  email       VARCHAR(191) NOT NULL,
  phone       VARCHAR(32),
  zipcode     CHAR(8),
  breed_id    INT UNSIGNED NOT NULL,
  birth_date  DATE,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(customer_id),
  UNIQUE KEY uk_email (email),
  KEY idx_zip (zipcode),
  CONSTRAINT fk_customer_breed FOREIGN KEY (breed_id)
      REFERENCES roro_dog_breed(breed_id)
) ENGINE=InnoDB;

/* ---- 5. 通知設定 (0 or 1) ---- */
CREATE TABLE IF NOT EXISTS roro_notification_pref (
  customer_id INT UNSIGNED PRIMARY KEY,
  email_on    TINYINT(1) DEFAULT 1,
  line_on     TINYINT(1) DEFAULT 1,
  fcm_on      TINYINT(1) DEFAULT 0,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pref_customer FOREIGN KEY (customer_id)
      REFERENCES roro_customer(customer_id)
      ON DELETE CASCADE
) ENGINE=InnoDB;

/* ---- 6. 写真投稿 (四半期パーティション) ---- */
CREATE TABLE IF NOT EXISTS roro_photo (
  photo_id   BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id INT UNSIGNED,
  breed_id    INT UNSIGNED,
  attachment_id BIGINT UNSIGNED NOT NULL,
  zipcode     CHAR(8),
  lat         DECIMAL(10,8),
  lng         DECIMAL(11,8),
  photo_pt    POINT SRID 4326 GENERATED ALWAYS AS (Point(lng,lat)) STORED,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(photo_id, created_at),
  KEY idx_cust (customer_id),
  KEY idx_breed (breed_id),
  SPATIAL INDEX spx_photo (photo_pt),
  CONSTRAINT fk_photo_cust FOREIGN KEY (customer_id)
      REFERENCES roro_customer(customer_id) ON DELETE SET NULL
) ENGINE=InnoDB
PARTITION BY RANGE (TO_DAYS(created_at)) (
  PARTITION p2025q3 VALUES LESS THAN (TO_DAYS('2025-10-01')),
  PARTITION p2025q4 VALUES LESS THAN (TO_DAYS('2026-01-01')),
  PARTITION pFuture VALUES LESS THAN MAXVALUE
);

/* ---- 7. レポート ---- */
CREATE TABLE IF NOT EXISTS roro_report (
  report_id   BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id INT UNSIGNED,
  content     JSON NOT NULL,
  breed_json  VARCHAR(64) GENERATED ALWAYS AS
      (JSON_UNQUOTE(JSON_EXTRACT(content,'$.breed'))) STORED,
  age_month   INT GENERATED ALWAYS AS
      (JSON_EXTRACT(content,'$.age_month')) STORED,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(report_id),
  KEY idx_breed_age (breed_json, age_month),
  CONSTRAINT fk_report_cust FOREIGN KEY (customer_id)
      REFERENCES roro_customer(customer_id) ON DELETE SET NULL
) ENGINE=InnoDB;

/* ---- 8. ガチャ履歴 (年パーティション) ---- */
CREATE TABLE IF NOT EXISTS roro_gacha_log (
  spin_id     BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id INT UNSIGNED,
  facility_id INT UNSIGNED,
  advice_id   INT UNSIGNED,
  prize_type  ENUM('facility','advice') NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(spin_id, created_at),
  KEY idx_cust_date (customer_id, created_at)
) ENGINE=InnoDB
PARTITION BY RANGE (YEAR(created_at)) (
  PARTITION p2025 VALUES LESS THAN (2026),
  PARTITION pMax  VALUES LESS THAN MAXVALUE
);

/* ---- 9. レビュー ---- */
CREATE TABLE IF NOT EXISTS roro_facility_review (
  review_id   BIGINT UNSIGNED AUTO_INCREMENT,
  facility_id INT UNSIGNED,
  customer_id INT UNSIGNED,
  rating      TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment     TEXT,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(review_id),
  KEY idx_fac (facility_id),
  CONSTRAINT fk_rev_fac FOREIGN KEY (facility_id)
      REFERENCES roro_facility(facility_id) ON DELETE CASCADE
) ENGINE=InnoDB;

/* ----10. 収益 ---- */
CREATE TABLE IF NOT EXISTS roro_revenue (
  rev_id      BIGINT UNSIGNED AUTO_INCREMENT,
  customer_id INT UNSIGNED,
  amount      DECIMAL(10,2) NOT NULL,
  source      ENUM('ad','affiliate','subscr') NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(rev_id, created_at),
  KEY idx_src_date (source, created_at)
) ENGINE=InnoDB
PARTITION BY RANGE (TO_DAYS(created_at)) (
  PARTITION p2025q3 VALUES LESS THAN (TO_DAYS('2025-10-01')),
  PARTITION p2025q4 VALUES LESS THAN (TO_DAYS('2026-01-01')),
  PARTITION pMax VALUES LESS THAN MAXVALUE
);
-- 共通スーパータイプは残す --
ALTER TABLE roro_facility MODIFY category ENUM('cafe','hospital','salon','park','hotel','school','store') NOT NULL;

-- サブタイプ: PK=facility_id, FK制約のみ
CREATE TABLE IF NOT EXISTS roro_facility_cafe (
  facility_id INT UNSIGNED PRIMARY KEY,
  opening_hours VARCHAR(191),
  pet_menu      TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_cafe_fac FOREIGN KEY (facility_id)
      REFERENCES roro_facility(facility_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS roro_facility_hospital (
  facility_id INT UNSIGNED PRIMARY KEY,
  treatment_speciality VARCHAR(191),
  emergency TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_hosp_fac FOREIGN KEY (facility_id)
      REFERENCES roro_facility(facility_id) ON DELETE CASCADE
) ENGINE=InnoDB;

/* salon / park / hotel / school / store も同様に雛形をコピーして作成 */
/* 新テーブル: Firebase UID ↔ customer/user mapping */
CREATE TABLE IF NOT EXISTS roro_identity (
  uid           VARCHAR(128) NOT NULL,
  customer_id   INT UNSIGNED NOT NULL,
  wp_user_id    BIGINT UNSIGNED NOT NULL,
  provider      ENUM('firebase') NOT NULL DEFAULT 'firebase',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(uid),
  UNIQUE KEY uk_customer (customer_id),
  UNIQUE KEY uk_user (wp_user_id),
  CONSTRAINT fk_ident_customer FOREIGN KEY (customer_id)
     REFERENCES roro_customer(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_ja_0900_as_cs;

/* 既存 roro_customer に認証プロバイダー列を追加 */
ALTER TABLE roro_customer
  ADD COLUMN auth_provider ENUM('local','firebase') NOT NULL DEFAULT 'local' AFTER email,
  ADD KEY idx_auth_provider (auth_provider);
