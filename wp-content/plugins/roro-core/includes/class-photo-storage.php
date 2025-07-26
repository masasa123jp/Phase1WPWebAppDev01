<?php
/**
 * 写真ストレージ用ヘルパー – 添付ファイルのメタ情報をカスタムテーブルに保存します。
 *
 * @package RoroCore
 */

declare( strict_types = 1 );

namespace RoroCore;

class Photo_Storage {

	private string $table;

	public function __construct( \wpdb $wpdb ) {
		$this->table = $wpdb->prefix . 'roro_photo_meta';
	}

	/**
	 * メタ情報を保存します（冪等）。
	 *
	 * @param int    $post_id 添付ファイルの ID。
	 * @param string $key     メタキー。
	 * @param mixed  $value   メタ値。
	 */
	public function save( int $post_id, string $key, $value ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->table} (post_id, meta_key, meta_value)
				 VALUES ( %d, %s, %s )
				 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
				$post_id,
				$key,
				maybe_serialize( $value )
			)
		);
	}

	/**
	 * 最新の写真を取得します（ページング）。
	 *
	 * @return array[]
	 */
	public function latest( int $limit = 20 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				 ORDER BY id DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}
}
