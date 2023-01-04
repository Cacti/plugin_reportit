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

$report_template_actions = array(
	'templates' => array(
		1 => __('Delete', 'reportit'),
		2 => __('Duplicate', 'reportit'),
		3 => __('Lock', 'reportit'),
		4 => __('Unlock', 'reportit'),
		5 => __('Export', 'reportit'),
	),
	'data_templates' => array(
		1 => __('Delete', 'reportit'),
	),
	'groups' => array(
		1 => __('Delete', 'reportit')
	),
	'variables' => array(
		1 => __('Delete', 'reportit'),
		2 => __('Duplicate', 'reportit'),
	),
	'measurands' => array(
		1 => __('Delete', 'reportit'),
		2 => __('Duplicate', 'reportit'),
	)
);

$report_template_display_text = array(
	'variables' => array(
		__('Name', 'reportit'),
		__('Internal Name', 'reportit'),
		__('Maximum', 'reportit'),
		__('Minimum', 'reportit'),
		__('Default', 'reportit'),
		__('Stepping', 'reportit'),
		__('Input Type', 'reportit'),
		__('Options', 'reportit'),
	),
	'measurands' => array(
		__('ID', 'reportit'),
		__('Abbreviation', 'reportit'),
		__('Group', 'reportit'),
		__('Unit', 'reportit'),
		__('Consolidation Function', 'reportit'),
		__('Visible', 'reportit'),
		__('Separate', 'reportit'),
		__('Calculation Formula', 'reportit')
	),
	'groups' => array(
		__('ID', 'reportit'),
		__('Name', 'reportit'),
		__('Generic ID', 'reportit'),
		__('Elements', 'reportit'),
		__('Measurands', 'reportit'),
		__('Associated Data Templates', 'reportit'),
	)
);

$report_template_tabs = array(
	'general'        => __esc('General', 'reportit'),
	'data_templates' => __esc('Data Templates', 'reportit'),
	'groups'         => __esc('Groups', 'reportit'),
	'measurands'     => __esc('Measurands', 'reportit'),
	'variables'      => __esc('Variables', 'reportit'),
);


#TODO :remove $report_time_frames
$report_time_frames = array(
	__('Today'),
	__('Last 1 Day'),
	__('Last 2 Days'),
	__('Last 3 Days'),
	__('Last 4 Days'),
	__('Last 5 Days'),
	__('Last 6 Days'),
	__('Last 7 Days'),
	__('Last Week (Sun - Sat)'),
	__('Last Week (Mon - Sun)'),
	__('Last 14 Days'),
	__('Last 21 Days'),
	__('Last 28 Days'),
	__('Current Month'),
	__('Last Month'),
	__('Last 2 Months'),
	__('Last 3 Months'),
	__('Last 4 Months'),
	__('Last 5 Months'),
	__('Last 6 Months'),
	__('Current Year'),
	__('Last Year'),
	__('Last 2 Years')
);


$report_schedule_frequency = array(
	1 => __('daily', 'reportit'),
	2 => __('weekly', 'reportit'),
	3 => __('monthly', 'reportit'),
	4 => __('yearly', 'reportit'),
);


$variable_input_types = array(
	1 => __('Dropdown', 'reportit'),
	2 => __('Input field', 'reportit')
);

/* - Begin measurands - */
$measurand_type_specifier = array(      //TODO update script: increase every value by one!
	0 => __('None', 'reportit'),
	1 => __('Binary', 'reportit'),
	2 => __('Floating point', 'reportit'),
	3 => __('Integer', 'reportit'),
	4 => __('Integer (unsigned)', 'reportit'),
	5 => __('Hexadecimal (lower-case)', 'reportit'),
	6 => __('Hexadecimal (upper-case)', 'reportit'),
	7 => __('Octal', 'reportit'),
	8 => __('Scientific Notation', 'reportit')
);

$measurand_precision = array(
	0 	=> __('None', 'reportit'),
	1 	=> 1,
	2 	=> 2,
	3 	=> 3,
	4 	=> 4,
	5 	=> 5,
	6 	=> 6,
	7 	=> 7,
	8 	=> 8,
	9 	=> 9,
	-1 	=> __('Unchanged', 'reportit')
);

$measurand_rounding = array(
	0 => __('off', 'reportit'),
	1 => __('Binary SI-Prefixes (Base 1024)', 'reportit'),
	2 => __('Decimal SI-Prefixes (Base 1000)', 'reportit')
);

$measurand_ops_and_opds = array(
	0 => array(
		'f_avg' => array(
			'title'			=> __('f_avg - Arithmetic Average', 'reportit'),
			'description'	=> __('Returns the average value of all measured values per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_avg',
			'examples'		=> 'f_avg*8'
		),
		'f_max' => array(
			'title'			=> __('f_max - Maximum Value', 'reportit'),
			'description'	=> __('Returns the highest measured value per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_max',
			'examples'		=> '(f_max-f_min)*8'
		),
		'f_min' => array(
			'title'			=> __('f_min - Minimum Value', 'reportit'),
			'description'	=> __('Returns the lowest measured value per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_min',
			'examples'		=> 'f_min*8'
		),
		'f_sum' => array(
			'title'			=> __('f_sum - Sum', 'reportit'),
			'description'	=> __('Returns the sum of all measured values per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_sum',
			'examples'		=> 'f_sum*8'
		),
		'f_num' => array(
			'title'			=> __('f_num - Number of Values (Not NaN)', 'reportit'),
			'description'	=> __('Returns the number of valid measured values per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>int</i> f_num',
			'examples'		=> 'f_num'
		),
		'f_grd' => array(
			'title'			=> __('f_grd - Gradient', 'reportit'),
			'description'	=> __('Returns the gradient of a straight line by using linear regression per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_grd',
			'examples'		=> 'f_grd'
		),
		'f_last' => array(
			'title'			=> __('f_last - Last Value', 'reportit'),
			'description'	=> __('Returns the last valid measured value per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_last',
			'examples'		=> 'f_last*16/2'
		),
		'f_1st' => array(
			'title'			=> __('f_1st - First Value', 'reportit'),
			'description'	=> __('Returns the first valid measured value per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_1st',
			'examples'		=> 'f_1st*2*(5.5-1.5)'
		),
		'f_nan' => array(
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
		'f_range' => array(
			'title'			=> __('f_range - Range', 'reportit'),
			'description'	=> __('Returns the difference between the largest and the smallest value per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_range',
			'examples'		=> 'f_range'
		),
		'f_iqr' => array(
			'title'			=> __('f_iqr - Interquartile Range', 'reportit'),
			'description'	=> __('Returns the distance of the middle50&#037; around the median per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_iqr',
			'examples'		=> 'f_iqr'
		),
		'f_sd' => array(
			'title'			=> __('f_sd - Standard Deviation', 'reportit'),
			'description'	=> __('Returns the square root per data source variance', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_sd',
			'examples'		=> 'f_sd'
		),
		'f_var' => array(
			'title'			=> __('f_var - Variance', 'reportit'),
			'description'	=> __('Returns the variance per DS', 'reportit'),
			'params'		=> __('none', 'reportit'),
			'syntax'		=> '<i>float</i> f_mode',
			'examples'		=> 'f_var'
		),
	),
	1 => array(
		'f_xth' => array(
			'title'			=> __('f_xth - Xth Percentile', 'reportit'),
			'description'	=> __('Returns the xth percentitle.', 'reportit'),
			'params'		=> __('$var: threshold in percent. Range: [0&lt; $var &le;100]', 'reportit'),
			'syntax'		=> '<i>float</i> f_xth <i>(float $var)</i>',
			'examples'		=> 'f_xth(95.7), f_xth(c1v)',
			'parentheses'	=> true
		),
		'f_dot' => array(
			'title'			=> __('f_dot - Duration Over Threshold', 'reportit'),
			'description'	=> __('Returns the duration over a defined threshold in percent.', 'reportit'),
			'params'		=> __('$var: threshold (absolute)', 'reportit'),
			'syntax'		=> '<i>float</i> f_dot <i>(float $var)</i>',
			'examples'		=> 'f_dot(10000000), f_dot(maxValue*c1v/100)',
			'parentheses'	=> true
		),
		'f_sot' => array(
			'title'			=> __('f_sot - Sum Over Threshold', 'reportit'),
			'description'	=> __('Returns the sum of values over a defined threshold.', 'reportit'),
			'params'		=> __('$var: threshold (absolute)', 'reportit'),
			'syntax'		=> '<i>float</i> f_sot <i>(float $var)</i>',
			'examples'		=> 'f_sot(75000000), f_sot(maxValue*c4v/100)',
			'parentheses'	=> true
		),
		'f_floor' => array(
			'title'			=> __('f_floor - Round Fractions Down', 'reportit'),
			'description'	=> __('Returns the next lowest integer value by rounding down value if necessary.', 'reportit'),
			'params'		=> __('$var: float', 'reportit'),
			'syntax'		=> '<i>integer</i> f_floor <i>(float $var)</i>',
			'examples'		=> 'f_floor(69.19) = 69',
			'parentheses'	=> true
		),
		'f_ceil' => array(
			'title'			=> __('f_ceil - Round Fractions Up', 'reportit'),
			'description'	=> __('Returns the next highest integer value by rounding up value if necessary.', 'reportit'),
			'params'		=> __('$var: float', 'reportit'),
			'syntax'		=> '<i>integer</i> f_ceil <i>(float $var)</i>',
			'examples'		=> 'f_ceil(69.19) = 70',
			'parentheses'	=> true
		),
		'f_round' => array(
			'title'			=> __('f_round - Round A Float', 'reportit'),
			'description'	=> __('Returns the rounded integer value of any given float.', 'reportit'),
			'params'		=> __('$var: float or string value', 'reportit'),
			'syntax'		=> '<i>integer</i> f_round <i>(float $var)</i>',
			'examples'		=> 'f_round(69.50) = 70, f_round(69,49) = 69',
			'parentheses'	=> true
		),
		'f_high' => array(
			'title'			=> __('f_high - Find Highest Value', 'reportit'),
			'description'	=> __('Returns the highest value of a given list of parameters', 'reportit'),
			'params'		=> __('$var1, $var2, $var3 ...: values to be compared', 'reportit'),
			'syntax'		=> '<i>float</i> f_high <i>(float $var1, float $var2)</i>',
			'examples'		=> 'f_high(27,70) = 70',
			'parentheses'	=> true
		),
		'f_low' => array(
			'title'			=> __('f_high - Find Lowest Value', 'reportit'),
			'description'	=> __('Returns the lowest value of a given list of parameters', 'reportit'),
			'params'		=> __('$var1, $var2, $var3 ...: values to be compared', 'reportit'),
			'syntax'		=> '<i>float</i> f_low <i>(float $var1, float $var2)</i>',
			'examples'		=> 'f_low(27,70) = 27',
			'parentheses'	=> true
		),
		'f_if' => array(
			'title'			=> __('f_if - Conditional Operation - IF-THEN-ELSE Logic', 'reportit'),
			'description'	=> __('Returns B if A is true or C if A is false', 'reportit'),
			'params'		=> '$A, $B, $C',
			'syntax'		=> '<i>bool</i> f_if <i>(float $A, float $B, float $C)</i>',
			'examples'		=> 'f_if (0,1,2) = 2, f_if(1,1,2) = 1, f_if(f_low(0,1),f_1st, f_last) = f_last',
			'parentheses'	=> true
		),
		'f_isNaN' => array(
			'title'			=> __('f_isNaN - Find whether a value is not a number', 'reportit'),
			'description'	=> __('Returns 1 (or B) if A === NaN or 0 (or C) if not. Parameters B and C are optional.', 'reportit'),
			'params'		=> '$A [, $B, $C]',
			'syntax'		=> '<i>bool</i> f_isNaN <i>(float $A [, float $B, float $C])</i>',
			'examples'		=> 'f_isNaN(5) = 0, f_isNaN(f_min) = 1 or 0, f_isNaN(f_min,5) = 5 or 0, f_isNaN(f_min,5,10) = 5 or 10',
			'parentheses'	=> true
		),
		'f_cmp' => array(
			'title'			=> __('f_cmp - Complex Comparison', 'reportit'),
			'description'	=> __('Returns 1 (or B) if A === NaN or 0 (or C) if not. Parameters B and C are optional.', 'reportit'),
			'params'		=> '$A [, $B, $C]',
			'syntax'		=> '<i>bool</i> f_nan <i>(float $A [, float $B, float $C])</i>',
			'examples'		=> 'f_nan(5) = 0, f_nan(f_min()) = 1 or 0, f_nan(f_min(),5) = 5 or 0, f_nan(f_min(),5,10) = 5 or 10',
			'parentheses'	=> true
		)
	),
	2 => array(
		'f_int' => array(
			'title'			=> __('f_int - Alias of f_floor', 'reportit'),
			'description'	=> __('Returns the next lowest integer value by rounding down value if necessary.', 'reportit'),
			'params'		=> __('$var: float', 'reportit'),
			'syntax'		=> '<i>integer</i> f_int <i>(float $var)</i>',
			'examples'		=> 'f_int(69.19) = 69',
			'parentheses'	=> true
		),
		'f_rnd' => array(
			'title'			=> __('f_rnd - Alias of f_round', 'reportit'),
			'description'	=> __('Returns the rounded integer value of any given float.', 'reportit'),
			'params'		=> __('$var: float or string value', 'reportit'),
			'syntax'		=> '<i>integer</i> f_rnd <i>(float $var)</i>',
			'examples'		=> 'f_rnd(69.50) = 70, f_rnd(69,49) = 69',
			'parentheses'	=> true
		),
		'f_eq' => array(
			'title'			=> __('f_eq - Alias of f_cmp - IS EQUAL', 'reportit'),
			'description'	=> __('Returns 1 (or C) if A == B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
			'params'		=> '$A, $B [, $C, $D]',
			'syntax'		=> '<i>bool</i> f_eq <i>(float $A, float $B [, float $C, float $D])</i>',
			'examples'		=> 'f_eq(5,6) = 0,  f_eq(6,6) = 1, f_eq(f_min,6) = 1 or 0, f_eq(6,6,f_min) = f_min, f_eq(6,5,f_min,f_max) = f_max',
			'parentheses'	=> true
		),
		'f_uq' => array(
			'title'			=> __('f_uq - Alias of f_cmp - IS UNEQUAL', 'reportit'),
			'description'	=> __('Returns 1 (or C) if A != B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
			'params'		=> '$A, $B [, $C, $D]',
			'syntax'		=> '<i>bool</i> f_uq <i>(float $A, float $B [, float $C, float $D])</i>',
			'examples'		=> 'f_uq(5,6) = 1,  f_uq(6,6) = 0, f_uq(f_min,6) = 1 or 0, f_uq(6,6,f_min) = 0, f_uq(6,5,f_min,f_max) = f_min',
			'parentheses'	=> true
		),
		'f_gt' => array(
			'title'			=> __('f_gt - Alias of f_cmp - IS GREATER THAN', 'reportit'),
			'description'	=> __('Returns 1 (or C) if A > B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
			'params'		=> '$A, $B [, $C, $D]',
			'syntax'		=> '<i>bool</i> f_gt <i>(float $A, float $B [, float $C, float $D])</i>',
			'examples'		=> 'f_gt(5,6) = 0,  f_gt(6,6) = 0, f_gt(f_min,6) = 1 or 0, f_gt(6,6,f_min) = 0, f_gt(6,5,f_min,f_max) = f_min',
			'parentheses'	=> true
		),
		'f_lt' => array(
			'title'			=> __('f_lt - Alias of f_cmp - IS LOWER THAN', 'reportit'),
			'description'	=> __('Returns 1 (or C) if A < B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
			'params'		=> '$A, $B [, $C, $D]',
			'syntax'		=> '<i>bool</i> f_lt <i>(float $A, float $B [, float $C, float $D])</i>',
			'examples'		=> 'f_lt(5,6) = 1,  f_lt(6,6) = 0, f_lt(f_min,6) = 1 or 0, f_lt(6,6,f_min) = 0, f_lt(6,5,f_min,f_max) = f_max()',
			'parentheses'	=> true
		),
		'f_ge' => array(
			'title'			=> __('f_ge - Alias of f_cmp - IS GREATER OR EQUAL', 'reportit'),
			'description'	=> __('Returns 1 (or C) if A >= B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
			'params'		=> '$A, $B [, $C, $D]',
			'syntax'		=> '<i>bool</i> f_ge <i>(float $A, float $B [, float $C, float $D])</i>',
			'examples'		=> 'f_ge(5,6) = 0,  f_ge(6,6) = 1, f_ge(f_min,6) = 1 or 0, f_ge(6,6,f_min) = f_min, f_ge(6,5,f_min,f_max) = f_min',
			'parentheses'	=> true
		),
		'f_le' => array(
			'title'			=> __('f_le - Alias of f_cmp - IS LOWER OR EQUAL', 'reportit'),
			'description'	=> __('Returns 1 (or C) if A <= B or 0 (or D) if not. Parameters C and D are optional.', 'reportit'),
			'params'		=> '$A, $B [, $C, $D]',
			'syntax'		=> '<i>bool</i> f_le <i>(float $A, float $B [, float $C, float $D])</i>',
			'examples'		=> 'f_le(5,6) = 1,  f_le(6,6) = 1, f_le(f_min,6) = 1 or 0, f_le(6,6,f_min) = f_min, f_le(6,5,f_min,f_max) = f_max',
			'parentheses'	=> true
		)
	),
	3 => array(
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
	),
	4 => array( '1' => '', '2' => '', '3' => '', '4' => '', '5' => '', '6' => '', '7' => '', '8' => '', '9' => '', '0' => '',
		'.' => array(
			'title' 		=> __('Decimal Mark', 'reportit'),
			'description'	=> __('Decimal Mark used to separate the integer part from the fractional part', 'reportit'),
			'params'		=> 'none',
			'syntax'		=> '-',
			'examples'		=> '10.2+14.8',
		),
	),
	5 => array(
		'( )' => array(
			'title' 		=> __('Round Bracket', 'reportit'),
			'description'	=> __('Used to override normal precedence or to mark the first level of nesting', 'reportit'),
			'params'		=> 'none',
			'syntax'		=> '-',
			'examples'		=> '(10 + 2) * (10 + 2) = 144',
			'parentheses'	=> true
		),
		'[ ]' => array(
			'title' 		=> __('Square Bracket', 'reportit'),
			'description'	=> __('Used to override normal precedence or to mark the second level of nesting', 'reportit'),
			'params'		=> 'none',
			'syntax'		=> '-',
			'examples'		=> '[(10 - 2) * (10 - 2)] - 4 = 60',
			'parentheses'	=> true
		),
		',' => array(
			'title' 		=> __('Punctuation mark', 'reportit'),
			'description'	=> __('Used to separate arguments forwarded to a function', 'reportit'),
			'params'		=> 'none',
			'syntax'		=> '-',
			'examples'		=> 'f_fhigh(fmin,fmax, ... )',
		),
	),
	6 => array(
		'minRRDValue' => array(
			'title'			=> 'minRRDValue',
			'description'	=> __('The minimum value of data that is allowed to be collected.', 'reportit') . "<br>\t"
							 . __('WARNING! This variable can be set to zero or unlimited', 'reportit'),
			'params'		=> 'none',
			'syntax'		=> '-',
			'examples'		=> '-',
		),
		'maxRRDValue' => array(
			'title'			=> 'maxRRDValue',
			'description'	=> __('The maximum value of data that is allowed to be collected.', 'reportit') . "<br>\t"
							 . __('WARNING! This variable can be set to zero or unlimited', 'reportit'),
			'params'		=> 'none',
			'syntax'		=> '-',
			'examples'		=> '-',
		),
		'step'        => array(
			'title'			=> 'step',
			'description'	=> __('Contains the number of seconds between two measured values.', 'reportit'),
			'params'		=> 'none',
			'syntax'		=> '-',
			'examples'		=> '-',
		)
	)
);

$measurands_rubrics = array(
	0 => __('Functions w/o parameters', 'reportit'),
	1 => __('Functions with parameters', 'reportit'),
	2 => __('Aliases', 'reportit'),
	3 => __('Operators', 'reportit'),
	4 => __('Numbers', 'reportit'),
	5 => __('Others', 'reportit'),
	6 => __('Variables', 'reportit'),
	7 => __('Data Query Variables', 'reportit'),
	8 => __('Interim Results', 'reportit')
);

$settings_max_record_per_report = array(
	'0'     => __('Unlimited', 'reportit'),
	'500'   => __('500 Data Items', 'reportit'),
	'1000'  => __('1,000 Data Items', 'reportit'),
	'2000'  => __('2,000 Data Items', 'reportit'),
	'5000'  => __('5,000 Data Source Items', 'reportit'),
	'7500'  => __('7,500 Data Source Items', 'reportit'),
	'10000' => __('10,000 Data Source Items', 'reportit'),
	'15000' => __('15,000 Data Source Items', 'reportit'),
	'25000' => __('25,000 Data Source Items', 'reportit'),
	'30000' => __('25,000 Data Source Items', 'reportit')
);

$settings_max_cache_life_time = array(
	60   => __('1 Minute', 'reportit'),
	120  => __('%d Minutes', 2, 'reportit'),
	300  => __('%d Minutes', 5, 'reportit'),
	600  => __('%d Minutes', 10, 'reportit'),
	1200 => __('%d Minutes', 20, 'reportit'),
	1800 => __('%d Minutes', 30, 'reportit'),
);

