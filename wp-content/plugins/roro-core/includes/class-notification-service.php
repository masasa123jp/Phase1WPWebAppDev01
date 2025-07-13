<?php
namespace RoroCore;

class Notification_Service {

	/** 受信者ごとのチャネル設定を読み込み */
	private function get_preferences( int $user_id ) : array {
		return get_user_meta( $user_id, 'roro_notify_pref', true ) ?: [
			'line'  => true,
			'email' => true,
			'fcm'   => false,
		];
	}

	/** 週次アドバイスを全ユーザーへ送信 */
	public function send_weekly_advice() {
		$users = get_users( [ 'role__in' => [ 'subscriber', 'administrator' ] ] );

		foreach ( $users as $u ) {
			$pref = $this->get_preferences( $u->ID );
			$content = $this->render_email( $u->ID );

			if ( $pref['email'] ) {
				wp_mail( $u->user_email, '今週の RoRo アドバイス', $content, [ 'Content-Type: text/html' ] );
			}
			if ( $pref['line'] && $token = get_user_meta( $u->ID, 'roro_line_token', true ) ) {
				$this->push_line( $token, strip_tags( $content ) );
			}
			if ( $pref['fcm'] && $fcm = get_user_meta( $u->ID, 'roro_fcm_token', true ) ) {
				$this->push_fcm( $fcm, strip_tags( $content ) );
			}
		}
	}

	private function push_line( string $token, string $msg ) {/* LINE Messaging API 呼び出し */}
	private function push_fcm( string $token, string $msg )  {/* Firebase Cloud Messaging */}
	private function render_email( int $user_id ) : string {
		ob_start();
		include RORO_CORE_PATH . '/templates/email/weekly_advice.php';
		return ob_get_clean();
	}
}
