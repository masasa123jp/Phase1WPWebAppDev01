<?php

/**
 * AIアドバイス用エンドポイント。
 * ペットケアに関する質問を受け取り、OpenAI Chat Completion APIで生成された
 * 回答を返します。プラグインの一般設定からAPIキーを読み込みます。
 * デフォルトの権限コールバックで認証が必要です。
 * 回答は質問のMD5ハッシュをキーにしたWordPressトランジェントにキャッシュされ、
 * APIコールを削減します。
 * プラグインは `roro_ai_advice_cache_ttl` フィルターでキャッシュTTLをカスタマイズ可能、
 * また新規／キャッシュ回答それぞれを `roro_ai_advice_answer`、
 * `roro_ai_advice_answer_cached` フィルターで修正できます。
 *
 * @package RoroCore\Api
 */

namespace RoroCore\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use OpenAI;

class Ai_Advice_Endpoint extends Abstract_Endpoint {

    /** エンドポイントのルート定義 */
    public const ROUTE = '/ai/advice';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }

    /**
     * REST API ルートの登録。
     *
     * @return void
     */
    public static function register() : void {
        register_rest_route( 'roro/v1', self::ROUTE, [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => [ self::class, 'permission_callback' ],
                'args'                => [
                    'question' => [ 'type' => 'string', 'required' => true ],
                    'breed'    = [ 'type' => 'string', 'required' => false ],
                ],
            ],
        ] );
    }

    /**
     * AIアドバイスリクエストの処理。
     * OpenAIキー未設定時はエラーを返します。デフォルトで gpt-4o モデルを使用。
     *
     * @param WP_REST_Request $request リクエストオブジェクト
     * @return WP_REST_Response|WP_Error
     */
    public static function handle( WP_REST_Request $request ) {
        // プラグイン一般設定からAPIキーを取得
        $options = get_option( \RoroCore\Settings\General_Settings::OPTION_KEY );
        $api_key = $options['openai_key'] ?? '';
        if ( empty( $api_key ) ) {
            return new WP_Error(
                'no_api_key',
                __( 'OpenAI API key not configured.', 'roro-core' ),
                [ 'status' => 500 ]
            );
        }

        // 質問パラメータを取得・トリム
        $question = trim( $request->get_param( 'question' ) );
        if ( empty( $question ) ) {
            return new WP_Error(
                'no_question',
                __( 'Question is required.', 'roro-core' ),
                [ 'status' => 400 ]
            );
        }

        // キャッシュ確認: 質問を小文字化してMD5ハッシュをキーに使用
        // `roro_ai_advice_cache_ttl` フィルターでTTLをカスタマイズ可能（デフォルト1時間）
        $cache_key = 'roro_ai_advice_' . md5( strtolower( $question ) );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            /**
             * キャッシュ済み回答を返す前にフィルタリングします。
             * 保存時にフィルタ済みの値ですが、必要に応じてさらに修正可能です。
             *
             * @param string           $cached   キャッシュ済みの回答
             * @param string           $question 元の質問
             * @param WP_REST_Request  $request  RESTリクエストオブジェクト
             */
            $cached = apply_filters( 'roro_ai_advice_answer_cached', $cached, $question, $request );
            return rest_ensure_response( [ 'answer' => $cached, 'cached' => true ] );
        }

        // キャッシュなし: OpenAI API 呼び出し
        try {
            $client   = OpenAI::client( $api_key );
            $response = $client->chat()->create( [
                'model'    => 'gpt-4o',
                'messages' => [
                    [ 'role' => 'system', 'content' => 'You are a pet-care expert.' ],
                    [ 'role' => 'user',   'content' => $question ],
                ],
            ] );
            $answer = $response->choices[0]->message->content ?? '';
        } catch ( \Throwable $e ) {
            // エラー発生時はWP_Errorで応答
            return new WP_Error( 'openai_error', $e->getMessage(), [ 'status' => 500 ] );
        }

        // 新規回答をキャッシュ前にフィルター
        // キャッシュ済み用フックとは別のフックです
        $answer = apply_filters( 'roro_ai_advice_answer', $answer, $question, $request );

        // トランジェントに回答を保存
        $ttl = apply_filters( 'roro_ai_advice_cache_ttl', HOUR_IN_SECONDS );
        set_transient( $cache_key, $answer, (int) $ttl );

        return rest_ensure_response( [ 'answer' => $answer, 'cached' => false ] );
    }
}
