# Photo Stats – Plugin for Kirby

**A [Kirby](https://getkirby.com) plugin to calculate and display statistics about published photos.**

![Stats example](/.github/stats-example.jpg)

Have a look at a live example of a [stats page on my photo blog](https://florian.photo/stats).

---

## Installation

Download/clone this repo into the `site/plugins` folder of your Kirby installation.

---

## Configuration

There are a couple of configuration options, which are most likely required.

### Define your source pages

By default the plugin uses `$site->pages()` as the base for the stats generation and it will include the images uploaded to these pages, when calculating the statistics.

You can define a callback function in your `site/config/config.php` file to override this default, just make sure it returns a [pages collection](https://getkirby.com/docs/reference/objects/cms/pages):

```php
'florianziegler.photo-stats.pages' => 'my_custom_callback_function',
```

You should probably put your callback function into a [custom plugin](https://getkirby.com/docs/guide/plugins/custom-plugins).

### Define your date field

You need to define which field contains the date of a page (it needs to be type [`date`](https://getkirby.com/docs/reference/panel/fields/date)), so that the plugin can calculate date based statistics, such as the longest streak of daily posts. Defaults to `published`.

```php
'florianziegler.photo-stats.dateField' => 'published',
```

### Define crop factor cameras

The focal length statistics are calculated under the assumption that cameras are full frame. For most crop factor cameras you need to use a 1.5 multiplier to compute the full-frame equivalent focal length, eg. a 23mm lens on a crop sensor camera is a 35mm in terms of full frame.

To let the plugin know for which camera models to use the 1.5 multiplier, you can define them in your options:

```php
'florianziegler.photo-stats.cropCameras' => [ 'X100S', 'X-T1', 'X-Pro2' ],
```

### Define medium format cameras

Medium format sensors are larger than full frame, so their focal lengths are multiplied by 0.79 to get the full-frame equivalent (based on a 44 x 33 mm sensor like in the Fujifilm GFX cameras), eg. a 63mm lens is a 50mm in terms of full frame.

The Fujifilm GFX cameras are included by default. To change the list, define the camera models in your options:

```php
'florianziegler.photo-stats.mediumFormatCameras' => [ 'GFX100S', 'GFX100RF' ],
```

The model names have to match the camera model in the EXIF data of your images exactly.

### Decide which stats you want to include

The following statistics are available and included by default:

- `total_number_posts`
- `total_number_images`
- `monochrome_ratio`
- `first_post`
- `first_color_post`
- `longest_time_no_post`
- `longest_streak`
- `current_streak`
- `cameras_focal_lengths`

If you only want to show some of the stats, you can define them in your options as an array:

```php
'florianziegler.photo-stats.statTypes' => [ 'total_number_posts', 'longest_streak' ],
```

---

## Usage

### Adding the stats snippet
Use the snippet `photo-stats` to display the photo stats:

```php
snippet( 'photo-stats' );
```

### Adding the color blueprint
Add the `photo-stats/color` field to your file/image blueprint, so that you can set the color manually:

```yml
# site/blueprints/files/default.yml
title: Default file
fields:
  # your fields
  color: photo-stats/color
```

This field is used to calculate the "Monochrome ratio" statistic.

There is a hook in place, which will set the value of this field automatically, when you upload images via the panel. (The Imagick PHP extension is required. Please note, that this will only work for images uploaded after the plugin has been installed.)

### Skip the color detection for some files

Loading very large files (e.g. high resolution TIFFs) with Imagick is slow and needs a lot of memory. You can exclude files from the automatic color detection by their [file template](https://getkirby.com/docs/guide/files/file-blueprints):

```php
'florianziegler.photo-stats.ignoreFileTemplates' => [ 'print-master' ],
```

Defaults to an empty array, so all images are checked.

---

## Update the stats page

The statistics are set to being recalculated each time you change a page status, eg. when you publish a new blog post.

You can then manually generate/update the stats page by visiting `https://YOUR_SITE_URL/generate-photo-stats`.

To automate this process and let it work in the background, you need to set up a cron job.

### Set a custom route

Set a custom endpoint for the cron job:

`generate-photo-stats` is used by default, but you should define a unique endpoint like so:

```php
'florianziegler.photo-stats.cronRoute' => 'generate-photo-stats-XYZ123',
```

### Cron Job

Setup a cron job on your server to run once every minute:

1. Log in via SSH.
2. Type `crontab -e` in your terminal.
3. Paste the following line `* * * * * curl https://YOUR_SITE_URL/generate-photo-stats > /dev/null` (replace with the actual URL and cron endpoint, see above).
4. Then type `:wq` (colon wq) to save.

The stats are only generated, when there is something to update (see above), so most of these calls return right away.

### Logging

By default, failed runs are logged to `photostats.log` in Kirby's logs folder (`site/logs` by default), including the error message. After a failed run, the stats are generated again on the next call.

You can change what is logged:

```php
'florianziegler.photo-stats.log' => 'all',
```

- `'errors'` (default): Only failed runs.
- `'all'`: Every run, including its duration and memory usage, and skipped runs (see below).
- `false`: Nothing is logged.

Only one run can happen at a time: If the stats are still being generated when the next call comes in, that call is skipped. The lock file for this is stored in Kirby's cache folder (`site/cache` by default).

### Hook after generating the stats

After every successful run, the plugin triggers the `florianziegler.photo-stats.generate:after` hook. It gets the `$pages` the stats are calculated from (the result of your callback function, see [Define your source pages](#define-your-source-pages), otherwise `$site->pages()`).

#### Page cache

If you use Kirby's page cache, the cached stats page keeps showing the old stats, since generating them does not change any content. Use the hook to remove the stats page from the cache:

```php
'hooks' => [
	'florianziegler.photo-stats.generate:after' => function ($pages) {
		kirby()->cache('pages')->remove('stats.latest.html');
	},
],
```

Replace `stats` with the id of the page that shows the stats snippet. On a multi-language site, the cache key also contains the language code, e.g. `stats.en.latest.html`, so remove one key per language.

---

## License

[MIT License](https://github.com/florianziegler/kirby-photo-stats/blob/main/LICENSE) Copyright © 2024 Florian Ziegler

---

## Questions/feedback

If you have any feedback on how to improve the plugin or fix a bug, feel free to [create an issue](https://github.com/florianziegler/kirby-photo-stats/issues).

If you want to leave comment or just say hello, please [do so](https://florianziegler.com).