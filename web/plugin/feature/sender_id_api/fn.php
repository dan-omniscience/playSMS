<?php
defined('_SECURE_') or die('Forbidden');


function sender_id_api_hook_webservices_output($operation, $requests, $returns) {
	$u = $requests['u'];
	$h = $requests['h'];

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
		'629' => 'duplicate sender id',
	);

	switch (strtoupper($operation)){
		case 'GET_SENDER_IDS':
			if ($u = webservices_validate($h, $u)) {
				if(function_exists('sender_id_getall')){
					$sender_ids = sender_id_getall($u);
					$json['status'] = 'OK';
					$json['sender_ids'] = $sender_ids;
				}
			} else {
				$json['status'] = 'ERR';
				$json['error'] = '100';
			}
			break;
		case 'GET_DEFAULT_SENDER_ID':
			if ($u = webservices_validate($h, $u)) {
				if(function_exists('sender_id_default_get')){
					$uid = user_username2uid($u);
					$default_sender_id = sender_id_default_get($uid);
					$json['status'] = 'OK';
					$json['sender_id'] = $default_sender_id;
				}
			} else {
				$json['status'] = 'ERR';
				$json['error'] = '100';
			}
			break;
		case 'ADD_SENDER_ID':
			if ($u = webservices_validate($h, $u)) {
				if(function_exists('sender_id_api_add')){
					$uid = user_username2uid($u);
					if($sender_id = core_query_sanitize($requests['sender_id'])){
						$sender_id_desc = core_query_sanitize($requests['desc']);
						$isDefault = core_query_sanitize($requests['default']);
						if($isDefault == ''){
							$isDefault = 0;
						}
						$isApproved = core_query_sanitize($requests['approved']);
						if($isApproved == ''){
							$isApproved = 0;
						}
						//sender_id_add($uid, $sender_id, $sender_id_description = '', $isdefault = 1, $isapproved = 1, $ws = false)
						$addResult = sender_id_api_add($uid, $sender_id, $sender_id_desc, $isDefault, $isApproved, true);
						$status = ($addResult[0]) ? 'OK' : 'ERR';
						if($status === 'ERR'){
							$json['status'] = 'ERR';
							$json['error'] = $addResult[1];
						}
						else{
							$json['status'] = 'OK';
							$json['data'] = array('sender_id' => $sender_id, 'desc' => $sender_id_desc, 'isDefault' => $isDefault, 'isApproved' => $isApproved);
						}
					}
				}
			} else {
				$json['status'] = 'ERR';
				$json['error'] = '100';
			}
			break;

		case 'UPDATE_SENDER_ID':
			if ($u = webservices_validate($h, $u)) {
				if(function_exists('sender_id_api_update')){
					$uid = user_username2uid($u);
					if($sender_id = core_query_sanitize($requests['sender_id'])){
						$sender_id_desc = core_query_sanitize($requests['desc']);
						$isDefault = core_query_sanitize($requests['default']);
						if(!$isDefault == ''){
							$isDefault = '_';
						}
						$isapproved = core_query_sanitize($requests['approved']);
						if($isapproved == ''){
							$isapproved = '_';
						}
						//sender_id_add($uid, $sender_id, $sender_id_description = '', $isdefault = 1, $isapproved = 1)
						$status = (sender_id_api_update($uid, $sender_id, $sender_id_desc, $isDefault, $isapproved, true)) ? 'OK' : 'ERR';
						$json['status'] = $status;
						if($status === 'ERR'){
							$json['error'] = '628';
						}
					}
				}
			} else {
				$json['status'] = 'ERR';
				$json['error'] = '100';
			}
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

/**
 * Add sender ID
 *
 * @param integer $uid
 *        User ID
 * @param string $sender_id
 *        Sender ID
 * @param string $sender_id_description
 *        Sender ID description
 * @param integer $isdefault
 *        Flag 1 for default sender ID
 * @param integer $isapproved
 *        Flag 1 for approved sender ID
 * @return boolean TRUE when new sender ID has been added
 */
function sender_id_api_add($uid, $sender_id, $sender_id_description = '', $isdefault = 1, $isapproved = 1, $ws = false) {
	global $user_config;

	if (sender_id_check($uid, $sender_id)) {

		// not available
		return array(FALSE, '629');
	} else {
		$default = ((auth_isadmin() || $ws) ? (int) $isdefault : 0);
		$approved = ((auth_isadmin() || $ws) ? (int) $isapproved : 0);

		$data_sender_id = array(
			$sender_id => $approved
		);
		$sender_id_description = (trim($sender_id_description) ? trim($sender_id_description) : $sender_id);
		$data_description = array(
			$sender_id => $sender_id_description
		);

		$uid = (((auth_isadmin() || $ws) && $uid) ? $uid : $user_config['uid']);

		if ($uid) {
			registry_update($uid, 'features', 'sender_id', $data_sender_id);
			$ret = registry_update($uid, 'features', 'sender_id_desc', $data_description);
		} else {

			// unknown error
			return array(FALSE, '627');
		}
		if ($ret[$sender_id]) {
			_log('sender ID has been added id:' . $sender_id . ' uid:' . $uid, 2, 'sender_id_add');
		} else {
			_log('fail to add sender ID id:' . $sender_id . ' uid:' . $uid, 2, 'sender_id_add');

			return FALSE;
		}

		// if default and approved
		if ((auth_isadmin() || $ws) && $default && $approved) {
			sender_id_default_set($uid, $sender_id);
		}

		// notify admins if user or subuser
		if ($user_config['status'] == 3 || $user_config['status'] == 4) {
			$admins = user_getallwithstatus(2);
			foreach ($admins as $admin) {
				$message_to_admins = sprintf(_('Username %s with account ID %d has requested approval for sender ID %s'), user_uid2username($uid), $uid, $sender_id);
				recvsms_inbox_add(core_get_datetime(), _SYSTEM_SENDER_ID_, $admin['username'], $message_to_admins);
			}
		}

		// added
		return TRUE;
	}
}

/**
 * Update sender ID
 *
 * @param integer $uid
 *        User ID
 * @param string $sender_id
 *        Sender ID
 * @param string $sender_id_description
 *        Sender ID description
 * @param integer $isdefault
 *        Flag 1 for default sender ID
 * @param integer $isapproved
 *        Flag 1 for approved sender ID
 * @return boolean TRUE when new sender ID has been updated
 */
function sender_id_api_update($uid, $sender_id, $sender_id_description = '', $isdefault = '_', $isapproved = '_', $ws = false) {
	global $user_config;

	if (sender_id_check($uid, $sender_id)) {
		$default = '_';
		if ($isdefault !== '_') {
			$default = ((int) $isdefault ? 1 : 0);
		}

		if ($isapproved !== '_') {
			if (auth_isadmin() || $ws) {
				$approved = ((int) $isapproved ? 1 : 0);
				$data_sender_id = array(
					$sender_id => $approved
				);
			}
		}

		$sender_id_description = (trim($sender_id_description) ? trim($sender_id_description) : $sender_id);
		$data_description = array(
			$sender_id => $sender_id_description
		);

		$uid = (((auth_isadmin() || $ws) && $uid) ? $uid : $user_config['uid']);

		if ($uid) {
			if ($data_sender_id) {
				registry_update($uid, 'features', 'sender_id', $data_sender_id);
			}
			registry_update($uid, 'features', 'sender_id_desc', $data_description);
		} else {

			// unknown error
			return FALSE;
		}

		// set default
		if ($default !== '_') {
			if ((auth_isadmin() || $ws) && $default && $approved) {

				// set default if isadmin, default and approved
				sender_id_default_set($uid, $sender_id);
			} else {

				// set to empty (remove default)
				sender_id_default_set($uid, '');
			}
		}

		return TRUE;
	} else {

		// not found
		return FALSE;
	}
}
