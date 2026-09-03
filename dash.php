<?php

session_start();
include_once('config.php');

$username = $_SESSION['username'];

$report_id = $_GET['rid'] ?? $_POST['rid'] ?? NULL;

$warning_amount_exceeded = False;

$function = $_POST['function'] ?? NULL;
$passkey = $_POST['passkey'] ?? NULL;

$warning_string = $_POST['warning'] ?? NULL;
$warning = array("warning" => $warning_string, 'date_warned' => date('F j, Y'));
$user_reported = $_GET['uiq'] ?? NULL;

$deactivate_amount = $_POST['deactivate'] ?? NULL;
$d_reason = $_POST['reason_deactivate'] ?? NULL;

$curr_date = new DateTime(date('F-j-Y'));

if (!empty($deactivate_amount)) {
	$deactivated_until = $curr_date->modify("+{$deactivate_amount} days");
}

$b_reason = $_POST['ban_reason'] ?? NULL;

$searched_user = $_POST['searched_username'] ?? NULL;

if (!empty($report_id)) {
	try {
		$sql = "SELECT * FROM reporting WHERE id = :id";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":id" => $report_id
		]);

		$reportData = $insertSql->fetch();
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	$cid = $reportData['is_reported'] ?? NULL;
}

function deleteReport($rid, $comment) {
	global $conn;

	if (empty($rid)) return;

	try {
		$sql = "DELETE FROM reporting WHERE id = :id";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":id" => $rid
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	if ($comment['is_deleted']) {
		try {
			$sql = "DELETE FROM comments WHERE id = :id";
			$insertSql = $conn->prepare($sql);
			$insertSql->execute([
				":id" => $comment['cid']
			]);
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
	}
}

if ($deactivate_amount > 5) {
	kick(array('is_error' => True, 'm' => "Deactivate amount exceeded"), "profile.php");
}

if ($function == "cancel") {
	kick(array('is_error' => False), "profile.php");
}

try {
	$sql = "SELECT position FROM users WHERE username = :username";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		":username" => $username
	]);

	$AdminData = $insertSql->fetch();
} catch (Exception $e) {
	error_log($e->getMessage());
}

if (empty($AdminData) || $AdminData['position'] != "admin" || empty($function)) {
	kick(array('is_error' => False), "404.php");
}

try {
	$sql = "SELECT is_banned, is_banned_till, warnings FROM users WHERE username = :username";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		":username" => $user_reported
	]);

	$userData = $insertSql->fetch();
} catch (Exception $e) {
	error_log($e->getMessage());
}

if (!empty($userData['is_banned_till'])) {
	kick(array('is_error' => True, 'm' => "User's account already deactivated"), "profile.php");
} elseif ($userData['is_banned'] == 1) {
	kick(array('is_error' => True, 'm' => "User is already banned"), "profile.php");
}

if ($function == "create_passkey" && !empty($passkey)) {
	try {
		$sql = "INSERT INTO user_creation (created_by, date_created, password) VALUES (:created_by, :date_created, :password)";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":created_by" => $username,
			":date_created" => date('F j, Y'),
			":password" => $passkey
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	kick(array('is_error' => True, 'm' => "Passkey created, awaiting owner approval"), "profile.php");
} elseif ($function == "create_passkey" && empty($passkey)) {
	kick(array('is_error' => True, 'm' => "Passkey cannot be empty"), "profile.php");
}

if ($function == "warn" && !empty($warning['warning'])) {

	$given_warnings = json_decode($userData['warnings'], true);
	if (!is_array($given_warnings)) {
		$given_warnings = [];
	}

	$given_warnings[] = $warning;

	$warnings_amount = count($given_warnings);

	$warning_json = json_encode($given_warnings);

	try {
		$sql = "UPDATE users SET warnings = :warnings WHERE username = :username";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":username" => $user_reported,
			":warnings" => $warning_json
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	if ($warnings_amount >= 3) {
		$warning_amount_exceeded = True;
		$b_reason = "User surpassed maximum warnings given";
	} else {
		deleteReport($report_id, array('is_deleted' => True, 'cid' => $cid));
		kick(array('is_error' => True, 'm' => "Warning given"), "profile.php");
	}
}

if ($function == "deactivate" && !empty($deactivated_until) && !empty($d_reason)) {
	$is_D_from_search = $_POST['is_from'] ?? NULL;
	try {
		$sql = "UPDATE users set is_banned_till = :duration, is_banned_reason = :reason WHERE username = :username";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":duration" => $deactivated_until->format('F j, Y'),
			":reason" => $d_reason,
			":username" => $user_reported
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	if ($is_D_from_search != "search") {
		deleteReport($report_id, array('is_deleted' => True, 'cid' => $cid));
	}
	kick(array('is_error' => True, 'm' => "Account deactivated untill {$deactivated_until->format('F j, Y')}"), "profile.php");
} elseif ($function == "deactivate" && empty($d_reason) && empty($deactivated_until)) {
	kick(array('is_error' => True, 'm' => "Fill out every field"), "profile.php");
}

if ($function == "ban" && !empty($b_reason) || $warning_amount_exceeded) {
	try {
		$sql = "UPDATE users SET is_banned = :ban, is_banned_reason = :reason WHERE username = :username";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":ban" => 1,
			":username" => $user_reported,
			":reason" => $b_reason
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	kick(array('is_error' => True, 'm' => "User has been banned for: {$b_reason}."), "profile.php");
} elseif ($function == "ban" && empty($b_reason)) {
	kick(array('is_error' => True, 'm' => "Ban reason cannot be empty."), "profile.php");
}

if ($function == "dismiss") {
	deleteReport($report_id, array('is_deleted' => True, 'cid' => $cid));
	kick(array('is_error' => True, 'm' => "Report dismissed"), "profile.php");
}

if ($function == "search") {
	try {
		$sql = "SELECT * FROM users WHERE username LIKE :username";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":username" => '%' . $searched_user . '%'
		]);

		$searchedUsers = $insertSql->fetchAll();
		$_SESSION['searchedUsers'] = $searchedUsers;
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	kick(array('is_error' => False), "profile.php");
}

?>