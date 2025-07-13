<?php
namespace RoroCore;
defined('ABSPATH') || exit;

final class Meta {

	public static function register(): void {
		add_action( 'init', [ self::class, 'add_photo_meta' ] );
	}

	public static function add_photo_meta(): void {
		register_meta(
			'post',
			'roro_geo',
			[
				'object_subtype'    => 'roro_photo',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'single'            => true
			]
		);
	}
}
