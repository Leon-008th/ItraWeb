<?php
session_start();
include_once('config.php');

$not_allowed = array("Shit", "Fuck", "Pussy", "Qi", "Mut", "Retard", "Fag", "Nigga", "Peder");
$pattern = '/\b(' . implode('|', array_map('preg_quote', $not_allowed)) . ')\b/i';

$username = $_SESSION['username'];
$comment = $_POST['comment'];
$came_from = $_SESSION['viewedMovieURL'];
$movieId = $_SESSION['movie_id'];

if (empty($comment)) {
	$_SESSION['message'] = "Not Authorised";
	header("Location: login.php");
	exit();
}

if (!preg_match($pattern, $comment)) {
	$sql = "INSERT INTO comments (username, comment, dateC, movie_id) VALUES (:username, :comment, :dateC, :movie_id)";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		":username" => $username,
		":comment" => $comment,
		":dateC" => date('l, F j, Y'),
		":movie_id" => $movieId
	]);

	header("Location: $came_from");
	exit();
} else {
	$_SESSION['message'] = "Comment contains message against our terms of service.";
	header("Location: $came_from");
	exit();
}

?>