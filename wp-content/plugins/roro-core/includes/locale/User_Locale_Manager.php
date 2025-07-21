<?php
/**
 * User locale manager.
 *
 * Hooks into WordPress’ locale determination process to honour a user’s
 * preferred language selection.  The preference is stored in user meta
 * under the key `roro_locale` and can be set by a front‑end selector or
 * via future profile settings.  If no user preference exists, the
 * default language from the Language Settings is used.  Finally, if
 * neither is available, WordPress’ own locale is returned.
 *
 * @package RoroCore\Locale
 */

namespace RoroCore\Locale;

class User_Locale_Manager {
    /**
     * Initialise the locale manager.  Hooks the determine_locale filter.
     */
    public static function init() : void {
        add_filter( 'determine_locale', [ self::class, 'filter_locale' ] );
    }

    /**
     * Determine the locale based on user preference or global default.
     *
     * @param string $locale The locale WordPress would normally use.
     *
     * @return string Filtered locale code.
     */
    public static function filter_locale( string $locale ) : string {
        // Logged‑in users can have a saved preference.
        if ( is_user_logged_in() ) {
            $user_locale = get_user_meta( get_current_user_id(), 'roro_locale', true );
            if ( $user_locale ) {
                return $user_locale;
            }
        }
        // Fallback to the default language configured by the admin.
        $options = get_option( \RoroCore\Settings\Language_Settings::OPTION_KEY );
        if ( ! empty( $options['default_language'] ) ) {
            return $options['default_language'];
        }
        return $locale;
    }
}
