<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2023 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/


function html_custom_header_box($title, $width, $div, $cell_padding, $align, $add_text, $add_label = false) {
//function html_custom_header_box($header, $hlink = false, $href = '', $link = '') {
//     print "<table calss='cactiTable'>
// 		<tr>
// 			<td>
// 				<table class='cactiTable'>
// 					<tr>
// 						<td class='textHeaderDark' align='left' style='padding: 3px;' colspan='100'>
// 							<table>
// 								<tr>
// 									<td class='textHeaderDark' align='left'>";
// 										print "<b>$header&nbsp;</b>";
// 										if ($hlink) print "[$hlink]";
// 	print '</td>';

// 	if ($href !='' && $link !='') {
// 		print "<td class='textHeaderDark' align='right'>";

// 		if (is_array($href) && is_array($link)) {
// 			foreach ($href as $key => $value){
// 				print "<a style='color:yellow' href='$value' alt='titlt'>" . $link[$key] . '</a>&nbsp;';
// 			}
// 		} else {
// 			print "<a style='color:yellow' href='$href' alt='titlt'>$link</a>";
// 		}

// 		print '</td>';
// 	}

// 	print '</tr>
// 			</table>
// 		</td>
// 	</tr>';

	static $table_suffix = 1;

	if ($add_label === false) {
		$add_label = __('Add', 'reportit');
	}

	$table_prefix = basename(get_current_page(), '.php');;
	if (!isempty_request_var('report')) {
		$table_prefix .= '_' . clean_up_name(get_nfilter_request_var('report'));
	} elseif (!isempty_request_var('tab')) {
		$table_prefix .= '_' . clean_up_name(get_nfilter_request_var('tab'));
	} elseif (!isempty_request_var('action')) {
		$table_prefix .= '_' . clean_up_name(get_nfilter_request_var('action'));
	}
	$table_id = $table_prefix . $table_suffix;

	if ($title != '') {
		print "<div id='$table_id' class='cactiTable' style='width:$width;text-align:$align;'>";
		print "<div>";
		print "<div class='cactiTableTitle' style='width:$width'><span>" . ($title != '' ? $title:'') . '</span></div>';
		print "<div></div>";
		//print "<div style='background: rgba(0,0,0,0.9);text-align: right;padding: 3px 0px;border-bottom: 1px solid rgba(0,0,0,0.5);font-weight: 700;font-size: 1em;'  class='' ><span>" . ($add_text != '' ? "<a href='" . html_escape($add_text) . "'>" . "<img src='./images/bar.gif' title='Graph View'>" . '</a>':'') . '</span></div>';
		print '</div>';

		if ($div === true) {
			print "<div id='$table_id" . "_child' class='cactiTable'>";
		} else {
			print "<table id='$table_id" . "_child' class='cactiTable' style='padding:" . $cell_padding . "px;'>";
		}
	} else {
		print "<div id='$table_id' class='cactiTable' style='width:$width;text-align:$align;'>";

		if ($div === true) {
			print "<div id='$table_id" . "_child' class='cactiTable'>";
		} else {
			print "<table id='$table_id" . "_child' class='cactiTable' style='padding:" . $cell_padding . "px;'>";
		}
	}

	$table_suffix++;


}

function html_error_box($message, $site, $jump, $link){
	html_wizard_header('Error', $site);

	print "<tr><td class='odd'><span class='textError'>$message</span></td></tr>\n";

	html_wizard_footer($jump, $link, '');
}


function html_wizard_header($title, $site, $size='60%') {
	html_start_box($title, $size, '', '2', 'center', '');

	print "<form action='$site' method='post' enctype='multipart/form-data'>\r\n";
}

function html_wizard_footer($jump, $link, $save_html, $colspan=1) {
	global $config;

	print "<tr>
		<td align='right' bgcolor='#eaeaea' colspan='$colspan'>
		    <input type='hidden' name='action' value='$jump'>
		    <a href='$link'><img src='../../images/button_cancel2.gif' alt='Chancel' align='absmiddle' border='0'></a>
		    $save_html
		</td>
	</tr>";

	html_end_box();

	include_once(CACTI_BASE_PATH . '/include/bottom_footer.php');
}

function html_blue_link($data, $id=0) {
	if ($id) {
	    print "<table width=" . '100%' . " align='center'><tr><td class='textinfo' valign='top' align='right'>";
	foreach ($data as $link) {
		print "<span class='linkmarker'>*</span><a class='hyperLink' href='" . htmlspecialchars($link['href']) . "'>{$link['text']}</a><br>";
	}
		print '</td></tr></table><br>';
	}
}

function html_checked_with_arrow($value) {
	if ($value == true) {
		print '<b>&radic;</b>';
	} else {
		print '';
	}
}

function html_checked_with_icon($value, $icon, $title='', $alternative='', $before='', $after='') {
	if ($value == true) {
		print "$before<img src='./images/" . $icon . "' alt='$title' border='0' align='top' title='$title'>$after";
	} else {
		print $alternative;
	}
}

/**
 * html_calc_syntax()
 * generates the links for the measurand configurator to add variables and existing interim results
 * to the calculation formula
 * @param int $measurand_id contains the id of the current measurand
 * @param int $template_id contains the id of the template which contains the measurand
 */
function html_calc_syntax($measurand_id, $template_id) {
	global $rubrics;

	$rubrics[__('Variables', 'reportit')] = get_possible_variables($template_id);
	$dq_variables = array_flip(get_possible_data_query_variables($template_id));
	$rubrics[__('Data Query Variables', 'reportit')] = $dq_variables;

	$interim_results = array_flip(get_interim_results($measurand_id, $template_id, false));
	$rubrics[__('Interim Results', 'reportit')] = $interim_results;

	$output = '';
	foreach ($rubrics as $key => $value) {
		$output .= "<div style='line-height: 1.5em;'><b>$key:</b></div><div style='line-height: 1.5em;'>";
		$measurand = false;
		foreach ($value as $name => $properties) {

			if ( $key == 'Interim Results') {
				if ($measurand === false) {
					$measurand = $name;
				} else {
					$temp = str_replace($measurand, '', $name);
					if (strpos($temp, ':') !== 0 && strlen($name) !== 0 ) {
						$output .='<br>';
						$measurand = $name;
					}
				}
			}

			$title  = "<div class='header'>" . (isset($properties['title']) ? $properties['title'] : $name) . "</div>";

			if (isset($properties['description'])) {
				$title .= "<div class='content preformatted'>"
				. "Description: " . $properties['description'] . "<br>"
				. "Syntax:      " . $properties['syntax'] . "<br>"
				. "Parameters:  " . $properties['params'] . "<br>"
				. "Examples:    " . $properties['examples'] . "</div>";
			}

	       	$output .= '<a id="' . $name . '" class="linkOverDark1" title="' . $title . '" onClick=add_to_calc("' . $name . '") style="cursor:pointer;">' . $name . "&nbsp;&nbsp;</a>";
		}
		$output .= "</div>";
	}
	return $output;
}

function html_report_variables($report_id, $template_id) {
	//Define some variables
	$array           = array();
	$form_array_vars = array();
	$input_types     = array(1 => 'drop_array', 2 => 'textbox');

	//Load the possible variables
	$variables = db_fetch_assoc_prepared('SELECT a.*, b.value
		FROM plugin_reportit_variables AS a
	    LEFT JOIN plugin_reportit_rvars AS b
	    ON a.id = b.variable_id
		AND report_id = ?
	    WHERE a.template_id = ?',
		array($report_id, $template_id));

	if (count($variables) == 0) {
		$variables = db_fetch_assoc_prepared('SELECT *
			FROM plugin_reportit_variables
			WHERE template_id = ?',
			array($template_id));
	}

	//Exit if there are no variables necessary for using this template
	if (count($variables) == 0) {
		return false;
	}

	//Put the headerline in
	$header = array(
		'friendly_name' => __('Variables', 'reportit'),
		'method'        => 'spacer'
	);

	$form_array_vars['report_var_header'] =  $header;

	//Start with a transformation
	foreach ($variables as $v) {
		$value	= (isset($v['value']) ? $v['value'] : $v['default_value']);
		$method = $input_types[$v['input_type']];
		$index 	= 'var_' . $v['id'];

		if ($method == 'drop_array') {
			$i     = 0;
			$array = array();

			$a = $v['min_value'];
			$b = $v['max_value'];
			$c = $v['stepping'];

			for($i = $a; $i <= $b; $i+=$c) {
				$array[] = strval($i);
			}

			$var = array(
				'friendly_name' => ($v['name']),
				'method'        => $method,
				'description'   => $v['description'],
				'value'         => array_search($value, $array),
				'array'         => $array
			);

		    $form_array_vars[$index] = $var;
		} else {
		    $var = array(
				'friendly_name' => $v['name'],
				'method'        => $method,
				'description'   => $v['description'],
				'max_length'    => 10,
				'value'         => $value,
				'default'       => $v['default_value']
			);

		    $form_array_vars[$index] = $var;
		}
	}

	return $form_array_vars;
}

/*
	This function creates the necessary HTML output for several input boxes
	displayed in the report template editor, which will be used to define
	an alias for every internal data source item.
	@arg                => report template id, if available (new template => 0)
	@data_template_id   => internal Cacti id of the used data template
*/
function html_template_ds_alias($template_id, $data_template_id) {
	$form_array_alias  = array();
	$data_source_items = array();

	/* load information about defined data sources of that data template */
	$data_source_items = db_fetch_assoc_prepared("SELECT a.id, a.data_source_name,
		b.data_source_alias, b.id AS enabled
		FROM data_template_rrd as a
		LEFT JOIN (
			SELECT * FROM plugin_reportit_data_source_items WHERE template_id = ?
		) AS b
		ON a.data_source_name = b.data_source_name
		WHERE a.local_data_id = 0
		AND a.data_template_id = ?",
		array($template_id, $data_template_id));

	/* create the necessary input field for defining the alias */
	if (cacti_sizeof($data_source_items)) {
		foreach ($data_source_items as $data_source_item) {
			$item = array(
				'friendly_name' => __('Enable [%s]', $data_source_item['data_source_name'], 'reportit'),
				'description' => __('Activate data source item \'%s\' for the calculation process.', $data_source_item['data_source_name'], 'reportit'),
				'method' => 'checkbox',
				'default' => 'on',
				'value' => ($data_source_item['enabled'] == true) ? 'on' : 'off'
			);

			$form_array_alias['ds_enabled__' . $data_source_item['id']] = $item;

			$var = array(
				'friendly_name' => __('Data Source Alias', 'reportit'),
				'description' => __('Optional: You can define an alias which should be displayed instead of the internal data source name \'%s\' in the reports.', $data_source_item['data_source_name'], 'reportit'),
				'method' => 'textbox',
				'max_length' => '25',
				'default' => '',
				'value' => ( $data_source_item['data_source_alias'] !== NULL ) ? stripslashes($data_source_item['data_source_alias']) : '',
			);

			$form_array_alias['ds_alias__' . $data_source_item['id']] = $var;
		}
	}

	/* add the alias for the group of separate measurands */
	$separate_group_alias = db_fetch_cell_prepared('SELECT data_source_alias
		FROM plugin_reportit_data_source_items
		WHERE id = 0
		AND template_id = ?',
		array($template_id));

	$var = array(
		'friendly_name' => __('Separate Group Title [overall]', 'reportit'),
		'description' => __('Optional: You can define an group name which should be displayed as the title for all separate measurands within the reports.', 'reportit'),
		'method' => 'textbox',
		'max_length' => '25',
		'default' => '',
		'value' => ( $separate_group_alias !== NULL ) ? stripslashes($separate_group_alias) : '',
	);

	$form_array_alias['ds_alias__0'] = $var;

	return $form_array_alias;
}

function html_onoff_icon($value, $class_on, $title_on, $class_off, $title_off) {
	return $value == 'on'
		? "<i class='fa $class_on' ria-hidden='true' title='$title_on'></i>"
		: "<i class='fa $class_off' ria-hidden='true' title='$title_off'></i>";
}

function html_lock_icon($value, $title_on = 'Locked', $title_off = 'Unlocked') {
	return html_onoff_icon($value, 'fa-lock', $title_on, 'fa-lock-open', $title_off);
}

function html_check_icon($value, $title_on = 'Yes', $title_off = 'No') {
	return html_onoff_icon($value, 'fa-check', $title_on, 'fa-times', $title_off);
}

function html_sources_icon($values, $title_on, $title_off) {
	if (is_array($values)) {
		$values = count($values);
	}
	$value_text = ($values == NULL ? '' :  ' (' . $values . ')');
	$value_on = ($values == NULL ? '' : 'on');
	return html_onoff_icon($values, 'fa-plus', $title_off, 'fa-wrench', $title_on) . $value_text;
}
