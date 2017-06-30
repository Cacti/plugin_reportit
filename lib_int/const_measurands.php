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


//----- CONSTANTS FOR: cc_measurands.php ---

$calc_functions = array('f_avg' 	=> 'Returns the average value.&#10;(sum of values /number of values)',
						'f_max'		=> 'Returns the highest value',
						'f_min'		=> 'Returns the lowest value',
						'f_sum'		=> 'Returns the sum of all values.&#10;(1.Value + 2.Value + 3.Value + ... + n.Value)',
						'f_num'		=> 'Returns the number of values',
						'f_grd'		=> 'Returns the gradient of a straight line by using linear regression',
						'f_last'	=> 'Returns the last measured value',
						'f_1st'		=> 'Returns the first measured value');
$calc_fct_names = array_keys($calc_functions);

$calc_functions_params	= array('f_xth'	=> 'Returns the xth percentitle.&#10;'.
											'Description: float f_xth(float $variable)&#10;'.
											'Variable has to be between 0 and 100&#10;'.
											'Example: f_xth(95)',
								'f_dot'	=> 'Returns the duration over a defined threshold in percent.&#10;'.
											'Description: float f_dot(float $variable)&#10;'.
											'Example: f_dot(10000000)',
								'f_sot'	=> 'Returns the sum of values over a defined threshold.&#10'.
											'Description: float f_sot(float $variable)&#10;'.
											'Example: f_sot(750000)',
								'f_int' => 'Returns float as integer',
								'f_rnd' => 'Returns float as rounded integer value',
								'f_high'=> 'Returns the highest value of a given list of parameters',
								'f_low'	=> 'Returns the lowest value of a given list of parameters');

$calc_fct_names_params	= array_keys($calc_functions_params);

$calc_operators = array('+'	=> 'Addition',
						'-'	=> 'Subtraction',
						'*'	=> 'Multiplication',
						'/'	=> 'Division');

$calc_variables = array('maxValue' 	=> 'Contains the maximum bandwidth if \'ifspeed\' is available.',
						'maxRRDValue' => 'Contains the maximum value that has been defined for the specific data source item under \"Data Sources\".',
						'step'		=> 'Contains the number of seconds between two measured values.',
						'nan'       => 'Contains the number of NAN\'s during the reporting period.');
$calc_var_names = array_keys($calc_variables);


$rubrics = array('Functions'				=> $calc_functions,
				'Functions with parameters' => $calc_functions_params,
				'Operators'					=> $calc_operators,
				'Variables'					=> $calc_variables,
				'Data Query Variables'		=> '',
				'Interim Results'			=> '');

$rounding = array('off', 'Binary SI-Prefixes (Base 1024)', 'Decimal SI-Prefixes (Base 1000)');

$type_specifier = array('Binary',
						'Floating point',
						'Integer',
						'Integer (unsigned)',
						'Hexadecimal (lower-case)',
						'Hexadecimal (upper-case)',
						'Octal',
						'Scientific Notation'
						);

$precision = array(0 => 'none', 1 => 1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9, -1 => 'unchanged');

//-----------------------------------------
?>
