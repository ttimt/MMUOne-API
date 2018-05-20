<?php
	/**
	 * Created by PhpStorm.
	 * User: Timothy
	 * Date: 29/3/2018
	 * Time: 12:13 AM
	 */

	//	Header
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Headers: access");
	header("Access-Control-Allow-Methods: GET");
	header("Access-Control-Allow-Credentials: true");
	header("Content-Type: application/json; charset=UTF-8");

	//	Connection
	require_once '../config/connection.php';

	//	Users object
	require_once '../objects/users.php';

	//	Portal object
	require_once '../objects/portal.php';

	//	Get Simple HTML DOM library
	require_once '../library/html_dom.php';

	//	Include Message Sender function
	require_once '../objects/messageSender.php';

	//	Include cURL function: curl(url, postRequest, data, cookie)
	require_once '../objects/curl.php';

	//	New Simple HTML DOM object
	$htmlDOM = new simple_html_dom();

	//	Instantiate users object and retrieve connection
	$db = new Database();
	$conn = $db->connect();

	//	Set up Portal object
	$portal = new Portal($conn);

	//	Set error
	$error = 000000;

	//	Set cookie
	$cookie = tempnam("/cookie", "PORTAL_");

	//	Get $_GET data
	//	Check if tab provided
	if (empty($_GET['tab']))
	{
		//	TODO Set error

		//	Echo JSON message

		//	Kill
		die("No tab provided");
	}
	$tab = $_GET['tab'];

	//	Check if Student ID provided
	if (empty($_GET['student_id']))
	{
		//	TODO Set error

		//	Echo JSON message

		//	Kill
		die("No student ID specified");
	}
	$student_id = $_GET['student_id'];

	//	Check if cookie path provided
	if (empty($_GET['cookie']))
	{
		//	TODO Set error and login

		//	Echo JSON message

		//	Kill
		die("No student ID specified");
	}
	file_put_contents($cookie, urldecode($_GET['cookie']));

	//	Check if token provided
	//	Token equals to page number
	if (!empty($_GET['token']))
	{
		$token = $_GET['token'];
	}

	if (empty($token))
	{
		//	If time getting data, no token exist
		//	Get all the bulletin news
		//	Get bulletin with specific page: Page 0 for 1-10 news, 1 for 11-20 news
		//	URL of MMU Portal's Bulletion Boarx
		$url = "https://online.mmu.edu.my/bulletin.php";

		//	cURL
		$curl = NULL;

		//	It is not a POST request
		$postRequest = FALSE;

		//	Execute cURL requets
		$curlResult = curl($curl, $url, $postRequest, $data = array(), $cookie);

		//	Set bulletin paged array
		//	bulletin contains max 9 news, page 0 for no pages 1 for more pages
		$bulletinPaged["bulletin"] = array();
		$bulletinPaged["hasPage"] = 0;
		$bulletinPaged["size"] = 0;

		if (!$curlResult[0])
		{
			$errorMessage = $curlResult[1];

			//	TODO ADD ERROR MESSAGE
			//	Get bulletin failed
			$error = 20602;

			//TODO check return result

			// TODO echo error
		}
		else if ($curlResult[0])
		{
			//	If bulletin data retrieved successfully
			//	Load the string to HTML DOM without stripping /r/n tags
			$htmlDOM->load($curlResult[1], TRUE, FALSE);

			//	Find the desired input field
			$bulletin = $htmlDOM->find("div[id=tabs-{$tab}] div.bulletinContentAll");

			//	Get old hash
			$portal->getHash($student_id, $tab);
			$oldHash = $portal->hash;

			//	Get latest hash
			$latestHash = hash('sha256', $bulletin[0]->plaintext);

			//	Set the latest bulletin news
			foreach ($bulletin as $key => $bulletinSingle)
			{
				//	Get new hash
				$currentHash = hash('sha256', $bulletinSingle->plaintext);

				//	If current new news is already in the database, return
				if ($oldHash == $currentHash)
				{
					break;
				}
				else
				{
					//	Push the plaintext into bulletinPaged's bulletin
					array_push($bulletinPaged["bulletin"], $bulletinSingle->plaintext);

					//	Increment the bulletin size by 1
					$bulletinPaged["size"] = $bulletinPaged["size"] + 1;

					//	If max key reached
					if ($key == 9)
					{
						//	Set more pages to true or 1
						$bulletinPaged["hasPage"] = 1;

						//	Break the foreach loop
						break;
					}
				}
			}

			//	Clear the htmlDOM memory
			$htmlDOM->clear();

			//	Update table with data and latest hash
			$portal->updateTable($student_id, $tab, json_encode($bulletin), $latestHash);
		}

		//	Echo result as JSON
		//	-	bulletin data
		//	-	hasPage
		//	-	size
		messageSender(1, $bulletinPaged);
	}
	else
	{
		//	If token exist, get next page of data and echo as JSON
		//	$token is page number
		//	Get bulletin data
		$bulletin = $portal->getBulletin($student_id, $tab);

		//	Load the string to HTML DOM without stripping /r/n tags
		$htmlDOM->load($bulletin, TRUE, FALSE);

		//	Find the desired input field
		$bulletin = $htmlDOM->find("div[id=tabs-{$tab}] div.bulletinContentAll");

		//	Counter to skip the bulletin data that are already sent
		$pageCount = 0;

		//	Set the next 10 bulletin data
		foreach ($bulletin as $key => $bulletinSingle)
		{
			if ($pageCount != $token)
			{
				break;
			}

			//	Increment the counter
			$pageCount++;

			//	Push the plaintext into bulletinPaged's bulletin
			array_push($bulletinPaged["bulletin"], $bulletinSingle->plaintext);

			//	Increment the bulletin size by 1
			$bulletinPaged["size"] = $bulletinPaged["size"] + 1;

			//	If max key reached
			if ($key == 9)
			{
				//	Set more pages to true or 1
				$bulletinPaged["hasPage"] = 1;

				//	Break the foreach loop
				break;
			}
		}
	}
