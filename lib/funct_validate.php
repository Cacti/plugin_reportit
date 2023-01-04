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

include_once($config['base_path'] . '/lib/xml.php');

$error = false;

function last_error($errno, $errstr) {
	global $error;

	$error = array(
		'Number'  => $errno,
		'Message' => $errstr
	);
}

function validate_calc_formula($calc_formula, $calc_intersizes, $calc_var_names, $calc_data_query_variables) {
	global $calc_fct_names, $calc_fct_names_params, $calc_fct_aliases;

	//Valid signs:
	$valids['intersizes']['S']   = $calc_intersizes;
	$valids['intersizes']['R']   = 'E';
	$valids['functions']['S']    = $calc_fct_names;
	$valids['functions']['R']    = 'E';
	$valids['fct_nwp']['S']      = $calc_fct_names_params;
	$valids['fct_nwp']['R']      = 'P';
	$valids['fct_awp']['S']      = $calc_fct_aliases;
	$valids['fct_awp']['R']      = 'P';

	$valids['variables']['S']    = $calc_var_names;
	$valids['variables']['R']    = 'E';

	$valids['dq_variables']['S'] = $calc_data_query_variables;
	$valids['dq_variables']['R'] = 'E';

	$valids['signs']['S']        = array('(', ')', '.', ',');
	$valids['signs']['R']        = array('L', 'R', '.', ',');
	$valids['operators']['S']    = array('+','-','*','/');
	$valids['operators']['R']    = array('+','-','*','/');
	$valids['numbers']['S']      = array('1','2','3','4','5','6','7','8','9','0');
	$valids['numbers']['R']      = 'N';

	//Invalid combinations of signs:
	$invalids = array(
		'++', '+*', '+/', '--', '-*', '-/', '**', '*/', '/*', '//',
		'NE', 'EN',
		'LR', 'L*', 'L/', '.L', 'EL', 'NL',
		'RL', '+R', '-R', '*R', '/R', 'R.', 'RE', 'RN',
		'EE', '..', ',,', '.,', 'L,', ',R'
	);

	//Sometime it's better and easier to work with whitelists
	$whitelist = array(
		'P' => array('PL')
	);

	//Invalid divisions:
	$invaldiv = array('/0');

	//Search for invalid signs or function calls:
	$debug = '';
	$debug = str_replace(array(' ',"\r\n","\n"), '', $calc_formula);

	foreach($valids as $array) {
		$debug = str_replace($array['S'], '', $debug);
	}

	if (strlen($debug) != 0) {
		return "Invalid characters: $debug";
	}

	//Check the number of parenthensis;
	if (substr_count($calc_formula, '(') != substr_count($calc_formula, ')')) {
		return "Missing parenthensis:  <span style='color:blue'>$calc_formula<span>";
	}

	//Check if the formula begins or ends with an operator
	if (in_array($calc_formula[0], $valids['operators']['S'])) {
		return 'Formula begins with an operator.';
	}

	if (in_array($calc_formula[strlen($calc_formula)-1], $valids['operators']['S'])) {
		return 'Formula ends with an operator.';
	}

	//Search invalid divisions
	$debug = $calc_formula;
	foreach($invaldiv as $div) {
		$position = strpos($debug, $div);

		if ($position !== false) {
			return "Division by zero: <span style='color:blue'>$calc_formula<span>";
		}
	}

	//Search invalid combinations of operators, functions and operands:
	$debug = $calc_formula;
	foreach($valids as $array) {
		$debug = str_replace($array['S'], $array['R'], $debug);
	}

	//Blacklist
	foreach($invalids as $invalid) {
		$position = strpos($debug, $invalid);

		if ($position !== false) {
			return "Syntax error:  <span style='color:blue'>$calc_formula<span>";
		}
	}

	//Whitelist
	foreach($whitelist as $key => $valids) {
		$debug_w = $debug;
		$position = strpos($debug_w, $key);

		while($position !== false) {
			foreach($valids as $valid) {
				if (substr($debug_w, $position, strlen($valid)) != $valid) {
					return "Syntax error w:  <span style='color:blue'>$calc_formula<span>";
				}
			}

			$debug_w = substr( $debug_w, $position+1, strlen($debug_w));
			$position = strpos($debug_w, $key);
		}
	}

	//If no error occurs the formula seems to be valid
	return 'VALID';
}

function die_html_custom_error($msg = '', $top_header = false) {
	global $config;

	$message = '';
	$message = ($msg == '') ? 'Validation error' : $msg;

	top_header();

	print "<table width='98%' align='center'>
		<tr>
			<td>
				$message
			</td>
		</tr>
	</table>";

	bottom_footer();
	exit;
}

function input_validate_input_whitelist($value, $valid_list, $undefined=false, $header=true){
	if ($value == false && $undefined == true) {
		return;
	}

	if (!in_array($value, $valid_list)) {
		die_html_custom_error('', $header);
	}
}

function input_validate_input_blacklist($value, $black_list, $undefined=false, $header=true){
	if ($value == false & $undefined == true) {
		return;
	}

	if (in_array($value, $black_list)) {
		die_html_custom_error('', $header);
	}
}

function input_validate_input_key($value, $valid_list, $undefined=false, $header=true){
	if ($value == false && $undefined == true) {
		return;
	}

	if (!array_key_exists($value, $valid_list)) {
		die_html_custom_error('', $header);
	}
}

/**
 * input_validate_input_limits()
 *
 * @param float 	$value			The input number that has to be checked
 * @param float 	$lower_limit	First limiting value
 * @param float		$upper_limit	Second limiting value
 * @param binary 	$inside			Returns an error if the value is outside the limits.
 * 									If 'false', function returns an error if value is inside the limits
 * @param binary 	$header			show top_header_graph
 */
function input_validate_input_limits($value, $lower_limit, $upper_limit, $inside=true, $header=true ){
	if ($inside) {
		if ($value<$lower_limit & $value>$upper_limit) {
			die_html_custom_error('', $header);
		}
	} elseif ($value>$lower_limit & $value<$upper_limit) {
		die_html_custom_error('', $header);
	}
}

function validate_xml_template_section(&$xml_template, $section, &$valid, &$checksum) {
	//print "validate_xml_template_section:start(xml_template, $section, $valid, $checksum)\n";
	if ($valid) {
		if (isset($xml_template->$section) && is_object($xml_template->$section)) {
			$checksum .= xml_to_string($xml_template->$section, false);
		} else {
			$valid = false;
		}
	}

	//print "validate_xml_template_section:end  (xml_template, $section, $valid, $checksum)\n";
	return $valid;
}

function validate_xml_template(&$xml_template, &$valid, &$checksum) {
	//print "validate_xml_template:begin(xml_template, $valid, $checksum)\n";
	if (isset($xml_template->reportit) && is_object($xml_template->reportit)) {
		$count =0;
		// Loop through the values of the first reportit element (there should be only one)
		foreach ($xml_template->reportit[0] as $key => $value) {
			if ($key == 'hash') {
				// Lets do some magic and remove the hash, saving it for later
				$hash = $value;
				unset($xml_template->reportit[0]->{$key});
				break;
			}
			$count++;
		}
	} else {
		$valid = false;
	}

	validate_xml_template_section($xml_template, 'reportit', $valid, $checksum);
	validate_xml_template_section($xml_template, 'settings', $valid, $checksum);
	validate_xml_template_section($xml_template, 'variables', $valid, $checksum);
	validate_xml_template_section($xml_template, 'measurands', $valid, $checksum);
	validate_xml_template_section($xml_template, 'data_source_items', $valid, $checksum);

	if (isset($hash)) {
		$xml_template->reportit->hash = $hash;
	}
	//print "validate_xml_template:end  (report_template, $valid, $checksum)\n";
}

function validate_uploaded_templates(){
	/* check file tranfer if used */
	if (isset($_FILES['file'])) {
		/* check for errors first */
		if ($_FILES['file']['error'] != 0) {
			switch ($_FILES['file']['error']) {
			case 1:
				session_custom_error_message('file', __('The file is to big.', 'reportit'), false);
				break;
			case 2:
				session_custom_error_message('file', __('The file is to big.', 'reportit'), false);
				break;
			case 3:
				session_custom_error_message('file', __('Incomplete file transfer.', 'reportit'), false);
				break;
			case 4:
				session_custom_error_message('file', __('No file uploaded.', 'reportit'), false);
				break;
			case 6:
				session_custom_error_message('file', __('Temporary folder missing.', 'reportit'), false);
				break;
			case 7:
				session_custom_error_message('file', __('Failed to write file to disk', 'reportit'), false);
				break;
			case 8:
				session_custom_error_message('file', __('File upload stopped by extension', 'reportit'), false);
				break;
			}

			if (is_error_message()) {
				return false;
			}
		}

		/* check mine type of the uploaded file */
		if ($_FILES['file']['type'] != 'text/xml') {
			session_custom_error_message('file', __('Invalid file extension.', 'reportit'), false);
			return false;
		}

		$template_data = file_get_contents($_FILES['file']['tmp_name']);
	} else {
		session_custom_error_message('file', __('No file uploaded.', 'reportit'), false);
		return false;
	}

	/* try to parse the report template */
	$xmldata    = simplexml_load_string($template_data);
	$checksum   = '';
	$valid      = true;
	$hash       = false;
	$compatible = false;

	if (!is_object($xmldata)) {
		session_custom_error_message('file', __('Unable to parse template file.', 'reportit'));
	} else {
		/* generate a hash to check the data structure and to find changes */
		$report_count = 0;
		foreach ($xmldata as $report_template) {
			$report_count++;
			$valid = true;
			$report_compatible = false;
			$checksum = '';
			$hash = (string)$report_template->reportit->hash;
			validate_xml_template($report_template, $valid, $checksum);

			if ($hash == false | $hash !== md5($checksum) | $valid === false) {
				print __('Checksum error with Template %s in XML file', $report_count, 'reportit') . PHP_EOL;
				session_custom_error_message('file', __('Checksum error with Template %s in XML file', $report_count, 'reportit'), false);
				return false;
			}

			/* check dependences with existing data templates... */
			$data_template_id   = $report_template->settings->data_template_id;
			$template_ds_items  = $report_template->data_source_items[0];

			foreach($template_ds_items as $template_ds_item) {
				$template_ds_names[] = (string)$template_ds_item->data_source_name;
			}

			/* load information about defined data sources of that data template */
			$sql = "SELECT data_source_name
				FROM data_template_rrd
				WHERE local_data_id=0
				AND data_template_id = $data_template_id";

			$ds_names = db_custom_fetch_assoc($sql,false,false,false);

			if (in_array($template_ds_names, $ds_names) === false) {
				$report_compatible = true;

				$sql = ("SELECT id, name FROM data_template WHERE id = $data_template_id");
				$data_templates = db_custom_fetch_assoc($sql, 'id', false);
			} else {
				/* try to find a compatible data template */
				$names = '';

				/* drop generic data source item 'overall' */
				if (array_search('overall', $template_ds_names) !== false) {
					unset($template_ds_names[array_search('overall', $template_ds_names)]);
				}

				foreach($template_ds_names as $ds) {
					$names .= "'$ds', ";
				}

				$data_templates = false;
				if (count($template_ds_names)) {
					$names = substr($names, 0, -2);

					$sql = "SELECT a.id, b.name
						FROM (
							SELECT data_template_id AS id, COUNT(*) as `cnt`
							FROM `data_template_rrd`
							WHERE local_data_id = 0
							AND data_source_name IN ($names)
							GROUP BY data_template_id
						) AS a
						INNER JOIN data_template as b
						ON b.id = a.id
						WHERE a.cnt >= " . count($template_ds_names);

					$data_templates = db_custom_fetch_assoc($sql, 'id', false);
				}

				if ($data_templates) {
					$report_compatible = true;
				}
			}

			if ($report_compatible) {
				$tmp_node = $report_template->addChild('data_templates');
				foreach ($data_templates as $id => $name) {
					$tmp_child = $tmp_node->addChild('data_template');
					$tmp_child->addChild('id',$id);
					$tmp_child->addChild('name',$name);
				}
				$report_template->compatible = true;
				$compatible = true;
			} else {
				$report_template->compatible = false;
			}
		}

		/* save data in the user session */
		if (!isset($_SESSION['sess_reportit'])) {
			$_SESSION['sess_reportit'] = array();
		}

		$xmlstring = xml_to_string($xmldata);
		$_SESSION['sess_reportit']['report_templates'] = $xmlstring;
	}

	return $valid && $compatible;
}

