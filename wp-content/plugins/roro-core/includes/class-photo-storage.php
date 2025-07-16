<?php
/**
 * Photo Storage helper – saves attachment meta to custom table.
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
	 * Save meta (idempotent).
	 *
	 * @param int    $post_id Attachment ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value.
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
	 * Get latest photos (paged).
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
