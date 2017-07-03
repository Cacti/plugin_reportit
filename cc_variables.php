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

chdir('../../');

include_once('./include/auth.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_validate.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_html.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_online.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_calculate.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/funct_shared.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/const_measurands.php');
include_once(REPORTIT_BASE_PATH . '/lib_int/const_variables.php');

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
	global $colors, $variable_actions, $link_array, $list_of_modes, $var_types, $desc_array;

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
		FROM reportit_variables
		WHERE template_id = ? ' . $affix,
		array(get_request_var('id')));

	$template_name = db_fetch_cell_prepared('SELECT description
		FROM reportit_templates
		WHERE id = ?',
		array(get_request_var('id')));

	$i = 0;

	//Number of variables
	$number_of_variables = count($variables_list);

	$header_label = __("Variables [Template:<a style='color:yellow' class='pic' href='cc_templates.php?action=template_edit&id=" . get_request_var('id') . "'>%s$template_name</a>] [%d]", $template_name, $number_of_variables);

	html_start_box($header_label, '100%', '', '2', 'center', 'cc_variables.php?action=variable_edit&template_id=' . get_request_var('id'));

	html_header_checkbox(html_sorted_with_arrows( $desc_array, $link_array, 'cc_variables.php', get_request_var('id')));

	if (sizeof($variables_list) > 0) {
		foreach($variables_list as $variable) {
			form_alternate_row_color($colors["alternate"], $colors["light"], $i, $variable["id"]); $i++;
			?>
			<td>
				<a class='linkEditMain' href="cc_variables.php?action=variable_edit&id=<?php print $variable['id'];?>">
					<?php print $variable['name'];?>
				</a>
			</td>
			<td><?php print $variable['abbreviation'];?></td>
			<td><?php print $variable['max_value'];?></td>
			<td><?php print $variable['min_value'];?></td>
			<td><?php print $variable['default_value'];?></td>
			<td><?php print $var_types[$variable['input_type']];?></td>
			<td style="<?php print get_checkbox_style();?>" width='1%' align='right'>
				<input type='checkbox' style='margin: 0px;' name='chk_<?php print $variable['id'];?>' title='Select'>
			</td>
			</tr>
			<?php
		}
	} else {
		print "<tr><td><em>No variables</em></td></tr>\n";
	}

	$form_array = array('id' => array('method'	=>'hidden_zero', 'value' =>get_request_var('id')));
	draw_edit_form(array( 'config' => array(),'fields' => $form_array));

	html_end_box(true);
	draw_custom_actions_dropdown($variable_actions, 'cc_templates.php');
}

function form_save() {
	global $colors;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	input_validate_input_number(get_request_var('template_id'));

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
	if (!(get_request_var('variable_maximum') > get_request_var('variable_minimum')))
	session_custom_error_message('variable_maximum', 'Maximum has to be greater than minimum.');
	if (!(get_request_var('variable_minimum') <= get_request_var('variable_default') && get_request_var('variable_default')<= get_request_var('variable_maximum')))
	session_custom_error_message('variable_default', 'Default value is out of values range.');
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
		header("Location: cc_variables.php?header=false&action=variable_edit&id=" . get_request_var('id') . "&template_id=" . get_request_var('template_id'));
	} else {
		//Save data
		$var_id = sql_save($variable_data, 'reportit_variables');

		if (get_request_var('id') == 0) {
			db_execute("UPDATE reportit_variables
				SET abbreviation = 'c". $var_id . "v'
				WHERE id = $var_id");

			//If its a new one we've to create the entries for all the reports
			//using this template.
			create_rvars_entries($var_id, $variable_data['template_id'], $variable_data['default_value']);
		}

		//Return to list view if it was an existing report
		header('Location: cc_variables.php?header=false&id=' . get_request_var('template_id'));
		raise_message(1);
		exit;
	}
}

function variable_edit() {
	global $colors, $template_actions, $var_types;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if (!isempty_request_var('id')) {
		$variable_data = db_fetch_row_prepared('SELECT *
			FROM reportit_variables
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label 		= __('Variable Configuration [edit: %s]', $variable_data['name']);
	} else {
		$header_label 		= __('Variable Configuration [new]');
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
			'friendly_name' => __('General'),
			'method' => 'spacer'
	    ),
		'variable_abbreviation'	=> array(
			'friendly_name' => __('Internal name'),
			'description' => __('The internal name that should be used to call this variable<br> out of a formula.'),
			'method' => 'custom',
			'max_length' => '100',
			'value' => (isset($variable_data['abbreviation']) ? $variable_data['abbreviation'] : '-Available after first saving-')
		),
		'variable_name' => array(
			'friendly_name' => __('Name'),
			'description' => __('The name that should be used as headline.'),
			'method' => 'textbox',
			'max_length' => '100',
			'value' => (isset($variable_data['name']) ? $variable_data['name'] : '')
		),
		'variable_description' => array(
			'friendly_name' => __('Description'),
			'description' => __('A short, pithy description that explains the sense of this variable.'),
			'method' => 'textarea',
			'textarea_rows' => '2',
			'textarea_cols' => '50',
			'default' => __('Your description'),
			'value' => (isset($variable_data['description']) ? $variable_data['description'] : '')
		),
		'variable_maximum' => array(
			'friendly_name' => __('Maximum Value'),
			'description' => __('Define the maximum value the variable can get.'),
			'method' => 'textbox',
			'max_length' => '10',
			'value' => (isset($variable_data['max_value']) ? $variable_data['max_value'] : '')
		),
		'variable_minimum' => array(
			'friendly_name' => __('Minimum Value'),
			'description' => __('Define the minimum value the variable can get.'),
			'method' => 'textbox',
			'max_length' => '10',
			'value' => (isset($variable_data['min_value']) ? $variable_data['min_value'] : '')
		),
		'variable_default' => array(
			'friendly_name' => __('Default Value'),
			'description' => __('Define the default value.'),
			'method' => 'textbox',
			'max_length' => '10',
			'value' => (isset($variable_data['default_value']) ? $variable_data['default_value'] : '')
		),
		'variable_type' => array(
			'friendly_name' => __('Type'),
			'description' => __('The method the report owner should use to define this variable.'),
			'method' => 'drop_array',
			'array' => $var_types,
			'value' => (isset($variable_data['input_type']) ? $variable_data['input_type'] : '')
		),
		'variable_stepping' => array(
			'friendly_name' => __('Stepping'),
			'description' => __('Define the step (only positive) the values should increase<br>within the values range that has been defined above.'),
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

	html_start_box($header_label, '100%', '', '2', 'center', '');

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
		)
	);

	html_end_box();

	form_save_button('cc_variables.php?&id=' . $template_id);
}

function form_actions() {
	global $colors, $variable_actions, $config;
	$error = FALSE;

	// ================= input validation =================
	get_filter_request_var('id');
	// ====================================================

	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_request_var('selected_items')));

		if (get_request_var('drp_action') == '1') { // delete variables
			db_execute('DELETE FROM reportit_variables WHERE ' . array_to_sql_or($selected_items, 'id'));
			db_execute('DELETE FROM reportit_rvars WHERE ' . array_to_sql_or($selected_items, 'variable_id'));
		}

		header('Location: cc_variables.php?header=false&id=' . get_request_var('id'));
		exit;
	}

	//Set preconditions
	$ds_list = ''; $i = 0;

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
				FROM reportit_variables
				WHERE id = ?',
				array($id));

			$ds_list[$variable_description] = '';
		}
	}

	top_header();

	html_start_box($variable_actions{get_request_var('drp_action')}, '60%', '', '2', 'center', '');

	form_start('cc_variables.php');

	if (get_request_var('drp_action') == '1') { //DELETE REPORT
		print "<tr>
			<td class='textArea'
				<p>" . __('Click \'Continue to Delete the following variables.') . '</p>';

		if (is_array($ds_list)) {
			//Check possible dependences for each variable
			foreach($variable_ids as $id) {
				$name = db_fetch_cell_prepared('SELECT abbreviation
					FROM reportit_variables
					WHERE id = ?',
					array($id));

				$count = db_fetch_cell_prepared("SELECT COUNT(*)
					FROM reportit_measurands
					WHERE template_id = ?
					AND calc_formula LIKE '%$name%'",
					array(get_request_var('id')));

				if ($count != 0) {
					$error = true;
					break;
				}
			}

			if (!$error){
				print '<p>' . __('List of selected variables:') . '</p>';
				print '<ul>';
				foreach($ds_list as $key => $value) {
					print '<li>' . __('Variable : %s', $key) . '</li>';
				}
				print '</ul>';
			}
		}

		print '</td>
		</tr>';

		if (!is_array($ds_list) || $error == TRUE) {
			if ($error) {
				print "<tr><td class='odd'><span class='textError'>" . __('There are one or more variables in use.') . '</span></td></tr>';
			} else {
				print "<tr><td class='odd'><span class='textError'>" . __('You must select at least one variable.') . '</span></td></tr>';
			}

			$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>";
		} else {
			$save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue') . "' title='" . __('Delete Template Variables') . "'>";
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

	bottom_footer();
}

