<?php
/*
 Plugin Name: Download Counter Chart
 Plugin URI: http://www.mmrotzek.de/en/software-development/wp-downloadcounter-chart 
 Plugin Description: Visualization of <a href="http://wordpress.org/extend/plugins/wp-downloadcounter/">Download Counter</a> plugin with Google Chart. It serves a sidebar and dashboard widget.
 Version: 1.2
 Author: Michael Mrotzek
 Author URI: http://www.mmrotzek.de
 */

/*  Copyright 2009  Michael Mrotzek  (email : develop@mmrotzek.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define("DLC_CHART_BAR_HOR","bar_horizontal");
define("DLC_CHART_PIE","pie");
define("DLC_CHART_PIE3D","pie3d");


$dc_base_page = 'edit.php?page=wp-downloadcounter/downloadcounter-options.php';

function downloadcounterchart_checkDependencies() {
	$plugin = 'DownloadCounter';
	if (!function_exists($plugin) && !class_exists($plugin)){
		return 'Plugin: '.$plugin.' missing. This plugins does not work without it.';
	}
	return true;
}


add_action('wp_dashboard_setup', 'downloadcounterchart_dashboard_setup');
add_action('admin_head', 'downloadcounterchart_admin_head');

add_action('init', 'downloadcounterchart_widgets_init', 1);

function downloadcounterchart_widgets_init() {
	register_widget('DownloadCounterChartWidget');
}

// register shortcut to include downloadcounterchart in pages or posts
add_shortcode('downloadcounterchart', 'downloadcounterchart_shortcode_handler');


#
# CHART
#

function get_downloadcounterchart_img($chart_type, $chart_width, $chart_height, $label_color = '333333', $label_size = '11', $sqlCondition = '', $bar_color='', $bar_height='', $bg_color='') {
	$dlc = new DownloadCounterChartApi($chart_width, $chart_height,$sqlCondition);
	$dlc->bar_color = $bar_color;
	$dlc->bar_height = $bar_height;
	$dlc->label_color = $label_color;
	$dlc->label_size = $label_size;
	$dlc->bg_color = $bg_color;
	
	return $dlc->get_chart_img($chart_type);
}

function downloadcounterchart_img($chart_type, $chart_width, $chart_height, $label_color = '333333', $label_size = '11', $sqlCondition = '', $bar_color='', $bar_height='', $bg_color='') {
	echo get_downloadcounterchart_img($chart_type, $chart_width, $chart_height, $label_color, $label_size, $sqlCondition, $bar_color, $bar_height, $bg_color);
}

function downloadcounterchart_shortcode_handler($atts, $content=null, $code="") {
	extract($atts);
	
	if(empty($chart_type) || empty($chart_width) || empty($chart_height)) {
		$o  = '<em><strong>ERROR:</strong> You have to specify the required parameters. The following is the minimum definition:</em>';
		$o .= '<pre>[downloadcounterchart chart_type="pie" chart_width="490" chart_height="190"]</pre>';
		
		return $o;
	}
	return get_downloadcounterchart_img($chart_type, $chart_width, $chart_height, $label_color, $label_size, $sqlcondition, $bar_color, $bar_height, $bg_color);
}


#
# DOWNLOADCOUNTERCHART 
#

class DownloadCounterChartApi {
	var $googleChartApiBaseUrl = 'http://chart.apis.google.com/chart?';
	var $chart_width;
	var $chart_height;
	
	var $sqlCondition = '';
	
	var $label_color = '';
	var $label_size = '';
	var $bar_height = '';
	var $bar_color = '';
	var $bg_color = '';
	
	function DownloadCounterChartApi($chart_width, $chart_height, $sqlCondition='') {
		$this->chart_width = $chart_width;
		$this->chart_height = $chart_height;
		$this->sqlCondition = $sqlCondition;
	}
	
	function get_chart_img($chart_type=DLC_CHART_BAR_HOR) {
		$tmpVal = array();
		$sum=0;
	
		$results = $this->get_downloads();
		
		for($i=0; $i < count($results); $i++) {
			$c = $results[$i]->download_count;
			$tmpVal[$results[$i]->download_name] = $c;
			$sum += $c;
		}
	
		// sort by value for bar chart
		if($chart_type === DLC_CHART_BAR_HOR) {
			arsort($tmpVal);
		}
	
		$labels = '';
		$names = '';
		#$axis = array('x1' => '', 'x2' => '', 'yl' => '', 'yr' => '');
		$values = '';
		
		$i = 0;
		foreach($tmpVal as $val => $k) {
			$percent = 100/$sum * $k;
			$values .= $percent.',';
			$names .= 't'.$val.' ('.$k.'),'.$this->label_color.',0,'.$i.','.$this->label_size.'|'; // text: ,color,value,size
			$labels .= $val.' ('.$k.')|';
			#$axis['x1'] .= $val.'|';
			#$axis['x2'] .= $k.'|';
			
			$i++;
		}
		
		// set further axis label values
		#$axis['yl'] .= '0|50|100|'; // percent scale on left vertical axis
	
		// clean up
		$last = strrpos($names,'|');
		if($last >- 1){
			$names = substr($names,0,$last);
		}
		$last2 = strrpos($values,',');
		if($last2 >- 1){
			$values = substr($values,0,$last2);
		}
		
		// generate request url
		$url = $this->googleChartApiBaseUrl;
		// chart type
		switch($chart_type) {
			case DLC_CHART_PIE:
				$url .= 'cht=p';
				break;
				
			case DLC_CHART_PIE3D:
				$url .= 'cht=p3';
				break;
			
			case DLC_CHART_BAR_HOR:	
				// falltrough
				
			default :
				$url .= 'cht=bhg';
				break;
		}
		
		// size
		$url .= '&amp;chs='.$this->chart_width.'x'.$this->chart_height;
		
		// bar dimension
		if($chart_type === DLC_CHART_BAR_HOR) {
			if(intval($this->bar_height) > 0) {
				$url .= '&amp;chbh='.$this->bar_height; // bar height
			} else {
				$url .= '&amp;chbh=a,4,4'; // auto size bars
			}
		}
		
		// data
		$url .= '&amp;chd=t:'.$values; 
		
		// labels
		switch($chart_type) {
			case DLC_CHART_PIE3D:
				// falltrough
				
			case DLC_CHART_PIE:
				$url .= '&amp;chl='.$labels;
				break;
			
			case DLC_CHART_BAR_HOR:	
				// falltrough
				
			default :
				$url .= '&amp;chm='.$names;
				break;
		}
		
		$url .= '&amp;chco='.$this->bar_color;
		$url .= '&amp;chf=bg,s,'.$this->bg_color; // bg color
		//$url .= '&amp;chtt=Download Statistics'; // title
	
		#echo $url;
	
		return '<img src="'.$url.'" width="'.$this->chart_width.'" height="'.$this->chart_height.'" alt="Download Chart"/>';
	}
	
	private function get_downloads() {
		global $wpdb;
	
		$sql = "SELECT * FROM ".$wpdb->prefix."downloadstats";
		$excludeSQLCondition = strip_tags(stripslashes($this->sqlCondition));
		if(!empty($excludeSQLCondition)) {
			$sql .= ' WHERE '.$excludeSQLCondition;
		}
		#$sql .= ' ORDER BY download_count';
		
		$results = $wpdb->get_results($sql);
		return $results;
	}
	
}

#
# SIDEBAR WIDGET
#

/**
 * DownloadCounterChartWidget Class
 */
class DownloadCounterChartWidget extends WP_Widget {
    /** constructor */
    function DownloadCounterChartWidget() {
        $widget_ops = array('classname' => 'download_counter_chart', 'description' => __( 'Visualization of your downloads') );
        $this->WP_Widget('downloadcounterchart', __('Download Counter Chart'), $widget_ops);
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		$options = get_option('downloadcounterchart_widget');
		echo $before_widget;
		if ( $title ) {
        	echo $before_title . $title . $after_title;
		}
		echo '<div id="downloadcounterchartwrap">' . get_downloadcounterchart_img($instance['chart_type'], $instance['width'],$instance['height'],$instance['labelcolor'],$instance['labelsize'],$instance['exludesqlcondition'],$instance['barcolor'],$instance['barheight'],$instance['bgcolor']) . '</div>';
		echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
		$instance = $old_instance;
   		foreach($this->optionData as $field => $fieldConf) {
			$instance[$field] = strip_tags(stripslashes($new_instance[$field]));
			if ( empty($instance[$field]) ) {
				$instance[$field] = $fieldConf['default'];
			}
			if($fieldConf['isInt']) {
				$instance[$field] = sprintf('%02d', intval($instance[$field]));
			}
		}
		
    	return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        if(downloadcounterchart_checkDependencies() !== true) {
			echo '<p class="wp-downloadcounter-chart-warning">'.downloadcounterchart_checkDependencies().'</p>';
		}
		
        foreach($this->optionData as $field => $fieldConf) {
        	echo '<p><label for="'.$this->get_field_id($field).'">';
        	_e($fieldConf['title']);
        	
        	$value = '';
        	if($fieldConf['isInt']) {
				$value = $instance[$field];
			} else if($fieldConf['isText']) {
				$value = esc_attr($instance[$field]);
			} else {
				$value = $instance[$field]; 
			}
			
			$class = 'class="widefat"';
			$size = '';
			if(intval($fieldConf['size']) > 0) {
				$size = 'size="'.$fieldConf['size'].'"';
				$class = '';
			}
			
        	if(!empty($size)) {
        		echo '<br/>';
        	}
        	if(is_array($fieldConf['values'])) {
        		echo '<select '.$class.' id="'.$this->get_field_id($field).'" name="'.$this->get_field_name($field).'" '.$size.' >';
        		foreach($fieldConf['values'] as $key => $title) {
        			$selected = ($value == $key) ? ' selected="selected" ' : '';
        			echo '<option value="'.$key.'" '.$selected.'>'.$title.'</option>';
        		}
        		echo '</select>';
        	} else {
        		echo '<input '.$class.' id="'.$this->get_field_id($field).'" name="'.$this->get_field_name($field).'" type="text" value="'.$value.'" '.$size.' />';
        	}
        	echo '</label>';
        	
        	if(!empty($fieldConf['description'])) {
        		if(!empty($size)) {
        			echo '<br/>';
        		}
        		echo '<small>'.$fieldConf['description'].'</small>';
        	}
        	echo '</p>';
        	
        }
    }
    
    var $optionData = array(
		'title' => array(
			'title' 	=> 'Title', 
			'description' => '',
			'default' 	=> 'Download Statistics',
			'isInt'		=> 0,
			'isText'	=> 1
		),
		'chart_type' => array(
			'title' 	=> 'Chart type',
			'description' => '', 
			'default' 	=> DLC_CHART_BAR_HOR,
			'size'		=> '',
			'isInt'		=> 0,
			'isText'	=> 0,
			'values'	=> array(DLC_CHART_BAR_HOR => 'Horizontal Bar', DLC_CHART_PIE => "Pie", DLC_CHART_PIE3D => "Pie 3d")
		),
		'width' => array(
			'title' 	=> 'Width',
			'description' => '', 
			'default' 	=> '190',
			'size'		=> '3',
			'isInt'		=> 1,
			'isText'	=> 0
		),
		'height' => array(
			'title' 	=> 'Height', 
			'description' => '',
			'default' 	=> '100',
			'size'		=> '3',
			'isInt'		=> 1,
			'isText'	=> 0
		),
		'barcolor' => array(
			'title' 	=> 'Color of bar or piece of pie', 	
			'description' => 'Color (hex) + optional opacity (default:0066CC90). You may specify more than one color (| seperated) to define different colors for each bar/piece.',
			'default' 	=> '0066CC90',
			'size'		=> '',
			'isInt'		=> 0,
			'isText'	=> 0
		),
		'labelcolor' => array(
			'title' 	=> 'Label color', 
			'description' => 'Color (hex) + optional opacity (default:333333)',
			'default' 	=> '333333',
			'size'		=> '7',
			'isInt'		=> 0,
			'isText'	=> 0
		),
		'bgcolor' => array(
			'title' 	=> 'Background color', 
			'description' => 'Color (hex) + optional opacity (default:FFFFFF00)',
			'default' 	=> 'FFFFFF00',
			'size'		=> '7',
			'isInt'		=> 0,
			'isText'	=> 0
		),
		'labelsize' => array(
			'title' 	=> 'Label font size', 
			'description' => '',
			'default' 	=> '11',
			'size'		=> '3',
			'isInt'		=> 1,
			'isText'	=> 0
		),
		'barheight' => array(
			'title' 	=> 'Bar height', 
			'description' => 'Bar chart only. You can explicitly set the bar height, but it may result in a chart that does not include all data, if the chart height is not big enough. <strong>Recommend</strong> to set -1 for auto-sizing.',
			'default' 	=> '-1',
			'size'		=> '3',
			'isInt'		=> 1,
			'isText'	=> 0
		),
		'exludesqlcondition' => array(
			'title' 	=> 'Optional SQL where statement', 
			'description' => '(e.g download_name NOT LIKE \'private%\')',
			'default' 	=> '',
			'size'		=> '',
			'isInt'		=> 0,
			'isText'	=> 1
		),
	);

} // class DownloadCounterChartWidget

#
# DASHBOARD
#

function downloadcounterchart_dashboard_setup() {
	wp_add_dashboard_widget( 'downloadcounterchart_wp_dashboard', __( 'Download Counter' ), 'downloadcounterchart_dashboard_widget' );
}

function downloadcounterchart_dashboard_widget() {
	global $wpdb,$dc_base_page;

	if(downloadcounterchart_checkDependencies() !== true) {
		echo '<p class="wp-downloadcounter-chart-warning">'.downloadcounterchart_checkDependencies().'</p>';
		return;
	}
	
	#$x .= '<p class="sub"></p>';
	$x .= '<div class="table">';
	$x .= '<table class="wp-downloadcounter-chart">';
	$x .= '<thead>';
	$x .= '<tr><th class="first dcc-name">Name</th><th class="dcc-count">Count</th><th class="last dcc-last">Last</th></tr>';
	$x .= '</thead>';
	$x .= '<tbody>';

	$results = $wpdb->get_results("SELECT *, date_format(download_last, '%d.%m.%Y %H:%i') as last FROM ".$wpdb->prefix."downloadstats ORDER BY download_count DESC");
	for($i=0;$i<count($results);$i++) {
		$id = $results[$i]->download_id;
		$c = $results[$i]->download_count;
		$name = $results[$i]->download_name;
		$class = $i%2==0 ? 'even' : 'odd';
		$x .= '<tr class="'.$class.'">';
		$x .= '<td class="first dcc-name"><a href="'.get_settings('siteurl').'/wp-admin/'.$dc_base_page.'&amp;action=view&amp;download_id='.$id.'" title="View details of download item">'.$name.'</a></td>';
		$x .= '<td class="dcc-count">'.$c.'</td>';
		$x .= '<td class="last dcc-last">'.$results[$i]->last.'</td>';
		$x .= '</tr>';
	
		// add link: http://staging.mmrotzek.de/blog/wp-admin/edit.php?page=wp-downloadcounter%2Fdownloadcounter-options.php&action=add
	}
	$x .= '</tbody>';
	$x .= '</table>';
	$x .= '</div>';
	
	echo $x;
}

#
# ADMIN
#

function downloadcounterchart_admin_head() {
    $url = get_settings('siteurl');
    $url = $url . '/wp-content/plugins/wp-downloadcounter-chart/wp-admin.css';
    echo '<link rel="stylesheet" type="text/css" href="' . $url . '" />';
}

?>
