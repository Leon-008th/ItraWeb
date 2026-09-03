<?php

session_start();
include_once('config.php');

if (empty($_SESSION['username'])) {
	$_SESSION['message'] = "Not Authorised";
	header("Location: login.php");
	exit();
}

$is_changing = $_POST['changing'] ?? False;

$username = $_SESSION['username'];

$_SESSION['came_from'] = "profile.php";

try {
	$sql = "SELECT id, name, username, password, pfp_url, position FROM users WHERE username = :username";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		":username" => $username
	]);

	$userData = $insertSql->fetch();
} catch (Exception $e) {
	error_log($e->getMessage());
	$userData = false;
}

if (!$userData) {
	header("Location: 404.php");
	exit();
}

$user_id = $userData['id'];
$pfp_url = $userData['pfp_url'];

if (empty($pfp_url)) {
    $pfp_url = $defaultpfp;
}

try {
	$sql = "SELECT * FROM comments WHERE username = :username";
	$insertSql = $conn->prepare($sql);
	$insertSql->execute([
		":username" => $username
	]);

	$userComments = $insertSql->fetchAll();
} catch (Exception $e) {
	error_log($e->getMessage());
	$userComments = [];
}

$message = $_SESSION['message'] ?? "";
unset($_SESSION['message']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Your profile — ItraDB</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="style.css">
	<style>
		.profile-page-body { padding-top: calc(var(--nav-h) + 24px); }

		.top-message {
			max-width: 720px;
			margin: 0 auto 20px;
			background: rgba(229,9,20,.12);
			border: 1px solid rgba(229,9,20,.4);
			color: #ff8a8a;
			padding: 10px 16px;
			border-radius: 8px;
			font-size: .85rem;
		}

		.profileBody {
			max-width: 720px;
			margin: 0 auto 40px;
			padding: 0 20px;
		}

		.profileContents {
			background: var(--surface);
			border-radius: 12px;
			padding: 28px;
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 14px;
			text-align: center;
		}
		.profileContents h3 {
			font-size: 1rem;
			font-weight: 600;
		}
		.profileContents form { margin: 0; }
		.profileContents button {
			background: rgba(255,255,255,.08);
			color: var(--text);
			border: 1px solid rgba(255,255,255,.12);
			border-radius: 8px;
			padding: 8px 16px;
			font-size: .8rem;
			font-weight: 600;
			cursor: pointer;
			transition: background .2s;
		}
		.profileContents button:hover { background: rgba(255,255,255,.15); }
		.profileContents button[name="is_changing"][value=""] {
			background: transparent;
			border-color: transparent;
			color: var(--muted);
		}

		.profile-field-row {
			display: flex;
			align-items: center;
			gap: 10px;
			justify-content: center;
			flex-wrap: wrap;
		}

		.profileContents input[type="text"] {
			background: rgba(255,255,255,.05);
			border: 1px solid rgba(255,255,255,.12);
			border-radius: 8px;
			padding: 10px 14px;
			color: var(--text);
			font-family: 'Inter', sans-serif;
			font-size: .875rem;
			outline: none;
			width: 220px;
		}
		.profileContents input[type="text"]:focus { border-color: var(--accent); }
		.profileContents label {
			font-size: .8rem;
			color: var(--muted);
			display: block;
			margin-bottom: 6px;
		}

		/* ── Crop tool ──────────────────────────────────── */
		.crop-tool { display: flex; flex-direction: column; align-items: center; gap: 14px; }
		.crop-stage {
			width: 220px;
			height: 220px;
			border-radius: 50%;
			overflow: hidden;
			background: var(--card);
			border: 2px solid rgba(255,255,255,.1);
			position: relative;
			touch-action: none;
			cursor: grab;
		}
		.crop-stage.dragging { cursor: grabbing; }
		.crop-stage canvas { display: block; }
		.crop-controls {
			display: flex;
			align-items: center;
			gap: 10px;
			width: 220px;
		}
		.crop-controls input[type="range"] { flex: 1; }
		.crop-controls i { color: var(--muted); font-size: 1rem; }
		.crop-hint { font-size: .72rem; color: var(--muted); }
		.crop-file-label {
			background: rgba(255,255,255,.08);
			border: 1px solid rgba(255,255,255,.12);
			border-radius: 8px;
			padding: 8px 16px;
			font-size: .8rem;
			font-weight: 600;
			cursor: pointer;
			display: inline-block;
		}
		.crop-file-label:hover { background: rgba(255,255,255,.15); }
		.crop-file-label input { display: none; }
		.crop-actions { display: flex; gap: 10px; }
		.crop-submit {
			background: var(--accent);
			color: #fff;
			border: none;
		}
		.crop-submit:hover { background: #c1000f; }

		.userComments {
			max-width: 720px;
			margin: 0 auto;
			padding: 0 20px;
			display: flex;
			flex-direction: column;
			gap: 14px;
		}
		.userComments h2 {
			font-size: 1rem;
			font-weight: 600;
			margin-bottom: 4px;
		}
		.comment {
			background: var(--surface);
			border-radius: var(--radius);
			padding: 16px 18px;
		}
		.comment h4 { font-size: .875rem; margin-bottom: 2px; }
		.comment > span { font-size: .72rem; color: var(--muted); }
		.comment p { font-size: .875rem; margin: 10px 0; line-height: 1.55; }
		.comment .report-link {
			font-size: .72rem;
			color: var(--muted);
			display: inline-block;
			margin-bottom: 10px;
		}
		.comment .report-link:hover { color: var(--accent); }
		.feedback { display: flex; gap: 18px; }
		.feedback .likes, .feedback .dislikes {
			display: flex; align-items: center; gap: 6px;
			font-size: .8rem; color: var(--muted);
		}

		/* =========================================================
		   ADMIN DASHBOARD / PROFILE ADMIN PANEL
		   Drop this after your existing profile styles.
		   ========================================================= */

		/* ── Admin section layout ───────────────────────────────── */

		.reports,
		.activeUsers,
		.passkeys,
		.userCreateCode,
		.searchAcounts {
		    width: min(1100px, calc(100% - 40px));
		    margin: 24px auto 0;
		}

		/* Give the whole admin area a little breathing room */
		.profileBody + .reports,
		.profileBody + .activeUsers {
		    margin-top: 32px;
		}


		/* ── Shared admin card styling ──────────────────────────── */

		.commentReports,
		.activeUsers,
		.approvedPass,
		.pendingPass,
		.userCreateCode,
		.searchAcounts,
		.SearchedUsers {
		    background:
		        linear-gradient(
		            145deg,
		            rgba(255, 255, 255, 0.045),
		            rgba(255, 255, 255, 0.018)
		        );
		    border: 1px solid rgba(255, 255, 255, 0.08);
		    border-radius: 16px;
		    box-shadow:
		        0 12px 35px rgba(0, 0, 0, 0.18),
		        inset 0 1px 0 rgba(255, 255, 255, 0.025);
		}


		/* ── Reports ─────────────────────────────────────────────── */

		.commentReports {
		    padding: 24px;
		}

		.commentReports > h5:first-child {
		    margin: 0 0 20px;
		    padding: 12px 14px;
		    color: #ffb4b4;
		    background: rgba(229, 9, 20, 0.08);
		    border: 1px solid rgba(229, 9, 20, 0.2);
		    border-radius: 9px;
		    font-size: 0.75rem;
		    font-weight: 500;
		    line-height: 1.5;
		}

		.commentReports > h5 {
		    margin: 8px 0;
		    color: var(--muted);
		    font-size: 0.78rem;
		    font-weight: 500;
		}

		.commentReports > h5:nth-of-type(2n) {
		    color: var(--text);
		}

		.reportedComment {
		    margin: 16px 0;
		    padding: 16px;
		    background: rgba(0, 0, 0, 0.18);
		    border: 1px solid rgba(255, 255, 255, 0.06);
		    border-left: 3px solid var(--accent);
		    border-radius: 10px;
		}

		.reportedComment h5 {
		    margin: 5px 0;
		    color: var(--text);
		    font-size: 0.8rem;
		    font-weight: 500;
		    line-height: 1.5;
		}

		.reportedComment h5:first-child {
		    color: var(--muted);
		    font-size: 0.72rem;
		}

		.commentReports form {
		    display: flex;
		    flex-wrap: wrap;
		    align-items: center;
		    gap: 9px;
		    margin: 14px 0 28px;
		    padding-bottom: 22px;
		    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
		}

		.commentReports form:last-child {
		    margin-bottom: 0;
		    padding-bottom: 0;
		    border-bottom: none;
		}


		/* ── Admin buttons ───────────────────────────────────────── */

		.commentReports button,
		.activeUsers button,
		.passkeys button,
		.userCreateCode button,
		.searchAcounts button,
		.SearchedUsers button {
		    appearance: none;
		    border: 1px solid rgba(255, 255, 255, 0.11);
		    border-radius: 8px;
		    padding: 9px 14px;
		    background: rgba(255, 255, 255, 0.055);
		    color: var(--text);
		    font-family: 'Inter', sans-serif;
		    font-size: 0.75rem;
		    font-weight: 600;
		    line-height: 1.2;
		    cursor: pointer;
		    transition:
		        background 0.2s ease,
		        border-color 0.2s ease,
		        color 0.2s ease,
		        transform 0.2s ease;
		}

		.commentReports button:hover,
		.activeUsers button:hover,
		.passkeys button:hover,
		.userCreateCode button:hover,
		.searchAcounts button:hover,
		.SearchedUsers button:hover {
		    background: rgba(255, 255, 255, 0.11);
		    border-color: rgba(255, 255, 255, 0.2);
		    transform: translateY(-1px);
		}

		.commentReports button:active,
		.activeUsers button:active,
		.passkeys button:active,
		.userCreateCode button:active,
		.searchAcounts button:active,
		.SearchedUsers button:active {
		    transform: translateY(0);
		}

		.commentReports button[value="warn"] {
		    color: #ffd27a;
		    border-color: rgba(255, 184, 77, 0.25);
		}

		.commentReports button[value="deactivate"] {
		    color: #ffb38a;
		    border-color: rgba(255, 120, 70, 0.25);
		}

		.commentReports button[value="ban"] {
		    color: #ff8b8b;
		    border-color: rgba(229, 9, 20, 0.3);
		}

		.commentReports button[value="dismiss"] {
		    color: #a8e6b0;
		    border-color: rgba(80, 200, 100, 0.2);
		}


		/* ── Admin forms / inputs ───────────────────────────────── */

		.commentReports form input[type="text"],
		.commentReports form input[type="number"],
		.commentReports form input[type="Number"],
		.userCreateCode input,
		.searchAcounts input,
		.SearchedUsers input {
		    width: 100%;
		    max-width: 320px;
		    box-sizing: border-box;
		    padding: 10px 13px;
		    background: rgba(0, 0, 0, 0.2);
		    border: 1px solid rgba(255, 255, 255, 0.1);
		    border-radius: 8px;
		    color: var(--text);
		    font-family: 'Inter', sans-serif;
		    font-size: 0.8rem;
		    outline: none;
		    transition:
		        border-color 0.2s ease,
		        background 0.2s ease,
		        box-shadow 0.2s ease;
		}

		.commentReports form input:focus,
		.userCreateCode input:focus,
		.searchAcounts input:focus,
		.SearchedUsers input:focus {
		    background: rgba(0, 0, 0, 0.28);
		    border-color: var(--accent);
		    box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
		}

		.commentReports label,
		.userCreateCode label,
		.searchAcounts label,
		.SearchedUsers label {
		    display: block;
		    margin-bottom: 7px;
		    color: var(--muted);
		    font-size: 0.72rem;
		    font-weight: 500;
		}


		/* ── Active users ────────────────────────────────────────── */

		.activeUsers {
		    display: grid;
		    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
		    gap: 14px;
		    padding: 20px;
		}

		.activeUsers::before {
		    content: "Active users";
		    grid-column: 1 / -1;
		    display: block;
		    margin-bottom: 2px;
		    color: var(--text);
		    font-size: 1rem;
		    font-weight: 600;
		}

		.activeUsers .user,
		.SearchedUsers .user {
		    min-width: 0;
		    padding: 18px;
		    background: rgba(255, 255, 255, 0.035);
		    border: 1px solid rgba(255, 255, 255, 0.065);
		    border-radius: 12px;
		    transition:
		        transform 0.2s ease,
		        background 0.2s ease,
		        border-color 0.2s ease;
		}

		.activeUsers .user:hover,
		.SearchedUsers .user:hover {
		    transform: translateY(-2px);
		    background: rgba(255, 255, 255, 0.055);
		    border-color: rgba(255, 255, 255, 0.12);
		}

		.userPfp {
		    width: 58px;
		    height: 58px;
		    margin-bottom: 12px;
		    overflow: hidden;
		    border-radius: 50%;
		    background: var(--card);
		    border: 2px solid rgba(255, 255, 255, 0.09);
		}

		.userPfp img {
		    display: block;
		    width: 100%;
		    height: 100%;
		    object-fit: cover;
		}

		.activeUsers .user h3,
		.SearchedUsers .user h3 {
		    margin: 4px 0;
		    color: var(--text);
		    font-size: 0.85rem;
		    font-weight: 600;
		    overflow-wrap: anywhere;
		}

		.activeUsers .user h3:last-child,
		.SearchedUsers .user h3:nth-of-type(2) {
		    color: var(--muted);
		    font-size: 0.7rem;
		    font-weight: 400;
		}


		/* ── Passkeys ────────────────────────────────────────────── */

		.passkeys {
		    display: grid;
		    grid-template-columns: repeat(2, minmax(0, 1fr));
		    gap: 16px;
		    background: transparent;
		    border: none;
		    box-shadow: none;
		}

		.approvedPass,
		.pendingPass {
		    min-width: 0;
		    padding: 22px;
		}

		.approvedPass {
		    border-top: 2px solid rgba(80, 200, 100, 0.55);
		}

		.pendingPass {
		    border-top: 2px solid rgba(255, 184, 77, 0.55);
		}

		.approvedPass h2,
		.pendingPass h2 {
		    margin: 0 0 18px;
		    font-size: 0.95rem;
		    font-weight: 600;
		}

		.approvedPass h2 {
		    color: #a8e6b0;
		}

		.pendingPass h2 {
		    color: #ffd27a;
		}

		.approvedPass h5,
		.pendingPass h5 {
		    margin: 10px 0;
		    color: var(--muted);
		    font-size: 0.72rem;
		    font-weight: 400;
		    line-height: 1.5;
		    overflow-wrap: anywhere;
		}

		.approvedPass h5:first-of-type,
		.pendingPass h5:first-of-type {
		    color: var(--text);
		    font-family: monospace;
		    font-size: 0.8rem;
		    letter-spacing: 0.03em;
		}

		.approvedPass a {
		    color: var(--accent);
		    text-decoration: none;
		    overflow-wrap: anywhere;
		}

		.approvedPass a:hover {
		    text-decoration: underline;
		}


		/* ── Create passkey ──────────────────────────────────────── */

		.userCreateCode {
		    padding: 22px;
		}

		.userCreateCode form {
		    display: flex;
		    align-items: flex-end;
		    flex-wrap: wrap;
		    gap: 10px;
		}

		.userCreateCode form > label {
		    width: 100%;
		    margin-bottom: -2px;
		}

		.userCreateCode input {
		    flex: 1 1 220px;
		    max-width: none;
		}

		.userCreateCode button {
		    background: var(--accent);
		    border-color: var(--accent);
		    color: #fff;
		}

		.userCreateCode button:hover {
		    background: #c1000f;
		    border-color: #c1000f;
		}


		/* ── Search accounts ────────────────────────────────────── */

		.searchAcounts {
		    padding: 22px;
		}

		.searchAcounts > form {
		    display: flex;
		    align-items: flex-end;
		    flex-wrap: wrap;
		    gap: 10px;
		}

		.searchAcounts > form label {
		    width: 100%;
		    margin-bottom: -2px;
		}

		.searchAcounts > form input {
		    flex: 1 1 240px;
		    max-width: none;
		}

		.searchAcounts > form button {
		    background: rgba(255, 255, 255, 0.08);
		}

		.SearchedUsers {
		    margin-top: 20px;
		    padding: 14px;
		    background: rgba(0, 0, 0, 0.12);
		    border-radius: 12px;
		}

		.SearchedUsers .user {
		    margin-bottom: 12px;
		}

		.SearchedUsers .user:last-of-type {
		    margin-bottom: 0;
		}

		.SearchedUsers .user h5 {
		    margin: 10px 0;
		    padding: 9px 11px;
		    color: var(--muted);
		    background: rgba(255, 255, 255, 0.035);
		    border-radius: 7px;
		    font-size: 0.72rem;
		    font-weight: 400;
		    line-height: 1.5;
		}

		.SearchedUsers .user form {
		    display: flex;
		    flex-wrap: wrap;
		    align-items: center;
		    gap: 9px;
		    margin-top: 14px;
		}

		.SearchedUsers > form {
		    margin-top: 14px;
		}

		.SearchedUsers > form button {
		    color: #ff9a9a;
		    border-color: rgba(229, 9, 20, 0.25);
		}

		.SearchedUsers > form button:hover {
		    background: rgba(229, 9, 20, 0.1);
		}


		/* ── Admin spacing before comments ──────────────────────── */

		.userComments {
		    margin-top: 40px;
		}


		/* ── Focus accessibility ────────────────────────────────── */

		.commentReports button:focus-visible,
		.activeUsers button:focus-visible,
		.passkeys button:focus-visible,
		.userCreateCode button:focus-visible,
		.searchAcounts button:focus-visible,
		.SearchedUsers button:focus-visible,
		.commentReports input:focus-visible,
		.userCreateCode input:focus-visible,
		.searchAcounts input:focus-visible,
		.SearchedUsers input:focus-visible {
		    outline: 2px solid var(--accent);
		    outline-offset: 2px;
		}


		/* ── Responsive ──────────────────────────────────────────── */

		@media (max-width: 700px) {
		    .reports,
		    .activeUsers,
		    .passkeys,
		    .userCreateCode,
		    .searchAcounts {
		        width: min(100% - 24px, 1100px);
		    }

		    .commentReports,
		    .userCreateCode,
		    .searchAcounts {
		        padding: 16px;
		    }

		    .activeUsers {
		        grid-template-columns: 1fr;
		        padding: 16px;
		    }

		    .passkeys {
		        grid-template-columns: 1fr;
		    }

		    .commentReports form {
		        flex-direction: column;
		        align-items: stretch;
		    }

		    .commentReports form input,
		    .commentReports form button {
		        width: 100%;
		        max-width: none;
		    }

		    .userCreateCode form,
		    .searchAcounts > form {
		        align-items: stretch;
		        flex-direction: column;
		    }

		    .userCreateCode input,
		    .searchAcounts > form input {
		        width: 100%;
		        flex: none;
		    }

		    .userCreateCode button,
		    .searchAcounts > form button {
		        width: 100%;
		    }

		    .SearchedUsers .user form {
		        flex-direction: column;
		        align-items: stretch;
		    }

		    .SearchedUsers .user form button {
		        width: 100%;
		    }
		}
	</style>
</head>
<body class="profile-page-body">

<nav class="nav" id="mainNav">
	<a class="nav-logo" href="home.php" style="text-decoration:none;">ItraDB</a>
	<ul class="nav-links">
		<li><a href="home.php">Home</a></li>
	</ul>
	<div class="nav-right">
		<img class="avatar avatar-sm" src="<?= htmlspecialchars($pfp_url) ?>" alt="">
	</div>
</nav>

<div class="profileBody">

	<?php if (!empty($message)): ?>
		<div class="top-message"><?= htmlspecialchars($message) ?></div>
	<?php endif; ?>

	<?php if ($is_changing == False): ?>

		<div class="profileContents">
			<img class="avatar avatar-lg" src="<?= htmlspecialchars($pfp_url) ?>" alt="">
			<h3><?= htmlspecialchars($userData['name']) ?></h3>
			<span style="color:var(--muted);font-size:.85rem;">@<?= htmlspecialchars($userData['username']) ?></span>
			<a style="color: red; font-size: 15px;" href="logout.php">Logout</a>

			<div class="profile-field-row">
				<form action="profile.php" method="POST">
					<input type="hidden" name="changing" value="pfp">
					<button>Change picture</button>
				</form>
				<form action="profile.php" method="POST">
					<input type="hidden" name="changing" value="name">
					<button>Change name</button>
				</form>
				<form action="profile.php" method="POST">
					<input type="hidden" name="changing" value="username">
					<button>Change username</button>
				</form>
				<form action="profile.php" method="POST">
					<input type="hidden" name="changing" value="password">
					<button>Change password</button>
				</form>
			</div>
		</div>

	<?php elseif ($is_changing == "pfp"): ?>

		<div class="profileContents">
			<h3>Update your profile picture</h3>

			<form id="pfpForm" method="POST" enctype="multipart/form-data" action="profileLogic.php?id=<?= (int)$user_id ?>">
				<div class="crop-tool">
					<label class="crop-file-label" id="pickLabel">
						Choose photo
						<input type="file" id="pfpPicker" accept="image/*">
					</label>

					<div class="crop-stage" id="cropStage" style="display:none;">
						<canvas id="cropCanvas" width="220" height="220"></canvas>
					</div>

					<div class="crop-controls" id="cropControls" style="display:none;">
						<i class="ti ti-photo"></i>
						<input type="range" id="zoomRange" min="1" max="3" step="0.01" value="1">
					</div>
					<p class="crop-hint" id="cropHint" style="display:none;">Drag to reposition, use the slider to zoom</p>

					<input type="hidden" name="is_changing" value="pfp">
					<input type="file" name="pfp" id="pfpFileInput" style="display:none;">

					<div class="crop-actions">
						<button type="submit" class="crop-submit" id="submitCrop" disabled>Save picture</button>
						<button type="submit" name="is_changing" value="False">Cancel</button>
					</div>
				</div>
			</form>
		</div>

		<script>
		(function () {
			const picker      = document.getElementById('pfpPicker');
			const stage       = document.getElementById('cropStage');
			const canvas      = document.getElementById('cropCanvas');
			const ctx         = canvas.getContext('2d');
			const controls    = document.getElementById('cropControls');
			const hint        = document.getElementById('cropHint');
			const zoomRange   = document.getElementById('zoomRange');
			const submitBtn   = document.getElementById('submitCrop');
			const fileInput   = document.getElementById('pfpFileInput');
			const form        = document.getElementById('pfpForm');

			let img = null;
			let scale = 1, minScale = 1;
			let offsetX = 0, offsetY = 0;
			let dragging = false, lastX = 0, lastY = 0;
			const SIZE = 220;

			function draw() {
				ctx.clearRect(0, 0, SIZE, SIZE);
				if (!img) return;
				const w = img.width * scale;
				const h = img.height * scale;
				ctx.drawImage(img, offsetX, offsetY, w, h);
			}

			function clampOffsets() {
				const w = img.width * scale;
				const h = img.height * scale;
				offsetX = Math.min(0, Math.max(SIZE - w, offsetX));
				offsetY = Math.min(0, Math.max(SIZE - h, offsetY));
			}

			picker.addEventListener('change', () => {
				const file = picker.files[0];
				if (!file) return;
				const reader = new FileReader();
				reader.onload = (e) => {
					const image = new Image();
					image.onload = () => {
						img = image;
						minScale = Math.max(SIZE / img.width, SIZE / img.height);
						scale = minScale;
						offsetX = (SIZE - img.width * scale) / 2;
						offsetY = (SIZE - img.height * scale) / 2;
						zoomRange.min = minScale;
						zoomRange.max = minScale * 3;
						zoomRange.value = minScale;
						stage.style.display = 'block';
						controls.style.display = 'flex';
						hint.style.display = 'block';
						submitBtn.disabled = false;
						draw();
					};
					image.src = e.target.result;
				};
				reader.readAsDataURL(file);
			});

			zoomRange.addEventListener('input', () => {
				if (!img) return;
				scale = parseFloat(zoomRange.value);
				clampOffsets();
				draw();
			});

			function startDrag(x, y) {
				dragging = true;
				lastX = x; lastY = y;
				stage.classList.add('dragging');
			}
			function moveDrag(x, y) {
				if (!dragging || !img) return;
				offsetX += x - lastX;
				offsetY += y - lastY;
				lastX = x; lastY = y;
				clampOffsets();
				draw();
			}
			function endDrag() {
				dragging = false;
				stage.classList.remove('dragging');
			}

			stage.addEventListener('mousedown', e => startDrag(e.clientX, e.clientY));
			window.addEventListener('mousemove', e => moveDrag(e.clientX, e.clientY));
			window.addEventListener('mouseup', endDrag);

			stage.addEventListener('touchstart', e => {
				const t = e.touches[0];
				startDrag(t.clientX, t.clientY);
			});
			stage.addEventListener('touchmove', e => {
				const t = e.touches[0];
				moveDrag(t.clientX, t.clientY);
				e.preventDefault();
			}, { passive: false });
			stage.addEventListener('touchend', endDrag);

			form.addEventListener('submit', (e) => {
				if (!img) return;
				e.preventDefault();
				canvas.toBlob((blob) => {
					const file = new File([blob], 'pfp.png', { type: 'image/png' });
					const dt = new DataTransfer();
					dt.items.add(file);
					fileInput.files = dt.files;
					form.submit();
				}, 'image/png', 0.95);
			});
		})();
		</script>

	<?php elseif ($is_changing == "name"): ?>

		<div class="profileContents">
			<img class="avatar avatar-lg" src="<?= htmlspecialchars($pfp_url) ?>" alt="">

			<form method="POST" action="profileLogic.php?id=<?= (int)$user_id ?>">
				<label for="name">Name</label>
				<input value="<?= htmlspecialchars($userData['name']) ?>" type="text" name="name">
				<div class="crop-actions" style="margin-top:12px;">
					<button name="is_changing" value="name" type="submit" class="crop-submit">Save</button>
					<button name="is_changing" value="False">Cancel</button>
				</div>
			</form>

			<span style="color:var(--muted);font-size:.85rem;">@<?= htmlspecialchars($userData['username']) ?></span>
		</div>

	<?php elseif ($is_changing == "username"): ?>

		<div class="profileContents">
			<img class="avatar avatar-lg" src="<?= htmlspecialchars($pfp_url) ?>" alt="">
			<h3><?= htmlspecialchars($userData['name']) ?></h3>

			<form method="POST" action="profileLogic.php?id=<?= (int)$user_id ?>">
				<label for="username">Username</label>
				<input value="<?= htmlspecialchars($userData['username']) ?>" type="text" name="username">
				<div class="crop-actions" style="margin-top:12px;">
					<button name="is_changing" value="username" type="submit" class="crop-submit">Save</button>
					<button name="is_changing" value="False">Cancel</button>
				</div>
			</form>
		</div>

	<?php elseif ($is_changing == "password"): ?>

		<div class="profileContents">
			<img class="avatar avatar-lg" src="<?= htmlspecialchars($pfp_url) ?>" alt="">
			<h3><?= htmlspecialchars($userData['name']) ?></h3>
			<span style="color:var(--muted);font-size:.85rem;">@<?= htmlspecialchars($userData['username']) ?></span>

			<form method="POST" action="profileLogic.php?id=<?= (int)$user_id ?>">
				<label for="prev_pass">Current Password</label>
				<input type="text" name="prev_pass">
				<label for="new_pass">New Password</label>
				<input type="text" name="new_pass">
				<div class="crop-actions" style="margin-top:12px;">
					<button name="is_changing" value="password" type="submit" class="crop-submit">Save</button>
					<button name="is_changing" value="False">Cancel</button>
				</div>
			</form>
		</div>

	<?php endif; ?>

</div>

<?php if ($userData['position'] == "admin"): ?>

	<?php
		$function = $_POST['function'] ?? NULL;
		$action = $_POST['action'] ?? NULL;
		$searchedUsers = $_SESSION['searchedUsers'] ?? NULL;

		if ($function == "clear") {
			$searchedUsers = NULL;
			unset($_SESSION['searchedUsers']);
		}

		try {
			$sql = "SELECT username, pfp_url, position FROM users WHERE is_active = :is_active";
			$insertSql = $conn->prepare($sql);
			$insertSql->execute([
				":is_active" => 1
			]);

			$active_users = $insertSql->fetchAll();
		} catch (Exception $e) {
			error_log($e->getMessage());
		}

		try {
			$sql = "SELECT * FROM user_creation WHERE created_by = :username";
			$insertSql = $conn->prepare($sql);
			$insertSql->execute([
				":username" => $username
			]);

			$AdminPasskeys = $insertSql->fetchAll();

		} catch (Exception $e) {
			error_log($e->getMessage());
		}

		try {
			$sql = "SELECT * FROM reportings";
			$insertSql = $conn->prepare($sql);
			$insertSql->execute();

			$reports = $insertSql->fetchAll();
		} catch (Exception $e) {
			error_log($e->getMessage());
		}

	?>

	<div class="reports">
		<div class="commentReports">
			<h5>*Any taken action will result with said comment being deleted.</h5>
			<?php if (!empty($reports)): ?>
				<?php foreach ($reports as $report): ?>
					<h5>Reported by: <?= htmlspecialchars($report['by_user']) ?></h5>
					<h5>On <?= htmlspecialchars($report['dateR']) ?></h5>
					<h5>Given reason: <?= htmlspecialchars($report['reason']) ?></h5>
					<div class="reportedComment">
						<h5>Commented by <?= htmlspecialchars($report['commented_by']) ?></h5>
						<h5><?= htmlspecialchars($report['comment']) ?></h5>
					</div>
					<?php 
						$report_given_id = $_POST['report_index'] ?? NULL;

						if (empty($action) || $report_given_id != $report['id']) {
							$redirect = "profile.php";
						} else {
							$redirect = "dash.php?uiq={$report['commented_by']}&rid={$report['id']}";
						}
					?>
					<form action="<?= $redirect ?>" method="POST">
						<?php if (empty($action) || $report_given_id != $report['id']): ?>
							<button name="action" value="warn">Warn <?= htmlspecialchars($report['commented_by']) ?></button>
							<button name="action" value="deactivate">Deactivate <?= htmlspecialchars($report['commented_by']) ?>'s account.</button>
							<button name="action" value="ban">Ban <?= htmlspecialchars($report['commented_by']) ?></button>
							<button name="action" value="dismiss">Dismiss</button>
							<input type="hidden" name="report_index" value="<?= htmlspecialchars($report['id']) ?>">
						<?php elseif ($action == "warn" && $report_given_id == $report['id']): ?>
							<label for="warning">Enter warning</label>
							<input type="text" name="warning">
							<button name="function" value="warn">Warn</button>
							<button name="function" value="cancel">Cancel</button>
						<?php elseif ($action == "deactivate" && $report_given_id == $report['id']): ?>
							<h5>*Limit is 5 days</h5>
							<label for="deactivate">Deactivate untill</label>
							<h5>*Number of days</h5>
							<input type="Number" name="deactivate">
							<label for="reason_deactivate">Reason</label>
							<input type="text" name="reason_deactivate">
							<button name="function" value="deactivate">Deactivate</button>
							<button name="function" value="cancel">Cancel</button>
						<?php elseif ($action == "ban" && $report_given_id == $report['id']): ?>
							<h5>*This action can only be undone by the owner</h5>
							<h5>Are you sure?</h5>
							<p>The report which led to the ban will be saved.</p>
							<label for="ban_reason">Enter reason</label>
							<input type="text" name="ban_reason">
							<button name="function" value="ban">Ban <?= htmlspecialchars($report['commented_by']) ?></button>
							<button name="function" value="cancel">Cancel</button>
						<?php elseif ($action == "dismiss" && $report_given_id == $report['id']): ?>
							<h5>*This report will be deleted, are you sure?</h5>
							<button name="function" value="dismiss">Dismiss</button>
							<button name="function" value="cancel">Cancel</button>
						<?php endif; ?>
					</form>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="activeUsers">
		<?php foreach ($active_users as $user): ?>
			<div class="user">
				<div class="userPfp">
					<img src="<?= htmlspecialchars($user['pfp_url']) ?>">
				</div>
				<h3><?= htmlspecialchars($user['username']) ?></h3>
				<h3>Role: <?= htmlspecialchars($user['position']) ?></h3>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="passkeys">
		<div class="approvedPass">
			<h2>Approved Passkey's</h2>
			<?php if (!empty($AdminPasskeys)): ?>
				<?php foreach($AdminPasskeys as $passkey): ?>
					<?php if ($passkey['is_approved'] == "approved"): ?>
						<h5><?= htmlspecialchars($passkey['password']) ?></h5>
						<h5><?= htmlspecialchars($passkey['date_created']) ?></h5>
						<h5>Link: <a href="createAcount.php?pass=<?= htmlspecialchars($passkey['password']) ?>">localhost/ItraWeb/createAcount.php?pass=<?= htmlspecialchars($passkey['password']) ?></a></h5>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<div class="pendingPass">
			<h2>Pending Passkey's</h2>
			<?php if (!empty($AdminPasskeys)): ?>
				<?php foreach($AdminPasskeys as $passkey): ?>
					<?php if ($passkey['is_approved'] == "pending"): ?>
						<h5><?= htmlspecialchars($passkey['password']) ?></h5>
						<h5><?= htmlspecialchars($passkey['date_created']) ?></h5>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="userCreateCode">
		<form action="dash.php" method="POST">
			<label for="passkey">Enter passkey</label>
			<input type="text" name="passkey" required>
			<button name="function" value="create_passkey" type="submit" class="crop-submit">Save</button>
		</form>
	</div>

	<div class="searchAcounts">
		<form action="dash.php" method="POST">
			<label for="searched_username">Search user:</label>
			<input type="text" name="searched_username" required>
			<button name="function" value="search">Search</button>
		</form>

		<div class="SearchedUsers">
			<?php if (!empty($searchedUsers)): ?>
				<?php foreach ($searchedUsers as $user): ?>
					<div class="user">
						<div class="userPfp">
							<img src="<?= htmlspecialchars($user['pfp_url']) ?>">
						</div>
						<h3><?= htmlspecialchars($user['username']) ?></h3>
						<h3>Role: <?= htmlspecialchars($user['position']) ?></h3>

						<?php if ($user['is_banned'] == 1): ?>
							<h5><?= htmlspecialchars($user['username']) ?> is banned for: <?= htmlspecialchars($user['is_banned_reason']) ?></h5>
						<?php endif;?>

						<?php if (!empty($user['is_banned_till'])): ?>
							<h5><?= htmlspecialchars($user['username']) ?>'s account is deactivated for: <?= htmlspecialchars($user['is_banned_reason']) ?> untill <?= htmlspecialchars($user['is_banned_till']) ?></h5>
						<?php endif; ?>

						<?php if (!empty($user['warnings'])): ?>
							<h3>Warnings</h3>
							<?php $userWarnings = json_decode($user['warnings'], True); ?>

							<?php foreach($userWarnings as $warning): ?>
								<h5><?= htmlspecialchars($warning['date_warned']) ?>, Given for: <?= htmlspecialchars($warning['warning']) ?></h5>
							<?php endforeach; ?>
						<?php endif; ?>

						<?php if ($action != "deactivateUser"): ?>
							<form action="profile.php" method="POST">
								<button name="action" value="deactivateUser">Deactivate <?= htmlspecialchars($report['commented_by']) ?>'s account.</button>
								<input type="hidden" name="user_verify" value="<?= htmlspecialchars($user['username']) ?>">
							</form>
						<?php elseif ($action == "deactivateUser" && $user['username'] == $_POST['user_verify']): ?>
							<form action="dash.php?uiq=<?= htmlspecialchars($report['commented_by'])?>" method="POST">
								<h5>*Limit is 5 days</h5>
								<label for="deactivate">Deactivate untill</label>
								<h5>*Number of days</h5>
								<input type="Number" name="deactivate">
								<label for="reason_deactivate">Reason</label>
								<input type="text" name="reason_deactivate">
								<input type="hidden" name="is_from" value="search">
								<button name="function" value="deactivate">Deactivate</button>
								<button name="function" value="cancel">Cancel</button>
							</form>
						<?php endif; ?>
					</div>
				<?php endforeach ?>
				<form action="profile.php" method="POST">
					<button name="function" value="clear">Clear search</button>
				</form>
			<?php endif; ?>
		</div>
	</div>
<?php endif ?>

<div class="userComments">
	<h2>Your comments</h2>
	<?php foreach ($userComments as $comment): ?>
		<div class="comment">
			<h4><?= htmlspecialchars($comment['username']) ?></h4>
			<span><?= htmlspecialchars($comment['dateC']) ?></span>
			<p><?= htmlspecialchars($comment['comment']) ?></p>
			<a class="report-link" href="report.php?cid=<?= (int)$comment['id'] ?>&id=<?= (int)$comment['movie_id'] ?>">Report</a>

			<div class="feedback">
				<div class="likes"><span><?= (int)$comment['likes'] ?> likes</span></div>
				<div class="dislikes"><span><?= (int)$comment['dislikes'] ?> dislikes</span></div>
			</div>
		</div>
	<?php endforeach; ?>
</div>

</body>
</html>