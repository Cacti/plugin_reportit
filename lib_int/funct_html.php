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


function html_custom_header_box($header, $hlink = false, $href = '', $link = '') {
    global $colors;

    echo "<table align='center' width=" . '100%' . " cellpadding='1' cellspacing='0' border='0' bgcolor='#{$colors['header']}'>
            <tr>
                <td>
                    <table align='center' width='100%' cellpadding='3' cellspacing='0' border='0' bgcolor='#{$colors['header']}'>
                        <tr>
                            <td class='textHeaderDark' align='left' style='padding: 3px;' colspan='100'>
                                <table width='100%' cellpadding='0' cellspacing='0'>
                                    <tr>
                                        <td class='textHeaderDark' align='left'>";
                                            print "<b>$header&nbsp;</b>";
                                            if($hlink) print "[$hlink]";
    echo                               "</td>";

    if($href !='' && $link !='') {
        print "<td class='textHeaderDark' align='right'>";
        if(is_array($href) && is_array($link)) {
            foreach($href as $key => $value){
                print "<a style='color:yellow' href='$value' alt='titlt'>$link[$key]</a>&nbsp;";
            }
        }else {
                print "<a style='color:yellow' href='$href' alt='titlt'>$link</a>";
        }
        print "</td>";
    }
    echo                           "</tr>
                                </table>
                            </td>
                        </tr>";
}


function html_error_box($message, $site, $jump, $link){
    global $colors;

    html_wizard_header('Error', '$site');
    print "<tr><td bgcolor='#" . $colors['form_alternate1'] . "'><span class='textError'>$message</span></td></tr>\n";
    html_wizard_footer($jump, $link, '');
}


function html_wizard_header($title, $site, $size='60%') {
    global $colors;

    html_start_box("<b>$title</b>", $size, $colors['header_panel'], '2', 'center', '');
    echo "<form action='$site' method='post' enctype='multipart/form-data'>\r\n";
}


function html_wizard_footer($jump, $link, $save_html, $colspan=1) {
	global $config;
    print " <tr>
		<td align='right' bgcolor='#eaeaea' colspan='$colspan'>
		    <input type='hidden' name='action' value='$jump'>
		    <a href='$link'><img src='../../images/button_cancel2.gif' alt='Chancel' align='absmiddle' border='0'></a>
		    $save_html
		</td>
	    </tr>";
    html_end_box();
    include_once(CACTI_BASE_PATH . '/include/bottom_footer.php');
}


function html_report_start_box() {
    global $colors;
    echo "<table align='center' width=" . '100%' . " cellpadding='3' cellspacing='1' bgcolor='#{$colors['header_panel']}'>";
}


function html_blue_link(& $data, $lb=true) {
    // Create a blue HTML link in an existing table
    echo   "<table width=" . '100%' . " align='center'>
	    		<tr>
		    		<td class='textinfo' valign='top' align='right'>";
	foreach($data as $link) {
			print "<span style='color: #c16921'>*</span><a href='{$link['href']}'>{$link['text']}</a><br>";
	}
    echo 	   		"</td>
				</tr>
	    	</table>";
	if($lb) echo "<br>";
}


function html_sorted_with_arrows(& $desc_array, & $link_array, $page, $id = FALSE) {
	$text	= '';
	$result 	= array();

	$asc	= (isset_request_var('sort') && isset_request_var('mode') && get_request_var('mode') == 'ASC') ? get_request_var('sort') : '';
	$desc	= (isset_request_var('sort') && isset_request_var('mode') && get_request_var('mode') == 'DESC') ? get_request_var('sort') : '';

	if($id) $id = '&id=' . $id;
	foreach($desc_array as $key => $description) {

        $text	= $description;

		if($link_array[$key] != '') {
            $text   = "<a class='textSubHeaderDark' href='$page?$id&sort={$link_array[$key]}&mode=" . (($asc == $link_array[$key]) ? "DESC'>" : "ASC'>" ) . $text . "</a>&nbsp;";
			$text	.= "<a href='$page?$id&sort={$link_array[$key]}&mode=ASC'>"
					. (($asc == $link_array[$key]) ? "<img src='./images/red_arrow_up.gif' alt='ASC' border='0' align='absmiddle' title='arranged in ascending order'>"
                                                   : "<img src='./images/arrow_up.gif' alt='ASC' border='0' align='absmiddle' title='arrange in ascending order'>")
					. "</a>";

			$text	.= "<a href='$page?$id&sort={$link_array[$key]}&mode=DESC'>"
					. (($desc == $link_array[$key]) ? "<img src='./images/red_arrow_down.gif' alt='DESC' border='0' align='absmiddle' title='arranged in descending order'>"
                                                    : "<img src='./images/arrow_down.gif' alt='DESC' border='0' align='absmiddle' title='arrange in descending order'>")
					. "</a>";
		}
		$result[]	= $text;
	}
	return $result;
}


function html_checked_with_arrow($value) {
    if($value == TRUE) {
	echo '<b>&radic;</b>';
    }else {
	echo '';
    }
}

function html_checked_with_icon($value, $icon, $title='', $alternative='', $before='', $after='') {
    if($value == TRUE) {
	print "$before<img src='./images/" . $icon . "' alt='$title' border='0' align='top' title='$title'>$after";
    }else {
	echo $alternative;
    }
}

function html_custom_page_list($current_page, $pages_per_screen, $rows_per_page, $total_rows, $url, $page_var = "page") {
	$url_page_select = "";

	$total_pages = ceil($total_rows / $rows_per_page);

	$start_page = max(1, ($current_page - floor(($pages_per_screen - 1) / 2)));
	$end_page = min($total_pages, ($current_page + floor(($pages_per_screen - 1) / 2)));

	/* adjust if we are close to the beginning of the page list */
	if ($current_page <= ceil(($pages_per_screen) / 2)) {
		$end_page += ($pages_per_screen - $end_page);
	}else{
		$url_page_select .= "...";
	}

	/* adjust if we are close to the end of the page list */
	if (($total_pages - $current_page) < ceil(($pages_per_screen) / 2)) {
		$start_page -= (($pages_per_screen - ($end_page - $start_page)) - 1);
	}

	/* stay within limits */
	$start_page = max(1, $start_page);
	$end_page = min($total_pages, $end_page);

	//print "start: $start_page, end: $end_page, total: $total_pages<br>";

	for ($page_number=0; (($page_number+$start_page) <= $end_page); $page_number++) {
		if ($page_number < $pages_per_screen) {
			if ($current_page == ($page_number + $start_page)) {
				$url_page_select .= "<strong><a style='color:black' href='$url&" . $page_var . "=" . ($page_number + $start_page) . "'>" . ($page_number + $start_page) . "</a></strong>";
			}else{
				$url_page_select .= "<a style='color:yellow' href='$url&" . $page_var . "=" . ($page_number + $start_page) . "'>" . ($page_number + $start_page) . "</a>";
			}
		}

		if (($page_number+$start_page) < $end_page) {
			$url_page_select .= ",";
		}
	}

	if (($total_pages - $current_page) >= ceil(($pages_per_screen) / 2)) {
		$url_page_select .= "...";
	}

	return $url_page_select;
}


function html_custom_form_button($cancel_url="", $force_type = "", $key_field = "id", $leading_br = false, $width='100%') {
	global $config;

	if (empty($force_type)) {
		if (isempty_request_var($key_field)) {
			$img = "button_create.gif";
			$alt = "Create";
		}else{
			$img = "button_save.gif";
			$alt = "Save";
		}
		$return = 'save';
	}else {
        $img = "button_" . $force_type . ".gif";
        $alt = ucfirst($force_type);
        $return = $force_type;
    }

	if($leading_br == true) echo "<br>";
	?>
	<table align='center' width='<?php print $width; ?>' style='background-color: #ffffff; border: 1px solid #bbbbbb;'>
		<tr>
			 <td bgcolor="#f5f5f5" align="right">
				<?php
				    print "<input type='hidden' name='action' value='$return'>";
				    if($cancel_url != "") {
                        print "<a href='$cancel_url'><img src='{$config['url_path']}images/button_cancel2.gif' alt='Cancel' align='absmiddle' border='0'></a> ";
                    }
					if($force_type != 'NONE') {
						print "<input type='image' src='{$config['url_path']}images/$img' alt='$alt' align='absmiddle'>";
					}
				?>
			</td>
		</tr>
	</table>
	</form>
	<?php
}




/**
 * html_calc_syntax()
 * generates the links for the measurand configurator to add variables and existing interim results
 * to the calculation formula
 * @param int $measurand_id contains the id of the current measurand
 * @param int $template_id contains the id of the template which contains the measurand
 */
function html_calc_syntax($measurand_id, $template_id) {
    global $rubrics;

    $rubrics['Variables']       = get_possible_variables($template_id);
    $dq_variables               = array_flip(get_possible_data_query_variables($template_id));
    $rubrics['Data Query Variables']     = $dq_variables;

    $interim_results            = array_flip(get_interim_results($measurand_id, $template_id, true));
    $rubrics['Interim Results'] = $interim_results;

    //Create an box to get an overview of possible commands
    foreach($rubrics as $key => $value) {
        echo "<tr><td width='15%' align='left' valign='top'><b>$key:</b></td><td>";
        echo "&nbsp;&nbsp";
        foreach($value as $name => $description) {
            $value = str_replace('<br>', '', $name);
            echo "<font id='$value' color='blue' onMouseover=tooltip('Tooltip','$value',1) onMouseout=tooltip('Tooltip','$value',0) onClick=add_to_calc('$value') style='cursor:pointer;'>$name&nbsp;&nbsp;</font>";
        }
        echo "</td><td width='5%'><td></tr>";
    }
}


function html_report_variables($report_id, $template_id) {

    //Define some variables
    $array		= array();
    $form_array_vars 	= array();
    $input_types 	= array(1 => 'drop_array', 2 => 'textbox');

    //Load the possible variables
    $sql	= "SELECT a.*, b.value FROM reportit_variables AS a
		    LEFT JOIN reportit_rvars AS b
		    ON a.id = b.variable_id AND report_id = $report_id
		    WHERE a.template_id = $template_id";

    $variables	= db_fetch_assoc($sql);

    if(count($variables) == 0) {
	$sql = "SELECT * FROM reportit_variables WHERE template_id = $template_id";
	$variables = db_fetch_assoc($sql);
    }

    //Exit if there are no variables necessary for using this template
    if(count($variables) == 0) return FALSE;

    //Put the headerline in
    $header = array('friendly_name'	=> 'Variables',
		    'method'		=> 'spacer');
    $form_array_vars['report_var_header'] =  $header;

    //Start with a transformation
    foreach($variables as $v) {
	$value	= (isset($v['value']) ? $v['value'] : $v['default_value']);
	$method = $input_types[$v['input_type']];
	$index 	= 'var_' . $v['id'];

	if($method == 'drop_array') {
	    $i 		= 0;
	    $array 	= array();


	    $a 		= $v['min_value'];
	    $b 		= $v['max_value'];
    	    $c 		= $v['stepping'];

	    for( $i = $a; $i <= $b; $i+=$c) {
		$array[]=strval($i);
	    }

	    $var = array('friendly_name'=> ($v['name']),
			 'method' 	=> $method,
			 'description' 	=> $v['description'],
			 'value'	=> array_search($value, $array),
			 'array'	=> $array);

	    $form_array_vars[$index] = $var;
	}else {
	    $var = array('friendly_name'=> $v['name'],
			 'method' 	=> $method,
			 'description' 	=> $v['description'],
			 'max_length'	=> 10,
			 'value'	=> $value,
			 'default'	=> $v['default_value']);
	    $form_array_vars[$index] = $var;
	}
    }
    return $form_array_vars;
}



/*
    This function creates the necessary HTML output for several input boxes
    displayed in the report template editor, which will be used to define
    an alias for every internal data source item.
    @arg                => report template id, if available (new template => 0)
    @data_template_id   => internal Cacti id of the used data template
*/
function html_template_ds_alias($template_id, $data_template_id) {

    $form_array_alias   = array();
    $data_source_items  = array();

    /* load information about defined data sources of that data template */
    $sql = "SELECT a.id, a.data_source_name, b.data_source_alias, b.id as enabled
            FROM data_template_rrd as a
            LEFT JOIN
                (SELECT * FROM reportit_data_source_items WHERE template_id = $template_id ) as b
            ON a.data_source_name = b.data_source_name
            WHERE a.local_data_id =0
            AND a.data_template_id =$data_template_id";

    $data_source_items = db_fetch_assoc($sql);

    /* create the necessary input field for defining the alias */
    if(sizeof($data_source_items)>0) {
        foreach($data_source_items as $data_source_item) {
            $item = array(
                'friendly_name' => "Data Source Item </font>[<i>{$data_source_item['data_source_name']}</i>]<font class='textEditTitle'>",
                'method'        => 'checkbox',
                'default'       => 'on',
                'description'   => "Activate data source item \"{$data_source_item['data_source_name']}\" for the calculation process.",
                'value'         => ($data_source_item['enabled'] == true) ? 'on' : 'off'
            );
            $form_array_alias['ds_enabled__' . $data_source_item['id']] = $item;

            $var = array(
                'friendly_name' => "Data Source Alias",
                'description'   => "Optional: You can define an alias which should be displayed
                                    instead of the internal data source name \"{$data_source_item['data_source_name']}\" in the reports.",
                'method'        => 'textbox',
                'max_length'    => '25',
                'default'       => '',
                'value'         => ( $data_source_item['data_source_alias'] !== NULL ) ? stripslashes($data_source_item['data_source_alias']) : '',
            );
            $form_array_alias['ds_alias__' . $data_source_item['id']] = $var;
        }
    }

    /* add the alias for the group of separate measurands */
    $sql = "SELECT data_source_alias FROM reportit_data_source_items WHERE id = 0 AND template_id = $template_id";
    $separate_group_alias = db_fetch_cell($sql);

    $var = array(
                'friendly_name' => "Separate Group Title </font>[<i>overall</i>]<font class='textEditTitle'>",
                'description'   => "Optional: You can define an group name which should be displayed
                                    as the title for all separate measurands within the reports.",
                'method'        => 'textbox',
                'max_length'    => '25',
                'default'       => '',
                'value'         => ( $separate_group_alias !== NULL ) ? stripslashes($separate_group_alias) : '',
            );
            $form_array_alias['ds_alias__0'] = $var;
    return $form_array_alias;
}



/* draw_actions_dropdown - draws a table the allows the user to select an action to perform
     on one or more data elements
   @arg $actions_array - an array that contains a list of possible actions. this array should
     be compatible with the form_dropdown() function */
function html_custom_actions_dropdown($actions_array, $text='Choose an action', $cancel_url = "", $action='actions') {
    global $config;
    ?>
    <form name="custom_dropdown" method="post">
        <table align='center' width='100%' border='0'>
            <tr>
                <td width='1' valign='top'>
                    <img src='<?php echo $config['url_path']; ?>images/arrow.gif' alt='' align='absmiddle'>&nbsp;
                </td>
                <td align='right'>
                    <?php
                        print "$text: ";
                        form_dropdown("drp_action",$actions_array,"","","1","","");
                    ?>
                </td>
                <td width='1' align='right'>
                    <input type='image' src='<?php echo $config['url_path']; ?>images/button_go.gif' alt='Go' align='absmiddle' border='0'>
                </td>
                <td width='1' align='right'>
                    <a href='<?php print $cancel_url;?>'><img src='<?php echo $config['url_path']; ?>images/button_cancel2.gif' alt='Cancel' align='absmiddle' border='0'></a>
                </td>
            </tr>
        </table>
        <input type='hidden' name='action' value='<?php print $action;?>'>
    </form>
    <?php
}

/* draw_custom_actions_dropdown - draws a table the allows the user to select an action to perform
   on one or more data elements
   @arg $actions_array - an array that contains a list of possible actions. this array should
   be compatible with the form_dropdown() function */
function draw_custom_actions_dropdown($actions_array, $href = '') {
global $config;
	$action = ($href !='')	? "window.location.href=\"$href\""
							: "window.history.back()";
?>
	<table align='center' width='100%'>
		<tr>
			<td width='1' valign='top'>
				<img src='<?php echo $config['url_path']; ?>images/arrow.gif' alt='' align='absmiddle'>&nbsp;
			</td>
			<td align='right'>
				Choose an action:
				<?php form_dropdown("drp_action",$actions_array,"","","1","","");?>
			</td>
			<td width='1' align='right'>
				<input type='submit' value='Go' title='Execute Action'>
			</td>
			<td width='1' align='right'>
				<input type='button' value='Cancel' onClick='<?php print $action;?>'>
			</td>
		</tr>
	</table>

	<input type='hidden' name='action' value='actions'>
	<?php
}

?>
