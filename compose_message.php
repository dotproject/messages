<?php
	global $AppUI, $user_hash, $reply_to_message_id;

	$default_recipient_id = 0;
	$default_title = $default_body = "";
	
	if(isset($reply_to_message_id)){
		$original_message = new CTask();
		$original_message->load($reply_to_message_id);
		
		$default_recipient_id = $original_message->task_owner;
		$default_title        = "RE: ".$original_message->task_name;
		$focus_command        = "document.frmComposeMessage.task_description.focus();";
	} else {
		$focus_command        = "document.frmComposeMessage.task_name.focus();";
	}
	
	$signature = db_loadResult("select user_signature
								from users
								where user_id = '".$AppUI->user_id."'");
	if($signature != ""){
		$default_body = "\n\n$signature";
	}
	
	$user_list = $user_hash;
	unset($user_list[$AppUI->user_id]);
	
?>
<center>
	<form action='index.php?m=messages' method='post' name='frmComposeMessage'>
		<table class='tbl'>
		
			<tr>
				<th><?php echo $AppUI->_("Recipient"); ?></th>
				<td><?php echo arraySelect($user_list, "recipient_user_id", "class='text'", $default_recipient_id); ?></td>
			</tr>
			
			<tr>
				<th><?php echo $AppUI->_("Title"); ?></th>
				<td><input name='task_name' type='text' size='40' value='<?php echo $default_title; ?>' class='text' /></td>
			</tr>
			
			<tr>
				<th colspan='2'><?php echo $AppUI->_("Body"); ?></th>
			</tr>
			<tr>
				<td colspan='2'><textarea name='task_description' class='text' cols='70' rows='10'><?php echo $default_body; ?></textarea></td>
			</tr>
			
			<tr>
				<td colspan='2' align='right'>
					<input type='hidden' name='task_start_date' value='<?php echo date("Y-m-d H:i:s"); ?>' />
					<input type='hidden' name="task_project" value="0" />
					<input type='hidden' name="task_id" value="0" />
					<input type='submit' class='button' value='<?php echo $AppUI->_("Send"); ?>' />
				</td>
			</tr>
			
		</table>
	</form>
</center>

<script language='javascript'>
	<?php echo $focus_command; ?>
</script>