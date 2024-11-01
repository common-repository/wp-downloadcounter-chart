=== Plugin Name ===
Contributors: m.mrotzek
Tags: counter, charts, google, widget, sidebar, dashboard
Requires at least: 2.8
Tested up to: 2.9
Stable tag: 1.3

Visualization of your downloads based on the Download Counter plugin.

== Description ==

This plugin adds a **widget** to display download statistics based on the [Download Counter](http://wordpress.org/extend/plugins/wp-downloadcounter/) 
plugin on your site. The sidebar widget will show a highly configurable chart based on the 
[Google Chart API](http://code.google.com/apis/chart/). Furthermore it offers a **dashboard widget** that summarizes your 
downloads, to get information quick at a glance.

The plugin supports following chart types: 

* Bar (horizontal)
* Pie 
* Pie 3D

You can include a chart of your downloads on a **page** or in a **post** using the downloadcounterchart-***shortcode***. 
Of course a download chart is includable in your **theme**. See [extended documentation](http://wordpress.org/extend/plugins/wp-downloadcounter-chart/other_notes/) 
for detailed explanations for that.

== Installation ==

*Note: This plugin depends on the <a href="http://wordpress.org/extend/plugins/wp-downloadcounter/">Download Counter</a> plugin 
(version 1.01), so you may need to install it as well.*

1. Upload `wp-downloadcounter-chart.zip` to the `/wp-content/plugins/` directory and unzip it. You may also use the 
install manager of wordpress to get the plugin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Add the Download Counter Chart widget to a sidebar and edit the options of the widget to your needs. You may simple add the widget to a 
sidebar and click save to setup the widget with default options.
4. You can enable the Download Counter dashboard widget by activating it in the screen options area of the dashboard page.
5. For usage in your theme or in a page / post see the [extended documentation](http://wordpress.org/extend/plugins/wp-downloadcounter-chart/other_notes/) 

== Usage ==

There are several ways to include a chart of your downloads on your site.

= Page / Post (Shortcode) =
<pre>[downloadcounterchart chart_type="pie" chart_width="490" chart_height="190" label_color="000000" label_size="11" sqlcondition="" bar_color="0066CC90" bar_height="-1" bg_color="FFFFFF00"]</pre> 
Parameters:

* *chart_type* - Type of the chart. Valid values: <code>bar_horizontal</code>, <code>pie</code> or <code>pie3d</code>.
* *chart_width* - Width of the chart image in px. May be about the 2.5 times size of the height for a pie chart.
* *chart_height* - Width of the chart image in px.
* *label_color* - Hex color value for labels, optional with additional opacity value (e.g. 50% black = 00000050).
* *label_size* - Font size of the labels.
* *sqlcondition* - Optional SQL where statement, leave blank for all downloads.
* *bar_height* - Bar chart only. You can explicitly set the bar height, but it may result in a chart that does not include 
all data, if the chart height is not big enough. Recommend to set <code>-1</code> for auto-sizing.
* *bg_color* - Background color. Hex color value, optional with additional opacity value (e.g. transparent = FFFFFF00)

= Theme =
<pre>downloadcounterchart_img($chart_type, $chart_width, $chart_height, $label_color = '333333', $label_size = '11', $sqlcondition = '', $bar_color='', $bar_height='', $bg_color='')</pre>
Parameters:

see section above

Example:
<pre>if (function_exists('downloadcounterchart_img') && class_exists('DownloadCounterChartApi')){
	downloadcounterchart_img(DLC_CHART_PIE, 490, 190, '000000', '11', '', '0066CC', '-1', 'FFFFFF00');
}</pre>

== Screenshots ==

1. Dashboard widget giving a quick overview of your downloads.
2. Sidebar widget options to configure the rendered chart to your custom needs.
3. Example widget output on your site.
4. Pie chart in a post.

== Changelog ==

Planned Features:

1. I18N

= 1.2 =
* Pie and Pie3d chart types
* shortcode to include in page/post
* optimized bar height configuration

= 1.1 =
* full configurable chart
* redesign of dashboard widget

== Upgrade Notice ==

= 1.2 =
Update your settings of the downloadcounterchart widget.
= 1.3 =
Fixed Bug: When files have been downloaded the same number of times, only one appears (Reported by MasterNoun, Thanks)

== Frequently Asked Questions ==

none
