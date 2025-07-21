<?php
/* ================================ 既存設定 ... ================================ */

/* ----------------------- RoRo プロジェクト追加定数 ----------------------- */

/* 1) JWT Authentication for WP REST API */
define( 'JWT_AUTH_SECRET_KEY', 'CHANGE_ME_TO_LONG_RANDOM_STRING' );
define( 'JWT_AUTH_CORS_ENABLE', true );

/* 2) Firebase Cloud Messaging – サーバキー */
define( 'RORO_FCM_SERVER_KEY', 'AAA...yourServerKey...' );

/* 3) OpenAI Key – AI Advice エンドポイント用 */
define( 'RORO_OPENAI_KEY', 'sk-...' );

/* 4) Sentry DSN – PHP 用 */
define( 'RORO_SENTRY_DSN', 'https://<hash>@o0.ingest.sentry.io/0' );

/* 5) キャッシュ・最適化（任意） */
define( 'WP_CACHE', true );
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'WP_POST_REVISIONS', 5 );

/* ----------------------- 必須: 末尾に wp-settings.php ----------------------- */
if ( ! defined( 'ABSPATH' ) ) {
  define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
