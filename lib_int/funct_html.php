<?php
/*
   +-------------------------------------------------------------------------+
   | Copyright (C) 2004-2017 The Cacti Group                                 |
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


function html_custom_header_box($header, $hlink = false, $href = '', $link = '') {
    print "<table calss='cactiTable'>
		<tr>
			<td>
				<table class='cactiTable'>
					<tr>
						<td class='textHeaderDark' align='left' style='padding: 3px;' colspan='100'>
							<table>
								<tr>
									<td class='textHeaderDark' align='left'>";
										print "<b>$header&nbsp;</b>";
										if ($hlink) print "[$hlink]";
	print '</td>';

	if ($href !='' && $link !='') {
		print "<td class='textHeaderDark' align='right'>";

		if (is_array($href) && is_array($link)) {
			foreach ($href as $key => $value){
				print "<a style='color:yellow' href='$value' alt='titlt'>" . $link[$key] . '</a>&nbsp;';
			}
		} else {
			print "<a style='color:yellow' href='$href' alt='titlt'>$link</a>";
		}

		print '</td>';
	}

	print '</tr>
			</table>
		</td>
	</tr>';
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
	if($id) {
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

    $rubrics[__('Variables')] = get_possible_variables($template_id);
    $dq_variables = array_flip(get_possible_data_query_variables($template_id));
    $rubrics[__('Data Query Variables')] = $dq_variables;

    $interim_results = array_flip(get_interim_results($measurand_id, $template_id, false));
    $rubrics[__('Interim Results')] = $interim_results;

    $output = '';
    foreach ($rubrics as $key => $value) {
        $output .= "<div style='line-height: 1.5em;'><b>$key:</b></div><div style='line-height: 1.5em;'>";
        $measurand = false;
        foreach ($value as $name => $properties) {

			if( $key == 'Interim Results') {
				if($measurand === false) {
					$measurand = $name;
				}else {
					$temp = str_replace($measurand, '', $name);
					if(strpos($temp, ':') !== 0 && strlen($name) !== 0 ) {
						$output .='<br>';
						$measurand = $name;
					}
				}
			}

			$title  = "<div class='header'>" . (isset($properties['title']) ? $properties['title'] : $name) . "</div>";

			if(isset($properties['description'])) {
				$title .= "<div class='content preformatted'>"
				. "Description: " . $properties['description'] . "<br>"
				. "Syntax:      " . $properties['syntax'] . "<br>"
				. "Parameters:  " . $properties['params'] . "<br>"
				. "Examples:    " . $properties['examples'] . "</div>";
			}

	       	$output .= '<a id="' . $name . '" class="linkOverDark" title="' . $title . '" onClick=add_to_calc("' . $name . '") style="cursor:pointer;">' . $name . "&nbsp;&nbsp;</a>";
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
		FROM reportit_variables AS a
	    LEFT JOIN reportit_rvars AS b
	    ON a.id = b.variable_id
		AND report_id = ?
	    WHERE a.template_id = ?',
		array($report_id, $template_id));

    if (count($variables) == 0) {
		$variables = db_fetch_assoc_prepared('SELECT *
			FROM reportit_variables
			WHERE template_id = ?',
			array($template_id));
    }

    //Exit if there are no variables necessary for using this template
    if (count($variables) == 0) {
		return false;
	}

    //Put the headerline in
    $header = array(
		'friendly_name' => __('Variables'),
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
			SELECT * FROM reportit_data_source_items WHERE template_id = ?
		) AS b
		ON a.data_source_name = b.data_source_name
		WHERE a.local_data_id = 0
		AND a.data_template_id = ?",
		array($template_id, $data_template_id));

	/* create the necessary input field for defining the alias */
	if (sizeof($data_source_items)) {
		foreach ($data_source_items as $data_source_item) {
			$item = array(
				'friendly_name' => __('Data Source Item </font>[<i>%s</i>]<font class="textEditTitle">', $data_source_item['data_source_name']),
				'description' => __('Activate data source item \'%s\' for the calculation process.', $data_source_item['data_source_name']),
				'method' => 'checkbox',
				'default' => 'on',
				'value' => ($data_source_item['enabled'] == true) ? 'on' : 'off'
            );

			$form_array_alias['ds_enabled__' . $data_source_item['id']] = $item;

			$var = array(
				'friendly_name' => __('Data Source Alias'),
				'description' => __('Optional: You can define an alias which should be displayed instead of the internal data source name \'%s\' in the reports.', $data_source_item['data_source_name']),
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
		FROM reportit_data_source_items
		WHERE id = 0
		AND template_id = ?',
		array($template_id));

	$var = array(
		'friendly_name' => __('Separate Group Title </font>[<i>overall</i>]<font class="textEditTitle">'),
		'description' => __('Optional: You can define an group name which should be displayed as the title for all separate measurands within the reports.'),
		'method' => 'textbox',
		'max_length' => '25',
		'default' => '',
		'value' => ( $separate_group_alias !== NULL ) ? stripslashes($separate_group_alias) : '',
	);

	$form_array_alias['ds_alias__0'] = $var;

	return $form_array_alias;
}

