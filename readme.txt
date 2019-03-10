=== Bean Unity3d WebGL ===
Contributors: Beanalby
Donate link: https://www.paypal.me/JasonViers
Tags: Unity, Unity3d, games, shortcode
Requires at least: 5.0
Tested up to: 5.1
Requires PHP: 5.3.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bean's Unity3d WebGL Manager allows you to upload and manage your WebGL builds of Unity3d projects to Wordpress.

== Description ==

After installation, the "Bean Unity3d" menu item is added on the left.

The main screen allows you to create games by name for storage in Wordpress.

After creating a game, you can upload the files for the game.  These will be the contents of the `Build` of the WebGL build - `UnityLoader.js` and the rest of the files next to it (their names vary based on the name of the folder you use for the WebGL Build).

These games can then be embedded in pages or posts with a shortcode, which the plugin will help you generate.
== Installation ==

Installation is standard for any Wordpress plugin.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/bean-unity3d-webgl` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Click on the "Bean Unity3d" menu item on the left to start managing your games.

== Frequently Asked Questions ==

= I can't upload any files larger than 8mb, help! =

This is a common server configuration in php, you need to edit your php configuration to allow for this.  See discussions such as https://stackoverflow.com/questions/2184513/change-the-maximum-upload-file-size

= I can embed the game, but then I can't type in any other text boxes on the page! =

By default, Unity WebGL will process all keyboard input send to the page, regardless of whether the WebGL canvas has focus or not. This is done so that a user can start playing a keyboard-based game right away without the need to click on the canvas to focus it first. However, this can cause problems when there are other HTML elements on the page which should receive keyboard input, such as text fields - as Unity will consume the input events before the rest of the page can get them. If you need to have other HTML elements receive keyboard input, you can change this behavior using the WebGLInput.captureAllKeyboardInput property.

See https://docs.unity3d.com/Manual/webgl-input.html for more information.

== Screenshots ==

1. The main management screen, which allows you to upload new games, edit your existing games, or create shortcodes for them.

2. The edit game screen, which allows you to upload the WebGL files for your game.

3. The Make Shortcode screen, which assists in putting together a shortcode you'll be able to use to embed this game in your page or post.

== Changelog ==

= 0.1 =
* Initial public release


