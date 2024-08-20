<?php
require_once __DIR__ . '/inc/generate-stats.php';

Kirby::plugin( 'florianziegler/photo-stats', [
	'options' => [
		'dateField' => 'published',
		'cronRoute' => 'generate-photo-stats',
		'cropCameras' => [ 'X100', 'X100S', 'X100T', 'X100F', 'X100V', 'X100VI', 'X-E1', 'X-E2', 'X-E3', 'X-E4', 'X-H1', 'X-H2', 'X-Pro1', 'X-Pro2', 'X-Pro3', 'X-T1', 'X-T2', 'X-T3', 'X-T4', 'X-T5' ],
	],
	'snippets' => [
		'photo-stats' => __DIR__ . '/snippets/photo-stats.php',
	],
	'blueprints' => [
		'photo-stats/color' => __DIR__ . '/blueprints/color.yml'
	],
	'routes' => function( $kirby ) {
		return [
			[
				'pattern' => $kirby->option( 'florianziegler.photo-stats.cronRoute' ),
				'action' => function() {
					// Check if there is a funktion defined, from which we get our pages
					$pages_option = kirby()->option( 'florianziegler.photo-stats.pages' );
					if ( ! empty( $pages_option ) && function_exists( $pages_option ) ) {
						$pages = $pages_option();
						// Make sure that, what comes back, is actually a pages collection
						if ( ! is_object( $pages ) || get_class( $pages ) != 'Kirby\Cms\Pages' ) {
							throw new Exception( 'Your callback function does not return Kirby Pages' );
						}
					}
					else {
						$pages = site()->pages();
					}

					$stats = new florianziegler\photoStats\PhotoStatsGenerator();
					$stats->set_date_field( kirby()->option( 'florianziegler.photo-stats.dateField' ) );
					$stats->maybe_run_generator( $pages );
					exit();
				}
			]
		];
	},
	'hooks' => [
		// Create "marker" file after changing page status
		'page.changeStatus:after' => function( $file ) {
			file_put_contents( __DIR__ . '/GENERATE_STATS', '' );
		},

		// Mark images as either monochrome or color
		'file.create:after' => function( $file ) {
			if ( $file->type() == 'image' && class_exists( 'Imagick' ) ) {
				$imagick = new Imagick( $file->realpath() );
				$imagick->transformImageColorspace( imagick::COLORSPACE_HSL );
				$saturation_channel = $imagick->getImageChannelMean( imagick::CHANNEL_GREEN );
				$color = 'color';
				if ( $saturation_channel['mean']/65535 <= 0.0000001 ) {
					$color = 'monochrome';
				}
				$file->update([
					'color' => $color
				]);
			}
		},
	],
	
] );