<?php
	//	Headers
	require_once '../objects/header_post.php';
	
	//	Connection
	require_once '../config/connection.php';
	
	//	Users object
	require_once '../objects/users.php';
	
	//	Instantiate users object and retrieve connection
	$db = new Database();
	$conn = $db->connect();
	
	//	Set connection for users table
	$users = new Users($conn);
	
	//	Get posted data
	$data = json_decode(file_get_contents("php://input"));
	
	//	Set user's input values
	$users->full_name = $data->full_name;
	$users->email = $data->email;
	$users->student_id = $data->student_id;
	$users->password_mmuone = $data->password_mmuone;
	$users->date_registered = date('Y-m-d H:i:s');
	
	//	Create the user
	if ($users->create())
	{
		//	Succeeded
		//	Set values for message array
		//	No code if succeeded
		$users->message['status'] = "1";
		$users->message['code'] = $errorCode;
		$users->message['message'] = "User successfully registered";
	}
	else
	{
		//	Failed
		//	Set error
		if (!empty($users->error))
		{
			$errorText = $users->getErrorText();
			$errorCode = $users->error;
		}
	
		//	Set values for message array
		$users->message['status'] = "0";
		$users->message['code'] = $errorCode;
		$users->message['message'] = $errorText;
	}

	//	Echo JSON message
	$users->echoMessage();