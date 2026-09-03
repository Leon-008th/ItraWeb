<?php

require __DIR__ . '/vendor/autoload.php';

include_once('config.php');
session_start();

if (empty($_SESSION['username'])) {
	header("Location: login.php");
	exit();
}

$prevUser = $_SESSION['username'];

$is_changing = $_POST['is_changing'] ?? NULL;
$givenPfp = $_FILES['pfp'] ?? NULL;
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$newName = trim($_POST['name'] ?? '');
$newUserName = trim($_POST['username'] ?? '');

$prev_pass = $_POST['prev_pass'] ?? NULL;
$new_pass = $_POST['new_pass'] ?? NULL;

try {
	$sql = "SELECT name, username, pfp_public_id, password FROM users WHERE id = :id";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		":id" => $user_id
	]);

	$verify = $insertSql->fetch();
} catch (Exception $e) {
	error_log($e->getMessage());
	$verify = false;
}

$ver_pass = $verify['password'];

if (!$verify) {
	kick(array('is_error' => False), "404.php");
}

$pfp_PID = $verify['pfp_public_id'];
$prevName = $verify['name'];
$selectedName = $verify['username'];
$sessionName = $_SESSION['username'];

if ($selectedName != $sessionName || empty($is_changing)) {
	kick(array('is_error' => False), "404.php");
}

if ($is_changing == "False") {
	kick(array('is_error' => False), "profile.php");
}

if ($is_changing == "pfp") {
	if (empty($givenPfp) || $givenPfp['error'] !== UPLOAD_ERR_OK) {
		kick(array('is_error' => True, 'm' => "Profile picture can't be empty"), "profile.php");
	}

	$allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
	$maxBytes = 5 * 1024 * 1024;
	$mime = mime_content_type($givenPfp['tmp_name']);

	if (!in_array($mime, $allowedTypes, true) || $givenPfp['size'] > $maxBytes) {
		kick(array('is_error' => True, 'm' => "Please upload a PNG, JPEG, or WebP under 5MB"), "profile.php");
	}

	try {
		$result = $cloudinary
			->uploadApi()
			->upload(
				$givenPfp['tmp_name'],
				['folder' => 'pfps']
			);
	} catch (Exception $e) {
		error_log($e->getMessage());
		kick(array('is_error' => True, 'm' => "Couldn't upload your picture. Try again."), "profile.php");
	}

	try {
		$sql = "UPDATE users SET pfp_url = :url, pfp_public_id = :pfpPID WHERE id = :id";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":url" => $result['secure_url'],
			":pfpPID" => $result['public_id'],
			":id" => $user_id
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	if (!empty($pfp_PID)) {
		try {
			$cloudinary->uploadApi()->destroy($pfp_PID);
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
	}
}

if ($is_changing == "name") {
	if (empty($newName)) {
		kick(array('is_error' => True, 'm' => "Name can't be empty"), "profile.php");
	}

	try {
		$sql = "UPDATE users SET name = :name WHERE id = :id";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":id" => $user_id,
			":name" => $newName
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}

	try {
		$sql = "UPDATE user_creation SET claimed_by = :name WHERE claimed_by = :prevName";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":prevName" => $prevName,
			":name" => $newName
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}
}

if ($is_changing == "username") {
	if (empty($newUserName)) {
		kick(array('is_error' => True, 'm' => "Username can't be empty"), "profile.php");
	}

	if ($newUserName == $prevUser) {
		kick(array('is_error' => True, 'm' => "Can't change to exisiting username"), "profile.php");
	}

	try {
		$sql = "SELECT username FROM users WHERE username = :username";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":username" => $newUserName
		]);

		$username_ver = $insertSql->fetchAll();

		if (empty($username_ver)) {
			$isUser_verified = True;
		} else {
			$isUser_verified = False;
		}
	} catch (PDOException $e) {
		error_log($e->getMessage());
	}

	if ($isUser_verified) {
		try {
			$sql = "UPDATE users SET username = :username WHERE id = :id";
			$insertSql = $conn->prepare($sql);
			$insertSql->execute([
				":id" => $user_id,
				":username" => $newUserName
			]);
		} catch (PDOException $e) {
			error_log($e->getMessage());
		}

		try {
			$sql = "UPDATE comments SET username = :username WHERE username = :prevUser";
			$insertSql = $conn->prepare($sql);
			$insertSql->execute([
				":prevUser" => $prevUser,
				":username" => $newUserName
			]);
		} catch (Exception $e) {
			error_log($e->getMessage());
		}

		try {
			$sql = "UPDATE reporting SET by_user = :username WHERE by_user = :prevUser";
			$insertSql = $conn->prepare($sql);
			$insertSql->execute([
				":prevUser" => $prevUser,
				":username" => $newUserName
			]);
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
	} elseif (!$isUser_verified) {
		kick(array('is_error' => True, 'm' => "Username already taken, select another one."), "profile.php");
	}

	$_SESSION['username'] = $newUserName;
}

if ($is_changing == "password" && !empty($new_pass) && !empty($prev_pass) && password_verify($prev_pass, $ver_pass)) {
	try {
		$sql = "UPDATE users SET password = :new_pass WHERE id = :id";
		$insertSql = $conn->prepare($sql);
		$insertSql->execute([
			":new_pass" => password_hash($new_pass, PASSWORD_DEFAULT),
			":id" => $user_id
		]);
	} catch (Exception $e) {
		error_log($e->getMessage());
	}
} elseif (empty($new_pass) && empty($prev_pass) && $is_changing == "password") {
	kick(array('is_error' => True, 'm' => "Password can't be empty."), "profile.php");
} elseif (!password_verify($prev_pass, $ver_pass) && $is_changing == "password") {
	kick(array('is_error' => True, 'm' => "Password's don't match."), "profile.php");
}

kick(array('is_error' => True, 'm' => "Changes applied"), "profile.php");
