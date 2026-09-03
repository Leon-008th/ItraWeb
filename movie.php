<?php
session_start();

include_once('config.php');
include_once('API_controll.php');

$error = $_SESSION['message'] ?? "";
unset($_SESSION['message']);

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$movieTitle = $_GET['movie'] ?? '';

if (!$movieId || empty($_SESSION['username'])) {
	$_SESSION['message'] = "Not authorised";
	header("Location: login.php");
	exit;
}

$_SESSION['viewedMovieURL'] = $server_url . "movie.php?movie=" . urlencode($movieTitle) . "&id={$movieId}";
$_SESSION['movie_id'] = $movieId;

$movie_Details = tmdb_get("3/movie/$movieId?language=en-US");
$pop_movies = popular_movies(8);

$username = $_SESSION['username'];

try {
	$sql = "SELECT pfp_url FROM users WHERE username = :username";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		":username" => $username
	]);

	$data = $insertSql->fetch();
} catch (Exception $e) {
	echo "Error!" . $e->getMessage();
	exit();
}

$pfp_url = $data['pfp_url'];

try {

	$sql = "SELECT * FROM comments WHERE movie_id = :movie_id";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		":movie_id" => $movieId
	]);

	$comments = $insertSql->fetchAll();

} catch (Exception $e) {
	echo "Error!" . $e->getMessage();
	exit();
}

$posterPath = $movie_Details['poster_path'] ?? null;
$title      = $movie_Details['original_title'] ?? $movieTitle;
$overview   = $movie_Details['overview'] ?? '';
$budget     = $movie_Details['budget'] ?? 0;
$isAdult    = !empty($movie_Details['adult']);
$genres     = $movie_Details['genres'] ?? [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Watch <?= htmlspecialchars($title) ?> — ItraDB</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="style.css">
	<style>
		.movie-page-body { padding-top: var(--nav-h); }

		.movie_Iframe {
			width: 100%;
			aspect-ratio: 16/7;
			background: #000;
			max-height: 640px;
		}
		.movie_Iframe iframe {
			width: 100%;
			height: 100%;
			border: none;
			display: block;
		}

		.movie-layout {
			display: grid;
			grid-template-columns: 2fr 1fr;
			gap: 32px;
			padding: 32px 40px;
			align-items: start;
		}
		@media (max-width: 900px) {
			.movie-layout { grid-template-columns: 1fr; }
		}

		.movie_details {
			display: flex;
			gap: 24px;
		}
		.movie_details img {
			width: 160px;
			border-radius: var(--radius);
			flex-shrink: 0;
			background: var(--card);
		}
		.movie_details h1 {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 2rem;
			letter-spacing: .5px;
			margin-bottom: 10px;
		}
		.movie_details p {
			font-size: .9rem;
			color: rgba(240,240,240,.8);
			line-height: 1.65;
			margin-bottom: 16px;
		}
		.movie_details h3 {
			font-size: .8rem;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: var(--muted);
			margin-bottom: 10px;
		}
		.movie_details span {
			display: inline-block;
			background: rgba(255,255,255,.08);
			border-radius: 20px;
			padding: 5px 12px;
			font-size: .78rem;
			margin: 0 6px 6px 0;
		}

		.reck_movies_sideFrame {
			display: flex;
			flex-direction: column;
			gap: 14px;
		}
		.reck_movies_sideFrame .movie {
			display: flex;
			gap: 12px;
			background: var(--surface);
			border-radius: var(--radius);
			padding: 10px;
			align-items: center;
		}
		.reck_movies_sideFrame .movie img {
			width: 56px;
			height: 84px;
			object-fit: cover;
			border-radius: 6px;
			background: var(--card);
			flex-shrink: 0;
		}
		.reck_movies_sideFrame .movie h1 {
			font-size: .85rem;
			font-weight: 600;
			font-family: 'Inter', sans-serif;
			letter-spacing: 0;
			margin-bottom: 2px;
		}
		.reck_movies_sideFrame .movie span {
			display: block;
			font-size: .72rem;
			color: var(--muted);
		}
		.reck_movies_sideFrame .movie a {
			display: inline-block;
			margin-top: 6px;
			font-size: .72rem;
			font-weight: 600;
			color: var(--accent);
		}
		.reck_movies_sideFrame .movie a:hover { color: var(--accent2); }

		.comment_section {
			padding: 0 40px 60px;
			max-width: 900px;
		}
		.comment_section h3 {
			font-size: .8rem;
			text-transform: uppercase;
			letter-spacing: 1px;
			color: var(--muted);
			margin-bottom: 14px;
		}
		.comment_form {
			display: flex;
			gap: 10px;
			margin-bottom: 10px;
		}
		.comment_form form {
			display: flex;
			gap: 10px;
			flex: 1;
		}
		.comment_form input[type="text"] {
			flex: 1;
			background: rgba(255,255,255,.05);
			border: 1px solid rgba(255,255,255,.12);
			border-radius: 8px;
			padding: 11px 14px;
			color: var(--text);
			font-family: 'Inter', sans-serif;
			font-size: .875rem;
			outline: none;
		}
		.comment_form input[type="text"]:focus { border-color: var(--accent); }
		.comment_form button {
			background: var(--accent);
			color: #fff;
			border: none;
			border-radius: 8px;
			padding: 0 20px;
			font-weight: 600;
			font-size: .875rem;
			cursor: pointer;
			transition: background .2s;
		}
		.comment_form button:hover { background: #c1000f; }
		.comment_form p {
			color: #ff8a8a;
			font-size: .8rem;
			margin-top: 8px;
		}

		.user_comments {
			display: flex;
			flex-direction: column;
			gap: 14px;
			margin-top: 20px;
		}
		.comment {
			background: var(--surface);
			border-radius: var(--radius);
			padding: 16px 18px;
		}
		.comment h4 { font-size: .875rem; margin-bottom: 2px; }
		.comment > span {
			font-size: .72rem;
			color: var(--muted);
		}
		.comment p {
			font-size: .875rem;
			margin: 10px 0;
			line-height: 1.55;
		}
		.feedback {
			display: flex;
			gap: 18px;
		}
		.feedback .likes, .feedback .dislikes {
			display: flex;
			align-items: center;
			gap: 6px;
			font-size: .8rem;
			color: var(--muted);
		}
		.feedback img { width: 16px; height: 16px; opacity: .7; }
	</style>
</head>
<body class="movie-page-body">

<nav class="nav" id="mainNav">
	<a class="nav-logo" href="home.php" style="text-decoration:none;">ItraDB</a>
	<ul class="nav-links">
		<li><a href="home.php">Home</a></li>
	</ul>
	<!--<form action="API_controll.php" method="GET">
            <input class="nav-search" type="text" name="movie_name"
                   placeholder="Search titles…" required>
            <input type="hidden" name="request" value="search">
            <input type="hidden" name="adult"   value="false">
    </form>-->

    <!-- Search movies/series ~~ LATER UPDATE -->
	<div class="profile">
        <div class="profilePhoto">
            <a href="profile.php"><img class="avatar avatar-sm" src="<?= htmlspecialchars($pfp_url) ?>"></a>
        </div>
    </div>
</nav>

<div class="movie_Iframe">
	<iframe src="<?= htmlspecialchars($vidSrcURL_m . $movieId) ?>" allowfullscreen></iframe>
</div>

<div class="movie-layout">

	<div class="movie_details">
		<?php if ($posterPath): ?>
			<img src="<?= htmlspecialchars(createPosterURL($posterPath)) ?>" alt="<?= htmlspecialchars($title) ?>">
		<?php endif; ?>

		<div>
			<h1><?= htmlspecialchars($title) ?></h1>
			<p><?= htmlspecialchars($overview) ?></p>

			<h3>Quick facts</h3>

			<span>Budget: $<?= number_format((float)$budget) ?></span>
			<span><?= $isAdult ? 'For adults' : 'Kid friendly' ?></span>

			<?php foreach ($genres as $genre): ?>
				<span><?= htmlspecialchars($genre['name']) ?></span>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="reck_movies_sideFrame">
		<?php foreach ($pop_movies as $m): ?>
			<div class="movie">
				<?php if (!empty($m['poster_url'])): ?>
					<img src="<?= htmlspecialchars($m['poster_url']) ?>" alt="<?= htmlspecialchars($m['title']) ?>">
				<?php endif; ?>
				<div>
					<h1><?= htmlspecialchars($m['title']) ?></h1>
					<span><?= htmlspecialchars(substr($m['release_date'] ?? '', 0, 4)) ?></span>
					<span>★ <?= number_format((float)($m['vote_average'] ?? 0), 1) ?></span>
					<a href="movie.php?movie=<?= urlencode($m['title']) ?>&id=<?= (int)$m['id'] ?>">Watch now</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

</div>

<div class="comment_section">
	<h3>Comments</h3>

	<div class="comment_form">
		<form action="uploadComment.php" method="POST">
			<input type="text" name="comment" placeholder="Add a comment…" required>
			<button type="submit">Post</button>
		</form>
	</div>
	<?php if (!empty($error)): ?>
		<p><?= htmlspecialchars($error) ?></p>
	<?php endif; ?>

	<div class="user_comments">
		<?php foreach ($comments as $comment): ?>
			<div class="comment">
				<h4><?= htmlspecialchars($comment['username']) ?></h4>
				<span><?= htmlspecialchars($comment['dateC']) ?></span>
				<p><?= htmlspecialchars($comment['comment']) ?></p>
				<a href="report.php?cid=<?= htmlspecialchars($comment['id']) ?>&id=<?= htmlspecialchars($movieId) ?>">Report</a>

				<div class="feedback">
					<div class="likes">
						<span><?= (int)$comment['likes'] ?> likes</span>
					</div>
					<div class="dislikes">
						<span><?= (int)$comment['dislikes'] ?> dislikes</span>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

</body>
</html>