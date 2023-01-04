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

$calc_functions = array(
	'f_avg'  => array(
		'title'			=> __('f_avg - Arithmetic Average', 'reportit'),
		'description'	=> __('Returns the average value of all measured values per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_avg',
		'examples'		=> 'f_avg*8'
	),
	'f_max'  => array(
		'title'			=> __('f_max - Maximum Value', 'reportit'),
		'description'	=> __('Returns the highest measured value per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_max',
		'examples'		=> '(f_max-f_min)*8'
	),
	'f_min'  => array(
		'title'			=> __('f_min - Minimum Value', 'reportit'),
		'description'	=> __('Returns the lowest measured value per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_min',
		'examples'		=> 'f_min*8'
	),
	'f_sum'  => array(
		'title'			=> __('f_sum - Sum', 'reportit'),
		'description'	=> __('Returns the sum of all measured values per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_sum',
		'examples'		=> 'f_sum*8'
	),
	'f_num'  => array(
		'title'			=> __('f_num - Number of Values (Not NaN)', 'reportit'),
		'description'	=> __('Returns the number of valid measured values per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>int</i> f_num',
		'examples'		=> 'f_num'
	),
	'f_grd'  => array(
		'title'			=> __('f_grd - Gradient', 'reportit'),
		'description'	=> __('Returns the gradient of a straight line by using linear regression per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_grd',
		'examples'		=> 'f_grd'
	),
	'f_last'  => array(
		'title'			=> __('f_last - Last Value', 'reportit'),
		'description'	=> __('Returns the last valid measured value per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_last',
		'examples'		=> 'f_last*16/2'
	),
	'f_1st'  => array(
		'title'			=> __('f_1st - First Value', 'reportit'),
		'description'	=> __('Returns the first valid measured value per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_1st',
		'examples'		=> 'f_1st*2*(5.5-1.5)'
	),
	'f_nan'  => array(
		'title'			=> __('f_nan - Number of NaNs', 'reportit'),
		'description'	=> __('Returns the number of NaNs stored per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>int</i> f_nan',
		'examples'		=> 'f_num+f_nan'
	),
	'f_median'  => array(
		'title'			=> __('f_median - Median', 'reportit'),
		'description'	=> __('Returns that value that separates the higher half from the lower half per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_median',
		'examples'		=> 'f_median'
	),
	'f_mode'  => array(
		'title'			=> __('f_mode - Mode', 'reportit'),
		'description'	=> __('Returns the value that appears most often per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_mode',
		'examples'		=> 'f_mode'
	),
	'f_range'  => array(
		'title'			=> __('f_range - Range', 'reportit'),
		'description'	=> __('Returns the difference between the largest and the smallest value per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_range',
		'examples'		=> 'f_range'
	),
	'f_iqr'  => array(
		'title'			=> __('f_iqr - Interquartile Range', 'reportit'),
		'description'	=> __('Returns the distance of the middle50&#037; around the median per DS', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_iqr',
		'examples'		=> 'f_iqr'
	),
	'f_sd'  => array(
		'title'			=> __('f_sd - Standard Deviation', 'reportit'),
		'description'	=> __('Returns the square root per data source variance', 'reportit'),
		'params'		=> __('none', 'reportit'),
		'syntax'		=> '<i>float</i> f_sd',
		'examples'		=> 'f_sd'
	),

);

$calc_fct_names = array_keys($calc_functions);

$calc_functions_aliases = array(
	'f_int' => array(
		'title'			=> __('f_int - Alias of f_floor', 'reportit'),
		'description'	=> __('Returns the next lowest integer value by rounding down value if necessary.', 'reportit'),
		'params'		=> __('$var: float', 'reportit'),
		'syntax'		=> '<i>integer</i> f_int <i>(float $var)</i>',
		'examples'		=> 'f_int(69.19) = 69'
	),
	'f_rnd' => array(
		'title'			=> __('f_rnd - Alias of f_round', 'reportit'),
		'description'	=> __('Returns the rounded integer value of any given float.', 'reportit'),
		'params'		=> __('$var: float or string value', 'reportit'),
		'syntax'		=> '<i>integer</i> f_rnd <i>(float $var)</i>',
		'examples'		=> 'f_rnd(69.50) = 70, f_rnd(69,49) = 69'
	),
);

$calc_fct_aliases = array_keys($calc_functions_aliases);

$calc_functions_params = array(
	'f_xth' => array(
		'title'			=> __('f_xth - Xth Percentile', 'reportit'),
		'description'	=> __('Returns the xth percentitle.', 'reportit'),
		'params'		=> __('$var: threshold in percent. Range: [0&lt; $var &le;100]', 'reportit'),
		'syntax'		=> '<i>float</i> f_xth <i>(float $var)</i>',
		'examples'		=> 'f_xth(95.7), f_xth(c1v)'
	),
	'f_dot' => array(
		'title'			=> __('f_dot - Duration Over Threshold', 'reportit'),
		'description'	=> __('Returns the duration over a defined threshold in percent.', 'reportit'),
		'params'		=> __('$var: threshold (absolute)', 'reportit'),
		'syntax'		=> '<i>float</i> f_dot <i>(float $var)</i>',
		'examples'		=> 'f_dot(10000000), f_dot(maxValue*c1v/100)'
	),
	'f_sot' => array(
		'title'			=> __('f_sot - Sum Over Threshold', 'reportit'),
		'description'	=> __('Returns the sum of values over a defined threshold.', 'reportit'),
		'params'		=> __('$var: threshold (absolute)', 'reportit'),
		'syntax'		=> '<i>float</i> f_sot <i>(float $var)</i>',
		'examples'		=> 'f_sot(75000000), f_sot(maxValue*c4v/100)'
	),
	'f_floor' => array(
		'title'			=> __('f_floor - Round Fractions Down', 'reportit'),
		'description'	=> __('Returns the next lowest integer value by rounding down value if necessary.', 'reportit'),
		'params'		=> __('$var: float', 'reportit'),
		'syntax'		=> '<i>integer</i> f_floor <i>(float $var)</i>',
		'examples'		=> 'f_floor(69.19) = 69'
	),
	'f_ceil' => array(
		'title'			=> __('f_ceil - Round Fractions Up', 'reportit'),
		'description'	=> __('Returns the next highest integer value by rounding up value if necessary.', 'reportit'),
		'params'		=> __('$var: float', 'reportit'),
		'syntax'		=> '<i>integer</i> f_ceil <i>(float $var)</i>',
		'examples'		=> 'f_ceil(69.19) = 70'
	),
	'f_round' => array(
		'title'			=> __('f_round - Round A Float', 'reportit'),
		'description'	=> __('Returns the rounded integer value of any given float.', 'reportit'),
		'params'		=> __('$var: float or string value', 'reportit'),
		'syntax'		=> '<i>integer</i> f_round <i>(float $var)</i>',
		'examples'		=> 'f_round(69.50) = 70, f_round(69,49) = 69'
	),
	'f_high' => array(
		'title'			=> __('f_high - Find Highest Value', 'reportit'),
		'description'	=> __('Returns the highest value of a given list of parameters', 'reportit'),
		'params'		=> __('$var1, $var2, $var3 ...: values to be compared', 'reportit'),
		'syntax'		=> '<i>float</i> f_high <i>(float $var1, float $var2)</i>',
		'examples'		=> 'f_high(27,70) = 70'
	),
	'f_low' => array(
		'title'			=> __('f_high - Find Lowest Value', 'reportit'),
		'description'	=> __('Returns the lowest value of a given list of parameters', 'reportit'),
		'params'		=> __('$var1, $var2, $var3 ...: values to be compared', 'reportit'),
		'syntax'		=> '<i>float</i> f_low <i>(float $var1, float $var2)</i>',
		'examples'		=> 'f_low(27,70) = 27'
	),
	'f_if' => array(
		'title'			=> __('f_if - Conditional Operation - IF-THEN-ELSE Logic', 'reportit'),
		'description'	=> __('Returns B if A is true or C if A is false', 'reportit'),
		'params'		=> '$A, $B, $C',
		'syntax'		=> '<i>bool</i> f_if <i>(float $A, float $B, float $C)</i>',
		'examples'		=> 'f_if (0,1,2) = 2, f_if(1,1,2) = 1, f_if(f_low(0,1),f_1st, f_last) = f_last'
	),
	'f_isNaN' => array(
		'title'			=> __('f_isNaN - Find whether a value is not a number', 'reportit'),
		'description'	=> __('Returns 1 (or B) if A === NaN or 0 (or C) if not. Parameters B and C are optional.', 'reportit'),
		'params'		=> '$A [, $B, $C]',
		'syntax'		=> '<i>bool</i> f_isNaN <i>(float $A [, float $B, float $C])</i>',
		'examples'		=> 'f_isNaN(5) = 0, f_isNaN(f_min) = 1 or 0, f_isNaN(f_min,5) = 5 or 0, f_isNaN(f_min,5,10) = 5 or 10'
	),
	'f_eq' => array(
		'title'			=> __('f_eq - Alias of f_cmp - IS EQUAL', 'reportit'),
		'description'	=> __('Returns 1 (or C) if A == B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
		'params'		=> '$A, $B [, $C, $D]',
		'syntax'		=> '<i>bool</i> f_eq <i>(float $A, float $B [, float $C, float $D])</i>',
		'examples'		=> 'f_eq(5,6) = 0,  f_eq(6,6) = 1, f_eq(f_min,6) = 1 or 0, f_eq(6,6,f_min) = f_min, f_eq(6,5,f_min,f_max) = f_max'
	),
	'f_uq' => array(
		'title'			=> __('f_uq - Alias of f_cmp - IS UNEQUAL', 'reportit'),
		'description'	=> __('Returns 1 (or C) if A != B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
		'params'		=> '$A, $B [, $C, $D]',
		'syntax'		=> '<i>bool</i> f_uq <i>(float $A, float $B [, float $C, float $D])</i>',
		'examples'		=> 'f_uq(5,6) = 1,  f_uq(6,6) = 0, f_uq(f_min,6) = 1 or 0, f_uq(6,6,f_min) = 0, f_uq(6,5,f_min,f_max) = f_min'
	),
	'f_gt' => array(
		'title'			=> __('f_gt - Alias of f_cmp - IS GREATER THAN', 'reportit'),
		'description'	=> __('Returns 1 (or C) if A > B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
		'params'		=> '$A, $B [, $C, $D]',
		'syntax'		=> '<i>bool</i> f_gt <i>(float $A, float $B [, float $C, float $D])</i>',
		'examples'		=> 'f_gt(5,6) = 0,  f_gt(6,6) = 0, f_gt(f_min,6) = 1 or 0, f_gt(6,6,f_min) = 0, f_gt(6,5,f_min,f_max) = f_min'
	),
	'f_lt' => array(
		'title'			=> __('f_lt - Alias of f_cmp - IS LOWER THAN', 'reportit'),
		'description'	=> __('Returns 1 (or C) if A < B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
		'params'		=> '$A, $B [, $C, $D]',
		'syntax'		=> '<i>bool</i> f_lt <i>(float $A, float $B [, float $C, float $D])</i>',
		'examples'		=> 'f_lt(5,6) = 1,  f_lt(6,6) = 0, f_lt(f_min,6) = 1 or 0, f_lt(6,6,f_min) = 0, f_lt(6,5,f_min,f_max) = f_max()'
	),
	'f_ge' => array(
		'title'			=> __('f_ge - Alias of f_cmp - IS GREATER OR EQUAL', 'reportit'),
		'description'	=> __('Returns 1 (or C) if A >= B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
		'params'		=> '$A, $B [, $C, $D]',
		'syntax'		=> '<i>bool</i> f_ge <i>(float $A, float $B [, float $C, float $D])</i>',
		'examples'		=> 'f_ge(5,6) = 0,  f_ge(6,6) = 1, f_ge(f_min,6) = 1 or 0, f_ge(6,6,f_min) = f_min, f_ge(6,5,f_min,f_max) = f_min'
	),
	'f_le' => array(
		'title'			=> __('f_le - Alias of f_cmp - IS LOWER OR EQUAL', 'reportit'),
		'description'	=> __('Returns 1 (or C) if A <= B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
		'params'		=> '$A, $B [, $C, $D]',
		'syntax'		=> '<i>bool</i> f_le <i>(float $A, float $B [, float $C, float $D])</i>',
		'examples'		=> 'f_le(5,6) = 1,  f_le(6,6) = 1, f_le(f_min,6) = 1 or 0, f_le(6,6,f_min) = f_min, f_le(6,5,f_min,f_max) = f_max'
	),
);

$calc_fct_names_params	= array_keys($calc_functions_params);

$calc_operators = array(
	'+' => array(
		'title' 		=> __('Addition', 'reportit'),
		'description'	=> __('Mathematical Operation to return the sum of two or more summands', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '$A + $B',
		'examples'		=> '10 + 2 = 12'
	),
	'-' => array(
		'title' 		=> __('Subtraction', 'reportit'),
		'description'	=> __('Mathematical Operation to return the difference of minuend and subtrahend', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '$A - $B',
		'examples'		=> '10 - 2 = 8'
	),
	'*' => array(
		'title' 		=> __('Multiplication', 'reportit'),
		'description'	=> __('Mathematical Operation to return the product of multiplier and multiplicant', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '$A * $B',
		'examples'		=> '10 * 2 = 20'
	),
	'/' => array(
		'title' 		=> __('Division', 'reportit'),
		'description'	=> __('Mathematical Operation to return the fraction of divident and divisor', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '$A / $B',
		'examples'		=> '10 / 2 = 5'
	),
	'%' => array(
		'title' 		=> __('Modulus', 'reportit'),
		'description'	=> __('Mathematical Operation to return the remainder of a division of two integers', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '$A % $B',
		'examples'		=> '10 % 2 = 0'
	),
	'**' => array(
		'title' 		=> __('Exponentiation - PHP5.6 or above required', 'reportit'),
		'description'	=> __('Mathematical Operation to return the repeated multiplication (or division) of a base by its exponent.', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '$A ** $B',
		'examples'		=> '10 ** 2 = 10 * 10 = 100'
	)
);

$calc_parentheses = array(
	'(' => array(
		'title' 		=> __('Left (Opening) parenthesis', 'reportit'),
		'description'	=> __('Used to override normal precedence or to mark the first level of nesting', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '-',
		'examples'		=> '(10 + 2) * (10 + 2) = 144'
	),
	')' => array(
		'title' 		=> __('Right (Closing) parenthesis', 'reportit'),
		'description'	=> __('Used to override normal precedence or to mark the first level of nesting', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '-',
		'examples'		=> '(10 + 2) * (10 + 2) = 144'
	),
	'[' => array(
		'title' 		=> __('Left (Opening) Square Bracket', 'reportit'),
		'description'	=> __('Used to override normal precedence or to mark the second level of nesting', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '-',
		'examples'		=> '[(10 - 2) * (10 - 2)] - 4 = 60'
	),
	']' => array(
		'title' 		=> __('Right (Closing) Square Bracket', 'reportit'),
		'description'	=> __('Used to override normal precedence or to mark the second level of nesting', 'reportit'),
		'params'		=> 'none',
		'syntax'		=> '-',
		'examples'		=> '[(10 - 2) * (10 - 2)] - 4 = 60'
	),
);

$calc_variables = array(
	'maxValue'    => __('Contains the maximum bandwidth if \'ifspeed\' is available.', 'reportit'),
	'maxRRDValue' => __('Contains the maximum value that has been defined for the specific data source item under \"Data Sources\".', 'reportit'),
	'step'        => __('Contains the number of seconds between two measured values.', 'reportit'),
	'nan'         => __('Contains the number of NAN\'s during the reporting period.', 'reportit')
);

$calc_var_names = array_keys($calc_variables);


$rubrics = array(
	__('Functions w/o parameters')	=> $calc_functions,
	__('Functions with parameters') => $calc_functions_params,
	__('Aliases')					=> $calc_functions_aliases,
	__('Operators')                 => $calc_operators,
	__('Parentheses')               => $calc_parentheses,
	__('Variables')                 => $calc_variables,
	__('Data Query Variables')      => '',
	__('Interim Results')           => ''
);

$rounding = array(
	__('off', 'reportit'),
	__('Binary SI-Prefixes (Base 1024)', 'reportit'),
	__('Decimal SI-Prefixes (Base 1000)', 'reportit')
);

$type_specifier = array(
	__('Binary', 'reportit'),
	__('Floating point', 'reportit'),
	__('Integer', 'reportit'),
	__('Integer (unsigned)', 'reportit'),
	__('Hexadecimal (lower-case)', 'reportit'),
	__('Hexadecimal (upper-case)', 'reportit'),
	__('Octal', 'reportit'),
	__('Scientific Notation', 'reportit')
);

$precision = array(
	0  => __('None', 'reportit'),
	1  => 1,
	2  => 2,
	3  => 3,
	4  => 4,
	5  => 5,
	6  => 6,
	7  => 7,
	8  => 8,
	9  => 9,
	-1 => __('Unchanged', 'reportit')
);

