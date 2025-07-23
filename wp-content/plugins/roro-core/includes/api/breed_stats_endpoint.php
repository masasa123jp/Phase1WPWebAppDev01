<?php
/**
 * Module path: wp-content/plugins/roro-core/includes/api/breed_stats_endpoint.php
 *
 * Endpoint: /breed-stats/<breed>
 * 各犬種の月齢ごとの平均体重・身長を返します。結果はトランジェントに24時間キャッシュします。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Breed_Stats_Endpoint {
    /** @var string データ取得に利用するテーブル名 */
    private string $table;

    /**
     * コンストラクタ。テーブル名を初期化し、REST API ルート登録を行う。
     *
     * @param \wpdb $wpdb WordPress DB オブジェクト。
     */
    public function __construct( \wpdb $wpdb ) {
        $this->table = $wpdb->prefix . 'roro_breed_growth';
        add_action( 'rest_api_init', [ $this, 'register_route' ] );
    }

    /**
     * ルート登録。
     */
    public function register_route(): void {
        register_rest_route(
            'roro/v1',
            '/breed-stats/(?P<breed>[a-z0-9_-]+)',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'stats' ],
                'permission_callback' => '__return_true',
                'args'                => [
                    'breed' => [
                        'sanitize_callback' => 'sanitize_title',
                    ],
                ],
            ]
        );
    }

    /**
     * 統計情報を取得して返す。
     *
     * @param WP_REST_Request $req リクエスト。
     * @return WP_REST_Response
     */
    public function stats( WP_REST_Request $req ): WP_REST_Response {
        global $wpdb;
        $breed = $req->get_param( 'breed' );
        $key   = "roro_stats_{$breed}";
        // キャッシュチェック
        $cache = get_transient( $key );
        if ( $cache ) {
            return rest_ensure_response( $cache );
        }
        // DB から取得
        $sql  = "SELECT month_age, weight_avg, height_avg FROM {$this->table} WHERE breed_slug = %s ORDER BY month_age";
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $breed ), ARRAY_A );
        if ( empty( $rows ) ) {
            return new WP_REST_Response( [ 'error' => 'Not found' ], 404 );
        }
        // 24h キャッシュ
        set_transient( $key, $rows, DAY_IN_SECONDS );
        return rest_ensure_response( $rows );
    }
}
