<?php
/**
 * Module path: wp-content/plugins/roro-core/includes/notifications/notification_service.php
 *
 * 週次アドバイス通知を送信するサービス。WordPress Cron を用いて毎週日曜にメッセージを配信します。
 * メール送信のほか、LINE/FCM 送信用のフックを残しており、実装はオーバーライド可能です。
 *
 * @package RoroCore\Notifications
 */

namespace RoroCore\Notifications;

class Notification_Service {
    /** @var string Cron フック名 */
    private const CRON_HOOK = 'roro_core_send_weekly_advice';

    public function __construct() {
        add_action( 'init', [ $this, 'schedule_events' ] );
        add_action( self::CRON_HOOK, [ $this, 'send_weekly_advice' ] );
    }

    /**
     * Cronイベントをスケジュール。まだ登録されていない場合のみ設定。
     */
    public function schedule_events() : void {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( strtotime( 'next Sunday' ), 'weekly', self::CRON_HOOK );
        }
    }

    /**
     * 週次アドバイスを送信する。メール、LINE、FCM の3チャネルに送る。
     */
    public function send_weekly_advice() : void {
        $advice = apply_filters( 'roro_weekly_advice', __( 'Remember to give your pet plenty of love and exercise!', 'roro-core' ) );
        $this->send_email( $advice );
        $this->send_line( $advice );
        $this->send_fcm( $advice );
    }

    /**
     * メール送信処理。デフォルトでは管理者メールへ送る。
     *
     * @param string $message 送信する本文。
     */
    protected function send_email( string $message ) : void {
        wp_mail( get_option( 'admin_email' ), __( 'Weekly Pet Advice', 'roro-core' ), $message );
    }

    /**
     * LINE通知処理。ここではログ出力のみを行う。
     *
     * @param string $message 送信する本文。
     */
    protected function send_line( string $message ) : void {
        error_log( 'RoRo Core LINE advice: ' . $message );
    }

    /**
     * FCM通知処理。ここではログ出力のみを行う。
     *
     * @param string $message 送信する本文。
     */
    protected function send_fcm( string $message ) : void {
        error_log( 'RoRo Core FCM advice: ' . $message );
    }
}
