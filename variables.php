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
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

chdir(__DIR__ . '/../../');
require_once('include/auth.php');

if (!defined('REPORTIT_BASE_PATH')) {
	include_once(__DIR__ . '/setup.php');
	reportit_define_constants();
}

include_once(REPORTIT_BASE_PATH . '/lib/funct_validate.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_html.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_online.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_calculate.php');
include_once(REPORTIT_BASE_PATH . '/lib/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib/const_measurands.php');

//----- CONSTANTS FOR: variables.php -----
$variable_actions = array(
	1 => __('Delete', 'reportit')
);

$var_types = array(
	1 => __('Dropdown', 'reportit'),
	2 => __('Input field', 'reportit')
);

$link_array = array(
	'name',
	'abbreviation',
	'max_value',
	'min_value',
	'default_value',
	'input_type'
);

$list_of_modes = array(
	'ASC',
	'DESC'
);

$desc_array = array(
	'description' => array('display' => __('Name', 'reportit'),          'align' => 'left', 'sort' => 'ASC'),
	'nosort'      => array('display' => __('Internal Name', 'reportit'), 'align' => 'left'),
	'pre_filter'  => array('display' => __('Maximum', 'reportit'),       'align' => 'left'),
	'nosort1'     => array('display' => __('Minimum', 'reportit'),       'align' => 'left'),
	'nosort2'     => array('display' => __('Default', 'reportit'),       'align' => 'left'),
	'nosort3'     => array('display' => __('Stepping', 'reportit'),      'align' => 'left'),
	'nosort4'     => array('display' => __('Input Type', 'reportit'),    'align' => 'left'),
	'nosort5'     => array('display' => __('Options', 'reportit'),       'align' => 'left'),
);


switch (get_request_var('action')) {
	case 'actions':
		form_actions();
		break;
	case 'variable_edit':
		top_header();
		variable_edit();
		bottom_footer();
		break;
	case 'save':
		form_save();
		break;
	default:
		top_header();
		standard();
		bottom_footer();
		break;
}

function standard() {
	global $variable_actions, $link_array, $list_of_modes, $var_types, $desc_array;

	$affix = '';

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	if (isset_request_var('sort') && isset_request_var('mode')) {
		if (!in_array(get_request_var('sort'), $link_array) || !in_array(get_request_var('mode'), $list_of_modes)) {
			die_html_custom_error();
		} else {
			$affix = ' ORDER BY ' . get_request_var('sort') . ' ' . get_request_var('mode');
		}
	}
	/* ==================================================== */

	$variables_list = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_variables
		WHERE template_id = ? ' . $affix,
		array(get_request_var('id')));

	$template_name = db_fetch_cell_prepared('SELECT description
		FROM plugin_reportit_templates
		WHERE id = ?',
		array(get_request_var('id')));

	$i = 0;

	//Number of variables
	$number_of_variables = count($variables_list);

	$header_label = __("Variables [Template: <a class='linkEditMain' href='templates.php?action=template_edit&id=" . get_request_var('id') . "'>%s</a> ] [%d]", $template_name, $number_of_variables, 'reportit');

	form_start('variables.php');
	html_start_box($header_label, '100%', '', '2', 'center', 'variables.php?action=variable_edit&template_id=' . get_request_var('id'));

	html_header_checkbox($desc_array);

	if (cacti_sizeof($variables_list) > 0) {
		foreach($variables_list as $variable) {
			$select_options_count = ($variable['input_type'] == 1) ? (($variable['max_value']-$variable['min_value'])/$variable['stepping'])+1 : false;
			$select_options_class = '';
			$icon = '';

			if ($select_options_count !== false) {
				if ($select_options_count <= 100) {
					$select_options_class = 'deviceUp';
					$icon = 'fa-thumbs-up';
				} else if ($select_options_count <= 500) {
					$select_options_class = 'deviceDownMuted';
					$icon = 'fa-thumbs-down';
				} else {
					$select_options_class = 'deviceDown';
					$icon = 'fa-exclamation-triangle';
				}
			}

			form_alternate_row('line' . $variable['id'], true);
			form_selectable_cell('<a class="linkEditMain" href="variables.php?action=variable_edit&id=' . $variable['id'] . '">' . $variable['name'] . '</a>', $variable['id']);
			form_selectable_cell($variable['abbreviation'], $variable['id']);
			form_selectable_cell($variable['max_value'], $variable['id']);
			form_selectable_cell($variable['min_value'], $variable['id']);
			form_selectable_cell($variable['default_value'], $variable['id']);
			form_selectable_cell($variable['stepping'], $variable['id']);
			form_selectable_cell($var_types[$variable['input_type']], $variable['id'], 'left');
			form_selectable_cell('<font class="' . $select_options_class . '"><i class="fa ' . $icon . '" aria-hidden="true"></i> ' . (($select_options_count !== false) ? "($select_options_count)" : 'n/a' ) . '</font>', $variable['id']);
			form_checkbox_cell($variable['name'], $variable['id']);
			form_end_row();
		}
	} else {
		print '<tr><td colspan="9"><em>' . __('No Variables Found', 'reportit') . '</em></td></tr>';
	}

	$form_array = array(
		'id' => array(
			'method' =>'hidden_zero',
			'value'  => get_request_var('id')
		)
	);

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => $form_array
		)
	);

	html_end_box(true);

	draw_actions_dropdown($variable_actions);
	form_end();
}

function form_save() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('template_id');

	form_input_validate(get_request_var('variable_name'), 'variable_name', '^[a-zA-Z0-9[:space:]]+$', false, 3);
	form_input_validate(get_request_var('variable_description'), 'variable_description', '[a-zA-Z0-9\n\r]+', false, 3);
	form_input_validate(get_request_var('variable_maximum'), 'variable_maximum', '^[-]?[0-9]+[.]?[0-9]*$', false, 3);
	form_input_validate(get_request_var('variable_minimum'), 'variable_minimum', '^[-]?[0-9]+[.]?[0-9]*$', false, 3);
	form_input_validate(get_request_var('variable_default'), 'variable_default', '^[-]?[0-9]+[.]?[0-9]*$', false, 3);
	form_input_validate(get_request_var('variable_type'), 'variable_type', '^[1-2]$', false, 3);

	if (get_request_var('variable_type') == 1) {
		form_input_validate(get_request_var('variable_stepping'), 'variable_stepping', '^[0-9]+[.]?[0-9]*$', false, 3);
	}
	/* ==================================================== */


	//Check defined variable
	if (!(get_request_var('variable_maximum') > get_request_var('variable_minimum'))) {
		session_custom_error_message('variable_maximum', __('Maximum has to be greater than minimum.', 'reportit'));
	}

	if (!(get_request_var('variable_minimum') <= get_request_var('variable_default') && get_request_var('variable_default') <= get_request_var('variable_maximum'))) {
		session_custom_error_message('variable_default', __('Default value is out of values range.', 'reportit'));
	}

	if (get_request_var('variable_type') == 1) {
		if ( !(get_request_var('variable_stepping') > 0) ||
		!(get_request_var('variable_stepping') <= (get_request_var('variable_maximum') - get_request_var('variable_minimum'))))
		session_custom_error_message('variable_stepping', 'Invalid step.');
	}

	$variable_data = array();
	$variable_data['id']            = get_request_var('id');
	$variable_data['name']          = get_request_var('variable_name');
	$variable_data['template_id']   = get_request_var('template_id');
	$variable_data['description']   = get_request_var('variable_description');
	$variable_data['max_value']     = get_request_var('variable_maximum');
	$variable_data['min_value']     = get_request_var('variable_minimum');
	$variable_data['default_value'] = get_request_var('variable_default');
	$variable_data['input_type']	= get_request_var('variable_type');

	if (isset_request_var('variable_stepping')) {
		$variable_data['stepping']  = get_request_var('variable_stepping');
	}

	if (is_error_message()) {
		raise_message(4);
		header("Location: variables.php?header=false&action=variable_edit&id=" . get_request_var('id') . "&template_id=" . get_request_var('template_id'));

	} else {
		//Save data
		$var_id = sql_save($variable_data, 'plugin_reportit_variables');

		if (get_request_var('id') == 0) {
			db_execute("UPDATE plugin_reportit_variables
				SET abbreviation = 'c". $var_id . "v'
				WHERE id = $var_id");

			//If its a new one we've to create the entries for all the reports
			//using this template.
			create_rvars_entries($var_id, $variable_data['template_id'], $variable_data['default_value']);
		}

		//Return to list view if it was an existing report
		header('Location: variables.php?header=false&id=' . get_request_var('template_id'));
		raise_message(1);
	}
}

function variable_edit() {
	global $template_actions, $var_types;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if (!isempty_request_var('id')) {
		$variable_data = db_fetch_row_prepared('SELECT *
			FROM plugin_reportit_variables
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label = __('Variable Configuration [edit: %s]', $variable_data['name'], 'reportit');
	} else {
		$header_label = __('Variable Configuration [new]', 'reportit');
	}

	session_custom_error_display();

	$variable_id = (isset_request_var('id') ? get_request_var('id') : '0');
	$template_id = (isset_request_var('template_id') ? get_request_var('template_id') : $variable_data['template_id']);

	$form_array = array(
		'id' => array(
			'method' => 'hidden_zero',
			'value' => $variable_id
		),
		'template_id' => array(
			'method' => 'hidden_zero',
			'value' => $template_id
		),
		'variable_header' => array(
			'friendly_name' => __('General', 'reportit'),
			'method' => 'spacer'
		),
		'variable_abbreviation'	=> array(
			'friendly_name' => __('Internal name', 'reportit'),
			'description' => __('A unique identifier which will be created by ReportIt itself. Use this ID within the definition of your calculation formulas to include that value the report user has defined individually for it.', 'reportit'),
			'method' => 'custom',
			'max_length' => '100',
			'value' => (isset($variable_data['abbreviation']) ? $variable_data['abbreviation'] : '-Available after first saving-')
		),
		'variable_name' => array(
			'friendly_name' => __('Name'),
			'description' => __('A name like "Threshold" for example which should be used as a headline within the report config.', 'reportit'),
			'method' => 'textbox',
			'max_length' => '100',
			'value' => (isset($variable_data['name']) ? $variable_data['name'] : '')
		),
		'variable_description' => array(
			'friendly_name' => __('Description', 'reportit'),
			'description' => __('A short, pithy description that explains the sense of this variable.', 'reportit'),
			'method' => 'textarea',
			'textarea_rows' => '2',
			'textarea_cols' => '50',
			'default' => __('Your description', 'reportit'),
			'value' => (isset($variable_data['description']) ? $variable_data['description'] : '')
		),
		'variable_maximum' => array(
			'friendly_name' => __('Maximum Value', 'reportit'),
			'description' => __('Defines the upper limit of this variable.', 'reportit'),
			'method' => 'textbox',
			'max_length' => '10',
			'value' => (isset($variable_data['max_value']) ? $variable_data['max_value'] : '')
		),
		'variable_minimum' => array(
			'friendly_name' => __('Minimum Value', 'reportit'),
			'description' => __('Defines the lower limit of this variable.', 'reportit'),
			'method' => 'textbox',
			'max_length' => '10',
			'value' => (isset($variable_data['min_value']) ? $variable_data['min_value'] : '')
		),
		'variable_default' => array(
			'friendly_name' => __('Default Value', 'reportit'),
			'description' => __('Sets the default value.', 'reportit'),
			'method' => 'textbox',
			'max_length' => '10',
			'value' => (isset($variable_data['default_value']) ? $variable_data['default_value'] : '')
		),
		'variable_type' => array(
			'friendly_name' => __('Type', 'reportit'),
			'description' => __('The method the report owner should use to define this variable.', 'reportit'),
			'method' => 'drop_array',
			'array' => $var_types,
			'value' => (isset($variable_data['input_type']) ? $variable_data['input_type'] : '')
		),
		'variable_stepping' => array(
			'friendly_name' => __('Stepping', 'reportit'),
			'description' => __('Defines the distance between two values if method "DropDown" has been chosen. Please ensure that this value is not set too low, because it defines indirectly the number of options the dropdown field will have. For example the following parameters: MAX:100, MIN:0, STEP:0.01  will result in a select box of 10.001 options. This can cause dramatical performance issues due to a high CPU load at the clients side. Try to keep it under 1000.', 'reportit'),
			'method' => 'textbox',
			'max_length' => '10',
			'value' => (isset($variable_data['stepping']) && $variable_data['stepping']) ? $variable_data['stepping'] : ''
		),
	);

	?>
	<script type='text/javascript'>
	function change_variable_type(){
		if ($('#variable_type').val() == 2) {
			$('#variable_stepping').prop('disabled', true);
		} else {
			$('#variable_stepping').prop('disabled', false);
		}
	}

	$(function(){
		$('#variable_type').change(function(){
			change_variable_type();
		});

		/* initiate settings */
		change_variable_type();
	});
	</script>
	<?php

	form_start('variables.php');
	html_start_box($header_label, '100%', '', '2', 'center', '');
	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => $form_array
		)
	);

	form_save_button('variables.php?&id=' . $template_id);
	html_end_box();
}

function form_actions() {
	global $variable_actions, $config;
	$error = false;

	// ================= input validation =================
	get_filter_request_var('id');
	// ====================================================

	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if (get_request_var('drp_action') == '1') { // delete variables
			db_execute('DELETE FROM plugin_reportit_variables WHERE ' . array_to_sql_or($selected_items, 'id'));
			db_execute('DELETE FROM plugin_reportit_rvars WHERE ' . array_to_sql_or($selected_items, 'variable_id'));
		}

		header('Location: variables.php?header=false&id=' . get_request_var('id'));
		exit;
	}

	//Set preconditions
	$ds_list = array(); $i = 0;

	foreach($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			//Fetch report id
			$id = substr($key, 4);
			$variable_ids[] = $id;
			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			//Fetch report description
			$variable_description 	= db_fetch_cell_prepared('SELECT name
				FROM plugin_reportit_variables
				WHERE id = ?',
				array($id));

			$ds_list[$variable_description] = '';
		}
	}

	top_header();

	form_start('variables.php');
	html_start_box($variable_actions[get_request_var('drp_action')], '60%', '', '2', 'center', '');

	if (get_request_var('drp_action') == '1') { //DELETE REPORT
		print "<tr>
			<td class='textArea'
				<p>" . __('Click \'Continue to Delete the following variables.', 'reportit') . '</p>';

		if (is_array($ds_list)) {
			//Check possible dependences for each variable
			foreach($variable_ids as $id) {
				$name = db_fetch_cell_prepared('SELECT abbreviation
					FROM plugin_reportit_variables
					WHERE id = ?',
					array($id));

				$count = db_fetch_cell_prepared("SELECT COUNT(*)
					FROM plugin_reportit_measurands
					WHERE template_id = ?
					AND calc_formula LIKE '%$name%'",
					array(get_request_var('id')));

				if ($count != 0) {
					$error = true;
					break;
				}
			}

			if (!$error){
				print '<p>' . __('List of selected variables:', 'reportit') . '</p>';
				print '<ul>';
				foreach($ds_list as $key => $value) {
					print '<li>' . __('Variable : %s', $key, 'reportit') . '</li>';
				}
				print '</ul>';
			}
		}

		print '</td>
		</tr>';

		if ($ds_list === false || empty($ds_list) || !is_array($ds_list) || $error == true) {
			if ($error) {
				print "<tr><td class='odd'><span class='textError'>" . __('There are one or more variables in use.', 'reportit') . '</span></td></tr>';
			} else {
				print "<tr><td class='odd'><span class='textError'>" . __('You must select at least one variable.', 'reportit') . '</span></td></tr>';
			}

			$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"variables.php?id=" . get_request_var('id') . "\")'>";
		} else {
			$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo(\"variables.php?id=" . get_request_var('id') . "\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc('Delete Template Variables', 'reportit') . "'>";
		}
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='id' value='" . get_request_var('id') . "'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($variable_ids) ? serialize($variable_ids) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}
