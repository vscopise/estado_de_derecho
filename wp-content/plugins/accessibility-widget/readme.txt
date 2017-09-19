=== Accessibility Widget ===
Contributors: webgrrrl
Tags: accessibility, widget, widgets, formatting, css, style, text, wcag
Requires at least: 3.0.1
Tested up to: 4.8
Stable tag: 2.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a sidebar widget to change text size in your WP site. Always go back to the Widget settings after each version update to Save your settings.

== Installation ==

= Automatic installation =
1. In your WordPress dashboard, select Plugins / Add.
1. Search for Accessibility Widget, and select Install.
1. Go to Appearances / Widgets.
1. Drag the Accessibility Widget to your widget area and select Save.

= Manual installation =
1. Upload `accessibilitywidget.php` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Place `<?php do_action('widget_accesstxt'); ?>` in your templates.

== Frequently Asked Questions ==

= How do I use this plugin? =

Follow the Installation instructions. Below are options you can set in the widget:-

*Resize the following HTML/CSS tags:*
Specify all the HTML or CSS tags you want to resize in this section. By default, the HTML tag body, p, li and td is set.
Different WordPress theme may have different and unique stylesheet classes and IDs. You can switch on your browser's built-in Developer Tools and use the Inspect Element option to detect what HTML/CSS codes to use.

*Set to these sizes:*
This is where you set the font-size you want to enable for the users to use (read up on the CSS font-size property at W3Schools). The simplest font-size group you can use are smaller, inherit, larger. By default, the size are set as 90%, 100%, 110%, and 120%.

*Set controller text:*
By default, the widget displays the letter "T" depending on the number of font sizes you use; that is, if you set only two font-size, then only two letter T appears, and so forth. Starting with version 1.2, you can set it to use more meaningful words, like Small, Normal, Large for each size you specify. By default, the controller texts are set as 90%, 100%, 110%, and 120%.

*Set tooltip text on mouse hover:*
Write descriptive info here as tooltips, which appear when you move your mouse over a text controller. Each tooltip corresponds to the respective controller text as arranges in the "Set controller text" section. By default, the tooltip texts read as "90%", "100%", "110%", and "120%".

= Can I use this on a non-widgetized website / theme? =

Not tested, but theoretically yes, you can. Follow the Manual installation steps in the Installation section for details.

== Changelog ==

= 2.0.1 =
* Updated WP_Widget constructor to remove deprecated method.

= 2 =
* Added a new function to display tooltip on mouse hover (HTML title tag).
* Modified default settings for font sizes and controllers.

= 1.2.1 =
* Debugged controller text to allow a single character/word or multiple character/word (but multiple characters MUST match the amount of font-size used).

= 1.2 =
* Added option to customise controller text, i.e. the words you click on to change the text size.

= 1.1.1 =
* Added HTML tags p, li and td options by default for first-time installation.

= 1.1 =
* Added options into the widget form for easy customisation.

= 1.0 =
* First release.
