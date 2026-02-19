=== BannerCast – Announcement Bar & Notice Banner ===
Contributors:       yourwordpressusername
Tags:               announcement bar, notice bar, banner, ticker, scrolling text, top bar, notification bar, broadcast
Requires at least:  5.8
Tested up to:       6.5
Requires PHP:       7.4
Stable tag:         2.0.0
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Broadcast beautiful announcement bars — each message with its own style, scroll speed, display rules, and shortcode.

== Description ==

**BannerCast** is a lightweight yet powerful announcement bar plugin for WordPress. Unlike other notice bar plugins that only let you show a single global bar, BannerCast lets you create **unlimited individual messages**, each fully independent with its own look, feel, and behaviour.

Think of it as broadcasting — create and manage your announcements like a pro, then let BannerCast deliver them exactly where and how you want.

= ✨ Key Features =

**Per-Message Control**
Each notice bar message is its own item. Create as many as you need and configure them individually.

**Full Style Customisation**
* Background colour or background image
* Text colour, font family, font size, font weight
* Bar height and horizontal padding
* Bottom/top border with custom colour and thickness
* Extra custom CSS per message

**Smart Display Targeting**
* Show on all pages and posts
* Show only on selected pages or posts
* Shortcode-only mode — place the bar exactly where you want it in content

**Scrolling Ticker or Static Banner**
* Toggle scrolling mode per message
* Adjustable scroll speed (slow to fast)
* Seamless infinite loop with no jumps or glitches
* Pauses on hover

**Interaction Options**
* Position at the top or bottom of the page
* Optional close/dismiss button (remembered via cookie for 1 day)
* Make the whole bar a clickable link (opens in same tab or new tab)

**Per-Message Shortcodes**
Every message automatically gets its own shortcode. Place [bannercast id="5"] anywhere — posts, pages, widget areas, or templates — and the bar renders inline.

**Live Preview**
The edit screen shows a real-time preview of your bar as you adjust colours, fonts, text and scroll settings.

**Clean Admin UI**
* Dedicated top-level BannerCast menu (not buried under Settings)
* Message list with visual preview strip, badges, and one-click shortcode copy
* Enable/disable toggle on the list view without opening the editor

= Use Cases =

* Sales and promotional announcements
* Shipping or delivery notices
* Important site-wide notifications
* Event countdowns and reminders
* Custom cookie or policy notices
* Maintenance mode warnings
* Breaking news tickers

= Shortcode Usage =

After saving a message, copy its unique shortcode from the list or edit screen:

[bannercast id="5"]

Place it in any post, page, Classic widget text area, or your theme template:

<?php echo do_shortcode('[bannercast id="5"]'); ?>

= No Coding Required =

BannerCast is designed for site owners, not just developers. Everything — colours, fonts, positions, pages — is controlled through a clean visual admin interface with a live preview.

== Installation ==

**From the WordPress Admin (recommended)**

1. Go to Plugins > Add New
2. Search for BannerCast
3. Click Install Now, then Activate
4. Navigate to BannerCast in your left admin menu

**Manual Installation**

1. Download the plugin zip file
2. Go to Plugins > Add New > Upload Plugin
3. Upload bannercast.zip and click Install Now
4. Click Activate Plugin
5. Navigate to BannerCast in your left admin menu

== Frequently Asked Questions ==

= How do I create my first announcement bar? =

After activating the plugin, click BannerCast in the left admin menu, then click Add New Message. Fill in your message text, choose your style and display settings, then click Save Message. Your bar will appear on the site immediately.

= Can I have multiple bars active at the same time? =

Yes! Every message is independent. You can have multiple bars active simultaneously — for example, one at the top and one at the bottom, or different bars on different pages.

= How does the shortcode work? =

Each message has a unique shortcode shown on the list and edit pages (e.g. [bannercast id="5"]). Paste it into any post, page, or widget. Set the message's Show On setting to Shortcode Only if you only want it to appear where you place the shortcode, not automatically sitewide.

= The bar overlaps my header / sticky menu. How do I fix it? =

BannerCast automatically adds padding to the body element to push content down. If your theme uses a sticky header with a fixed height, you may need to add a small CSS adjustment in Appearance > Customize > Additional CSS:

body.bc-has-top-bar .site-header { top: var(--bc-top-height, 44px); }

= How long does the close button dismissal last? =

Dismissing the bar stores a browser cookie for 1 day. After 24 hours (or if the user clears their cookies) the bar will reappear.

= Can I change the font to a Google Font? =

Yes. Choose a font family from the built-in list, or paste a custom font family name (e.g. 'Poppins', sans-serif) into the Font Family field. You will need to load the Google Font separately — either via your theme or a font plugin.

= Does BannerCast slow down my site? =

No. BannerCast only loads its small CSS and JS files on the frontend. The JS file is under 3 KB. It uses requestAnimationFrame for the scroll animation — the most performance-friendly approach available.

= Is BannerCast compatible with page builders? =

Yes. BannerCast works alongside Elementor, Beaver Builder, Divi, WPBakery, and other page builders. Use the shortcode block or widget to embed individual bars within your builder layouts.

= Is BannerCast compatible with caching plugins? =

Yes. Compatible with WP Rocket, W3 Total Cache, WP Super Cache, and similar plugins.

= Does it work with WordPress block themes (FSE)? =

Mostly yes. The bar is injected via wp_body_open (top) and wp_footer (bottom), which are both supported by block themes. Some FSE themes may require wp_body_open() to be present in the theme template for the top bar to appear.

== Screenshots ==

1. BannerCast message list — visual card grid with preview strip, badges, shortcode copy, and enable toggle.
2. Edit message screen — full style controls, display targeting, scroll settings, and live preview sidebar.
3. Frontend top bar — scrolling ticker example with gradient background.
4. Frontend static banner — bottom-positioned static banner with close button.
5. Shortcode inline — [bannercast id="5"] rendered inside a page content area.

== Changelog ==

= 2.0.0 =
* New: Complete rewrite with per-message Custom Post Type architecture
* New: Dedicated top-level BannerCast admin menu
* New: Individual shortcode per message ([bannercast id="X"])
* New: Per-message style settings — colour, font, background image, height, border
* New: Per-message scroll settings — enable/disable, adjustable speed
* New: Per-message display targeting — all pages, selected pages/posts, shortcode only
* New: Live preview panel on the edit screen
* New: Message list with visual preview strip and badge indicators
* New: Enable/disable toggle directly from the list view
* New: Seamless RAF-based scroll ticker (no CSS animation glitches)
* New: Body padding auto-adjusts when multiple bars are stacked

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major update: each message is now its own independent item with individual styles and shortcodes. If upgrading from 1.0.0, please recreate your messages in the new interface — old global settings are not migrated automatically.

== Privacy Policy ==

BannerCast stores a browser cookie (bc_closed_{id}) when a visitor dismisses a bar using the close button. This cookie is used only to remember the dismissal preference and expires after 1 day. No personal data is collected or transmitted.
