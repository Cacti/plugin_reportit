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
require('include/auth.php');

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

$measurand_actions 	= array(
	2 => __('Delete', 'reportit')
);

set_default_action();

switch (get_request_var('action')) {
	case 'actions':
		form_actions();
		break;
	case 'measurand_edit':
		top_header();
		measurand_edit();
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
	global $measurand_actions, $config, $consolidation_functions;

	/* ================= input validation ================= */
	$id = get_filter_request_var('id');
	/* ==================================================== */

	$measurands_list = db_fetch_assoc_prepared('SELECT *
		FROM plugin_reportit_measurands
		WHERE template_id = ?
		ORDER BY id', array(get_request_var('id')));

	$template_name	= db_fetch_cell_prepared('SELECT description
		FROM plugin_reportit_templates
		WHERE id = ?',
		array(get_request_var('id')));

	$i = 0;

	//Number of measurands
	$number_of_measurands = count($measurands_list);
	$header_label	= __("Measurands [Template: <a class='linkEditMain' href='templates.php?action=template_edit&id=" . get_request_var('id') . "'>%s</a>] [%d]", $template_name, $number_of_measurands, 'reportit');

	form_start('measurands.php');
	html_start_box($header_label, '100%', '', '2', 'center', 'measurands.php?action=measurand_edit&template_id=' . get_request_var('id'));

	$display_text = array(
		__('Name', 'reportit'),
		__('Abbreviation', 'reportit'),
		__('Unit', 'reportit'),
		__('Consolidation Function', 'reportit'),
		__('Visible', 'reportit'),
		__('Separate', 'reportit'),
		__('Calculation Formula', 'reportit')
	);

	html_header_checkbox($display_text);

	if (cacti_sizeof($measurands_list)) {
		foreach($measurands_list as $measurand) {
			form_alternate_row('line' . $measurand['id'], true);
			form_selectable_cell("<a class='linkEditMain' href='measurands.php?action=measurand_edit&id=" . $measurand['id'] . "'>" . $measurand['description'] . '</a>', $measurand['id']);
			form_selectable_cell($measurand['abbreviation'], $measurand['id']);
			form_selectable_cell($measurand['unit'], $measurand['id']);
			form_selectable_cell($consolidation_functions[$measurand['cf']], $measurand['id']);
			form_selectable_cell( ($measurand['visible'] ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>'), $measurand['id']);
			form_selectable_cell( ($measurand['spanned'] ? '<i class="fa fa-check" aria-hidden="true"></i>' : '<i class="fa fa-times" aria-hidden="true"></i>'), $measurand['id']);
			form_selectable_cell($measurand['calc_formula'], $measurand['id']);
			form_checkbox_cell($measurand['description'], $measurand['id']);
			form_end_row();
		}
	} else {
		print '<tr><td colspan="8"><em>' . __('No Measurands Found', 'reportit') . '</em></td></tr>';
	}

	$form_array = array(
		'id' => array(
			'method' => 'hidden_zero',
			'value' => get_request_var('id')
		)
	);

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
		)
	);

	html_end_box(true);

	draw_actions_dropdown($measurand_actions, 'measurands.php');
	form_end();
}

function form_save() {
	global $calc_var_names, $rounding, $precision, $type_specifier;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	input_validate_input_number(get_request_var('template_id'));
	input_validate_input_key(get_request_var('measurand_type'), $type_specifier);
	input_validate_input_key(get_request_var('measurand_precision'), $precision, true);
	input_validate_input_key(get_request_var('measurand_rounding'), array(0,1,2), true);
	form_input_validate(get_request_var('measurand_description'), 'measurand_description', '', false, 3);
	form_input_validate(get_request_var('measurand_abbreviation'), 'measurand_abbreviation', '^[a-zA-Z0-9]+$', false, 3);
	form_input_validate(get_request_var('measurand_unit'), 'measurand_unit', '^[\/\\\$a-zA-Z0-9%²³-]+$', false, 3);
	form_input_validate(get_request_var('measurand_formula'), 'measurand_formula', '', false, 3);
	form_input_validate(get_request_var('measurand_cf'), 'measurand_cf', '[1-4]', false, 3);
	/* ==================================================== */

	//Check if the abbreviation is in use.
	$count = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM plugin_reportit_measurands
		WHERE abbreviation = ?
		AND id != ?
		AND template_id = ?',
		array(get_request_var('measurand_abbreviation'), get_request_var('id'), get_request_var('template_id')));

	if ($count != 0) {
		session_custom_error_message('measurand_abbreviation', __('Duplicate abbreviation', 'reportit'));
	}

	//Check calculation formula
	if (strlen(get_request_var('measurand_formula'))) {
		$calc                 = get_request_var('measurand_formula');
		$intersizes           = get_interim_results(get_request_var('id'), get_request_var('template_id'));
		$calc_var_names       = array_keys(get_possible_variables(get_request_var('template_id')));
		$data_query_variables = get_possible_data_query_variables(get_request_var('template_id'));
		$error                = validate_calc_formula($calc, $intersizes, $calc_var_names, $data_query_variables);

		if ($error != 'VALID') {
			session_custom_error_message('measurand_formula', $error);
		}
	}

	//Check possible dependences with other measurands
	if (!is_error_message_field('measurand_abbreviation') && get_request_var('id') != 0) {
		$dependences = array();
		$dependencies = array();

		$new = get_request_var('measurand_abbreviation');

		$old = db_fetch_cell_prepared("SELECT abbreviation
			FROM plugin_reportit_measurands
			WHERE id = ?",
			array(get_request_var('id')));

		if ($old != $new) {
			$dependencies = db_fetch_assoc_prepared("SELECT id, calc_formula
				FROM plugin_reportit_measurands
				WHERE template_id = ?
				AND id > ?
				AND calc_formula LIKE '%$old%'",
				array(get_request_var('template_id'), get_request_var('id')));

			if (cacti_sizeof($dependencies)) {
				foreach($dependences as $key => $value) {
					$value['calc_formula'] = str_replace($old, $new, $value['calc_formula']);
					$dependences[$key]     = $value;
				}
			}
		}

		//Check if interim results are used in other measurands
		if (isset_request_var('measurand_spanned')) {
			$count = db_fetch_cell_prepared("SELECT COUNT(*)
				FROM plugin_reportit_measurands
				WHERE template_id = ?
				AND id > ?
				AND calc_formula LIKE '%$old:%'",
				array(get_request_var('template_id'), get_request_var('id')))
;

			if ($count != 0) {
				session_custom_error_message('measurand_spanned', __('Interim results are used by other measurands.', 'reportit'));
			}
		}
	}

	$measurand_data = array();
	$measurand_data['id']             = get_request_var('id');
	$measurand_data['template_id']    = get_request_var('template_id');
	$measurand_data['description']    = get_request_var('measurand_description');
	$measurand_data['abbreviation']   = strtoupper(get_request_var('measurand_abbreviation'));
	$measurand_data['unit']           = get_request_var('measurand_unit');
	$measurand_data['visible']        = isset_request_var('measurand_visible') ? 'on' : '';
	$measurand_data['spanned']        = isset_request_var('measurand_spanned') ? 'on' : '';
	$measurand_data['calc_formula']   = get_request_var('measurand_formula');
	$measurand_data['rounding']       = isset_request_var('measurand_rounding') ? get_request_var('measurand_rounding'): '';
	$measurand_data['cf']             = get_request_var('measurand_cf');
	$measurand_data['data_type']      = get_request_var('measurand_type');
	$measurand_data['data_precision'] = isset_request_var('measurand_precision') ? get_request_var('measurand_precision') : '';

	if (is_error_message()) {
		header('Location: measurands.php?header=false&action=measurand_edit&id=' . get_request_var('id') . '&template_id=' . get_request_var('template_id'));
	} else {
		//Save data
		sql_save($measurand_data, 'plugin_reportit_measurands');

		//Update dependences if it's necessary
		if (isset($dependences) && sizeof($dependencies)) {
			update_formulas($dependences);
		}

		//Return to list view if it was an existing report
		header('Location: measurands.php?header=false&id=' . get_request_var('template_id'));
		raise_message(1);
	}
}

function measurand_edit() {
	global $config, $template_actions, $rounding, $consolidation_functions, $type_specifier, $precision;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if (!isempty_request_var('id')) {
		$measurand_data = db_fetch_row_prepared('SELECT *
			FROM plugin_reportit_measurands
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label   = __('Measurand Configuration [edit: %s]', $measurand_data['description'], 'reportit');
	} else {
		$header_label   = __('Measurand Configuration [new]', 'reportit');
	}

	$measurand_id = (isset_request_var('id') ? get_request_var('id') : '0');
	$template_id  = (isset_request_var('template_id') ? get_request_var('template_id') : $measurand_data['template_id']);

	$form_array = array(
		'id'				=> array(
			'method'			=> 'hidden_zero',
			'value'				=> $measurand_id
		),
		'template_id'		=> array(
			'method'			=> 'hidden_zero',
			'value'				=> $template_id
		),
		'measurand_header'	=> array(
			'friendly_name'		=> __('General', 'reportit'),
			'method'			=> 'spacer',
			'collapsible' 		=> 'true'
		),
		'measurand_description'	=> array(
			'friendly_name'		=> __('Description', 'reportit'),
			'description'		=> __('The explanation given to this measurand. This will be shown as legend within exports as well as a tooltip within the presentation of a report itself.', 'reportit'),
			'method'			=> 'textbox',
			'max_length'		=> '255',
			'value'				=> (isset($measurand_data['description']) ? $measurand_data['description'] : '')
		),
		'measurand_abbreviation'=> array(
			'friendly_name'		=> __('Abbreviation', 'reportit'),
			'description'		=> __('Define a unique abbreviation for this measurand with max. 8 letters/numbers.', 'reportit'),
			'method'			=> 'textbox',
			'max_length'		=> '10',
			'value'				=> (isset($measurand_data['abbreviation']) ? $measurand_data['abbreviation'] : '')
		),
		'measurand_unit'	=> array(
			'friendly_name'		=> __('Unit', 'reportit'),
			'description'		=> __('The unit given to this measurand. e.g. \'Bits/s\'', 'reportit'),
			'method'			=> 'textbox',
			'max_length'		=> '100',
			'value'				=> (isset($measurand_data['unit']) ? $measurand_data['unit'] : '')
		),
		'measurand_cf'		=> array(
			'friendly_name'		=> __('Consolidation function', 'reportit'),
			'description'		=> __('The name of the consolidation function to define which CDPs should be read out.', 'reportit'),
			'method'			=> 'drop_array',
			'default'			=> '0',
			'value'				=> (isset($measurand_data['cf']) ? $measurand_data['cf'] : ''),
			'array'				=> $consolidation_functions
		),
		'measurand_visible'	=> array(
			'friendly_name'		=> __('Visible', 'reportit'),
			'description'		=> __('Choose \'enable\' if this measurand should be become part of the final report output. Leave it unflagged if this measurands will only be used as an auxiliary calculation.', 'reportit'),
			'method'			=> 'checkbox',
			'value'				=> ((isset($measurand_data['visible']) && $measurand_data['visible'] == true) ? 'on' : ''),
			'form_id'			=> (isset_request_var('id') ? get_request_var('id') : ''),
			'default'			=> 'on',

		),
		'measurand_spanned'	=> array(
			'friendly_name'		=> __('Separate', 'reportit'),
			'description'		=> __('Choose \'enable\' if this measurand will only have one result in total instead of one for every Data Source Item. It\'s result<br>will be shown separately. Use this option in combination with "Visible" = "off" if you are looking for a measurand keeping an interim result only that should be reused within the calculation of other measurands without being visible for end users.', 'reportit'),
			'method'			=> 'checkbox',
			'value'				=> ((isset($measurand_data['spanned']) && $measurand_data['spanned'] == true) ? 'on' : ''),
			'form_id'			=> (isset_request_var('id') ? get_request_var('id') : ''),
			'default'			=> '',

		),
		'measurand_header2'	=> array(
			'friendly_name'		=> __('Formatting', 'reportit'),
			'method'			=> 'spacer',
			'collapsible' 		=> 'true'
		),
		'measurand_type'=> array(
			'friendly_name'		=> __('Type', 'reportit'),
			'method'			=> 'drop_array',
			'array'				=> $type_specifier,
			'description'		=> __('Defines as what type the data should be treated as.', 'reportit'),
			'value'				=> (isset($measurand_data['data_type']) ? $measurand_data['data_type'] : '1' )
		),
		'measurand_precision'=> array(
			'friendly_name'		=> __('Precision', 'reportit'),
			'description'		=> __('Defines how many decimal digits should be displayed for floating-point numbers.', 'reportit'),
			'method'			=> 'drop_array',
			'array'				=> $precision,
			'value'				=> (isset($measurand_data['data_precision']) ? $measurand_data['data_precision'] : '2' )
		),
		'measurand_rounding'=> array(
			'friendly_name'		=> __('Prefixes', 'reportit'),
			'description'		=> __('Choose the type of prefix beeing used to format the result. With the use of decimal prefixes \'1024\' will be formatted to \'1.024k\' while the binary prefixes option returns \'1ki\'. Select \'off\' to display the raw data, here \'1024\'.', 'reportit'),
			'method'			=> 'drop_array',
			'array'				=> $rounding,
			'value'				=> (isset($measurand_data['rounding']) ? $measurand_data['rounding'] : '2' )
		),
		'measurand_header3'	=> array(
			'friendly_name'		=> __('Formula', 'reportit'),
			'method'			=> 'spacer',
			'collapsible' 		=> 'true',
		),
		'measurand_formula'	=> array(
			'friendly_name'		=> __('Calculation Formula', 'reportit'),
			'description'		=> __('The mathematical definion of this measurand. Allowed are all combinations of operators and operands listed below following the rules of mathematics. Use round and square brackets to signify complex terms and the order of operations.', 'reportit'),
			'method' 			=> 'custom',
			'value'				=> "<textarea aria-multiline='true' cols='60' rows='5' id='measurand_formula' name='measurand_formula'>" . (isset($measurand_data['calc_formula']) ? $measurand_data['calc_formula'] : "" ) . '</textarea>'
		),
		'measurand_ops_and_opds'=> array(
			'friendly_name'		=> __('Operators & Operands', 'reportit'),
			'description'		=> __('Click on one of the listed operators or operand to append them to your calucalion formula. The tooltip will show you additional information like description, return value, arguments and usage.', 'reportit'),
			'method' 			=> 'custom',
			'value'				=> html_calc_syntax($measurand_id, $template_id)
		),
	);

	?>
	<script type='text/javascript'>
	function change_data_type(){
		if ($('#measurand_type').val() in {0:'',2:'',3:'',4:'',5:'',6:''}) {
	 		$('#measurand_precision').prop('disabled', true);
		} else {
			$('#measurand_precision').prop('disabled', false);
		};

		if ($('#measurand_type').val() in {0:'', 4:'', 5:'', 6:'', 7:''}) {
			$('#measurand_rounding').prop('disabled', true);
		} else {
			$('#measurand_rounding').prop('disabled', false);
		}
	}

	$(function(){
		$('#measurand_type').change(function() {
			change_data_type();
		});

		/* initiate settings */
		change_data_type();

	});
	</script>

	<?php

	form_start('measurands.php');
	html_start_box($header_label, '100%', '', '2', 'center', '');


	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => $form_array
		)
	);

	html_end_box();

//	html_graph_start_box(1);
//	html_graph_end_box();

	//print '<br>';

	form_save_button('measurands.php?id=' . $template_id);
	//form_end();



	//Define layer
	print '<div id="Tooltip"></div>';

	//A little bit of java
	?>
	<script type='text/javascript'>


	function add_to_calc(name) {
		fieldId = document.getElementById('measurand_formula');
		old = fieldId.value;
		fieldId.value = old + name;
		fieldId.focus();
		fieldId.value = fieldId.value;
		return false;
	}

	</script>
	<?php
}

function form_actions() {
	global $measurand_actions, $config;

	// ================= input validation =================
	get_filter_request_var('id');
	// ====================================================

	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if (get_request_var('drp_action') == '2') { // DELETE MEASURANDS
			db_execute('DELETE FROM plugin_reportit_measurands WHERE ' . array_to_sql_or($selected_items, 'id'));

			//Check if it is necessary to lock the report template
			if (stat_autolock_template(get_request_var('id'))) {
				set_autolock_template(get_request_var('id'));
			}
		}

		header('Location: measurands.php?header=false&id=' . get_request_var('id'));
		exit;
	}

	//Set preconditions
	$ds_list = array(); $i = 0;

	foreach($_POST as $key => $value) {
		if (strstr($key, 'chk_')) {
			//Fetch report id
			$id = substr($key, 4);
			$measurand_ids[] = $id;
			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			//Fetch report description
			$measurand_description 	= db_fetch_cell_prepared('SELECT description
				FROM plugin_reportit_measurands
				WHERE id = ?',
				array($id));

			$ds_list[$measurand_description] = '';
		}
	}

	top_header();

	form_start('measurands.php');

	html_start_box($measurand_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (get_request_var('drp_action') == '2') { //DELETE REPORT
		print "<tr class='odd'>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Delete the following Measurands.  Notice: If there are no other Measurands left after this process, the Report Template will be locked automatically.', 'reportit') . '<p>';

		if (is_array($ds_list)) {
			print '<p>' . __('List of selected measurands:', 'reportit') . '</p>';
			print '<ul>';
			foreach($ds_list as $key => $value) {
				print '<li>' . __('Measurand: %s', $key, 'reportit') . '</li>';
			}
			print '</ul>';
		}

		print '</td>
			</tr>';

		if (!is_array($ds_list) || empty($ds_list)) {
			print "<tr>
				<td class='textArea'>
					<span class='textError'>" . __('You must select at least one measurand.', 'reportit') . '</span>
				</td>
			</tr>';

			$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>";
		} else {
			$save_html = "<input type='button' value='" . __esc('Cancel', 'reportit') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'reportit') . "' title='" . __esc('Delete Template Measurands', 'reportit') . "'>";
		}
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='id' value='" . get_request_var('id') . "'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($measurand_ids) ? serialize($measurand_ids) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

