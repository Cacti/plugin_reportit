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

$measurand_actions 	= array(
	2 => __('Delete')
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

	global $colors, $measurand_actions, $config, $consolidation_functions;

	/* ================= input validation ================= */
	$id = input_validate_input_number(get_request_var('id'));
	/* ==================================================== */

	$sql = "SELECT * FROM reportit_measurands WHERE template_id={get_request_var('id')} ORDER BY id";
	$measurands_list	= db_fetch_assoc($sql);
	$template_name	= db_fetch_cell('SELECT description FROM reportit_templates WHERE id=' .get_request_var('id'));

	$i = 0;

	//Number of measurands
	$number_of_measurands = count($measurands_list);
	$header_label	= "<b>Measurands </b>[Template: <a style='color:yellow' href='cc_templates.php?action=template_edit&id={get_request_var('id')}'>$template_name</a>] [$number_of_measurands]";

	html_start_box("$header_label", '100%', $colors["header"], "2", "center", "cc_measurands.php?action=measurand_edit&template_id={get_request_var('id')}");

	html_header_checkbox(array('Name', 'Abbreviation', 'Unit', 'Consolidation Function', 'Visible', 'Separate', 'Calculation Formula'));

	if (sizeof($measurands_list) > 0) {
		foreach($measurands_list as $measurand) {
			form_alternate_row_color($colors["alternate"], $colors["light"], $i, $measurand["id"]);
			$i++;
			?>
				<td>
					<a class='linkEditMain' href="cc_measurands.php?action=measurand_edit&id=<?php print $measurand['id'];?>"><?php print $measurand['description'];?> </a>
				</td>
				<td><?php print $measurand['abbreviation'];?></td>
				<td><?php print $measurand['unit'];?></td>
				<td><?php print $consolidation_functions[$measurand['cf']];?></td>
				<td><?php html_checked_with_arrow($measurand['visible']);?></td>
				<td><?php html_checked_with_arrow($measurand['spanned']);?></td>
				<td><?php print $measurand['calc_formula'];?></td>
				<td	style="<?php print get_checkbox_style();?>" width='1%' align='right'>
					<input type='checkbox' style='margin: 0px;' name='chk_<?php print $measurand['id'];?>' title='Select'>
				</td>
			</tr>
			<?php
		}
	}else {
		print "<tr><td><em>No measurands</em></td></tr>\n";
	}

	$form_array = array('id' => array('method' => 'hidden_zero', 'value' => get_request_var('id')));
	draw_edit_form(array( 'config' => array(), 'fields' => $form_array));

	html_end_box(true);
	draw_custom_actions_dropdown($measurand_actions, 'cc_templates.php');
}



function form_save() {
	global $colors, $calc_var_names, $rounding, $precision, $type_specifier;

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
	$sql = "SELECT COUNT(*) FROM reportit_measurands WHERE abbreviation='{get_request_var('measurand_abbreviation')}'
			 AND id!='{get_request_var('id')}' AND template_id='{get_request_var('template_id')}'";
	$count 	= db_fetch_cell($sql);
	if($count != 0) session_custom_error_message('measurand_abbreviation', 'Duplicate abbreviation');

	//Check calculation formula
	if(strlen(get_request_var('measurand_formula'))) {
		$calc 		= get_request_var('measurand_formula');
		$intersizes 	= get_interim_results(get_request_var('id'), get_request_var('template_id'));
		$calc_var_names = array_keys(get_possible_variables(get_request_var('template_id')));
		$data_query_variables = get_possible_data_query_variables(get_request_var('template_id'));
		$error 		= validate_calc_formula($calc, $intersizes, $calc_var_names, $data_query_variables);
		if($error != 'VALID') session_custom_error_message('measurand_formula', $error);
	}

	//Check possible dependences with other measurands
	if(!is_error_message_field('measurand_abbreviation') && get_request_var('id') != 0) {
		$dependences 	= array();
		$new		= get_request_var('measurand_abbreviation');

		$sql		= "SELECT abbreviation FROM reportit_measurands WHERE id={get_request_var('id')}";
		$old		= db_fetch_cell($sql);

		if($old != $new) {
			$sql	= "SELECT id, calc_formula FROM reportit_measurands WHERE template_id={get_request_var('template_id')}
						 AND id >{get_request_var('id')}
						 AND calc_formula LIKE '%$old%'";
			$dependences= db_fetch_assoc($sql);
			foreach($dependences as $key => $value) {
				$value['calc_formula'] 	= str_replace($old, $new, $value['calc_formula']);
				$dependences[$key]		= $value;
			}
		}

		//Check if interim results are used in other measurands
		if(isset_request_var('measurand_spanned')) {
			$sql	= "SELECT COUNT(*) FROM reportit_measurands WHERE template_id={get_request_var('template_id')}
						 AND id >{get_request_var('id')}
						 AND calc_formula LIKE '%$old:%'";
			$count	= db_fetch_cell($sql);
			if($count != 0) session_custom_error_message('measurand_spanned', 'Interim results are used by other measurands.');
		}
	}

	$measurand_data 					= array();
	$measurand_data['id']				= get_request_var('id');
	$measurand_data['template_id']		= get_request_var('template_id');
	$measurand_data['description']		= get_request_var('measurand_description');
	$measurand_data['abbreviation']		= strtoupper(get_request_var('measurand_abbreviation'));
	$measurand_data['unit']				= get_request_var('measurand_unit');
	$measurand_data['visible']			= isset_request_var('measurand_visible') ? 1 : 0;
	$measurand_data['spanned']			= isset_request_var('measurand_spanned') ? 1 : 0;
	$measurand_data['calc_formula']		= get_request_var('measurand_formula');
	$measurand_data['rounding']			= isset_request_var('measurand_rounding') ? get_request_var('measurand_rounding'): '';
	$measurand_data['cf']				= get_request_var('measurand_cf');
	$measurand_data['data_type']		= get_request_var('measurand_type');
	$measurand_data['data_precision']	= isset_request_var('measurand_precision') ? get_request_var('measurand_precision') : '';

	if(is_error_message()) {
		header("Location: cc_measurands.php?action=measurand_edit&id={get_request_var('id')}&template_id={get_request_var('template_id')}");
	}else {
		//Save data
		sql_save($measurand_data, 'reportit_measurands');

		//Update dependences if it's necessary
		if(isset($dependences)) update_formulas($dependences);

		//Return to list view if it was an existing report
		header("Location: cc_measurands.php?&id={get_request_var('template_id')}");
		raise_message(1);
	}
}



function measurand_edit() {
	global $colors, $config, $template_actions, $rounding, $consolidation_functions, $type_specifier, $precision;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	/* ==================================================== */

	if (!isempty_request_var('id')) {
		$measurand_data		= db_fetch_row('SELECT * FROM reportit_measurands WHERE id=' . get_request_var('id'));
		$header_label 		= '[edit: ' . $measurand_data['description'] . ']';
	}else {
		$header_label 		= '[new]';
	}

	session_custom_error_display();

	$measurand_id		= (isset_request_var('id') ? get_request_var('id') : '0');
	$template_id		= (isset_request_var('template_id') ? get_request_var('template_id') : $measurand_data['template_id']);

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
		'friendly_name'		=> 'General',
		'method'			=> 'spacer'
		),
		'measurand_description'	=> array(
		'friendly_name'		=> 'Name',
		'method'			=> 'textbox',
		'max_length'		=> '100',
		'description'		=> 'The explanation given to this measurand.<br>Available as legend and tooltip.',
		'value'				=> (isset($measurand_data['description']) ? $measurand_data['description'] : '')
		),
		'measurand_abbreviation'=> array(
		'friendly_name'		=> 'Abbreviation',
		'method'			=> 'textbox',
		'max_length'		=> '4',
		'description'		=> 'Define a unique abbreviation for this<br> measurand with max. 4 letters/numbers.',
		'value'				=> (isset($measurand_data['abbreviation']) ? $measurand_data['abbreviation'] : '')
		),
		'measurand_unit'	=> array(
		'friendly_name'		=> 'Unit',
		'method'			=> 'textbox',
		'max_length'		=> '25',
		'description'		=> 'The unit given to this measurand.<br>e.g. "Bits/s"',
		'value'				=> (isset($measurand_data['unit']) ? $measurand_data['unit'] : '')
		),
		'measurand_cf'		=> array(
		'friendly_name'		=> 'Consolidation function',
		'method'			=> 'drop_array',
		'default'			=> '0',
		'description'		=> 'The name of the consolidation function to define which CDPs should be read out.',
		'value'				=> (isset($measurand_data['cf']) ? $measurand_data['cf'] : ''),
		'array'				=> $consolidation_functions
		),
		'measurand_visible'	=> array(
		'friendly_name'		=> 'Visible',
		'method'			=> 'custom',
		'description'		=> 'Choose \'enable\' if this measurand should be shown in the report.',
		'value'				=> "<input type='checkbox' id='measurand_visible' name='measurand_visible'". ((!isset($measurand_data['visible']) || $measurand_data['visible'] == true) ? ' checked' :'') . '>',
		),
		'measurand_spanned'	=> array(
		'friendly_name'		=> 'Separate',
		'method'			=> 'custom',
		'description'		=> 'Choose \'enable\' if this measurand will only have one result<br>
								instead of one for every Round Robin Archive and it\'s result<br>should be displayed separately.',
		'value'				=> "<input type='checkbox' id='measurand_spanned' name='measurand_spanned'". ((isset($measurand_data['spanned']) && $measurand_data['spanned'] == true) ? ' checked' :'') . '>',
		),
		'measurand_header2'	=> array(
		'friendly_name'		=> 'Formatting',
		'method'			=> 'spacer'
		),
		'measurand_type'=> array(
		'friendly_name'		=> 'Type',
		'method'			=> 'drop_array',
		'array'				=> $type_specifier,
		'description'		=> 'Defines as what type the data should be treated as.',
		'value'				=> (isset($measurand_data['data_type']) ? $measurand_data['data_type'] : '1' )
		),
		'measurand_precision'=> array(
		'friendly_name'		=> 'Precision',
		'method'			=> 'drop_array',
		'array'				=> $precision,
		'description'		=> 'Defines how many decimal digits should be displayed for floating-point numbers.',
		'value'				=> (isset($measurand_data['data_precision']) ? $measurand_data['data_precision'] : '2' )
		),
		'measurand_rounding'=> array(
		'friendly_name'		=> 'Prefixes',
		'method'			=> 'drop_array',
		'array'				=> $rounding,
		'description'		=> 'Choose the type of prefix beeing used to format the result. With the use of decimal prefixes "1024" will be formatted to "1.024k" while the binary prefixes option returns "1ki". Select "off" to display the raw data, here "1024".',
		'value'				=> (isset($measurand_data['rounding']) ? $measurand_data['rounding'] : '2' )
		),
		'measurand_header3'	=> array(
		'friendly_name'		=> 'Formula',
		'method'			=> 'spacer'
		),
		'measurand_formula'	=> array(
		'friendly_name'		=> 'Calculation Formula',
		'method' 			=> 'custom',
		'description'		=> 'This measurand\'s mathematical definition.<br>Use the operators and operands below.',
		'value'				=> "<input type='text' id='measurand_formula' name='measurand_formula' size='40' maxlength='200'" . (isset($measurand_data['calc_formula']) ? "value='{$measurand_data['calc_formula']}'" : "" ) . '>'
		),
	);


	?>
	<script language="JavaScript">
	$(document).ready(function(){

		function change_data_type(){
			if($("#measurand_type").val() in {0:'',2:'',3:'',4:'',5:'',6:''}) {
		 		$("#measurand_precision").attr("disabled", "disabled");
			}else {
				$("#measurand_precision").removeAttr("disabled");
			};

			if($("#measurand_type").val() in {0:'', 4:'', 5:'', 6:'', 7:''}) {
				$("#measurand_rounding").attr("disabled", "disabled");
			}else {
				$("#measurand_rounding").removeAttr("disabled");
			}
		}

		$("#measurand_type").change(function() {
			change_data_type();
		});

		/* initiate settings */
		change_data_type();

	});
	</script>

	<?php


	html_start_box("<strong>Measurand configuration</strong> $header_label", '100%', $colors["header"], "2", "center", "");

	draw_edit_form(array(
		'config' => array(),
		'fields' => $form_array
	));
	html_end_box();

	html_graph_start_box(1);
	html_calc_syntax($measurand_id, $template_id);
	html_graph_end_box();
	echo "<br>";

	form_save_button("cc_measurands.php?&id=$template_id");

	//Define layer
	echo "<div id=\"Tooltip\"></div>";

	//A little bit of java
	?>
	<script language="JavaScript">

	var desc = new Array();

	desc[0] = new Object();
	desc[0]["name"]			= "f_avg";
	desc[0]["params"]		= "none";
	desc[0]["syntax"]		= "<i>float</i> f_avg";
	desc[0]["description"]	= "returns the average of all measured values."
	desc[0]["example"]		= "f_avg*8";

	desc[1] = new Object();
	desc[1]["name"]			= "f_max";
	desc[1]["params"]		= "none";
	desc[1]["syntax"]		= "<i>float</i> f_max";
	desc[1]["description"]	= "returns measured data's highest value."
	desc[1]["example"]		= "(f_max-f_min)*8";

	desc[2] = new Object();
	desc[2]["name"] 	= "f_min";
	desc[2]["params"] 	= "none";
	desc[2]["syntax"]	= "<i>float</i> f_min";
	desc[2]["description"] 	= "returns measured data's lowest value."
	desc[2]["example"]	= "f_min*8";

	desc[3] = new Object();
	desc[3]["name"] 	= "f_sum";
	desc[3]["params"] 	= "none";
	desc[3]["syntax"]	= "<i>float</i> f_sum";
	desc[3]["description"] 	= "returns the sum of all measured values."
	desc[3]["example"]	= "f_sum*8";

	desc[4] = new Object();
	desc[4]["name"] 	= "f_num";
	desc[4]["params"] 	= "none";
	desc[4]["syntax"]	= "<i>int</i> f_num";
	desc[4]["description"] 	= "returns the number of valid measured values. (excludes NaN\'s)";
	desc[4]["example"]	= "f_num";

	desc[5] = new Object();
	desc[5]["name"] 	= "f_grd";
	desc[5]["params"] 	= "none";
	desc[5]["syntax"]	= "<i>float</i> f_grd";
	desc[5]["description"] 	= "returns the gradient of a straight line by using linear regression. (trend analysis)";
	desc[5]["example"]	= "f_grd";

	desc[6] = new Object();
	desc[6]["name"] 	= "f_last";
	desc[6]["params"] 	= "none";
	desc[6]["syntax"]	= "<i>float</i> f_last";
	desc[6]["description"] 	= "returns the last valid measured value of the reporting period. (excludes NaN\'s)";
	desc[6]["example"]	= "f_last*16/2";

	desc[7] = new Object();
	desc[7]["name"] 	= "f_1st";
	desc[7]["params"] 	= "none";
	desc[7]["syntax"]	= "<i>float</i> f_1st";
	desc[7]["description"] 	= "returns the first valid measured value of the reporting period. (excludes NaN\'s)";
	desc[7]["example"]	= "f_1st*2*(5.5-1.5)";

	desc[8] = new Object();
	desc[8]["name"] 	= "f_xth";
	desc[8]["params"] 	= '$var: threshold in percent. Range: [0&lt; $var &le;100]';
	desc[8]["syntax"]	= "<i>float</i> f_xth <i>(float $var)</i>";
	desc[8]["description"] 	= "returns the xth percentile.";
	desc[8]["example"]	= '<table><tr><td width="75" align="left">--&gt;fixed:<br>--&gt;variable:</td>'
						+ '<td align="left">f_xth(95.7)<br>f_xth(c1v)</td></tr></table>';

	desc[9] = new Object();
	desc[9]["name"] 	= "f_dot";
	desc[9]["params"] 	= '$var: threshold (absolute)';
	desc[9]["syntax"]	= "<i>float</i> f_dot <i>(float $var)</i>";
	desc[9]["description"] 	= "returns the duration over a defined threshold in percent.";
	desc[9]["example"]	= '<table><tr><td width="75" align="left">--&gt;fixed:<br>--&gt;variable:</td>'
				+ '<td align="left">f_dot(10000000)<br>f_dot(maxValue*c1v/100)</td></tr></table>';

	desc[10] = new Object();
	desc[10]["name"] 	= "f_sot";
	desc[10]["params"] 	= '$var: threshold (absolute)';
	desc[10]["syntax"]	= "<i>float</i> f_sot <i>(float $var)</i>";
	desc[10]["description"] = "returns the sum over a defined threshold.";
	desc[10]["example"]	= '<table><tr><td width="75" align="left">--&gt;fixed:<br>--&gt;variable:</td>'
				+ '<td align="left">f_sot(75000000)<br>f_sot(maxValue*c4v/100)</td></tr></table>';

	desc[11] = new Object();
	desc[11]["name"]		= "maxValue";
	desc[11]["params"]		= 'none';
	desc[11]["syntax"]		= "<i>float</i> maxValue";
	desc[11]["description"]	= 'This variable contains the maximum bandwidth if a value for SNMP parameter \"ifspeed\" or \"ifHighSpeed\" is available.'
								+ ' If not, maxValue will automatically have the same value like maxRRDValue.'
	desc[11]["example"]		= "f_sot(maxValue*c4v/100)";

	desc[12] = new Object();
	desc[12]["name"]		= "maxRRDValue";
	desc[12]["params"]		= 'none';
	desc[12]["syntax"]		= "<i>float</i> maxRRDValue";
	desc[12]["description"]	= 'This variable contains the maximum value that has been defined for the specific data source item under \"Data Sources\".'
								+ ' If it is not available, maximum value of the related data template will be used.'
	desc[12]["example"]		= "f_sot(maxRRDValue*c4v/100)";

	desc[13] = new Object();
	desc[13]["name"] 	= "step";
	desc[13]["params"] 	= 'none';
	desc[13]["syntax"]	= "<i>int</i> step";
	desc[13]["description"] 	= "The step is the distance of time between two measured values in seconds.";
	desc[13]["example"]	= 'step';

	desc[14] = new Object();
	desc[14]["name"] 	= "nan";
	desc[14]["params"] 	= 'none';
	desc[14]["syntax"]	= "<i>int</i> nan";
	desc[14]["description"] = "Sum of NaN's during the reporting period. It's <b>NOT</b> differentiated in data sources!";
	desc[14]["example"]	= 'nan';

	desc[15] = new Object();
	desc[15]["name"] = "f_int";
	desc[15]["params"] = '$var: float or string value';
	desc[15]["syntax"] = "<i>integer</i> f_int <i>(int $var)</i>";
	desc[15]["description"] = "returns the integer value of any given float, string.";
	desc[15]["example"] = '<table><tr><td width="75" align="left">--&gt;decimal:<br>--&gt;returns:</td><td align="left">f_int(69.69)<br>69</td></tr></table>';

	desc[16] = new Object();
	desc[16]["name"] = "f_rnd";
	desc[16]["params"] = '$var: float or string value';
	desc[16]["syntax"] = "<i>integer</i> f_rnd <i>(int $var)</i>";
	desc[16]["description"] = "returns the rounded integer value of any given float, string.";
	desc[16]["example"] = '<table><tr><td width="75" align="left">--&gt;decimal:<br>--&gt;returns:</td><td align="left">f_rnd(69.69)<br>70</td></tr></table>';

	desc[17] = new Object();
	desc[17]["name"] = "f_high";
	desc[17]["params"] = '$var1, $var2, $var3 ...: values to be compared';
	desc[17]["syntax"] = "<i>float</i> f_hgh <i>(float $var1, float $var2)</i>";
	desc[17]["description"] = "returns the highest value of a given list of parameters";
	desc[17]["example"] = '<table><tr><td width="75" align="left">--&gt;float:<br>--&gt;returns:</td><td align="left">f_high(27,70)<br>70</td></tr></table>';

	desc[18] = new Object();
	desc[18]["name"] = "f_low";
	desc[18]["params"] = '$var1, $var2, §var3 ...: values to be compared';
	desc[18]["syntax"] = "<i>float</i> f_low <i>(float $var1, float $var2)</i>";
	desc[18]["description"] = "returns the lowest value of a given list of parameters.";
	desc[18]["example"] = '<table><tr><td width="75" align="left">--&gt;float:<br>--&gt;returns:</td><td align="left">f_high(27,70)<br>27</td></tr></table>';

	function add_to_calc(name) {
		fieldId = document.getElementById('measurand_formula');
		old = fieldId.value;
		fieldId.value = old + name;
		fieldId.focus();
		fieldId.value = fieldId.value;
	}

	function tooltip(layer, name, status) {
		if(status) {
			for(var i = 0; i < desc.length; i++) {
				if (desc[i]["name"] == name) {
					Tooltip.innerHTML =
						'<br>'
						+'<table width=<?php print '100%'; ?> align="center" cellpadding="2" style="background-color:#FFFACD; border: 1px solid #bbbbbb;" >'
						+'<tr><td width="100">Name:</td><td>' + name + '</td></tr>'
						+'<tr><td>Syntax:</td><td>' + desc[i]["syntax"] + '</td></tr>'
						+'<tr><td>Parameters:</td><td>' + desc[i]["params"] + '</td></tr>'
						+'<tr><td valign="top">Description:</td><td>' + desc[i]["description"] + '</td></tr>'
						+'<tr><td align="left" valign="top">Example:</td><td>' + desc[i]["example"] + '</td></tr>'
						+'</table>';
						document.getElementById(layer).style.visibility = "visible"
					break;
				}
			}
		}else {
			Tooltip.innerHTML = "";
			Tooltip.style.visibility = "hidden"
		}
	}

	</script>
	<?php
}



function form_actions() {
	global $colors, $measurand_actions, $config;

	// ================= input validation =================
	input_validate_input_number(get_request_var('id'));
	// ====================================================


	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_request_var('selected_items')));

		if (get_request_var('drp_action') == '2') { // DELETE MEASURANDS
			$sql = 'DELETE FROM reportit_measurands WHERE ' . array_to_sql_or($selected_items, 'id');
			db_execute($sql);

			//Check if it is necessary to lock the report template
			if(stat_autolock_template(get_request_var('id'))) set_autolock_template(get_request_var('id'));
		}
		header("Location: cc_measurands.php?id={get_request_var('id')}");
		exit;
	}

	//Set preconditions
	$ds_list = ''; $i = 0;

	foreach($_POST as $key => $value) {
		if(strstr($key, 'chk_')) {
			//Fetch report id
			$id = substr($key, 4);
			$measurand_ids[] = $id;
			// ================= input validation =================
			input_validate_input_number($id);
			// ====================================================

			//Fetch report description
			$measurand_description 	= db_fetch_cell('SELECT description FROM reportit_measurands WHERE id=' . $id);
			$ds_list[$measurand_description] = '';
		}
	}

	top_header();
	html_start_box("<strong>" . $measurand_actions{get_request_var('drp_action')} . "</strong>", "60%", $colors["header_panel"], "3", "center", "");

	print "<form action='cc_measurands.php' autocomplete='off' method='post'>";

	if (get_request_var('drp_action') == '2') { //DELETE REPORT
		print "	<tr>
					<td bgcolor='#" . $colors["form_alternate1"]. "'>
						<p>Are you sure you want to delete the following measurands?</p>
						Notice: If there are no other measurands left after this process, the report template will be locked automatically.<p>";
		if(is_array($ds_list)) {
			print	"<p>List of selected measurands:<br>";
			foreach($ds_list as $key => $value) {
				print "&#160 |_Measurand: $key<br>";
			}
		}
		print "		</td>
				</tr>";

		if (!is_array($ds_list)) {
			print "<tr>
						<td bgcolor='#" . $colors['form_alternate1']. "'>
							<span class='textError'>You must select at least one measurand.</span>
						</td>
					</tr>";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>";
		}else {
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Delete Template Measurands'>";
		}
	}

	print " <tr>
				<td align='right' bgcolor='#eaeaea'>
				<input type='hidden' name='id' value='{get_request_var('id')}'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($measurand_ids) ? serialize($measurand_ids) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
			</tr>";

	html_end_box();
	bottom_footer();
}
