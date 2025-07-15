<?php
declare( strict_types=1 );

namespace RoroCore;

class Post_Types {

	public static function register(): void {
		add_action( 'init', [ self::class, 'dog_advice' ] );
	}

	private static function dog_advice(): void {
		register_post_type(
			'roro_advice',
			[
				'labels'       => [
					'name'          => __( 'Dog Advice', 'roro-core' ),
					'singular_name' => __( 'Advice', 'roro-core' ),
				],
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
				'menu_icon'    => 'dashicons-carrot',
			]
		); // post type registration :contentReference[oaicite:8]{index=8}
	}
}
Post_Types::register();
