=== Gtbabel ===
Contributors: vielhuber
Tags: bilingual, language, multilingual, translate, translation
Donate link: https://vielhuber.de
Requires at least: 5.3.2
Tested up to: 5.3.2
Requires PHP: 7.2
Stable tag: 4.3.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Gtbabel automatically translates your HTML/PHP pages – server sided!

== Description ==
-   Gtbabel extracts on every page load any page into logical paragraph tokens.
-   Static and dynamic content is deliberately treated the same.
-   All tokens are replaced (if available) by it's translation before rendered.
-   The tokens get dumped (if not available) into gettext, where they can be translated.

== Installation ==
1. Make sure you are using WordPress 5.3 or later and that your server is running PHP 7.2 or later (same requirement as WordPress itself).

2. If you tried other multilingual plugins, deactivate them before activating Gtbabel, otherwise, you may get unexpected results.

3. Install and activate the plugin as usual from the ‘Plugins’ menu in WordPress.

4. Start the Setup wizard from the In the notice message shown.

5. Enjoy!

== Frequently Asked Questions ==
= Does this plugin work with caching plugins? =

Yes.

= Does this plugin work with contact form plugins? =

Yes.

= Can I use language specific subdomains or different domains? =

Currently only a path based approach is supported. However, this feature is planned in the future.

= Where are my language files, logs and settings stored? =

They are stored in /wp-content/uploads/gtbabel.

= Should I checkout my language files, logs and settings into version control? =

Yes (in order to ensure portability).

= What happens to my language files, logs and settings when disabling / uninstalling? =

The files are intentionally preserved.

= Can I use Gtbabel without WordPress? =

Yes (see [repository](https://github.com/vielhuber/gtbabel)).

== Screenshots ==
1. Backend settings
2. Setup wizard

== Changelog ==
= 3.8.7 =
* Add dom change detection

= 3.7.4 =
* Exclude urls being added on 404 pages

= 3.4.8 =
* Switch data from gettext files to sql database

= 3.2.7 =
* Add file support

= 3.0.3 =
* Improve stability

= 2.8.2 =
* Slug collission detection

= 2.7.3 =
* Auto set shared after inital translation

= 2.5.8 =
* Improve performance

= 2.4.8 =
* Improve google translation api

= 2.4.6 =
* Include string checked functionality

= 2.4.5 =
* Polish readme and screenshots

== Upgrade Notice ==
None.