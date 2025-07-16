<?php
/**
 * Simple per‑IP Rate Limiter using Transients.
 *
 * @package RoroCore
 */

declare( strict_types = 1 );

namespace RoroCore;

class Rate_Limiter {

	private string $key;
	private int    $limit;
	private int    $window;

	/**
	 * @param string $key     Unique action key (e.g. route slug).
	 * @param int    $limit   Max allowed hits per window.
	 * @param int    $window  Window in seconds.
	 */
	public function __construct( string $key, int $limit = 20, int $window = HOUR_IN_SECONDS ) {
		$this->key    = $key . '_' . $_SERVER['REMOTE_ADDR'];
		$this->limit  = (int) apply_filters( 'roro_rate_limit', $limit, $key );
		$this->window = $window;
	}

	/**
	 * Returns true if allowed, false if blocked.
	 */
	public function check(): bool {
		$count = (int) get_transient( $this->key );

		if ( $count >= $this->limit ) {
			return false;
		}

		set_transient(
			$this->key,
			$count + 1,
			( $count === 0 ) ? $this->window : 0 // set TTL only on first hit
		);
		return true;
	}
}
