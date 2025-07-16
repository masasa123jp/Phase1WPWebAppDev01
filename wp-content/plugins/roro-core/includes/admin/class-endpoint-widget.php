<?php
/**
 * Admin dashboard KPI widget.
 *
 * @package RoroCore\Admin
 */

declare( strict_types = 1 );

namespace RoroCore\Admin;

use function wp_create_nonce;

class Dashboard_Widget {

	public static function init(): void {
		add_action( 'wp_dashboard_setup', [ self::class, 'register' ] );
	}

	public static function register(): void {
		wp_add_dashboard_widget(
			'roro_kpi_widget',
			__( 'RoRo KPI', 'roro-core' ),
			[ self::class, 'render' ]
		);
	}

	public static function render(): void {
		$nonce = wp_create_nonce( 'wp_rest' );
		echo '<div id="roro-kpi" data-nonce="' . esc_attr( $nonce ) . '">Loading…</div>';
		?>
		<script>
			( () => {
				const el   = document.getElementById( 'roro-kpi' );
				const nonce = el.dataset.nonce;
				fetch( '<?php echo esc_js( home_url( '/wp-json/roro/v1/analytics' ) ); ?>', {
					headers: { 'X-WP-Nonce': nonce }
				} )
					.then( r => r.json() )
					.then( j => {
						el.innerHTML = `
							<ul style="margin:0;padding-left:1.2em">
								<li>Today Spins: ${ j.today_spins }</li>
								<li>Active Days: ${ j.active_days }</li>
								<li>Unique IPs 30d: ${ j.unique_ips_30d }</li>
							</ul>`;
					} )
					.catch( () => { el.textContent = 'Failed to load'; } );
			} )();
		</script>
		<?php
	}
}
Dashboard_Widget::init();
