<?php

session_start();
include_once("config.php");

$passkey = $_POST['passkey'];
$name = $_POST['name'];
$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$is_verified = False;

try {
	$sql = "SELECT id FROM users WHERE username = :newUserName";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		':newUserName' => $newUserName
	]);

	$is_taken = $insertSql->fetchAll();
} catch (Exception $e) {
	echo "Error!" . $e->getMessage();
}

if (empty($name)) {
	$_SESSION['message'] = "Not authorised";
	header("Location: login.php");
	exit();
}

try {
	$sql = "SELECT * FROM user_creation WHERE password=:passkey";
	$insertSql = $conn->prepare($sql);

	$insertSql->execute([
		":passkey" => $passkey
	]);
	$data = $insertSql->fetch();

	if ($data['is_approved'] != "approved") {
		kick(array('is_error'=>False), "404.php");
	}

	if (!empty($data) && $data['password'] === $passkey && $data['is_claimed'] == 0 && empty($is_taken)) {
		$sql = "DELETE FROM user_creation WHERE id = :id";
		$insertSql = $conn->prepare($sql);

		$insertSql->execute([
			":id" => $data['id'],
		]);

		$is_verified = True;
	} else {
		$_SESSION['message'] = "Already claimed username.";
		header("Location: createAcount.php");
		exit;
	}
} catch (PDOException $e) {
	$_SESSION['message'] = "No such passkey was found.";
	echo "Error!" . $e->getMessage();
	#header("Location: createAcount.php");
	exit;
}

if ($is_verified == True) {
	$sql = "INSERT INTO users (name, username, password) VALUES (:name, :username, :password)";
	$insertSql = $conn->prepare($sql);

	$insertSql->execute([
		":name" => $name,
		":username" => $username,
		":password" => $password,
	]);

	$_SESSION['message'] = "Account created, please log in.";
	header("Location: login.php");
	exit;
}

?>