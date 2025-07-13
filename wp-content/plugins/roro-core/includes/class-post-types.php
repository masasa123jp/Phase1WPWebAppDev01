<?php
namespace RoroCore;
defined('ABSPATH') || exit;

final class Post_Types {

	public static function register(): void {
		add_action( 'init', [ self::class, 'register_photo_cpt' ] );
	}

	public static function register_photo_cpt(): void {
		register_post_type(
			'roro_photo',
			[
				'labels' => [
					'name'          => __( 'Pet Photos', 'roro-core' ),
					'singular_name' => __( 'Pet Photo', 'roro-core' ),
				],
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-camera',
				'supports'     => [ 'title', 'thumbnail', 'custom-fields' ],
			]
		);
	}
}
