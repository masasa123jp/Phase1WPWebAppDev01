<?php
/**
 * Module path: wp-content/plugins/roro-core/includes/api/geocode_endpoint.php
 *
 * 郵便番号をZipCloudのAPIで検索し、結果を返すエンドポイント。郵便番号はハイフン付き／なしのどちらでも受け付けます。
 * 取得結果は月単位でキャッシュされます。エラー時はHTTPステータス503または404を返します。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class Geocode_Endpoint {
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_route' ] );
    }

    public function register_route(): void {
        register_rest_route(
            'roro/v1',
            '/geocode/(?P<zip>[0-9]{3}-?[0-9]{4})',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'lookup' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    /**
     * 郵便番号を検索する。
     *
     * @param WP_REST_Request $req
     * @return WP_REST_Response
     */
    public function lookup( WP_REST_Request $req ): WP_REST_Response {
        $zip = str_replace( '-', '', $req['zip'] );
        $key = 'roro_geo_' . $zip;

        if ( $cache = get_transient( $key ) ) {
            return rest_ensure_response( $cache );
        }

        $url      = 'https://zipcloud.ibsnet.co.jp/api/search?zipcode=' . $zip;
        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return new WP_REST_Response( [ 'error' => 'service_unreachable' ], 503 );
        }

        $json = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $json['results'][0] ) ) {
            return new WP_REST_Response( [ 'error' => 'not_found' ], 404 );
        }

        $result = $json['results'][0];
        set_transient( $key, $result, MONTH_IN_SECONDS );
        return rest_ensure_response( $result );
    }
}
