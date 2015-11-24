<?php
defined('_SECURE_') or die('Forbidden');


function api_login_auth_hook_webservices_output($operation, $requests, $returns) {
	$u = $requests['u'];
	$p = $requests['p'];

	$ws_error_string = array(
		'100' => 'authentication failed',
		// '101' => 'type of action is invalid or unknown',
		// '102' => 'one or more field empty',
		// '103' => 'not enough credit for this operation',
		// '104' => 'webservice token is not available',
		// '105' => 'webservice token not enable for this user',
		// '106' => 'webservice token not allowed from this IP address',
		// '200' => 'send message failed',
		// '201' => 'destination number or message is empty',
		// '400' => 'no delivery status available',
		// '401' => 'no delivery status retrieved and SMS still in queue',
		// '402' => 'no delivery status retrieved and SMS has been processed from queue',
		// '501' => 'no data returned or result is empty',
		// '600' => 'admin level authentication failed',
		// '601' => 'inject message failed',
		// '602' => 'sender id or message is empty',
		// '603' => 'account addition failed due to missing data',
		// '604' => 'fail to add account',
		// '605' => 'account removal failed due to unknown username',
		// '606' => 'fail to remove account',
		// '607' => 'set parent failed due to unknown username',
		// '608' => 'fail to set parent',
		// '609' => 'get parent failed due to unknown username',
		// '610' => 'fail to get parent',
		// '611' => 'account ban failed due to unknown username',
		// '612' => 'fail to ban account',
		// '613' => 'account unban failed due to unknown username',
		// '614' => 'fail to unban account',
		// '615' => 'editing account preferences failed due to missing data',
		// '616' => 'fail to edit account preferences',
		// '617' => 'editing account configuration failed due to missing data',
		// '618' => 'fail to edit account configuration',
		// '619' => 'viewing credit failed due to missing data',
		// '620' => 'fail to view credit',
		// '621' => 'adding credit failed due to missing data',
		// '622' => 'fail to add credit',
		// '623' => 'deducting credit failed due to missing data',
		// '624' => 'fail to deduct credit',
		// '625' => 'setting login key failed due to missing data',
		// '626' => 'fail to set login key',
		'627' => 'failed to add new sender id',
		'628' => 'failed to update sender id',
	);

	switch (strtoupper($operation)){
		case 'AUTH':
		$user = array();

		if (preg_match('/^(.+)@(.+)\.(.+)$/', $u)) {
			if (auth_validate_email($u, $p)) {
				$u = user_email2username($u);
				$user = user_getdatabyusername($u);
			}
		} else {
			if (auth_validate_login($u, $p)) {
				$user = user_getdatabyusername($u);
			}
		}

		if ($user['uid']) {
			$continue = false;
			$json['status'] = 'ERR';
			$json['error'] = '106';
			$ip = explode(',', $user['webservices_ip']);
			if (is_array($ip)) {
				foreach ($ip as $key => $net) {
					if (core_net_match($net, $_SERVER['REMOTE_ADDR'])) {
						$continue = true;
					}
				}
			}
			if ($continue) {
				$continue = false;
				if ($token = $user['token']) {
					$continue = true;
				} else {
					$json['status'] = 'ERR';
					$json['error'] = '104';
				}
			}
			if ($continue) {
				if ($user['enable_webservices']) {
					$json['status'] = 'OK';
					$json['error'] = '0';
					$json['user'] = $user;
					$json['token'] = $token;
				} else {
					$json['status'] = 'ERR';
					$json['error'] = '105';
				}
			}
		} else {
			$json['status'] = 'ERR';
			$json['error'] = '100';
		}
		$log_this = TRUE;
			break;
		default :
			return FALSE;
	}

	$json['error_string'] = $ws_error_string[$json['error']];

	$returns['modified'] = TRUE;
	$returns['param']['content'] = json_encode($json);
	$returns['param']['content-type'] = 'text/plain';

	return $returns;
}
