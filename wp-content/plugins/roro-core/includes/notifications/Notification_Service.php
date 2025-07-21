<?php
/**
 * Notification service.  Responsible for dispatching scheduled messages
 * such as weekly pet care advice via email, LINE and Firebase Cloud
 * Messaging (FCM).  Uses WordPress cron (Action Scheduler could be used
 * instead for more reliability).  The actual sending implementation is
 * abstracted to protected methods which can be overridden or extended.
 *
 * @package RoroCore\Notifications
 */

namespace RoroCore\Notifications;

class Notification_Service {
    /**
     * Hook name for scheduled notifications.
     */
    private const CRON_HOOK = 'roro_core_send_weekly_advice';

    public function __construct() {
        // Schedule the weekly task if not already scheduled.
        add_action( 'init', [ $this, 'schedule_events' ] );
        add_action( self::CRON_HOOK, [ $this, 'send_weekly_advice' ] );
    }

    /**
     * Schedule weekly advice notifications using WordPress cron.  If the
     * event is already scheduled it will not be rescheduled.
     */
    public function schedule_events() : void {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            // Default schedule: once weekly at midnight on Sunday.
            wp_schedule_event( strtotime( 'next Sunday' ), 'weekly', self::CRON_HOOK );
        }
    }

    /**
     * Dispatch weekly advice notifications.  This method is invoked by
     * WordPress cron.  In this stub implementation we simply log a
     * message.  A real implementation would query users who opted in
     * and send advice via the channels configured in settings.
     */
    public function send_weekly_advice() : void {
        // Fetch advice content – could be a random post, AI generated text, etc.
        $advice = apply_filters( 'roro_weekly_advice', __( 'Remember to give your pet plenty of love and exercise!', 'roro-core' ) );
        // Send via email.
        $this->send_email( $advice );
        // Send via LINE.
        $this->send_line( $advice );
        // Send via FCM.
        $this->send_fcm( $advice );
    }

    /**
     * Send advice via email to subscribed users.  Override this method to
     * implement your own subscription logic.  For demonstration purposes
     * this simply emails the site admin.
     */
    protected function send_email( string $message ) : void {
        wp_mail( get_option( 'admin_email' ), __( 'Weekly Pet Advice', 'roro-core' ), $message );
    }

    /**
     * Send advice via LINE.  You will need to integrate the LINE
     * Messaging API and store access tokens for each subscriber.
     */
    protected function send_line( string $message ) : void {
        // Implement LINE message sending.  Use `get_option( 'roro_core_options' )['liff_id']` or other keys.
        // For now we simply log the message.
        error_log( 'RoRo Core LINE advice: ' . $message );
    }

    /**
     * Send advice via FCM.  Requires the FCM server key stored in
     * settings.  You will also need to persist user device tokens.
     */
    protected function send_fcm( string $message ) : void {
        // Implement FCM sending using the fcm_key from settings.
        // For now we simply log the message.
        error_log( 'RoRo Core FCM advice: ' . $message );
    }
}
