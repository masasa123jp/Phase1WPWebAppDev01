<?php
declare( strict_types=1 );

namespace RoroCore;

class Meta {

	public static function register(): void {
		add_action( 'init', [ self::class, 'add_fields' ] );
	}

	public static function add_fields(): void {
		register_post_meta(
			'roro_advice',
			'difficulty',
			[
				'show_in_rest' => true,
				'type'         => 'string',
				'single'       => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}
}
Meta::register();
