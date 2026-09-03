<?php

session_start();
include_once('config.php');

$comment_id = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$came_from = $_SESSION['viewedMovieURL'] ?? '';
$came_fromMovie = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

if (!$comment || empty($came_from) || empty($came_fromMovie) || (int)$comment['movie_id'] !== $came_fromMovie) {
	header("Location: 404.php");
	exit();
}

if (empty($_SESSION['username'])) {
	$_SESSION['message'] = "Not authorised";
	header("Location: login.php");
	exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Report comment — ItraDB</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="style.css">
	<style>
		.report-page-body { padding-top: calc(var(--nav-h) + 24px); }
		.report-wrap {
			max-width: 520px;
			margin: 0 auto;
			padding: 0 20px 60px;
		}
		.report-wrap h3 {
			font-size: 1.1rem;
			font-weight: 600;
			margin-bottom: 16px;
		}
		.reportingComment {
			background: var(--surface);
			border-radius: var(--radius);
			padding: 16px 18px;
			margin-bottom: 20px;
		}
		.reportingComment h4 { font-size: .875rem; margin-bottom: 2px; }
		.reportingComment > span { font-size: .72rem; color: var(--muted); }
		.reportingComment p { font-size: .875rem; margin: 10px 0; line-height: 1.55; }
		.feedback { display: flex; gap: 18px; }
		.feedback .likes, .feedback .dislikes {
			display: flex; align-items: center; gap: 6px;
			font-size: .8rem; color: var(--muted);
		}
		.report-form {
			display: flex;
			flex-direction: column;
			gap: 10px;
		}
		.report-form label {
			font-size: .8rem;
			color: var(--muted);
		}
		.report-form input[type="text"] {
			background: rgba(255,255,255,.05);
			border: 1px solid rgba(255,255,255,.12);
			border-radius: 8px;
			padding: 11px 14px;
			color: var(--text);
			font-family: 'Inter', sans-serif;
			font-size: .875rem;
			outline: none;
		}
		.report-form input[type="text"]:focus { border-color: var(--accent); }
		.report-form button {
			background: var(--accent);
			color: #fff;
			border: none;
			border-radius: 8px;
			padding: 12px;
			font-weight: 600;
			font-size: .875rem;
			cursor: pointer;
			transition: background .2s;
		}
		.report-form button:hover { background: #c1000f; }
	</style>
</head>
<body class="report-page-body">

<nav class="nav" id="mainNav">
	<a class="nav-logo" href="home.php" style="text-decoration:none;">ItraDB</a>
</nav>

<div class="report-wrap">
	<h3>Reporting comment</h3>

	<div class="reportingComment">
		<h4><?= htmlspecialchars($comment['username']) ?></h4>
		<span><?= htmlspecialchars($comment['dateC']) ?></span>
		<p><?= htmlspecialchars($comment['comment']) ?></p>

		<div class="feedback">
			<div class="likes"><span><?= (int)$comment['likes'] ?> likes</span></div>
			<div class="dislikes"><span><?= (int)$comment['dislikes'] ?> dislikes</span></div>
		</div>
	</div>

	<form class="report-form" action="reportLogic.php?cid=<?= (int)$comment_id ?>" method="POST">
		<label for="reason">Reason for report</label>
		<input type="text" id="reason" name="reason" required>
		<button type="submit">Submit report</button>
	</form>
</div>

</body>
</html>