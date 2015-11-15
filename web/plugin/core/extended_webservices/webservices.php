<?php

/**
 * This file is part of playSMS.
 *
 * playSMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * playSMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with playSMS. If not, see <http://www.gnu.org/licenses/>.
 */
defined('_SECURE_') or die('Forbidden');

// parameters
$h = trim($_REQUEST['h']);
$u = trim($_REQUEST['u']);
$p = trim($_REQUEST['p']);

// output format
$format = strtoupper(trim($_REQUEST['format']));

// PV
$to = trim($_REQUEST['to']);
$schedule = trim($_REQUEST['schedule']);
$footer = trim($_REQUEST['footer']);
$nofooter = (trim($_REQUEST['nofooter']) ? TRUE : FALSE);
$type = (trim($_REQUEST['type']) ? trim($_REQUEST['type']) : 'text');
$unicode = (trim($_REQUEST['unicode']) ? trim($_REQUEST['unicode']) : 0);

// PV, INJECT
$from = trim($_REQUEST['from']);
$msg = trim($_REQUEST['msg']);

// INJECT
$recvnum = trim($_REQUEST['recvnum']);
$smsc = trim($_REQUEST['smsc']);

// DS, IN, SX, IX, GET_CONTACT, GET_CONTACT_GROUP
$src = trim($_REQUEST['src']);
$dst = trim($_REQUEST['dst']);
$dt = trim($_REQUEST['dt']);
$c = trim($_REQUEST['c']);
$last = trim($_REQUEST['last']);

// DS
$queue = trim($_REQUEST['queue']);
$smslog_id = trim($_REQUEST['smslog_id']);

// IN, GET_CONTACT, GET_CONTACT_GROUP
$kwd = trim($_REQUEST['kwd']);

// QUERY
$query = trim($_REQUEST['query']);

$log_this = FALSE;

$ws_error_string = array(
	'100' => 'authentication failed',
	'101' => 'type of action is invalid or unknown',
	'102' => 'one or more field empty',
	'103' => 'not enough credit for this operation',
	'104' => 'webservice token is not available',
	'105' => 'webservice token not enable for this user',
	'106' => 'webservice token not allowed from this IP address',
	'200' => 'send message failed',
	'201' => 'destination number or message is empty',
	'400' => 'no delivery status available',
	'401' => 'no delivery status retrieved and SMS still in queue',
	'402' => 'no delivery status retrieved and SMS has been processed from queue',
	'501' => 'no data returned or result is empty',
	'600' => 'admin level authentication failed',
	'601' => 'inject message failed',
	'602' => 'sender id or message is empty',
	'603' => 'account addition failed due to missing data',
	'604' => 'fail to add account',
	'605' => 'account removal failed due to unknown username',
	'606' => 'fail to remove account',
	'607' => 'set parent failed due to unknown username',
	'608' => 'fail to set parent',
	'609' => 'get parent failed due to unknown username',
	'610' => 'fail to get parent',
	'611' => 'account ban failed due to unknown username',
	'612' => 'fail to ban account',
	'613' => 'account unban failed due to unknown username',
	'614' => 'fail to unban account',
	'615' => 'editing account preferences failed due to missing data',
	'616' => 'fail to edit account preferences',
	'617' => 'editing account configuration failed due to missing data',
	'618' => 'fail to edit account configuration',
	'619' => 'viewing credit failed due to missing data',
	'620' => 'fail to view credit',
	'621' => 'adding credit failed due to missing data',
	'622' => 'fail to add credit',
	'623' => 'deducting credit failed due to missing data',
	'624' => 'fail to deduct credit',
	'625' => 'setting login key failed due to missing data',
	'626' => 'fail to set login key',
	'627' => 'failed to add sender id',
	'628' => 'failed to update sender id'
);

if (_OP_) {
	switch (strtoupper(_OP_)) {
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
			if(function_exists('sender_id_add')){
				$uid = user_username2uid($u);
				if($sender_id = core_query_sanitize($_REQUEST['sender_id'])){
					$sender_id_desc = core_query_sanitize($_REQUEST['desc']);
					$isDefault = core_query_sanitize($_REQUEST['default']);
					if(!$isDefault == ''){
						$isDefault = 0;
					}
					$isapproved = 1;
					//sender_id_add($uid, $sender_id, $sender_id_description = '', $isdefault = 1, $isapproved = 1)
					$status = (sender_id_add($uid, $sender_id, $sender_id_desc, $isDefault, $isapproved)) ? 'OK' : 'ERR';
					$json['status'] = $status;
					if($status === 'ERR'){
						$json['error'] = '627';
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
			if (_OP_) {

				// output do not require valid login
				// output must not be empty
				$ret = webservices_output(_OP_, $_REQUEST, $returns);

				if ($ret['modified'] && $ret['param']['content']) {
					ob_end_clean();
					if ($ret['param']['content-type'] && $ret['param']['charset']) {
						header('Content-type: ' . $ret['param']['content-type'] . '; charset=' . $ret['param']['charset']);
					}
					_p($ret['param']['content']);
				}
				exit();
			} else {

				// default error return
				$json['status'] = 'ERR';
				$json['error'] = '102';
			}
	}
}

// add an error_string to json response
$json['error_string'] = $ws_error_string[$json['error']];

// add timestamp
$json['timestamp'] = mktime();

if ($log_this) {
	logger_print("u:" . $u . " ip:" . $_SERVER['REMOTE_ADDR'] . " op:" . _OP_ . ' timestamp:' . $json['timestamp'] . ' status:' . $json['status'] . ' error:' . $json['error'] . ' error_string:' . $json['error_string'], 3, "webservices");
}

if ($format == 'SERIALIZE') {
	ob_end_clean();
	header('Content-Type: text/plain');
	_p(serialize($json));
} else if ($format == 'XML') {
	$xml = core_array_to_xml($json, new SimpleXMLElement('<response/>'));
	ob_end_clean();
	header('Content-Type: text/xml');
	_p($xml->asXML());
} else {
	ob_end_clean();
	header('Content-Type: application/json');
	_p(json_encode($json));
}
