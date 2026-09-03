<?php

session_start();
include_once('config.php');

$username = $_SESSION['username'];

if (empty($_SESSION['username']) && $_SESSION['came_from'] != "profile.php") {
	kick(array('is_error' => False), "404.php");
} else {
	unset($_SESSION['username']);
	unset($_SESSION['came_from']);

	try {
		$sql = "UPDATE users SET is_active = :is_active WHERE username = :username";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":is_active" => 0,
			":username" => $username
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	kick(array('is_error' => False), "login.php");
}

?>