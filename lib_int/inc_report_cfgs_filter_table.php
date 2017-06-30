<tr bgcolor="#<?php print $colors["panel"];?>">
		<form name="form_graph_id" method="post">
		<td>
			<table width='100%' cellpadding="0" cellspacing="0">
				<tr>
					<?php
						if ($reportAdmin) {
							?>
							<td width="100">
								&nbsp;Filter by Owner:&nbsp;
							</td>
							<td width="1">
								<select name="cbo_graph_id" onChange="window.location=document.form_graph_id.cbo_graph_id.options[document.form_graph_id.cbo_graph_id.selectedIndex].value">
									<option value="cc_reports.php?owner=-1&template=<?php print get_request_var('template');?>&filter=<?php print get_request_var('filter');?>"<?php if (get_request_var('owner') == "-1") {?> selected<?php }?>>Any</option>

									<?php
									if (sizeof($ownerlist) > 0) {
										foreach ($ownerlist as $owner) {
											print "<option value='cc_reports.php?owner=" . $owner["id"] . "&template=" . get_request_var('template') . "&page=1'"; if (get_request_var('owner') == $owner["id"]) { print " selected"; } print ">" . title_trim($owner["username"], 40) . "</option>\n";
										}
									}
									?>
								</select>
							</td>
							<td width="30"></td>
							<td width="75">
								Template:&nbsp;
							</td>
							<td width="1">
								<select name="cbo_graph_id_2" onChange="window.location=document.form_graph_id.cbo_graph_id_2.options[document.form_graph_id.cbo_graph_id_2.selectedIndex].value">
									<option value="cc_reports.php?template=-1&owner=<?php print get_request_var('owner');?>&filter=<?php print get_request_var('filter');?>"<?php if (get_request_var('template') == "-1") {?> selected<?php }?>>Any</option>

									<?php
									if (sizeof($templatelist) > 0) {
										foreach ($templatelist as $template) {
											print "<option value='cc_reports.php?template=" . $template["id"] . "&owner=" . get_request_var('owner') . "&page=1'"; if (get_request_var('template') == $template["id"]) { print " selected"; } print ">" . title_trim($template["description"], 40) . "</option>\n";
										}
									}
									?>

								</select>
							</td>
							<td width="30"></td>
							<?php
					}

					?>
					<td width="60">
						&nbsp;Search:&nbsp;
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
