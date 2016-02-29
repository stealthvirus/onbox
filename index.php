<?php
	include(__DIR__ . '/system/firewall.php');
	
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: */*");
	
	/* Config */
	$cache = TRUE;
	$route = ltrim(str_replace(trim(str_replace('index.php', '', $_SERVER['SCRIPT_NAME']), '/'), '', $_SERVER['REQUEST_URI']), '/');
	$cookie = 'system/cookie/'. explode(',', isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] . '-' . time())[0] . '.txt';
	$useragent = 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:42.0) Gecko/20100101 Firefox/42.0';

	/* Caching */
	if($cache === TRUE)
	{
		// Auto clean cache
		if(!file_exists('system/cache/clear.cached') && function_exists('file_put_contents'))
		{
			file_put_contents('system/cache/clear.cached', time());
		}
			
		if(filemtime('system/cache/clear.cached') < time() - 1800)
		{
			array_map('unlink', glob("system/cache/*.cached"));
		}
		
		// Use cache
		$encode = md5($route);
		
		if(file_exists('system/cache/' . $encode .'.cached') && function_exists('file_get_contents'))
		{
			if(filemtime('system/cache/' . $encode .'.cached') > time() - 500) // 500 second cache life time
			{
				exit(explode("End -->\r\n", file_get_contents('system/cache/' . $encode .'.cached'))[1]);
			}
		}
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://m.onbox.vn/' . $route);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_AUTOREFERER, FALSE);
	curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);

	if(count($_POST) > 0)
	{
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

	$exec = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	/* Remove XML */
	$exec = str_replace('<?xml version="1.0" encoding="utf-8" ?>', '', $exec);
	/* Replace GA */
	$exec = str_replace('UA-63074386-1', 'UA-72807110-1', $exec);
	/* Replace Domain Grab */
	$exec = str_replace(array('http://m.onbox.vn/', 'http://onbox.vn/', 'http://vip.onbox.vn/'), '', $exec);
	/* Replace CSS */
	$exec = str_replace(array('css/swiper', 'css/style', 'Master/css'), array('http://vip.onbox.vn/css/swiper', 'http://vip.onbox.vn/css/style', 'http://vip.onbox.vn/Master/css'), $exec);
	/* Replace JS */
	$exec = str_replace(array('../js', './js'), 'http://vip.onbox.vn/js', $exec);
	$exec = str_replace(array('"js/jquery.min.js', '"js/jquery.bxSlider.min.js'), '"', $exec);
	/* Replace Icons */
	$exec = str_replace(array('images/mn', 'images/icon'), array('http://vip.onbox.vn/images/mn', 'http://vip.onbox.vn/images/icon'), $exec);
	/* Replace Logo, Favicon */
	$exec = str_replace(array('images/xemdi_logo01.png', '/favicon.ico'), array('http://vip.onbox.vn/images/xemdi_logo01.png', 'http://vip.onbox.vn/favicon.ico'), $exec);
	/* Player */
	$exec = str_replace(array('player/jwplayer', "primary: 'flash'", 'repeat: true'), array('http://vip.onbox.vn/player/jwplayer', "primary: 'html5'", 'repeat: false'), $exec);
	/* Embed */
	$exec = str_replace('player.aspx?code', 'http://' . $_SERVER['HTTP_HOST'] . '/player.aspx?code', $exec);
	/* Prepare */
	$exec = str_replace(array('./http', ':8080/mp4', ':8080/images/xemdi', ':8080/images/chanels', '/home.aspx'), array('http', ':8181/mp4', ':8181/images/xemdi', ':8686/images/chanels', 'home.aspx'), $exec);
	$exec = preg_replace("/<div style=\"text-align:center;color:red;font-weight: bold;\">(.*?)<\/div>/is", '', $exec);
	$exec = preg_replace("/<!--QC-->(.*?)<!--QC-->/is", '', $exec);
	/* No Referrer */
	$exec = str_replace('</head>', '<meta name="referrer" content="never"></head>', $exec);

	/* Cache */
	if($cache === TRUE)
	{
		$encode = md5($route);

		if(function_exists('file_put_contents'))
		{
			file_put_contents('system/cache/' . $encode .'.cached', '<!-- Cache: ' . $route . " End -->\r\n" . trim($exec));
		}
	}

	echo $exec;
?>