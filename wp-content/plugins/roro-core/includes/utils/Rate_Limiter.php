<?php
/**
 * Simple per‑IP rate limiter using WordPress transients.  Instantiating
 * this class with a unique key, maximum number of hits and a time window
 * will allow you to gate expensive operations.  For example, you can
 * construct a limiter with a key of `gacha` and a limit of 5 to ensure
 * users cannot spin the gacha more than five times per hour.  Use the
 * `check()` method before performing the protected action.
 *
 * @package RoroCore\Utils
 */

declare( strict_types = 1 );

namespace RoroCore\Utils;

class Rate_Limiter {

    /**
     * Composite key containing the action and IP address.
     *
     * @var string
     */
    private string $key;

    /**
     * Max allowed hits within the window.
     *
     * @var int
     */
    private int $limit;

    /**
     * Window length in seconds.
     *
     * @var int
     */
    private int $window;

    /**
     * Constructor.
     *
     * @param string $action_key Unique key for the action (e.g. route slug).
     * @param int    $limit      Maximum number of allowed hits per window. Defaults to 20.
     * @param int    $window     Length of the window in seconds. Defaults to one hour.
     */
    public function __construct( string $action_key, int $limit = 20, int $window = HOUR_IN_SECONDS ) {
        $ip            = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $this->key     = $action_key . '_' . $ip;
        // Allow filters to override the limit per action.
        $this->limit   = (int) apply_filters( 'roro_rate_limit', $limit, $action_key );
        $this->window  = $window;
    }

    /**
     * Determine whether the current request is allowed.  If the number of
     * hits exceeds the limit, the method returns false.  Otherwise the hit
     * count is incremented and true is returned.
     *
     * @return bool True if allowed, false if blocked.
     */
    public function check(): bool {
        $count = (int) get_transient( $this->key );
        if ( $count >= $this->limit ) {
            return false;
        }
        // Increment and set the transient. Only set expiry on first hit.
        set_transient( $this->key, $count + 1, ( $count === 0 ) ? $this->window : 0 );
        return true;
    }
}
