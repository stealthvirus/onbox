<?php
	/****************************************************************************************************
	********************    Firewall by Stealth Virus - version 1.0     *********************************
	*****************************************************************************************************/
	define('FW_SECURE_PATH', dirname(__FILE__));
	
	//------------------------------------------------------------------------------------
	//	Config Firewall - Custom configuration active
	//------------------------------------------------------------------------------------
	
	$_fw_config['status'] 						= TRUE; 		// TRUE is active firewall | FALSE is inactive firewall
	$_fw_config['alert_site'] 					= TRUE; 		// TRUE - use alert site local | FALSE - use alert site redirect
	$_fw_config['max_connect'] 					= 0000; 		// Developing - version 1.*
	$_fw_config['level_banned_ip'][0] 			= 60; 			// Time banned IP each level [level 1] [seconds] - custom more level - 0 is forever
	$_fw_config['level_banned_ip'][1] 			= 500; 			// Time banned IP level 2 [seconds]
	$_fw_config['level_banned_ip'][2] 			= 3600; 		// Time banned IP level 3 [seconds]
	$_fw_config['level_banned_ip'][3] 			= 84600; 		// Time banned IP level 4 [seconds]
	$_fw_config['limit_flood_time'] 			= 60; 			// Limit time refreshed verifiable flood [seconds]
	$_fw_config['limit_flood_count'] 			= 40; 			// Limit the number of times proven flood to ban IP
	$_fw_config['time_between_request'] 		= 2; 			// Time between each request [seconds]
	
	//------------------------------------------------------------------------------------
	//	Do not edit anything bellow
	//------------------------------------------------------------------------------------
	
	if($_fw_config['status'] === FALSE) goto endFirewall;

	// Check HTTP_X_FORWARDED_FOR header
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$addrs = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else
	{
		$addrs = $_SERVER['REMOTE_ADDR'];
	}
	
	// Client IP Address
	$clientIP = explode(',', $addrs)[0];
	// Workspace folder path
	$workPath = FW_SECURE_PATH . '/firewall/';
	// Path file client info
	$clientPath = $workPath . '/logs/' . $clientIP . '.addr';
	// Assign logs 
	$logs = array('requestTime' => $_SERVER['REQUEST_TIME'], 'ipAddr' => $addrs, 'requestMethod' => $_SERVER['REQUEST_METHOD'], 'queryString' => $_SERVER['QUERY_STRING'], 'requestUri' => $_SERVER['REQUEST_URI'], 'userAgent' => $_SERVER['HTTP_USER_AGENT'], 'floodCount' => 0, 'floodTime' => $_SERVER['REQUEST_TIME_FLOAT'], 'forbidden' => FALSE, 'forbiddenLevel' => -1);

	// Handling last request of client
	if(file_exists($clientPath))
	{
		$lastRequest = json_decode(file_get_contents($clientPath), TRUE);
		
		if($lastRequest['forbidden'] === TRUE)
		{
			isForbidden:
			
			if(array_key_exists($lastRequest['forbiddenLevel'], $_fw_config['level_banned_ip']))
			{
				$forbiddenTime = $_fw_config['level_banned_ip'][$lastRequest['forbiddenLevel']];
			}
			else
			{
				$forbiddenTime = $logs['requestTime'];
			}
			
			if($lastRequest['requestTime'] < $logs['requestTime'] - $forbiddenTime)
			{
				$lastRequest['forbidden'] = FALSE;
				goto provenFlood;
			}
			
			header('HTTP/1.1 403 Forbidden');
			
			if($_fw_config['alert_site'] === FALSE)
			{
				header('Location: http://antiddos.wapath.com/index?ipaddr='.$clientIP.'&wait='.($lastRequest['requestTime'] + $forbiddenTime - $logs['requestTime']));
			}
			
			include($workPath . 'showalert.php');
		}
		else
		{
			provenFlood:
			
			if($lastRequest['requestTime'] > $logs['requestTime'] - $_fw_config['time_between_request'])
			{
				$lastRequest['floodCount']++;
				$lastRequest['floodTime'] = $logs['floodTime'];
			}
			else
			{
				if($lastRequest['floodTime'] < $logs['requestTime'] - $_fw_config['limit_flood_time'])
				{
					$lastRequest['floodCount'] = 0;
					$lastRequest['floodTime'] = $logs['requestTime'];
				}
			}
			
			if($lastRequest['floodCount'] > $_fw_config['limit_flood_count'])
			{
				$lastRequest['forbidden'] = TRUE;
				$lastRequest['forbiddenLevel']++;
			}
			
			$lastRequest['ipAddr'] = $logs['ipAddr'];
			$lastRequest['userAgent'] = $logs['userAgent'];
			$lastRequest['requestUri'] = $logs['requestUri'];
			$lastRequest['queryString'] = $logs['queryString'];
			$lastRequest['requestTime'] = $logs['requestTime'];
			$lastRequest['requestMethod'] = $logs['requestMethod'];
			
			file_put_contents($clientPath, json_encode($lastRequest));
			
			if($lastRequest['forbidden'] === TRUE)
			{
				goto isForbidden;
			}
		}
	}
	else
	{
		$fp = fopen($clientPath, 'w'); fwrite($fp, json_encode($logs)); fclose($fp);
	}
	
	// End Firewall
	endFirewall:
?>