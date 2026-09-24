<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
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

// ----- Functions without external parameters -----

// Count the number of available measuring points
/**
 * Calculation function: counts the number of values in a data series,
 * caching the result. Called via the calculation engine for the 'num'
 * formula function.
 *
 * @param array $array   Reference, the data series to count.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return int The count of values.
 */
function f_num(&$array, &$f_cache) {
	$f_cache['f_count'] = count($array);

	return $f_cache['f_count'];
}

/**
 * Calculation function: sums a data series, caching the result. Called
 * via the calculation engine for the 'sum' formula function.
 *
 * @param array $array   Reference, the data series to sum.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The sum of values, or REPORTIT_NAN if $array is empty.
 */
// Sum
function f_sum(&$array, &$f_cache) {
	$f_cache['f_sum'] = empty($array) ? REPORTIT_NAN : array_sum($array);

	return $f_cache['f_sum'];
}

/**
 * Calculation function: computes the arithmetic mean of a data series,
 * caching the result. Called via the calculation engine for the 'avg'
 * formula function.
 *
 * @param array $array   Reference, the data series to average.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The average value, or REPORTIT_NAN if $array is empty.
 */
// Average
function f_avg(&$array, &$f_cache) {
	$f_cache['f_avg'] = empty($array) ? REPORTIT_NAN : array_sum($array) / count($array);

	return $f_cache['f_avg'];
}

/**
 * Calculation function: finds the maximum value in a data series,
 * caching the result. Called via the calculation engine for the 'max'
 * formula function.
 *
 * @param array $array   Reference, the data series to search.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The maximum value, or REPORTIT_NAN if $array is empty.
 */
// Maximum
function f_max(&$array, &$f_cache) {
	$f_cache['f_max'] = empty($array) ? REPORTIT_NAN : max($array);

	return $f_cache['f_max'];
}

/**
 * Calculation function: finds the minimum value in a data series,
 * caching the result. Called via the calculation engine for the 'min'
 * formula function.
 *
 * @param array $array   Reference, the data series to search.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The minimum value, or REPORTIT_NAN if $array is empty.
 */
// Minimum
function f_min(&$array, &$f_cache) {
	$f_cache['f_min'] = empty($array) ? REPORTIT_NAN : min($array);

	return $f_cache['f_min'];
}

/**
 * Calculation function: returns the first value in a data series,
 * caching the result. Called via the calculation engine for the '1st'
 * formula function.
 *
 * @param array $array   Reference, the data series to read from.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The first value, or REPORTIT_NAN if $array is empty.
 */
// First measured value
function f_1st(&$array, &$f_cache) {
	$f_cache['f_1st'] = empty($array) ? REPORTIT_NAN : reset($array);

	return $f_cache['f_1st'];
}

/**
 * Calculation function: returns the last value in a data series,
 * caching the result. Called via the calculation engine for the 'last'
 * formula function.
 *
 * @param array $array   Reference, the data series to read from.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The last value, or REPORTIT_NAN if $array is empty.
 */
// Last measured value
function f_last(&$array, &$f_cache) {
	$f_cache['f_last'] = empty($array) ? REPORTIT_NAN : end($array);

	return $f_cache['f_last'];
}

/**
 * Calculation function: computes the linear-regression gradient (slope)
 * of a data series against its sample indexes, caching the result.
 * Called via the calculation engine for the 'grd' formula function.
 *
 * @param array $array   Reference, the data series to analyze.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The computed gradient/slope, or REPORTIT_NAN if
 *               $array is empty.
 */
// Gradient
function f_grd(&$array, &$f_cache) {
	if (empty($array)) {
		$f_cache['f_grd'] = REPORTIT_NAN;

		return $f_cache['f_grd'];
	}

	$cnt = count($array);

	$y_array = array_values($array);
	$x_array = array_keys($array);

	$y_i = array_sum($y_array) / $cnt;
	$x_i = array_sum($x_array) / $cnt;

	$num   = 0;
	$denum = 0;

	for ($i = 0; $i < $cnt; $i++) {
		$num   += ($x_array[$i] - $x_i) * ($y_array[$i] - $y_i);
		$denum += pow(($x_array[$i] - $x_i),2);
	}

	$f_cache['f_grd'] = $num / $denum;

	return $f_cache['f_grd'];
}

/**
 * Calculation function: computes the median of a data series, caching
 * the result. Called via the calculation engine for the 'median'
 * formula function.
 *
 * @param array $array   Reference, the data series to analyze.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The median value, or REPORTIT_NAN if $array is empty.
 */
// returns the median
function f_median(&$array, &$f_cache) {
	if ($f_cache['f_median'] === false) {
		if (empty($array)) {
			$f_cache['f_median'] = REPORTIT_NAN;
		} else {
			$cnt    = f_num($array, $f_cache);
			$values = $array;
			sort($values);

			if ($cnt % 2 == 1) {
				$index               = (($cnt + 1) / 2) - 1;
				$f_cache['f_median'] = $values[$index];
			} else {
				$index               = (($cnt) / 2) - 1;
				$f_cache['f_median'] = 0.5 * ($values[$index] + $values[$index + 1]);
			}
		}
	}

	return $f_cache['f_median'];
}

/**
 * Calculation function: computes the range (max minus min) of a data
 * series, caching the result. Called via the calculation engine for
 * the 'range' formula function.
 *
 * @param array $array   Reference, the data series to analyze.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The range value, or REPORTIT_NAN if $array is empty.
 */
// returns the distance between the highest and lowest measured value
function f_range(&$array, &$f_cache) {
	if ($f_cache['f_range'] === false) {
		$f_cache['f_range'] = empty($array) ? REPORTIT_NAN : f_max($array, $f_cache) - f_min($array, $f_cache);
	}

	return $f_cache['f_range'];
}

/**
 * Calculation function: computes the interquartile range (Q3-Q1) of a
 * data series, caching the result. Called via the calculation engine
 * for the 'iqr' formula function.
 *
 * @param array $array   Reference, the data series to analyze.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The interquartile range, or REPORTIT_NAN if $array is
 *               empty.
 */
// returns the interquartile range
function f_iqr(&$array, &$f_cache) {
	if ($f_cache['f_iqr'] === false) {
		if (empty($array)) {
			$f_cache['f_iqr'] = REPORTIT_NAN;
		} else {
			$cnt              = f_num($array, $f_cache);
			$first_quartile   = ((0.25 * $cnt) % 1 == 0) ? 0.5 * (0.25 * $cnt + (0.25 * $cnt + 1)) : intval(0.25 * $cnt + 1);
			$fourth_quartile  = ((0.25 * $cnt) % 1 == 0) ? 0.5 * (0.75 * $cnt + (0.75 * $cnt + 1)) : intval(0.75 * $cnt + 1);
			$f_cache['f_iqr'] = $array[$fourth_quartile - 1] - $array[$first_quartile - 1];
		}
	}

	return $f_cache['f_iqr'];
}

/**
 * Calculation function: computes the variance of a data series, caching
 * the result. Called via the calculation engine for the 'var' formula
 * function (and internally by f_sd()).
 *
 * @param array $array   Reference, the data series to analyze.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The computed variance, or REPORTIT_NAN if $array is
 *               empty.
 */
// returns the variance
function f_var(&$array, &$f_cache) {
	if ($f_cache['f_var'] === false) {
		if (empty($array)) {
			$f_cache['f_var'] = REPORTIT_NAN;
		} else {
			$avg       = f_avg($array, $f_cache);
			$num       = f_num($array, $f_cache);
			$numerator = 0;

			foreach ($array as $value) {
				$numerator += sqrt($value - $avg);
			}
			$f_cache['f_sd'] = $numerator / $num;
		}
	}

	return $f_cache['f_var'];
}

/**
 * Calculation function: computes the standard deviation of a data
 * series (via f_var()), caching the result. Called via the calculation
 * engine for the 'sd' formula function.
 *
 * @param array $array   Reference, the data series to analyze.
 * @param array $f_cache Reference, the per-formula result cache.
 *
 * @return float The standard deviation, or REPORTIT_NAN if $array is
 *               empty or its variance is NaN.
 */
// returns the standard deviation
function f_sd(&$array, &$f_cache) {
	if ($f_cache['f_sd'] === false) {
		if (empty($array)) {
			$f_cache['f_sd'] = REPORTIT_NAN;
		} else {
			$variance        = f_var($array, $f_cache);
			$f_cache['f_sd'] = ($variance !== REPORTIT_NAN) ? sqrt($variance) : REPORTIT_NAN;
		}
	}

	return $f_cache['f_sd'];
}

// ----- Functions with external variables -----

/**
 * Calculation function: computes the Xth percentile of a sorted data
 * series. Called via the calculation engine for the 'xth' formula
 * function.
 *
 * @param array $array   Reference, the data series to analyze (sorted
 *                       in place).
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 * @param float $value   The percentile to compute (0-100 exclusive of
 *                       0).
 *
 * @return float The value at the requested percentile, or REPORTIT_NAN
 *               if $array is empty or $value is out of range.
 */
// Xth percentitle
function f_xth(&$array, &$p_cache, $value) {
	if ($value > 100 || $value <= 0) {
		return REPORTIT_NAN;
	}

	if (empty($array)) {
		$p_cache['f_xth'] = REPORTIT_NAN;

		return $p_cache['f_xth'];
	}

	sort($array);

	$x                = intval(count($array) * ($value / 100));
	$p_cache['f_xth'] = $array[$x];

	return $p_cache['f_xth'];
}

/**
 * Calculation function: sums the amount by which each value in a data
 * series exceeds a given threshold (values at or below the threshold
 * contribute nothing). Called via the calculation engine for the 'sot'
 * formula function.
 *
 * @param array $array     Reference, the data series to analyze.
 * @param array $p_cache   Reference, the per-formula-parameter result
 *                         cache.
 * @param float $threshold The threshold value.
 *
 * @return float The sum of amounts over the threshold, or REPORTIT_NAN
 *               if $array is empty.
 */
// Sum Over Threshold
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

/**
 * Calculation function: computes the percentage of samples in a data
 * series that exceed a given threshold. Called via the calculation
 * engine for the 'dot' formula function.
 *
 * @param array $array     Reference, the data series to analyze.
 * @param array $p_cache   Reference, the per-formula-parameter result
 *                         cache.
 * @param float $threshold The threshold value.
 *
 * @return float The percentage (0-100) of samples exceeding the
 *               threshold, or REPORTIT_NAN if $array is empty.
 */
// Duration Over Threshold
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
	$p_cache['f_dot'] = ($i / count($array)) * 100;

	return $p_cache['f_dot'];
}

/**
 * Calculation function: truncates a value to an integer (rounding down,
 * an alias of f_floor()). Called via the calculation engine for the
 * 'int' formula function.
 *
 * @param array $array   Reference, only checked for emptiness (result
 *                       is otherwise based solely on $value).
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 * @param float $value   The value to truncate.
 *
 * @return float The floored value, or REPORTIT_NAN if $array is empty.
 */
// Get the integer value <-- should become an alias of 'f_floor'
function f_int(&$array, &$p_cache, $value) {
	$p_cache['f_int']   = empty($array) ? REPORTIT_NAN : floor($value);
	$p_cache['f_floor'] = $p_cache['f_int'];

	return $p_cache['f_int'];
}

/**
 * Calculation function: rounds a value down to the nearest integer.
 * Called via the calculation engine for the 'floor' formula function.
 *
 * @param array $array   Reference, only checked for emptiness (result
 *                       is otherwise based solely on $value).
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 * @param float $value   The value to floor.
 *
 * @return float The floored value, or REPORTIT_NAN if $array is empty.
 */
// Round fractions down
function f_floor(&$array, &$p_cache, $value) {
	$p_cache['f_floor'] = empty($array) ? REPORTIT_NAN : floor($value);
	$p_cache['f_int']   = $p_cache['f_floor'];

	return $p_cache['f_floor'];
}

/**
 * Calculation function: rounds a value up to the nearest integer.
 * Called via the calculation engine for the 'ceil' formula function.
 *
 * @param array $array   Reference, only checked for emptiness (result
 *                       is otherwise based solely on $value).
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 * @param float $value   The value to ceil.
 *
 * @return float The ceiled value, or REPORTIT_NAN if $array is empty.
 */
// Round fractions up
function f_ceil(&$array, &$p_cache, $value) {
	$p_cache['f_ceil'] = empty($array) ? REPORTIT_NAN : ceil($value);

	return $p_cache['f_ceil'];
}

/**
 * Calculation function: rounds a value to the nearest integer (an alias
 * of f_round()). Called via the calculation engine for the 'rnd'
 * formula function.
 *
 * @param array $array   Reference, only checked for emptiness (result
 *                       is otherwise based solely on $value).
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 * @param float $value   The value to round.
 *
 * @return float The rounded value, or REPORTIT_NAN if $array is empty.
 */
// Get the rounded integer value   <--- should become an alias of 'f_round'
function f_rnd(&$array, &$p_cache, $value) {
	$p_cache['f_rnd']   = empty($array) ? REPORTIT_NAN : round($value);
	$p_cache['f_round'] = $p_cache['f_rnd'];

	return $p_cache['f_rnd'];
}

/**
 * Calculation function: rounds a value to the nearest integer. Called
 * via the calculation engine for the 'round' formula function.
 *
 * @param array $array   Reference, only checked for emptiness (result
 *                       is otherwise based solely on $value).
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 * @param float $value   The value to round.
 *
 * @return float The rounded value, or REPORTIT_NAN if $array is empty.
 */
// Get the rounded integer value
function f_round(&$array, &$p_cache, $value) {
	$p_cache['f_round'] = empty($array) ? REPORTIT_NAN : round($value);
	$p_cache['f_rnd']   = $p_cache['f_round'];

	return $p_cache['f_round'];
}

/**
 * Calculation function: returns the highest of a variadic list of
 * given numbers (not a data series aggregate). Called via the
 * calculation engine for the 'high' formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the
 *                       variadic numeric arguments are read via
 *                       func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return float The highest supplied value, or REPORTIT_NAN if fewer
 *               than one extra argument was supplied or $array is
 *               empty.
 */
// Get the highest value of a list of given numbers
function f_high(&$array, &$p_cache) {
	if (func_num_args() < 3 || empty($array)) {
		$p_cache['f_high'] = REPORTIT_NAN;

		return $p_cache['f_high'];
	}

	$p_cache['f_high'] = max(array_slice(func_get_args(), 2));

	return $p_cache['f_high'];
}

/**
 * Calculation function: returns the lowest of a variadic list of given
 * numbers (not a data series aggregate). Called via the calculation
 * engine for the 'low' formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the
 *                       variadic numeric arguments are read via
 *                       func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return float The lowest supplied value, or REPORTIT_NAN if fewer
 *               than one extra argument was supplied or $array is
 *               empty.
 */
// Get the lowest values of a list of given numbers
function f_low(&$array, &$p_cache) {
	if (func_num_args() < 3 || empty($array)) {
		$p_cache['f_low'] = REPORTIT_NAN;

		return $p_cache['f_low'];
	}

	$p_cache['f_low'] = min(array_slice(func_get_args(), 2));

	return $p_cache['f_low'];
}

/**
 * Calculation function: ternary if/then/else logic - returns the
 * second extra argument if the first is truthy, otherwise the third.
 * Called via the calculation engine for the 'if' formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the
 *                       three extra arguments (condition, true-value,
 *                       false-value) are read via func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return mixed The selected true/false-value argument, or REPORTIT_NAN
 *               if not exactly 3 extra arguments were supplied or $array
 *               is empty.
 */
// If then else logic .. If arg1 is true then return arg2 else arg3
function f_if(&$array, &$p_cache) {
	if (func_num_args() != 5 || empty($array)) {
		$p_cache['f_if'] = REPORTIT_NAN;
	} else {
		$args            = array_slice(func_get_args(), 2);
		$p_cache['f_if'] = ($args[0]) ? $args[1] : $args[2];
	}

	return $p_cache['f_if'];
}

/**
 * Calculation function: 'greater than' comparison with optional
 * true/false return values, delegating to f_cmp(). Called via the
 * calculation engine for the 'gt' formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the 2-4
 *                       extra comparison arguments are read via
 *                       func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return mixed The comparison result (see f_cmp()), or REPORTIT_NAN if
 *               the argument count is out of range or $array is empty.
 */
// 'Greater than' logic supporting predefined return values for true and false
function f_gt(&$array, &$p_cache) {
	if (func_num_args() < 4 || func_num_args() > 6 || empty($array)) {
		$p_cache['f_gt'] = REPORTIT_NAN;
	} else {
		$args = array_slice(func_get_args(), 2);

		return f_cmp($array, $p_cache, 'gt', $args);
	}
}

/**
 * Calculation function: 'lower than' comparison with optional true/
 * false return values, delegating to f_cmp(). Called via the
 * calculation engine for the 'lt' formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the 2-4
 *                       extra comparison arguments are read via
 *                       func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return mixed The comparison result (see f_cmp()), or REPORTIT_NAN if
 *               the argument count is out of range or $array is empty.
 */
// Alias for f_cmp - 'Lower than' logic
function f_lt(&$array, &$p_cache) {
	if (func_num_args() < 4 || func_num_args() > 6 || empty($array)) {
		$p_cache['f_lt'] = REPORTIT_NAN;
	} else {
		$args = array_slice(func_get_args(), 2);

		return f_cmp($array, $p_cache, 'lt', $args);
	}
}

/**
 * Calculation function: 'greater than or equal' comparison with
 * optional true/false return values, delegating to f_cmp(). Called via
 * the calculation engine for the 'ge' formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the 2-4
 *                       extra comparison arguments are read via
 *                       func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return mixed The comparison result (see f_cmp()), or REPORTIT_NAN if
 *               the argument count is out of range or $array is empty.
 */
// Alias for f_cmp - 'Greater than or equal' logic
function f_ge(&$array, &$p_cache) {
	if (func_num_args() < 4 || func_num_args() > 6 || empty($array)) {
		$p_cache['f_ge'] = REPORTIT_NAN;
	} else {
		$args = array_slice(func_get_args(), 2);

		return f_cmp($array, $p_cache, 'ge', $args);
	}
}

/**
 * Calculation function: 'lower than or equal' comparison with optional
 * true/false return values, delegating to f_cmp(). Called via the
 * calculation engine for the 'le' formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the 2-4
 *                       extra comparison arguments are read via
 *                       func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return mixed The comparison result (see f_cmp()), or REPORTIT_NAN if
 *               the argument count is out of range or $array is empty.
 */
// Alias for f_cmp - 'Lower than or equal ' logic
function f_le(&$array, &$p_cache) {
	if (func_num_args() < 4 || func_num_args() > 6 || empty($array)) {
		$p_cache['f_le'] = REPORTIT_NAN;
	} else {
		$args = array_slice(func_get_args(), 2);

		return f_cmp($array, $p_cache, 'le', $args);
	}
}

/**
 * Calculation function: 'equal' comparison with optional true/false
 * return values, delegating to f_cmp(). Called via the calculation
 * engine for the 'eq' formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the 2-4
 *                       extra comparison arguments are read via
 *                       func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return mixed The comparison result (see f_cmp()), or REPORTIT_NAN if
 *               the argument count is out of range or $array is empty.
 */
// Alias for f_cmp - 'Equal' logic
function f_eq(&$array, &$p_cache) {
	if (func_num_args() < 4 || func_num_args() > 6 || empty($array)) {
		$p_cache['f_eq'] = REPORTIT_NAN;
	} else {
		$args = array_slice(func_get_args(), 2);

		return f_cmp($array, $p_cache, 'eq', $args);
	}
}

/**
 * Calculation function: 'not equal' comparison with optional true/false
 * return values, delegating to f_cmp(). Called via the calculation
 * engine for the 'uq' formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the 2-4
 *                       extra comparison arguments are read via
 *                       func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return mixed The comparison result (see f_cmp()), or REPORTIT_NAN if
 *               the argument count is out of range or $array is empty.
 */
// Alias for f_cmp - 'Equal' logic
function f_uq(&$array, &$p_cache) {
	if (func_num_args() < 4 || func_num_args() > 6 || empty($array)) {
		$p_cache['f_uq'] = REPORTIT_NAN;
	} else {
		$args = array_slice(func_get_args(), 2);

		return f_cmp($array, $p_cache, 'uq', $args);
	}
}

/**
 * Shared comparison engine backing f_gt()/f_lt()/f_ge()/f_le()/f_eq()/
 * f_uq(): evaluates 'arg0 OP arg1' (via eval()) for the operator
 * corresponding to $function, returning 1/0 for a bare boolean result,
 * or a caller-supplied true-value (and optional false-value) instead.
 * Called from each of the f_gt/f_lt/f_ge/f_le/f_eq/f_uq wrapper
 * functions.
 *
 * @param array  $array    Reference, only checked for emptiness by the
 *                         callers; not used directly here.
 * @param array  $p_cache  Reference, the per-formula-parameter result
 *                         cache, keyed as 'f_' . $function.
 * @param string $function The comparison type: 'eq', 'lt', 'gt', 'le',
 *                         'ge', or 'uq'.
 * @param array  $args     The comparison operands ($args[0], $args[1])
 *                         plus optional true-value ($args[2]) and
 *                         false-value ($args[3]).
 *
 * @return mixed The comparison result: 1/0, a supplied true-value/0, or
 *               a supplied true-value/false-value, depending on how many
 *               extra arguments were given.
 */
// compare function
function f_cmp(&$array, &$p_cache, $function, $args) {
	$operators = ['eq' => '==', 'lt' => '<', 'gt' => '>', 'le' => '<=', 'ge' => '>=', 'uq' => '!='];

	$condition = 'return (' . $args[0] . $operators[$function] . $args[1] . ') ? true : false;';

	if (cacti_sizeof($args) == 2) {
		// no return value given - return 1 or 0 if true or false
		$p_cache['f_' . $function] = eval($condition) ? 1 : 0;
	} elseif (cacti_sizeof($args) == 3) {
		// pos. return value given - return third argument if true or 0 if false
		$p_cache['f_' . $function] = eval($condition) ? $args[2] : 0;
	} else {
		/* pos. return value given - return third argument if true
		   neg. return value given - return fourth argument if false */
		$p_cache['f_' . $function] = eval($condition) ? $args[2] : $args[3];
	}

	return $p_cache['f_' . $function];
}

/**
 * Calculation function: tests whether a value is NaN/null, with
 * optional true/false return values (mirroring f_cmp()'s argument-count
 * conventions). Called via the calculation engine for the 'isnan'
 * formula function.
 *
 * @param array $array   Reference, only checked for emptiness; the 1-3
 *                       extra arguments (value to test, plus optional
 *                       true/false-value) are read via func_get_args().
 * @param array $p_cache Reference, the per-formula-parameter result
 *                       cache.
 *
 * @return mixed 1/0, a supplied true-value/0, or a supplied true-value/
 *               false-value depending on argument count, or REPORTIT_NAN
 *               if the argument count is out of range or $array is
 *               empty.
 */
function f_isNaN(&$array, &$p_cache) {
	if (func_num_args() < 3 || func_num_args() > 5 || empty($array)) {
		$p_cache['f_nan'] = REPORTIT_NAN;
	} else {
		$args = array_slice(func_get_args(), 2);

		if (cacti_sizeof($args) == 2) {
			// no return value given - return 1 or 0 if true or false
			$p_cache['f_nan'] = (is_nan($args[0]) || is_null($args[0])) ? 1 : 0;
		} elseif (cacti_sizeof($args) == 3) {
			// pos. return value given - return third argument if true or 0 if false
			$p_cache['f_nan'] = (is_nan($args[0]) || is_null($args[0])) ? $args[2] : 0;
		} else {
			/* pos. return value given - return third argument if true
			   neg. return value given - return fourth argument if false */
			$p_cache['f_nan'] = (is_nan($args[0]) || is_null($args[0])) ? $args[2] : $args[3];
		}
	}

	return $p_cache['f_nan'];
}

/**
 * Shutdown-function safety net that logs the last-attempted calculation
 * formula if the script terminated abnormally (e.g. a fatal error
 * inside an eval() call in f_cmp()/calculate()), aiding diagnosis of
 * bad measurand formulas. Registered once (via calculate()) as a PHP
 * shutdown function.
 *
 * @return void
 *
 * @global string $calculate_last_formula The most recently attempted
 *                                       formula string, if any.
 */
function calculate_handler() {
	global $calculate_last_formula;

	if (!empty($calculate_last_formula)) {
		cacti_log('ERROR: Bad Formula: ' . $calculate_last_formula, false, 'REPORTIT');
	}
}

// ----- Main function for calculating -----

global $calculate_handler_set, $calculate_last_formula;

// Normal way of calculation
/**
 * Core calculation engine: for every measurand formula and every RRA
 * (Round Robin Archive)/data-source combination, substitutes in
 * RRA-specific variables (maxValue/maxRRDValue) and report variable
 * values, then evaluates the resulting formula (via eval()) against the
 * extracted RRD data to produce each measurand's final result,
 * registering a shutdown-time error handler to log any formula that
 * causes a fatal evaluation error. Called from runtime() once report
 * data extraction is complete, to compute every report measurand's
 * value.
 *
 * @param array $data      Reference, the extracted/prepared RRD data
 *                         series to calculate against.
 * @param array $params    Reference, calculation parameters (RRA
 *                         indexes, data source count/names, etc.).
 * @param array $variables Reference, the report's resolved variable
 *                         name => value substitutions.
 * @param array $df_cache  Reference, the initial 'functions' cache
 *                         state (per-RRA copy seeded from this).
 * @param array $dm_cache  Reference, the map of measurand id => formula
 *                         string to evaluate.
 * @param array $dr_cache  Reference, the initial 'interim results'
 *                         cache state.
 * @param array $dp_cache  Reference, the initial 'functions with
 *                         parameters' cache state.
 * @param array $ds_cache  Reference, the initial 'spanned metrics'
 *                         cache state.
 *
 * @return array The computed results, keyed by data-source/RRA and
 *               measurand id.
 *
 * @global bool   $calculate_handler_set    Whether the shutdown error
 *                                         handler has already been
 *                                         registered, to avoid
 *                                         double-registration.
 * @global string $calculate_last_formula   Updated with the formula
 *                                         currently being evaluated,
 *                                         for calculate_handler() to
 *                                         report on fatal errors.
 */
function calculate(&$data, &$params, &$variables, &$df_cache, &$dm_cache, &$dr_cache, &$dp_cache, &$ds_cache) {
	$results = [];

	$f_cache = $df_cache;	// Functions
	$m_cache = $dm_cache;	// Metrics
	$r_cache = $dr_cache;	// Interim results
	$p_cache = $dp_cache;	// Functions with parameters
	$s_cache = $ds_cache;	// Metrics with flag 'spanned'

	$n_rra   = $params['rrd_ds_cnt'];
	$ds_namv = $params['rras'];

	$specific_variables = ['maxValue', 'maxRRDValue'];

	// Create a cache for every Round Robin Archive
	foreach ($ds_namv as $key => $ds_name) {
		$cache[$key] = 	[$f_cache, $m_cache, $p_cache];
	}

	// Use reportit's error handler.
	set_error_handler('last_error');

	global $calculate_handler_set, $calculate_last_formula;

	if (!$calculate_handler_set) {
		register_shutdown_function('calculate_handler');
		$calculate_handler_set = true;
	}

	// Build the calculation command and execute it
	foreach ($m_cache as $k => $m) {
		debug($cache, 'Main Cache Status: f,m,p');

		// we need the correct rra index to choose the right data
		$rra_index = $params['rra_indexes'][$k];

		foreach ($ds_namv as $i => $ds_name) {
			debug($cache, 'Main Cache Status: f,m,p');

			// Debug
			$debug = [];

			// Formula
			$formula = str_replace([' ', "\r\n", "\n"], '', $m);
			$debug[] = $formula;

			// transform RRA specific variables (maxValue, maxRRDValue) used in that formula
			foreach ($specific_variables as $specific_variable) {
				$formula = str_replace($specific_variable, $specific_variable . ':' . $ds_name, $formula);
			}

			$debug[] = $formula;

			// Replace our variables
			foreach ($variables as $key => $value) {
				$formula = str_replace($key, $value, $formula);
			}

			$debug[] = $formula;

			// Replace measurands (spanned)
			foreach ($s_cache as $key => $value) {
				$pattern = '/(^|[+|\-|*|\/|\(|\)|,| ])' . $key . '([+|\-|*|\/|\(|\)|,| ]|$)/';
				$formula = preg_replace($pattern, "\${1}$value\${2}", $formula);
			}

			$debug[] = $formula;

			// Replace interim results first:
			foreach ($r_cache as $key => $value) {
				if ($value !== false) {
					$formula = str_replace($key, $value, $formula);
				}
			}

			$debug[] = $formula;

			// Replace measurands with an existing result if we have one
			foreach ($cache[$i][1] as $key => $value) {
				$pattern = '/(^|[+|\-|*|\/|\(|\)|,| ])' . $key . '([+|\-|*|\/|\(|\)|,| ]|$)/';
				$formula = preg_replace($pattern, "\${1}$value\${2}", $formula);
			}

			$debug[] = $formula;

			// Replace formula calls
			foreach ($cache[$i][0][$rra_index] as $key => $value) {
				if ($value === false) {
					$formula = str_replace($key, $key . '($data[$rra_index][$i], $cache[$i][0][$rra_index])', $formula);
				} else {
					$formula = str_replace($key, $value, $formula);
				}
			}

			$debug[] = $formula;

			// Replace formula calls with parameters
			foreach ($cache[$i][2][$rra_index] as $key => $value) {
				$formula = str_replace($key, $key . '( $data[$rra_index][$i], $cache[$i][2][$rra_index]||', $formula);
				$formula = str_replace('||(', ', ', $formula);
			}

			$debug[] = $formula;

			// calculate
			$result    = false;
			$completed = false;

			debug($debug, 'Interpretation');

			if (stripos($formula, '|query_NAN|') !== false) {
				return 'NULL';
			}

			$calculate_last_formula = $formula;
			eval("\$result = $formula;");
			$calculate_last_formula = '';

			if ($result === false || is_nan($result) || is_null($result)) {
				$result = 'NULL';
			}

			$debug   = [];
			$debug[] = $result;

			debug($debug, 'Result');

			// If its flagged as 'spanned' then update the s_cache, update the main cache
			// and jump to the next measurand
			if (array_key_exists($k, $s_cache)) {
				$s_cache[$k] = $result;

				for ($i = 0; $i < $n_rra; $i++) {
					unset($cache[$i][1][$k]);
				}

				continue 2;
			} else {
				// Update r_cache with the result of our measurand
				$name           = $k . ':' . $params['rras'][$i];
				$r_cache[$name] = $result;

				// Update main cache with the result of our measurand
				$cache[$i][1][$k] = $result;
			}
		}
	}

	// Clear up and return to main function
	$result = [];

	foreach ($ds_namv as $i => $ds_name) {
		$result[$ds_name] = $cache[$i][1];
	}

	// Add s_cache
	$result['_spanned_'] = $s_cache;

	// Fall back to normal error handler
	restore_error_handler();
	debug($cache, 'Main Cache Status: f,m,p');

	return $result;
}
