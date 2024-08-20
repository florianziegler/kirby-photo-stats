<?php
namespace florianziegler\photoStats;


class PhotoStatsGenerator {
	public $pages;
	public $date_field = 'published';


	/**
	 * Check, if stats should be regenerated.
	 *
	 * @param object $pages Kirby pages object
	 */
	public function maybe_run_generator( $pages ) {
		$file = dirname( __DIR__, 1 ) . '/GENERATE_STATS';
		if ( file_exists( $file ) ) {
			// Generate stats
			$this->generate_stats( $pages );
			// Delete "marker" file
			unlink( $file );
		}
	}


	/**
	 * Set the date field.
	 *
	 * @param string $date_field Name of the field used for the blog post date
	 */
	public function set_date_field( $date_field ) {
		$this->date_field = $date_field;
	}


	/**
	 * Calculate the total number of posts
	 *
	 * @param object $pages Kirby pages object
	 * @return string Total number of posts
	 */
	function total_number_posts( $pages ) {
		ob_start();
		?>
		<dt>Total Number of Posts:</dt>
		<dd><?= $pages->listed()->count(); ?></dd>
		<?php
		return ob_get_clean();
	}


	/**
	 * Calculate the total number of images.
	 *
	 * @param object $pages Kirby pages object
	 * @return string Total number of images
	 */
	function total_number_images( $pages ) {
		ob_start();
		?>
		<dt>Total Number of Images:</dt>
		<dd><?php
			$num_all_images = $pages->files()->filterBy('type', 'image')->count();
			echo $num_all_images; ?></dd>
		<?php
		return ob_get_clean();
	}


	/**
	 * Calculate the monochrome ratio.
	 *
	 * @param object $pages Kirby pages object
	 * @return string Monochrome ratio
	 */
	function monochrome_ratio( $pages ) {
		ob_start();
		$num_all_images = $pages->files()->filterBy('type', 'image')->count();
		?>
		<dt>Monochrome Ratio:</dt>
		<dd><?php
			if ( $num_all_images <= 0 ) {
				echo '-';
			}
			else {
				$monochrome_images = count( $pages->files()->filterBy( 'type', 'image' )->filterBy( 'color', 'monochrome' ) );
				echo number_format( ( $monochrome_images * 100 / $num_all_images ), 0 ) . '%';
			}
		?></dd>
		<?php
		return ob_get_clean();
	}


	/**
	 * Calculate first post.
	 *
	 * @param object $pages Kirby pages object
	 * @return string First post
	 */
	function first_post( $pages ) {
		ob_start();
		?>
		<dt>First Post:</dt>
		<dd><a href="<?= $pages->listed()->first()->url(); ?>"><?= $pages->listed()->first()->title(); ?></a></dd><?php 
		return ob_get_clean();
	}


	/**
	 * Calculate first color post.
	 *
	 * @param object $pages Kirby pages object
	 * @return string First color post
	 */
	function first_color_post( $pages ) {
		ob_start();
		$temp = '-';
		foreach( $pages as $page ) {
			if ( count( $page->files()->filterBy( 'type', 'image' )->filterBy( 'color', 'color' ) ) > 0 ) {
				$temp = '<a href="' . $page->url() . '">' . $page->title() . '</a>';
				break;
			}
		}
		?>
		<dt>First Color Post:</dt>
		<dd><?= $temp; ?></dd><?php 
		return ob_get_clean();
	}


	/**
	 * Calculate longest time without posting.
	 *
	 * @param object $pages Kirby pages object
	 * @return string Longest time without posting
	 */
	function longest_time_no_post( $pages ) {
		$date_field = $this->date_field;
		ob_start();
		$p = $pages->listed()->last();
		$i = 0;
		$longest = [
			'timespan' => 0,
			'from' => 0,
			'from_url' => '',
			'to' => 0,
			'to_url' => ''
		];

		while( $p ) {
			if ( $p->prevListed() ) {
				$diff = (int) abs( $p->prevListed()->$date_field()->toDate() - $p->$date_field()->toDate() );
				if ( $diff > $longest['timespan'] ) {
					$longest = [
						'timespan' => $diff,
						'from' => $p->prevListed()->$date_field()->toDate(),
						'from_url' => $p->prevListed()->url(),
						'to' => $p->$date_field()->toDate(),
						'to_url' => $p->url(),
					];
				}
				$p = $p->prevListed();
				$i++;
			}
			else {
				break;
			}
		}
		?>
		<dt>Longest Time Without Posting:</dt>
		<dd><?= floor( $longest['timespan'] / 86400 ); ?> days,<br />from <a href="<?= $longest['from_url']?>"><?= date( 'Y-m-d', $longest['from'] ); ?></a> to <a href="<?= $longest['to_url']?>"><?= date( 'Y-m-d', $longest['to'] ); ?></a></dd>
		<?php
		return ob_get_clean();
	}


	/**
	 * Calculate longest streaks.
	 *
	 * @param object $pages Kirby pages object
	 * @return string Three longest streaks
	 */
	function longest_streak( $pages ) {
		// TODO: Make number of streaks optional?
		$date_field = $this->date_field;

		ob_start();
		/**
		 * Caclulate the longest streak
		 */
		$streak = 1;
		$streaks = [];
		$to_temp = '';
		$to_temp_url = '';
		// Get the newest blog post
		$p = $pages->listed()->first();

		while( $p ) {
			$from_temp = date_create( substr( $p->$date_field(), 0, 10 ) );

			if ( empty( $from_date ) ) {
				$from_date = substr( $p->$date_field(), 0, 10 );
				$from_url = (string) $p->url();
			}

			$p = $p->nextListed();

			if ( $p ) {
				$to = date_create( substr( $p->$date_field(), 0, 10 ) );
				$diff = date_diff( $from_temp, $to );
				
				if ( $diff->d === 0 && $diff->m === 0 && $diff->y === 0 ) {
					// Do nothing
				}
				// If the prev date is exactly one day earlier, we count the streak up by one
				// Also add the prev date as the preliminary end date
				elseif ( $diff->d === 1 && $diff->m === 0 && $diff->y === 0 ) {
					$to_temp = substr( $p->$date_field(), 0, 10 );
					$to_temp_url = (string) $p->url();
					$streak++;
				}
				else {
					// Save streak in array
					$streaks[] = [
						'days' => $streak,
						'from' => $from_date,
						'from_url' => $from_url,
						'to' => $to_temp,
						'to_url' => $to_temp_url
					];
					// Reset everything
					$streak = 1;
					$from_date = substr( $p->$date_field(), 0, 10 );
					$from_url = $p->url();
				}
			}
			else {
				// Then we use the current page!
				$streaks[] = [
					'days' => $streak,
					'from' => $from_date,
					'from_url' => $from_url,
					'to' => $to_temp,
					'to_url' => $to_temp_url
				];
			}
		}

		usort( $streaks, function( $a, $b ) {
			return $a['days'] <=> $b['days'];
		});

		$streaks = array_reverse( $streaks );
		$longest = $streaks[0];
		if ( ! empty( $streaks[1] ) ) {
			$second_longest = $streaks[1];
		}

		if ( ! empty( $streaks[2] ) ) {
			$third_longest = $streaks[2];
		}
		?>
		<dt>Longest Daily Streak:</dt>
		<dd><?= $longest['days']; ?> days,<br />from <a href="<?= $longest['from_url']?>"><?= $longest['from']; ?></a> to <a href="<?= $longest['to_url']?>"><?= $longest['to']; ?></a></dd>

		<?php
		if ( ! empty( $streaks[1] ) ) {
		?>
		<dt>Second Longest Daily Streak:</dt>
		<dd><?= $second_longest['days']; ?> days,<br />from <a href="<?= $second_longest['from_url']?>"><?= $second_longest['from']; ?></a> to <a href="<?= $second_longest['to_url']?>"><?= $second_longest['to']; ?></a></dd>
		<?php } ?>

		<?php
		if ( ! empty( $streaks[2] ) ) {
		?>
		<dt>Third Longest Daily Streak:</dt>
		<dd><?= $third_longest['days']; ?> days,<br />from <a href="<?= $third_longest['from_url']?>"><?= $third_longest['from']; ?></a> to <a href="<?= $third_longest['to_url']?>"><?= $third_longest['to']; ?></a></dd>
		<?php
		}

		return ob_get_clean();
	}


	/**
	 * Calculate current streak.
	 *
	 * @param object $pages Kirby pages object
	 * @return string Current daily streak
	 */
	function current_streak( $pages ) {
		$date_field = $this->date_field;

		ob_start();
		/**
		 * Calculate the number of current daily streak
		 */
		// Get number of consecutive days of posting
		$num = 1;
		$p = $pages->listed()->last();
		$last_date = date_create( substr( $p->$date_field(), 0, 10 ) );
		$started = $p->title();
		$started_url = $p->url();

		while( $p ) {
			// Use date/time field substr to only get day accuracy
			$date = date_create( substr( $p->$date_field(), 0, 10 ) );
			$p = $p->prevListed();
			if ( empty( $p ) ) {
				break;
			}
			$date_prev = date_create( substr( $p->$date_field(), 0, 10 ) );
			$diff = date_diff( $date, $date_prev );
			if ( $diff->d === 0 && $diff->m === 0 && $diff->y === 0 ) {
				// Do nothing -> same day!
			}
			elseif ( $diff->d === 1 && $diff->m === 0 && $diff->y === 0 ) {
				$num++;
				$started = $p->title();
				$started_url = $p->url();
			}
			else {
				break;
			}
		}

		// Check if the last streak day is today or yesterday
		$today = date_create( 'today' );
		$today_diff = date_diff( $today, $last_date );
		$today_diff_days = (int) $today_diff->format( "%R%a" );
		?>
		<dt>Current Daily Streak:</dt>
		<?php if ( $today_diff_days < -1 ) { ?>
		<dd>&ndash;</dd>
		<?php } else { ?>
			<dd><?= $num; ?> day<?php if ( $num > 1 ) { echo 's'; } ?>,<br />started on <a href="<?= $started_url ?>"><?= $started; ?></a></dd>
		<?php }

		return ob_get_clean();
	}


	/**
	 * Calculate most used cameras and focal lengths.
	 *
	 * @param object $pages Kirby pages object
	 * @return string Top five cameras and focal lengths
	 */
	function cameras_focal_lengths( $pages ) {
		$date_field = $this->date_field;

		ob_start();
		/**
		 * Calculate cameras and focal lengths
		 */
		$images = $pages->files()->filterBy('type', 'image');
		$crop_cameras = Kirby()->option( 'florianziegler.photo-stats.cropCameras' );
		$cameras = [];
		$camera_meta = [];
		$fls = [];
		foreach( $images as $image ) {
			// Camera model
			$model = $image->exif()->camera()->model();
			if ( array_key_exists( $model , $cameras ) ) {
				$cameras[$model]++;
				$camera_meta[$model]['last_post'] = $image->parent()->$date_field()->toDate( 'Y-m-d' );
				$camera_meta[$model]['last_post_url'] = $image->parent()->url();
			}
			else {
				$cameras[$model] = 1;
				$camera_meta[$model] = [
					'first_post' => $image->parent()->$date_field()->toDate( 'Y-m-d' ),
					'first_post_url' => $image->parent()->url(),
					'last_post' => '',
					'last_post_url' => '',
				];
			}

			// Focal length
			if ( $image->exif()->focalLength() != NULL ) {
				$temp = explode( '/', $image->exif()->focalLength() );
				$focal_length = number_format( $temp[0] / $temp[1], 0, ',', '.' );
				
				if ( in_array( $model, $crop_cameras ) ) {
					$focal_length = round( $focal_length * 1.5 );

					// Correct for rounding issues to exactly hit the correct focal lengths
					if ( $focal_length == 53 ) {
						$focal_length = 50;
					}

					if ( $focal_length == 84 ) {
						$focal_length = 85;
					}

					if ( $focal_length == 41 ) {
						$focal_length = 40;
					}

					if ( $focal_length == 27 ) {
						$focal_length = 28;
					}
				}

				if ( array_key_exists( 'fl_' . $focal_length, $fls ) ) {
					$fls['fl_' . $focal_length]++;
				}
				else {
					$fls['fl_' . $focal_length] = 1;
				}
			}
		}

		arsort( $cameras );
		arsort( $fls );
		$cameras = array_slice( $cameras, 0, 5 );
		$fls = array_slice( $fls, 0, 5 );
		?>
		<dt>Top Five Cameras:</dt>
		<dd><?php $i = 1; foreach( $cameras as $camera => $num ) {
			echo $i . '. ' . $camera . ' (' . $num . ')<br />';
			echo '<span class="camera-from-to">from <a href="' . $camera_meta[$camera]['first_post_url'] . '">' . $camera_meta[$camera]['first_post'] . '</a> to <a href="' . $camera_meta[$camera]['last_post_url'] . '">' . $camera_meta[$camera]['last_post'] . '</a></span>';
			$i++;
		}?></dd>

		<dt>Top Five Focal Lengths:<br /><small>(full frame equivalent when applicable)</small></dt>
		<dd><?php $i = 1; foreach( $fls as $fl => $num ) {
			echo $i . '. ' . substr( $fl, 3 ) . ' mm (' . $num . ')<br />'; $i++;
		}?></dd>
		<?php
		return ob_get_clean();
	}


	/**
	 * Generate the stats snippet.
	 *
	 * @param object $pages Kirby pages
	 */
	public function generate_stats( $pages ) {
		ob_start();
		$stat_types = [
			'total_number_posts',
			'total_number_images',
			'monochrome_ratio',
			'first_post',
			'first_color_post',
			'longest_time_no_post',
			'longest_streak',
			'current_streak',
			'cameras_focal_lengths'
		];

		if ( ! empty( Kirby()->option( 'florianziegler.photo-stats.statTypes' ) ) ) {
			$stat_types = Kirby()->option( 'florianziegler.photo-stats.statTypes' );
		}

		echo '<dl class="photo-stats">';
		foreach( $stat_types as $stat ) {
			if ( method_exists( $this, $stat ) ) {
				echo $this->$stat( $pages );
			}
		}
		echo '</dl>';

		$stats_content = ob_get_clean();
		file_put_contents( dirname( __DIR__, 1 ) . '/snippets/photo-stats.php', $stats_content );
	}
}