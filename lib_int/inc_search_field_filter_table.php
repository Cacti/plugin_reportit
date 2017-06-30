	<tr bgcolor="#<?php print $colors["panel"];?>" class="noprint">
		<form name="form_graph_id" method="post">
		<td>
			<table width='100%' cellpadding="0" cellspacing="0">
				<tr>
					<td width="60">
						&nbsp;Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="40" value="<?php print get_request_var('filter');?>">
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
