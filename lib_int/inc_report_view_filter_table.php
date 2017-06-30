<tr bgcolor="#<?php print $colors["panel"];?>">
		<form name="form_graph_id"  method="post">
		<td>
			<table width='100%' cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td width="100">
						&nbsp;Select type:&nbsp;
					</td>
					<td width="1">
						<select name="cbo_graph_id" onChange="window.location=document.form_graph_id.cbo_graph_id.options[document.form_graph_id.cbo_graph_id.selectedIndex].value">
							<option value="cc_view.php?type=-1"<?php if (get_request_var('type') == "-1") {?> selected<?php }?>>Public reports</option>
							<option value="cc_view.php?type=0"<?php if (get_request_var('type') == "0") {?> selected<?php }?>>My reports</option>
						</select>
					</td>
					<td width="30"></td>
					<td width="60">
						Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="20" value="<?php print get_request_var('filter');?>">
					</td>
					<td>
						&nbsp;<input type="submit" value="Go" alt="Go" border="0" align="absmiddle">
						<input type="submit" name="clear_x" value="Clear" alt="Clear" border="0" align="absmiddle">
					</td>
				</tr>
			</table>
		</td>
		<input type='hidden' name='page' value='1'>
		</form>
	</tr>
