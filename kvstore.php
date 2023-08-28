<?php

//
// Copyright (c) 2023 Ron Gilbert
//
// You are free to use this in anyway to want just don't claim it is yours.
//
// If you found this useful you can express your appreciation by buying Thimbleweed Park.
//

include "kvstore_inc.php";

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

function fatal_error($text) {
 	global $json_response;
  error_log("ERROR: ".$text);
 	if ($json_response) echo "{ \"error\": true }";
  die;
}

function public_error($text) {
 	global $json_response;
  error_log("ERROR: ".$text);
 	if ($json_response) echo "{ \"error\": \"".$text."\" }";
  die;
}

function getValue($db, $project, $key, $json=false) {
	if (strlen($key) == 0) public_error("Bad key");
	$stmt = $db->prepare("SELECT * FROM `kv_store` WHERE `project` = ? AND `key` = ? ORDER BY `id` DESC LIMIT 1");
	if (!$stmt) fatal_error($db->lastErrorMsg());
	$stmt->bindParam(1, $project, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$stmt->bindParam(2, $key, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$result = $stmt->execute();
	if (!$result) fatal_error($db->lastErrorMsg());
	$row = $result->fetchArray(SQLITE3_ASSOC);
	$stmt->close();
	if ($json) {
		return json_encode([ 'key' => $key, 'value' => $row['value'] ], JSON_UNESCAPED_SLASHES);
	} else {
		return $row['value'];
	}
}

function putValue($db, $project, $key, $value, $json=false) {
	if (strlen($key) == 0) fatal_error("Bad key");
	$stmt = $db->prepare("INSERT INTO `kv_store` (`project`, `key`, `value`, `time`) VALUES (?, ?, ?, datetime())");
	if (!$stmt) fatal_error($db->lastErrorMsg());
	$stmt->bindParam(1, $project, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$stmt->bindParam(2, $key, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$stmt->bindParam(3, $value, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$stmt->execute() || fatal_error($db->lastErrorMsg());
	$stmt->close();
	if ($json) {
		return json_encode([ 'key' => $key, 'value' => $value ], JSON_UNESCAPED_SLASHES);
	} else {
		return $value;
	}
}

function addValue($db, $project, $key, $value, $json=false) {
	if (strlen($key) == 0) public_error("Bad key");
	$stmt = $db->prepare("SELECT * FROM `kv_store` WHERE `project` = ? AND `key` = ? ORDER BY `id` DESC LIMIT 1");
	if (!$stmt) fatal_error($db->lastErrorMsg());
	$stmt->bindParam(1, $project, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$stmt->bindParam(2, $key, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$result = $stmt->execute();
	if (!$result) fatal_error($db->lastErrorMsg());
	$row = $result->fetchArray(SQLITE3_ASSOC);
	$stmt->close();
	$value = strval(intval($row['value'])+intval($value));
	$stmt = $db->prepare("INSERT INTO `kv_store` (`project`, `key`, `value`, `time`) VALUES (?, ?, ?, datetime())");
	if (!$stmt) fatal_error($db->lastErrorMsg());
	$stmt->bindParam(1, $project, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$stmt->bindParam(2, $key, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$stmt->bindParam(3, $value, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$stmt->execute() || fatal_error($db->lastErrorMsg());
	$stmt->close();
	if ($json) {
		return json_encode([ 'key' => $key, 'value' => $value ], JSON_UNESCAPED_SLASHES);
	} else {
		return $value;
	}
}

if (isset($_REQUEST['project']) && isset($_REQUEST['secret']) && isset($_REQUEST['action']))
{
	$json_response = isset($_REQUEST['json'])?true:false;
	$project = $_REQUEST['project'];
	$secret = $_REQUEST['secret'];
	$action = $_REQUEST['action'];

	$db = new SQLite3($DB_NAME);
	if (!$db) fatal_error("Can't open db: $DB_NAME");

	$stmt = $db->prepare("SELECT * FROM auth WHERE `project` = ? AND `secret` = ? LIMIT 1");
	if (!$stmt) fatal_error($db->lastErrorMsg());
	$stmt->bindParam(1, $project, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$stmt->bindParam(2, $secret, SQLITE3_TEXT) || fatal_error($db->lastErrorMsg());
	$result = $stmt->execute();
	if (!$result) fatal_error($db->lastErrorMsg());
	$row = $result->fetchArray(SQLITE3_ASSOC);
	if ($row == false || $project != $row['project']) {
	 	public_error("Bad auth");
	}
	$stmt->close();

	if ($action == "get") {
		$key = $_REQUEST['key'];
		echo getValue($db, $project, $key, $json_response);
		die;
	}

	if ($action == "put") {
		$key = $_REQUEST['key'];
		$value = $_REQUEST['value'];
		echo putValue($db, $project, $key, $value, $json_response);
		die;
	}

	if ($action == "inc") {
		$key = $_REQUEST['key'];
		echo addValue($db, $project, $key, 1, $json_response);
		die;
	}

	if ($action == "dec") {
		$key = $_REQUEST['key'];
		echo addValue($db, $project, $key, -1, $json_response);
		die;
	}

	if ($action == "add") {
		$key = $_REQUEST['key'];
		$value = $_REQUEST['value'];
		echo addValue($db, $project, $key, $value, $json_response);
		die;
	}
}

if ($json_response) echo "{ \"error\": \"Param error\" }";

?>
