<?php
namespace RoroCore;

class Rate_Limiter {

	private string $key;
	private int    $limit;
	private int    $window;

	public function __construct( string $action, int $limit = 100, int $window = HOUR_IN_SECONDS ) {
		$user = get_current_user_id() ?: 0;
		$ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		$this->key    = "roro_rl_{$action}_{$user}_{$ip}";
		$this->limit  = $limit;
		$this->window = $window;
	}

	public function check() : bool {
		$count = (int) get_transient( $this->key );
		if ( $count >= $this->limit ) {
			return false;
		}
		set_transient( $this->key, $count + 1, $this->window );
		return true;
	}
}
