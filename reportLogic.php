<?php
session_start();
include_once('config.php');

if (empty($_SESSION['username'])) {
	$_SESSION['message'] = "Not authorised";
	header("Location: login.php");
	exit();
}

$comment_id = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$came_from = $_SESSION['viewedMovieURL'] ?? 'home.php';
$reason = trim($_POST['reason'] ?? '');

if (!$comment_id) {
	header("Location: 404.php");
	exit();
}

if (empty($reason)) {
	$_SESSION['message'] = "Please provide a reason for the report.";
	header("Location: $came_from");
	exit();
}

try {
	$sql = "SELECT * FROM comments WHERE id = :cid";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		":cid" => $comment_id
	]);
	$comment = $insertSql->fetch();
} catch (Exception $e) {
	error_log($e->getMessage());
	$comment = false;
}

if (!$comment) {
	header("Location: 404.php");
	exit();
}

try {
	$sql = "INSERT INTO reportings (comment, is_reported, by_user, reason, dateR, commented_by) VALUES (:comment, :is_reported, :by_user, :reason, :dateR, :commented_by)";
	$insertSql = $conn->prepare($sql);

	$insertSql->execute([
		":comment" => $comment['comment'],
		":is_reported" => $comment['id'],
		":by_user" => $_SESSION['username'],
		":reason" => $reason,
		":dateR" => date('F j, Y'),
		":commented_by" => $comment['username']
	]);

	$_SESSION['message'] = "Report submitted.";
	header("Location: $came_from");
	exit();
} catch (Exception $e) {
	error_log($e->getMessage());
	$_SESSION['message'] = "Something went wrong submitting your report.";
	header("Location: $came_from");
	exit();
}