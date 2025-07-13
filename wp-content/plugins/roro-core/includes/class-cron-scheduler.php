<?php
namespace RoroCore;

class Cron_Scheduler {

	const HOOK_WEEKLY_PUSH = 'roro_weekly_push';

	/** プラグイン有効化時に呼び出し */
	public static function activate() {
		if ( ! wp_next_scheduled( self::HOOK_WEEKLY_PUSH ) ) {
			wp_schedule_event( time(), 'weekly', self::HOOK_WEEKLY_PUSH );
		}
	}

	/** プラグイン停止時に呼び出し */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::HOOK_WEEKLY_PUSH );
	}

	/** 実際のジョブ処理：週次提案メール生成 */
	public static function handle_weekly_push() {
		$service = new Notification_Service();
		$service->send_weekly_advice(); // LINE & メール
	}
}
/* hook 登録は roro-core.php で：
register_activation_hook( __FILE__, [Cron_Scheduler::class, 'activate'] );
register_deactivation_hook( __FILE__, [Cron_Scheduler::class, 'deactivate'] );
add_action( Cron_Scheduler::HOOK_WEEKLY_PUSH, [Cron_Scheduler::class, 'handle_weekly_push'] );
*/
