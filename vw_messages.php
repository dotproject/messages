<?php
	global $AppUI, $user_hash, $view_message, $show_read_messages, $show_sent_messages;
	
	$task_owner_filter     = "";
	$task_recipient_filter = " and ut.user_id     = '".$AppUI->user_id."' ";
	$task_status_filter    = " and t.task_status = '0' ";
	
	if($show_read_messages){
		$task_status_filter = " and t.task_status = '-1' ";
		
	} else if ($show_sent_messages) {
		$task_status_filter    = "";
		$task_recipient_filter = "";
		$task_owner_filter     = " and t.task_owner = '".$AppUI->user_id."'";
	}
	
	$sql = "select t.*
			from tasks as t,
				 user_tasks as ut
			where t.task_id = ut.task_id
			      and t.task_project = '0'
				  $task_recipient_filter
				  $task_status_filter
				  $task_owner_filter
			order by task_priority, task_start_date";
	$incoming_messages = db_loadObjectList($sql, new CTask());
	
	$table_headers = array("Date", "Title");
	if($show_sent_messages){
		$table_headers[] = "Already read?";
	} else {
		$table_headers[] = "Author";
	}
?>
<table width='100%'>
	<tr>
		<td width='50%' valign='top'>
			<table border="0" class="tbl">
				<tr>
					<th>&nbsp;</th>
					<?php
						foreach($table_headers as $table_header){
							echo "<th>".$AppUI->_($table_header)."</th>";
						}
					?>
					<th>&nbsp;</th>
				</tr>
				<?php
					if(count($incoming_messages) == 0){
						echo "<tr><td colspan='".(count($table_headers)+2)."'>".$AppUI->_("No messages under this clasification")."</td></tr>";
					}
				
					foreach($incoming_messages as $message){
						$m_row  = "<tr>";
						
						$m_row .= "<td>";
						if ($message->task_priority < 0 ) {
							$m_row .= "<img src=\"./images/icons/low.gif\" alt='L' />";
						} else if ($message->task_priority > 0) {
							$m_row .= "<img src=\"./images/icons/" . $message->task_priority ."'.gif\" alt='" . $message->task_priority . "' />'";
						}
						$m_row .= "</td>"; // Priority icons
						$m_row .= "<td>".$message->task_start_date."</td>";
						$task_name = $message->task_name != "" ? $message->task_name : $AppUI->_("No subject");
						$m_row .= "<td> <a href='index.php?m=messages&message_id=".$message->task_id."'>$task_name</a></td>";
						
						if($show_sent_messages){
							$m_row .= "<td>".$AppUI->_($message->task_status == -1 ? "Yes" : "No")."</td>";
						} else {
							$m_row .= "<td>".$user_hash[$message->task_owner]."</td>";
						}
						
						$m_row .= "<td></td>"; // Actions
						
						echo $m_row;
					}
				?>
			</table>
		</td>
		<td width='50%' valign='top'>
			<?php
				if (isset($view_message)) {
					$sql = "select user_id
							from user_tasks
							where task_id = '".$view_message->task_id."'";
					$recipient_list = "";
					foreach(db_loadColumn($sql) as $user_id){
						$recipient_list .= $user_hash[$user_id].", ";
					}
					$recipient_list = substr($recipient_list, 0, strlen($recipient_list)-2);
					
					?>
						<table class="tbl">
							<tr>
								<th><?php echo $AppUI->_("Date"); ?></th><td><?php echo $view_message->task_start_date; ?></td>
							</tr>
							<tr>
								<th><?php echo $AppUI->_("Title"); ?></th><td><?php echo $view_message->task_name; ?></td>
							</tr>
							<tr>
								<th><?php echo $AppUI->_("Author"); ?></th><td><?php echo $user_hash[$view_message->task_owner]; ?></td>
							</tr>
							<tr>
								<th><?php echo $AppUI->_("Recipients"); ?></th><td><?php echo $recipient_list; ?></td>
							</tr>
							<tr>
								<th colspan='2'><?php echo $AppUI->_("Message"); ?></th>
							</tr>
							<tr>
								<td colspan='2'><?php echo nl2br($view_message->task_description); ?></td>
							</tr>
							<tr>
								<td colspan='2'>
									<?php
										$links = array();
										$links["Reply"] = "index.php?m=messages&reply_to_message_id=".$view_message->task_id;
										
										if($show_read_messages){
											$links["Mark as unread"] = "index.php?m=messages&message_id=".$view_message->task_id."&action=mark_as_unread";
										} else {
											$links["Mark as read"] = "index.php?m=messages&message_id=".$view_message->task_id."&action=mark_as_read";
										}
										
										if($show_sent_messages){
											$links = array();
											if($view_message->task_status == '-1'){
												$links["Resend message"] = "index.php?m=messages&message_id=".$view_message->task_id."&action=resend_message";
											}
										}
										
										$links["Delete"] = "index.php?m=messages&message_id=".$view_message->task_id."&action=delete";
									?>
									<table width='100%' cellpadding="0" cellspacing="0">
										<tr>
											<?php
												foreach ($links as $name => $link) {
													echo "<td align='center'><a href='$link'>".$AppUI->_($name)."</td>";
												}
											?>
										</tr>
									</table>
									<?php
										if(!$show_sent_messages){
											?>
											<hr />
											<form action='index.php?m=messages&message_id=<?php echo $view_message->task_id; ?>&action=convert_to_task' method='post'>
												<?php
													echo $AppUI->_("Converto to task within the following project");
													echo "<br />";
													$project_list = db_loadHashList("select project_id, project_name
																						from projects
																						where project_active = '1'
																							and project_status != '6'
																							and project_status != '7'
																						order by project_name");
													echo arraySelect($project_list, "project_id", "class='text'", 0);
												?>
												<div align='right'>
													<input type='submit' value='Convert' class='button' />
												</div>
											</form>
										<?php
										}
										?>
								</td>
							</tr>
						</table>
					<?php
				}
			?>
		</td>
	</tr>
</table>