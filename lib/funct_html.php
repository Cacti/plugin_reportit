<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2018 The Cacti Group                                 |
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
 * @param ref $measurand reference to the measurand parameter set
 * @param int $template_id contains the id of the template which contains the measurand
 */
function html_operations_and_operands(&$measurand) {
	global $measurands_rubrics, $measurand_ops_and_opds;

	if(!$measurand['group_id']) {
		$output = '<div class="formData">' . __('Select a Data Template Group first.', 'reportit') . '</div>';
	}else {

		/* get a list of defined variables for this report template */
		$variables = db_fetch_assoc_prepared('SELECT * FROM plugin_reportit_variables WHERE template_id = ?', array($measurand['template_id']));
		if($variables && sizeof($variables)>0) {
			foreach($variables as $var) {
				$measurand_ops_and_opds[6][$var['abbreviation']] = array(
					'title'			=> $var['abbreviation'],
					'description'	=> $var['description'],
					'params'		=> 'none',
					'syntax'		=> '-',
					'examples'		=> '-',
				);
			}
		}

		$measurand_ops_and_opds[7] = array_flip(get_possible_data_query_variables($measurand['group_id']));
		$measurand_ops_and_opds[8] = array_flip(get_interim_results($measurand['id'], $measurand['group_id']));

		$output = '';
		foreach($measurand_ops_and_opds as $key => $functions) {
			$output .= "<span class='formFieldName' style='padding-left:0px;float:none;line-height:normal;display:block;'>" . $measurands_rubrics[$key] . ":</span><span style='line-height:normal;display:block;margin-bottom:0.5em;'>";
			$mea_name = false;

			foreach ($functions as $name => $properties) {

				if( $key == 8) { // interim results
					if($mea_name === false) {
						$mea_name = $name;
					}else {
						$temp = str_replace($mea_name, '', $name);
						if(strpos($temp, ':') !== 0 && strlen($name) !== 0 ) {
							$output .='<br>';
							$mea_name = $name;
						}
					}
				}

				$title  = "<div class='header'>" . (isset($properties['title']) ? $properties['title'] : $name) . "</div>";

				if(isset($properties['description'])) {
					$title .= "<div class='content preformatted'>" . 	"Description: " . $properties['description'] . "<br>";
					$title .= isset($properties['syntax']) ? 			"Syntax:      " . $properties['syntax'] . "<br>" : '';
					$title .= isset($properties['params']) ? 			"Parameters:  " . $properties['params'] . "<br>" : '';
					$title .= isset($properties['examples']) ? 			"Examples:    " . $properties['examples'] : '';
					$title .= "</div>";
				}

				if ($name === '( )') {
					$id = 'rb';
				}elseif ($name === '[ ]') {
					$id = 'sb';
				}else {
					$id = $name;
				}

				$output .= '<a id="' . $id . '" href="#" class="linkEditMain ops-and-opds ' . ( (isset($properties['parentheses']) && $properties['parentheses']) ? 'parentheses' : '' ) . '" title="' . $title . '" style="cursor:pointer;">' . $name . "</a>&nbsp;&nbsp;";
			}
			$output .= "</span>";
		}
	}
	return $output;
}

/**
 * html_calc_syntax()
 * generates the links for the measurand configurator to add variables and existing interim results
 * to the calculation formula
 * @param int $measurand_id contains the id of the current measurand
 * @param int $template_id contains the id of the template which contains the measurand
 */
function html_calc_syntax($measurand_id, $template_id) {
	global $measurands_rubrics, $measurand_ops_and_opds;

	$measurand_ops_and_opds[6] = get_possible_variables($template_id);
	$dq_variables = array_flip(get_possible_data_query_variables($template_id));
	$measurand_ops_and_opds[7] = $dq_variables;

	$interim_results = array_flip(get_interim_results($measurand_id, $template_id, false));
	$measurand_ops_and_opds[8] = $interim_results;

	$output = '';
	foreach($measurand_ops_and_opds as $key => $functions) {
		$output .= "<span class='formFieldName' style='padding-left:0px;float:none;line-height:normal;display:block;'>" . $measurands_rubrics[$key] . ":</span><span style='line-height:normal;display:block;margin-bottom:0.5em;'>";
		$measurand = false;

		foreach ($functions as $name => $properties) {

			if( $key == 8) { // interim results
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

			if ($name === '( )') {
				$id = 'rb';
			}elseif ($name === '[ ]') {
				$id = 'sb';
			}else {
				$id = $name;
			}

			$output .= '<a id="' . $id . '" href="#" class="linkEditMain ops-and-opds ' . ( (isset($properties['parentheses']) && $properties['parentheses']) ? 'parentheses' : '' ) . '" title="' . $title . '" style="cursor:pointer;">' . $name . "&nbsp;&nbsp;</a>";
		}
		$output .= "</span>";
	}
	return $output;
}

function html_report_variables($report_id, $template_id) {
	//Define some variables
	$array           = array();
	$form_array_vars = array();
	$input_types     = array(1 => 'drop_array', 2 => 'textbox');

	// load report related variables
	$variables = db_fetch_assoc_prepared('SELECT a.*, b.value
		FROM plugin_reportit_variables AS a
		LEFT JOIN plugin_reportit_rvars AS b
		ON a.id = b.variable_id
		AND report_id = ?
		WHERE a.template_id = ?',
		array($report_id, $template_id));

	// fall back to template defaults if necessary
	if (count($variables) == 0) {
		$variables = db_fetch_assoc_prepared('SELECT *
			FROM plugin_reportit_variables
			WHERE template_id = ?',
			array($template_id));
	}

	//return if no variables have been declared
	if (count($variables) == 0) {
		return false;
	}else {
		foreach ($variables as $variable) {
			$value	= (isset($variable['value']) ? $variable['value'] : $variable['default_value']);
			$method = $input_types[$variable['input_type']];
			$index 	= 'report_var_' . $variable['id'];

			if ($method == 'drop_array') {
				$i     = 0;
				$array = array();

				$a = $variable['min_value'];
				$b = $variable['max_value'];
				$c = $variable['stepping'];

				for($i = $a; $i <= $b; $i+=$c) {
					$array[] = strval($i);
				}

				$var = array(
					'friendly_name' => ($variable['name']),
					'method'        => $method,
					'description'   => $variable['description'],
					'value'         => array_search($value, $array),
					'array'         => range($variable['min_value'], $variable['max_value'], $variable['stepping'])
				);

				$form_array_vars[$index] = $var;
			} else {
				$var = array(
					'friendly_name' => $variable['name'],
					'method'        => $method,
					'description'   => $variable['description'],
					'max_length'    => 10,
					'value'         => $value,
					'default'       => $variable['default_value']
				);

				$form_array_vars[$index] = $var;
			}
		}
		return $form_array_vars;
	}
}

/*
    This function creates the necessary HTML output for several input boxes
    displayed in the report template editor, which will be used to define
    an alias for every internal data source item.
    @arg                => report template id, if available (new template => 0)
    @data_template_id   => internal Cacti id of the used data template
*/
function html_template_ds_alias($template_id, $data_template_id) {
	$form_array  = array();
	$data_source_items = array();

	/* load information about defined data sources of that data template */
	$data_source_items = db_fetch_assoc_prepared("SELECT a.id, a.data_source_name,
		b.data_source_alias, b.data_source_title, b.data_source_mp, b.id AS enabled
		FROM data_template_rrd as a
		LEFT JOIN (
			SELECT * FROM plugin_reportit_data_source_items WHERE template_id = ?
		) AS b
		ON a.data_source_name = b.data_source_name
		WHERE a.local_data_id = 0
		AND a.data_template_id = ?",
		array($template_id, $data_template_id));

	/* create the necessary input field for defining the alias */
	if (sizeof($data_source_items)) {
		$i = 0;
		foreach ($data_source_items as $data_source_item) {
			$i++;

			$header = array(
				'friendly_name' => 'Data Source Item [' . $data_source_item['data_source_name'] . ']',
				'method' => 'spacer',
				'collapsible' => true,
			);

			$form_array['ds_header__' . $data_source_item['id']] = $header;

			$item = array(
				'friendly_name' => __('Enable </font>[<i>%s</i>]<font class="textEditTitle">', $data_source_item['data_source_name']),
				'description' => __('Activate data source item \'%s\' for the calculation process.', $data_source_item['data_source_name']),
				'method' => 'checkbox',
				'default' => 'on',
				'value' => ($data_source_item['enabled'] == true) ? 'on' : 'off'
			);

			$form_array['ds_enabled__' . $data_source_item['id']] = $item;

			$var = array(
				'friendly_name' => __('Data Source Alias'),
				'description' => __('Optional: This parameter allows to overwrite the internal data source name so that a common calculation formular can be used for different data templates of the same nature like cpu and cpu_5. Please note that if defined this alias has to be unique and needs to differ from other internal data source names without an alias as well.', $data_source_item['data_source_name']),
				'method' => 'textbox',
				'max_length' => '25',
				'default' => '',
				'value' => ( $data_source_item['data_source_alias'] !== NULL ) ? stripslashes($data_source_item['data_source_alias']) : '',
			);

			$form_array['ds_alias__' . $data_source_item['id']] = $var;


			$var = array(
				'friendly_name' => __('Data Source Title'),
				'description' => __('Optional: You can define an alias which should be displayed instead of the internal data source name \'%s\' in the reports. The use of different aliases will be strongly recommended but is not a prerequisite.', $data_source_item['data_source_name']),
				'method' => 'textbox',
				'max_length' => '25',
				'default' => '',
				'value' => ( $data_source_item['data_source_title'] !== NULL ) ? stripslashes($data_source_item['data_source_title']) : '',
			);

			$form_array['ds_title__' . $data_source_item['id']] = $var;


			$var = array(
				'friendly_name' => __('Data Source Multiplicator'),
				'description' => __('Optional: To automatically group data source items of different data template within one report it is necessary that all items have the same base. Use this to define a multiplier different to 1 (default) if necessary.'),
				'method' => 'textbox',
				'max_length' => '10',
				'default' => '1',
				'value' => ( $data_source_item['data_source_mp'] !== NULL ) ? $data_source_item['data_source_mp'] : '1',
			);

			$form_array['ds_mp__' . $data_source_item['id']] = $var;

		}
	}

	/* add the alias for the group of separate measurands */
	$form_array['ds_header__0'] = array(
		'friendly_name' => 'Separate Group Title [overall]',
		'method' => 'spacer',
		'collapsible' => true,
	);

	$separate_group_title = db_fetch_cell_prepared('SELECT data_source_title
		FROM plugin_reportit_data_source_items
		WHERE id = 0
		AND template_id = ?
		AND data_template_id = ?',
		array($template_id, $data_template_id));

	$form_array['ds_title__0'] = array(
		'friendly_name' => __('Separate Group Title </font>[<i>overall</i>]<font class="textEditTitle">'),
		'description' => __('Optional: You can define an group name which should be displayed as the title for all separate measurands within the reports.'),
		'method' => 'textbox',
		'max_length' => '25',
		'default' => '',
		'value' => ( $separate_group_title !== NULL ) ? stripslashes($separate_group_title) : '',
	);

	$form_array += array(
		'id' =>	array(
			'method' => 'hidden_zero',
			'value' => $data_template_id
		),
		'tab' => array(
			'method' => 'hidden_zero',
			'value' => 'data_templates'
		),
		'template_id' => array(
			'method' => 'hidden_zero',
			'value' => $template_id
		)
	);

	return $form_array;
}

