<?php
namespace RoroPush;

use ActionScheduler;

class Sender {

	const HOOK = 'roro_push_weekly';

	public static function init() {
		if ( class_exists( '\ActionScheduler' ) ) {
			add_action( self::HOOK, [ self::class, 'send_weekly' ] );
			if ( ! as_has_scheduled_action( self::HOOK ) ) {
				as_schedule_recurring_action( time(), WEEK_IN_SECONDS, self::HOOK );
			}
		}
	}

	public static function send_weekly() {
		$users = get_users(
			[
				'meta_key'     => 'fcm_token',
				'meta_compare' => 'EXISTS',
				'fields'       => [ 'ID' ],
			]
		);

		foreach ( $users as $user ) {
			$token = get_user_meta( $user->ID, 'fcm_token', true );
			if ( $token ) {
				self::push_to_fcm( $token, '今週のワンポイントアドバイスが届きました！' );
			}
		}
	}

	protected static function push_to_fcm( $token, $body ) {
		$key = defined( 'RORO_FCM_SERVER_KEY' ) ? RORO_FCM_SERVER_KEY : '';
		if ( ! $key ) { return; }

		wp_remote_post(
			'https://fcm.googleapis.com/fcm/send',
			[
				'headers' => [
					'Authorization' => 'key=' . $key,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'to'   => $token,
						'notification' => [
							'title' => 'RoRo',
							'body'  => $body,
						],
						'data' => [ 'url' => home_url( '/dashboard' ) ],
					]
				),
				'timeout' => 10,
			]
		);
	}
}
Sender::init();
