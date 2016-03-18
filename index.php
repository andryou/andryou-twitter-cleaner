<?php
require 'tmhOAuth.php';
require 'tmhUtilities.php';

$tmhOAuth = new tmhOAuth(array(
  'consumer_key'    => 'YOURAPPCONSUMERKEYHERE',
  'consumer_secret' => 'YOURAPPCONSUMERSECRETHERE',
));

$here = tmhUtilities::php_self();
session_start();

function outputError($tmhOAuth) {
	$error = json_decode($tmhOAuth->response['response'], true);
	$_SESSION['error'] = $error['errors'][0]['message'];
}

// clear session
if (isset($_REQUEST['wipe'])) {
	session_destroy();
	header("Location: {$here}");
	exit;
// already got some credentials stored?
} elseif ( isset($_SESSION['access_token']) ) {
	$tmhOAuth->config['user_token']  = $_SESSION['access_token']['oauth_token'];
	$tmhOAuth->config['user_secret'] = $_SESSION['access_token']['oauth_token_secret'];
	if (!isset($_SESSION['screen_name'])) {
		$code = $tmhOAuth->request('GET', $tmhOAuth->url('1.1/account/verify_credentials'));
		if ($code == 200) {
			$resp = json_decode($tmhOAuth->response['response']);
			$_SESSION['screen_name'] = $resp->screen_name;
		} else {
			outputError($tmhOAuth);
		}
	}
// we're being called back by Twitter
} elseif (isset($_REQUEST['oauth_verifier'])) {
	$tmhOAuth->config['user_token']  = $_SESSION['oauth']['oauth_token'];
	$tmhOAuth->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];
	$code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/access_token', ''), array(
		'oauth_verifier' => $_REQUEST['oauth_verifier']
	));
	if ($code == 200) {
		$_SESSION['access_token'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
		unset($_SESSION['oauth']);
		header("Location: {$here}");
	} else {
		outputError($tmhOAuth);
	}
// start the OAuth dance
} elseif ( isset($_REQUEST['authenticate']) || isset($_REQUEST['authorize']) ) {
	$code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/request_token', ''));
	if ($code == 200) {
		$_SESSION['oauth'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
		$method = 'authorize';
		$force  = isset($_REQUEST['force']) ? '&force_login=1' : '';
		$authurl = $tmhOAuth->url("oauth/{$method}", '') .  "?oauth_token={$_SESSION['oauth']['oauth_token']}{$force}";
		header('location: '.$authurl);
		exit;
	} else {
		outputError($tmhOAuth);
	}
}
$ratehit = false;
$incomplete = false;
$refresh = false;

echo '<title>andryou twitter cleaner</title>';
echo '<style type="text/css">.green { color: green; } .red { color: red; }</style>';
echo '<h1>andryou twitter cleaner</h1><p><b><em>- a simple, open-source, database-less Twitter cleaner</em></b></p><p><hr></p>';

if ($_SESSION['error']) {
	if (!$_GET['m']) echo '<b class="red">Error: '.$_SESSION['error'].'</b><br><br>This page will autorefresh in 5 minutes (it is currently '.date('G:i:s').'). Please leave page open.<br><br>If needed: <a href="?wipe=1">log out</a> and try again.<br><script>setTimeout(function() { location.reload() }, 300000);</script>';
	$_SESSION['error'] = false;
}

if ($_SESSION['screen_name']) {
	echo '<a href="./">Home</a> | <a href="?wipe=1">Log Out</a><p><hr></p><h2>Welcome @'.$_SESSION['screen_name'].'!</h2>';
	if (!$_GET['m']) {
		echo '<p>Please choose an option below (warning: there will be no confirmation after you click on one of the below links):</p>';
		echo '<ul><li><a href="?m=1">Remove Likes Only</a></li><li><a href="?m=2">Remove (Re)tweets Only</a></li><li><a href="?m=3">Remove Likes and (Re)tweets</a></li></ul>';
	} else {
		if ($_GET['m'] == '1' || $_GET['m'] == '3') {
			echo '<h3>Removing Likes...</h3>';
			$params = array(
				'screen_name' => $_SESSION['screen_name'],
				'count' => '200'
			);
			processTweets('unlike', $params, 15, '1.1/favorites/list', '1.1/favorites/destroy');
		}
		if ($_GET['m'] == '2' || $_GET['m'] == '3') {
			echo '<h3>Removing (Re)tweets...</h3>';
			$params = array(
				'screen_name' => $_SESSION['screen_name'],
				'contributor_details' => '0',
				'exclude_replies' => '0',
				'include_rts' => '1',
				'count' => '200'
			);
			processTweets('delete', $params, 16, '1.1/statuses/user_timeline', '1.1/statuses/destroy');
		}
		if ($refresh) {
			if (!$incomplete) echo '<h3>CONTINUING IN 5 SECS...</h3><b>Not all tweets were processed.</b> This page will autorefresh in 5 seconds (it is currently '.date('G:i:s').'). Please leave page open.<br><script>setTimeout(function() { location.reload() }, 5000);</script>';
			else echo '<h3>CONTINUING IN 5 MINS...</h3><b>Not all tweets were processed.</b> This page will autorefresh in 5 minutes (it is currently '.date('G:i:s').'). Please leave page open.<br><script>setTimeout(function() { location.reload() }, 300000);</script>';
		} else {
			echo '<h3>Done!</h3><p>Please verify your profile: <a href="https://twitter.com/'.$_SESSION['screen_name'].'" target="_blank">@'.$_SESSION['screen_name'].'</a>. If everything is done, you may revoke access to "andryou twitter cleaner" <a href="https://twitter.com/settings/applications" target="_blank">here</a> (if you want!)</p>';
		}
	}
} else {
	if (!isset($_REQUEST['authorize'])) echo '<h2>Authorization Required</h2><p>To continue, you must allow this page to process tweets.</p><p><a href="?authorize=1">Authorize</a></p>';
}

echo '<p><hr></p><p>By <a href="https://twitter.com/andryou" target="_blank">@andryou</a></p><p><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
</form>
</p>';

function processTweets($action, $params, $limit, $api1, $api2) {
	global $ratehit, $incomplete, $refresh, $tmhOAuth;
	$tweetids = array();
	$lasttweet = null;
	
	$actioned = $action.'d';

	for ($count = 0; $count < $limit; $count++) {
		$code = $tmhOAuth->user_request(array(
			'method' => 'GET',
			'url' => $tmhOAuth->url($api1),
			'params' => $params
		));
		if ($code <> 200) {
			if ($code == 429) {
				$ratehit = true;
				$incomplete = true;
			}
			//echo $tmhOAuth->response['error'].'<br>';
			break;
		}
		$tweets = json_decode($tmhOAuth->response['response'], true);
		if (count($tweets)) {
			$modified = false;
			foreach ($tweets as $tweet) {
				$tweetids[] = $tweet['id'];
				if (!$modified) $modified = true;
			}
			$params['max_id'] = end($tweetids);
		}
		if (!$modified) break;
	}

	$todelete = count($tweetids);

	if ($ratehit) {
		if ($todelete) {
			echo '<b>The rate limit was hit, but going to try to '.$action.' <b>'.$todelete.'</b> tweets...</b><br>';
			$refresh = true;
		} else echo '<b>The rate limit was hit...</b><br>';
		$ratehit = false;
	} else {
		if ($todelete) echo 'Number of tweets to '.$action.': <b>'.$todelete.'</b><br>';
		else echo '<b class="green">There are no tweets to '.$action.' :)</b><br>';
	}

	if ($todelete) {
		$deleted = 0;
		foreach ($tweetids as $tweet) {
			if ($action == 'delete') $api3 = $api2.'/'.$tweet;
			else $api3 = $api2;
			$code = $tmhOAuth->user_request(array(
				'method' => 'POST',
				'url' => $tmhOAuth->url($api3),
				'params' => array(
					'id' => $tweet
				)
			));
			if ($code <> 200) {
				if ($code == 429) {
					$ratehit = true;
					$incomplete = true;
				}
				echo $tmhOAuth->response['error'].'<br>';
				break;
			}
			$deleted++;
		}
		if ($ratehit) {
			echo '<b>The rate limit was hit; below are how many tweets were successfully '.$actioned.'...</b><br>';
			$ratehit = false;
		}
		echo 'Successfully '.$actioned.' <b>'.$deleted.' </b> tweets<br>';
		if ($todelete > $deleted) $refresh = true;
	}
}
?>