<?php
/**
 * roro_photo 独自テーブルのスキーマとクエリラッパー。
 * 外部 CDN へオフロードする際はここを差し替えるだけで対応可能。
 */
namespace RoroCore;

use wpdb;

class Photo_Storage {

	private wpdb $db;
	private string $table;

	public function __construct( wpdb $wpdb ) {
		$this->db    = $wpdb;
		$this->table = $wpdb->prefix . 'roro_photo';
	}

	/** 初回有効化時に呼び出し */
	public function maybe_create_table() {
		$sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			attachment_id BIGINT UNSIGNED NOT NULL,
			breed VARCHAR(64) DEFAULT NULL,
			zipcode CHAR(8) DEFAULT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY breed (breed),
			KEY zipcode (zipcode)
		) {$this->db->get_charset_collate()};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/** 最近の投稿を取得 */
	public function list_recent( int $limit = 12 ) : array {
		return $this->db->get_results(
			$this->db->prepare( "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT %d", $limit ),
			ARRAY_A
		);
	}
}
