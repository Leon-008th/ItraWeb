<?php

session_start();
include_once('config.php');

unset($_SESSION['viewedMovieURL']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Page not found — ItraDB</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="style.css">
	<style>
		.notfound-page {
			min-height: 100vh;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			text-align: center;
			padding: 20px;
			gap: 10px;
		}
		.notfound-code {
			font-family: 'Bebas Neue', sans-serif;
			font-size: clamp(4rem, 12vw, 8rem);
			line-height: 1;
			background: linear-gradient(135deg, var(--accent), var(--accent2));
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
		}
		.notfound-title {
			font-size: 1.1rem;
			color: var(--muted);
			margin-bottom: 20px;
		}
		.notfound-link {
			background: var(--accent);
			color: #fff;
			font-weight: 600;
			font-size: .9rem;
			padding: 12px 26px;
			border-radius: var(--radius);
			transition: background .2s, transform .15s;
		}
		.notfound-link:hover { background: #c1000f; transform: translateY(-1px); }
	</style>
</head>
<body>
	<div class="notfound-page">
		<div class="notfound-code">404</div>
		<p class="notfound-title">This page doesn't exist.</p>
		<?php if (!empty($_SESSION['username'])): ?>
			<a class="notfound-link" href="home.php">Go to homepage</a>
		<?php endif; ?>
	</div>
</body>
</html>