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

/* ----- Functions without external parameters ----- */

//Count the number of available measuring points
function f_num(&$array, &$f_cache) {
	$f_cache['f_count'] = count($array);

	return $f_cache['f_count'];
}

//Sum
function f_sum(&$array, &$f_cache) {
	$f_cache['f_sum'] = empty($array) ? REPORTIT_NAN : array_sum($array);

	return $f_cache['f_sum'];
}

//Average
function f_avg(&$array, &$f_cache) {
	$f_cache['f_avg'] = empty($array) ? REPORTIT_NAN : array_sum($array)/count($array);

	return $f_cache['f_avg'];
}

//Maximum
function f_max(&$array, &$f_cache) {
	$f_cache['f_max'] = empty($array) ? REPORTIT_NAN : max($array);

	return $f_cache['f_max'];
}

//Minimum
function f_min(&$array, &$f_cache) {
	$f_cache['f_min'] = empty($array) ? REPORTIT_NAN : min($array);

	return $f_cache['f_min'];
}

//First measured value
function f_1st(&$array, &$f_cache) {
	$f_cache['f_1st'] = empty($array) ? REPORTIT_NAN : reset($array);

	return $f_cache['f_1st'];
}

//Last measured value
function f_last(&$array, &$f_cache) {
	$f_cache['f_last'] = empty($array) ? REPORTIT_NAN : end($array);

	return $f_cache['f_last'];
}

//Gradient
function f_grd(&$array, &$f_cache) {
	if (empty($array)) {
		$f_cache['f_grd'] = REPORTIT_NAN;
		return $f_cache['f_grd'];
	}

	$cnt = count($array);

	$y_array = array_values($array);
	$x_array = array_keys($array);

	$y_i = array_sum($y_array)/$cnt;
	$x_i = array_sum($x_array)/$cnt;

	$num   = 0;
	$denum = 0;

	for ($i=0; $i<$cnt; $i++) {
		$num   += ($x_array[$i]-$x_i)*($y_array[$i]-$y_i);
		$denum += pow(($x_array[$i]-$x_i),2);
	}

	$f_cache['f_grd'] = $num/$denum;
	return $f_cache['f_grd'];
}

/* ----- Functions with external variables ----- */

//Xth percentitle
function f_xth(&$array, &$p_cache, $value) {
	if ($value > 100 || $value <= 0) {
		return REPORTIT_NAN;
	}

	if (empty($array)) {
		$p_cache['f_xth'] = REPORTIT_NAN;

		return $p_cache['f_xth'];
	}

	sort($array);

	$x = intval(count($array)*($value/100));
	$p_cache['f_xth'] = $array[$x];

	return $p_cache['f_xth'];
}

//Over Threshold
function f_sot(&$array, &$p_cache, $threshold) {
	if (empty($array)) {
		$p_cache['f_sot'] = REPORTIT_NAN;

		return $p_cache['f_sot'];
	}

	$over_threshold = 0;

	foreach ($array as $value) {
		if ($value != 0) {
			$value -= $threshold;

			if ($value > 0) {
				$over_threshold += $value;
			}
		}
	}

	$p_cache['f_sot'] = $over_threshold;

	return $p_cache['f_sot'];
}

//Duration Over Threshold
function f_dot(&$array, &$p_cache, $threshold) {

	if (empty($array)) {
		$p_cache['f_dot'] = REPORTIT_NAN;
		return $p_cache['f_dot'];
	}

	$i = 0;
	foreach ($array as $value) {
		if ($value != 0) {
			$value -= $threshold;
			if ($value > 0) {
				$i++;
			}
		}
	}

	$p_cache['f_dot'] = ($i/count($array))*100;

	return $p_cache['f_dot'];
}

//Get the integer value
function f_int(&$array, &$p_cache, $value) {
	if (empty($array)) {
		$p_cache['f_int'] = REPORTIT_NAN;

		return $p_cache['f_int'];
	}

	$intvalue = floor($value);
	$p_cache['f_int'] = $intvalue;

	return $p_cache['f_int'];
}

//Get the rounded integer value
function f_rnd(&$array, &$p_cache, $value) {
	if (empty($array)) {
		$p_cache['f_rnd'] = REPORTIT_NAN;

		return $p_cache['f_rnd'];
	}

	$intvalue = round($value);
	$p_cache['f_rnd'] = $intvalue;

	return $p_cache['f_rnd'];
}

//Get the highest value of a list of given numbers
function f_high(&$array, &$p_cache) {
	if (func_num_args() < 3 | empty($array)) {
		$p_cache['f_high'] = REPORTIT_NAN;

		return $p_cache['f_high'];
	}

	$p_cache['f_high'] = max(array_slice(func_get_args(), 2));

	return $p_cache['f_high'];
}

//Get the lowest values of a list of given numbers
function f_low(&$array, &$p_cache) {
	if (func_num_args() < 3 | empty($array)) {
		$p_cache['f_low'] = REPORTIT_NAN;

		return $p_cache['f_low'];
	}

	$p_cache['f_low'] = min(array_slice(func_get_args(), 2));

	return $p_cache['f_low'];
}

/* ----- Main function for calculating ----- */

//Normal way of calulation
function calculate(& $data,& $params, & $variables, & $df_cache, & $dm_cache, & $dr_cache, & $dp_cache, & $ds_cache) {
	$results = array();

	$f_cache = $df_cache;	//Functions
	$m_cache = $dm_cache;	//Measurands
	$r_cache = $dr_cache;	//Interim results
	$p_cache = $dp_cache;	//Functions with parameters
	$s_cache = $ds_cache;	//Measurands with flag "spanned"

	$n_rra   = $params['rrd_ds_cnt'];
	$ds_namv = $params['rras'];

	$specific_variables = array('maxValue', 'maxRRDValue');

	//Create a cache for every Round Robin Archive
	foreach ($ds_namv as $key => $ds_name) {
		$cache[$key] = 	array($f_cache, $m_cache, $p_cache);
	}

	//Use reportit's error handler.
	set_error_handler('last_error');

	//Build the calculation command and execute it
	foreach ($m_cache as $k => $m) {
		debug($cache, "Main Cache Status: f,m,p");

		// we need the correct rra index to choose the right data
		$rra_index = $params['rra_indexes'][$k];

		foreach ($ds_namv as $i => $ds_name) {
			debug($cache, "Main Cache Status: f,m,p");

			// Debug
			$debug = array();

			//Formula
			$formula = $m;
			$debug[]= $formula;

			// transform RRA specific variables (maxValue, maxRRDValue) used in that formula
			foreach ($specific_variables as $specific_variable){
				$formula = str_replace($specific_variable, $specific_variable . ':' . $ds_name, $formula);
			}

			$debug[]= $formula;

			//Replace our variables
			foreach ($variables as $key => $value) {
				$formula = str_replace($key, $value, $formula);
			}

			$debug[]= $formula;

			//Replace measurands (spanned)
			foreach ($s_cache as $key => $value) {
				$pattern = '/(^|[+|\-|*|\/|\(|\)|,| ])'.$key.'([+|\-|*|\/|\(|\)|,| ]|$)/';
				$formula = preg_replace($pattern, "\${1}$value\${2}", $formula);
			}

			$debug[]= $formula;

			//Replace interim results first:
			foreach ($r_cache as $key => $value) {
				if ($value !== false) $formula = str_replace($key, $value, $formula);
			}

			$debug[]= $formula;

			//Replace measurands with an existing result if we have one
			foreach ($cache[$i][1] as $key => $value) {
				$pattern = '/(^|[+|\-|*|\/|\(|\)|,| ])'.$key.'([+|\-|*|\/|\(|\)|,| ]|$)/';
				$formula = preg_replace($pattern, "\${1}$value\${2}", $formula);
			}

			$debug[]= $formula;

			//Replace formula calls
			foreach ($cache[$i][0][$rra_index] as $key => $value) {
				if ($value === false) {
					$formula = str_replace($key, $key . '($data[$rra_index][$i], $cache[$i][0][$rra_index])', $formula);
				} else {
					$formula = str_replace($key, $value, $formula);
				}
			}

			$debug[]= $formula;

			//Replace formula calls with parameters
			foreach ($cache[$i][2][$rra_index] as $key => $value) {
				$formula = str_replace($key, $key . '( $data[$rra_index][$i], $cache[$i][2][$rra_index]||', $formula);
				$formula = str_replace('||(', ', ', $formula);
			}

			$debug[]= $formula;

			//calculate
			$result = false;

			eval("\$result = $formula;");

			if ($result === false || is_nan($result)) {
				$result = 'NULL';
			}

			$debug[] = $result;

			debug($debug, "Interpretation & Result");

			//If its flagged as "spanned" then update the s_cache, update the main cache
			//and jump to the next measurand
			if (array_key_exists($k, $s_cache)) {
				$s_cache[$k] = $result;

				for ($i=0; $i<$n_rra; $i++) {
					unset($cache[$i][1][$k]);
				}

				continue 2;
			} else {
				//Update r_cache with the result of our measurand
				$name = $k . ':' . $params['rras'][$i];
				$r_cache[$name] = $result;

				//Update main cache with the result of our measurand
				$cache[$i][1][$k] = $result;
			}
		}
	}

	//Clear up and return to main function
	$result = array();
	foreach ($ds_namv as $i => $ds_name) {
		$result[$ds_name] = $cache[$i][1];
	}

	//Add s_cache
	$result['_spanned_'] = $s_cache;

	//Fall back to normal error handler
	restore_error_handler();
	debug($cache, "Main Cache Status: f,m,p");

	return $result;
}

