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
'florianziegler.photo-stats.pages' => my_custom_callback_function(),
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
4. The type `:wq` (colon wq) to save.

---

## License

[MIT License](https://github.com/florianziegler/kirby-photo-stats/blob/main/LICENSE) Copyright © 2024 Florian Ziegler

---

## Questions/feedback

If you have any feedback on how to improve the plugin or fix a bug, feel free to [create an issue](https://github.com/florianziegler/kirby-photo-stats/issues).

If you want to leave comment or just say hello, please [do so](https://florianziegler.com).