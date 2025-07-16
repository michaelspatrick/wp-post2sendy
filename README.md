# WP Post to Sendy Plugin

This WordPress plugin integrates with [Sendy](https://sendy.co/) to allow automatic post pushing to your Sendy newsletter list.

## Features

- Adds a Metabox to posts for selecting a Sendy list and campaign details
- Sends the post content as a campaign to Sendy upon publishing or updating
- Uses the Sendy API for campaign creation and scheduling
- Customizable Sendy settings via the WordPress admin interface

## Installation

1. Download the plugin and unzip the files into your WordPress plugin directory (`wp-content/plugins/wp-post2sendy/`).
2. Activate the plugin through the WordPress admin panel.
3. Go to **Settings > WP Post2Sendy** to configure your Sendy installation URL, API key, and list IDs.

## Usage

1. Create or edit a post in WordPress.
2. Use the **Sendy Campaign Settings** metabox to enable sending and fill in the campaign details.
3. When the post is published or updated, a campaign will be created and sent via Sendy.

## Configuration Options

In the **Settings > WP Post2Sendy** admin page, you can set:
- Sendy installation URL
- API key
- List ID for your default newsletter list
- From name and email
- Reply-to email
- Whether to auto-send on publish

## Developer Notes

Main plugin file: `wp-post2sendy-plugin.php`  
Settings panel: `settings.php`  
Sendy API interaction: `sendy.php`  
Post metabox and options: `metabox.php`

## License

This plugin is released under the GPLv2 License.
