<?php
defined('_SECURE_') or die('Forbidden');


function sender_id_api_hook_webservices_output($operation, $requests, $returns) {
	$u = $requests['u'];
	$h = $requests['h'];
	switch (strtoupper($operation)){
		case 'GET_SENDER_IDS':
			if(function_exists('sender_id_getall')){
				$sender_ids = sender_id_getall($u);
				$json['status'] = 'OK';
				$json['sender_ids'] = $sender_ids;
			}
			break;
		case 'GET_DEFAULT_SENDER_ID':
			if(function_exists('sender_id_default_get')){
				$uid = user_username2uid($u);
				$default_sender_id = sender_id_default_get($uid);
				$json['status'] = 'OK';
				$json['sender_id'] = $default_sender_id;
			}
			break;
		case 'ADD_SENDER_ID':
			if(function_exists('sender_id_api_add')){
				$uid = user_username2uid($u);
				if($sender_id = core_query_sanitize($_REQUEST['sender_id'])){
					$sender_id_desc = core_query_sanitize($_REQUEST['desc']);
					$isDefault = core_query_sanitize($_REQUEST['default']);
					if($isDefault == ''){
						$isDefault = 0;
					}
					$isApproved = core_query_sanitize($_REQUEST['approved']);
					if($isApproved == ''){
						$isApproved = 0;
					}
					//sender_id_add($uid, $sender_id, $sender_id_description = '', $isdefault = 1, $isapproved = 1, $ws = false)
					$addResult = sender_id_add($uid, $sender_id, $sender_id_desc, $isDefault, $isApproved, true);
					$status = ($addResult) ? 'OK' : 'ERR';
					if($status === 'ERR'){
						$json['status'] = 'ERR';
						$json['error'] = '627';
					}
					else{
						$json['status'] = 'OK';
						$json['data'] = array('sender_id' => $sender_id, 'desc' => $sender_id_desc, 'isDefault' => $isDefault, 'isApproved' => $isApproved);
					}
				}
			}
			break;

		case 'UPDATE_SENDER_ID':
			if(function_exists('sender_id_add')){
				$uid = user_username2uid($u);
				if($sender_id = core_query_sanitize($_REQUEST['sender_id'])){
					$sender_id_desc = core_query_sanitize($_REQUEST['desc']);
					$isDefault = core_query_sanitize($_REQUEST['default']);
					if(!$isDefault == ''){
						$isDefault = '_';
					}
					$isapproved = core_query_sanitize($_REQUEST['approved']);
					if($isapproved == ''){
						$isapproved = '_';
					}
					//sender_id_add($uid, $sender_id, $sender_id_description = '', $isdefault = 1, $isapproved = 1)
					$status = (sender_id_update($uid, $sender_id, $sender_id_desc, $isDefault, $isapproved)) ? 'OK' : 'ERR';
					$json['status'] = $status;
					if($status === 'ERR'){
						$json['error'] = '628';
					}
				}
			}
			break;
		default :
			return FALSE;
	}

	$returns['modified'] = TRUE;
	$returns['param']['content'] = json_encode($json);
	$returns['param']['content-type'] = 'text/plain';

	return $returns;
}

function sender_id_api_add($uid, $sender_id, $sender_id_description = '', $isdefault = 1, $isapproved = 1, $ws = false) {
	global $user_config;

	if (sender_id_check($uid, $sender_id)) {

		// not available
		return FALSE;
	} else {
		if(!$ws){
			$default = (auth_isadmin() ? (int) $isdefault : 0);
			$approved = (auth_isadmin() ? (int) $isapproved : 0);
		}
		else{
			$default = (int) $isdefault;
			$approved = (int) $isapproved;
		}


		$data_sender_id = array(
			$sender_id => $approved
		);
		$sender_id_description = (trim($sender_id_description) ? trim($sender_id_description) : $sender_id);
		$data_description = array(
			$sender_id => $sender_id_description
		);

		if(!$ws){
				$uid = ((auth_isadmin() && $uid) ? $uid : $user_config['uid']);
		}
		if ($uid) {
			registry_update($uid, 'features', 'sender_id', $data_sender_id);
			$ret = registry_update($uid, 'features', 'sender_id_desc', $data_description);
		} else {

			// unknown error
			return FALSE;
		}

		if ($ret[$sender_id]) {
			_log('sender ID has been added id:' . $sender_id . ' uid:' . $uid, 2, 'sender_id_add');
		} else {
			_log('fail to add sender ID id:' . $sender_id . ' uid:' . $uid, 2, 'sender_id_add');

			return FALSE;
		}

		if(!$ws){
			// if default and approved
			if (auth_isadmin() && $default && $approved) {
				sender_id_default_set($uid, $sender_id);
			}
		}
		else{
			if ($default && $approved) {
				sender_id_default_set($uid, $sender_id);
			}
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
