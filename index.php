<?php
	/**
	* Ok, here is the deal. There is no need to create a new table for internal messages if we have 
	* a task table that happens to have all the information we require for this purpose. So I'll
	* use the tasks table with the task_project set to 0
	*/
	define("READ_MESSAGES_TAB", 3);
	define("SENT_MESSAGES_TAB", 2);
	define("COMPOSE_MESSAGE_TAB", 1);
	
	error_reporting(E_ALL);
	global $AppUI;
	
	require_once($AppUI->getModuleClass("tasks"));

	$sql = "select user_id, concat_ws(' ', user_first_name, user_last_name)
			from users as u left join permissions as p on u.user_id = p.permission_user
			where !isnull(p.permission_user)
			group by user_id
			order by user_first_name";
	$user_hash = db_loadHashList($sql);
	
	// Let's start showing off the information
	$titleBlock = new CTitleBlock( 'Internal messages', 'messages.png', $m, "$m.$a" );
	$titleBlock->addCell("Internal messages");
	$titleBlock->show();
	
	if( ($message_tab = dpGetParam($_GET, "tab", -1)) != -1){
		$AppUI->setState("message_tab", $_GET["tab"]);
	} else {
		$message_tab 		= $AppUI->getState("message_tab");
		if(!$message_tab) {
			$message_tab = 0;
		}
	}
	
	$show_read_messages = false;
	$show_sent_messages = false;
	
	if (dpGetParam($_POST, "task_description", "") != "") {
		$new_message = new CTask();
		$new_message->bind($_POST);
		
		$new_message->task_owner = $AppUI->user_id;
		
		$error_message = $new_message->store();
		if(is_null($error_message)){
			$sql  = "delete from user_tasks where task_id ='$new_message->task_id';";
			db_exec($sql);
			
			$sql = "insert into user_tasks (task_id, user_id) values ('$new_message->task_id','".$_POST["recipient_user_id"]."')";
			db_exec($sql);

			$recipient_email = db_loadResult("select user_email from users where user_id = '".$_POST["recipient_user_id"]."'");
			
			if(dPgetParam($_POST, "send_email", "") != "" && $recipient_email != ""){
        		$mail = new Mail();
        		$mail->Subject($AppUI->getConfig("company_name")." - ".$AppUI->_("Internal message"));
        		
        		$body = $AppUI->getConfig("company_name")." - ".$AppUI->_("Internal message");
        		$body .= "\n\n";
        		$body .= $new_message->task_name."\n";
        		$body .= $new_message->task_description;
        		$body .= "\n-----";
        		
        		$mail->Body($body);
        		$mail->From ( '"' . $AppUI->user_first_name . " " . $AppUI->user_last_name 
        			. '" <' . $AppUI->user_email . '>'
        		);
        		
        		$mail->To($recipient_email);
        		if ($mail->ValidEmail($recipient_email)) {
        		  $mail->send();
        		}
        		
        		$AppUI->setState("send_email_checked", "checked");
			} else {
			    $AppUI->setState("send_email_checked", "");
			}
			
			$AppUI->setMsg("Message sent succesfully");
		} else {
			$AppUI->setMsg("Message was not sent [$error_message]", UI_MSG_ERROR);
		}
	}
	
	if(dpGetParam($_GET, "message_id", 0) > 0) {
		$view_message = new CTask();
		$view_message->load($_GET["message_id"]);
		
		// This is for security reasons
		$user_present_in_recipients = db_loadResult("select count(user_id)
													 from user_tasks
													 where task_id = '".$view_message->task_id."'
													 and user_id = '".$AppUI->user_id."'");
	
		if(!$user_present_in_recipients && $view_message->task_owner != $AppUI->user_id) {
			unset($view_message);
		}
		
		if(dpGetParam($_GET, "action", "") != ""){
			
			$unset_view_message = false;
			switch($_GET["action"]) {
				case "mark_as_read":
					if($user_present_in_recipients){
						$view_message->task_status   = '-1';
						$view_message->task_priority = 0;
						$message_tab                 = 0;
						$unset_view_message          = true;
					}
					break;
				case "mark_as_unread":
					if($user_present_in_recipients){
						$view_message->task_status = '0';
					}
					break;
				case "resend_message":
					if($view_message->task_owner == $AppUI->user_id){
						$view_message->task_status     = '0';
						$view_message->task_start_date = date("Y-m-d H:i:s");
					}
					break;
				case "delete":
					if($view_message->task_owner == $AppUI->user_id || $user_present_in_recipients){
						$view_message->delete();
						$unset_view_message = true;
					}
					break;
				case "convert_to_task":
					if($user_present_in_recipients){
						$view_message->task_project = $_POST["project_id"];
						$view_message->task_status  = '0';
					}
					break;
			}
			
			$view_message->store();
			if($unset_view_message){
				unset($view_message);
			}
		}
		
		if(isset($view_message)) {
			if($view_message->task_owner == $AppUI->user_id){
				$message_tab = SENT_MESSAGES_TAB;
				
			} else if($view_message->task_status == -1){
				$message_tab = READ_MESSAGES_TAB;
			}
		}
		
	}
	
	if(dPgetParam($_GET, "reply_to_message_id", 0) > 0){
		$user_present_in_recipients = db_loadResult("select count(user_id)
														 from user_tasks
														 where task_id = '".$_GET["reply_to_message_id"]."'
														 and user_id = '".$AppUI->user_id."'");
		if($user_present_in_recipients){
			$message_tab = COMPOSE_MESSAGE_TAB;
			$reply_to_message_id = $_GET["reply_to_message_id"];
		}
	}
	
	$AppUI->setState("message_tab", $message_tab);
	switch($message_tab){
		case SENT_MESSAGES_TAB:
			$show_sent_messages = true;
			break;
		case READ_MESSAGES_TAB:
			$show_read_messages = true;
			break;
	}
	
	$tabBox = new CTabBox("?m=messages", "{$AppUI->cfg['root_dir']}/modules/messages/", $message_tab);
	$tabBox->add("vw_messages", "Incoming messages");
	$tabBox->add("compose_message", "Compose message");
	$tabBox->add("vw_messages", "Sent messages");
	$tabBox->add("vw_messages", "Viewed messages");
	$tabBox->show();
	
?>