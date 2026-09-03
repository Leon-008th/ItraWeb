<?php

session_start();
include_once('config.php');

$error_message = $_SESSION['message'] ?? "";
unset($_SESSION['message']);
$passkey = $_GET['pass'] ?? "";

$came_from = $_SESSION['came_from'] ?? NULL;
$username = $_SESSION['username'] ?? NULL;

if (empty($passkey)) {
	header("Location: 404.php");
	exit();
}

if ($came_from == "profile.php" || !empty($username)) {
	kick(array('is_error' => True, 'm' => 'You already have an account'), "profile.php");
}

try {
	$sql = "SELECT is_approved FROM user_creation WHERE password = :pass";
	$insertSql = $conn->prepare($sql);

	$insertSql->execute([
		":pass" => $passkey,
	]);
	$claim = $insertSql->fetch();

	if (empty($claim) || $claim['is_approved'] != "approved") {
		header("Location: 404.php");;
		exit();
	}
} catch (Exception $e) {
	header("Location: 404.php");
	exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Create account — ItraDB</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="style.css">
	<style>
		.auth-page {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
		}
		.auth-card {
			background: var(--surface);
			border: 1px solid rgba(255,255,255,.08);
			border-radius: 12px;
			padding: 40px 36px;
			width: 100%;
			max-width: 400px;
			box-shadow: 0 20px 60px rgba(0,0,0,.5);
		}
		.auth-logo {
			font-family: 'Bebas Neue', sans-serif;
			font-size: 2.2rem;
			letter-spacing: 2px;
			text-align: center;
			background: linear-gradient(135deg, var(--accent), var(--accent2));
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
			margin-bottom: 6px;
		}
		.auth-subtitle {
			text-align: center;
			color: var(--muted);
			font-size: .85rem;
			margin-bottom: 28px;
		}
		.error-msg {
			background: rgba(229,9,20,.12);
			border: 1px solid rgba(229,9,20,.4);
			color: #ff8a8a;
			padding: 10px 14px;
			border-radius: 8px;
			font-size: .85rem;
			margin-bottom: 18px;
		}
		.auth-form {
			display: flex;
			flex-direction: column;
			gap: 16px;
		}
		.auth-field {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}
		.auth-field label {
			font-size: .8rem;
			color: var(--muted);
			font-weight: 500;
		}
		.auth-field input {
			background: rgba(255,255,255,.05);
			border: 1px solid rgba(255,255,255,.12);
			border-radius: 8px;
			padding: 12px 14px;
			color: var(--text);
			font-family: 'Inter', sans-serif;
			font-size: .9rem;
			outline: none;
			transition: border-color .2s;
		}
		.auth-field input:focus { border-color: var(--accent); }
		.auth-submit {
			margin-top: 8px;
			background: var(--accent);
			color: #fff;
			border: none;
			border-radius: 8px;
			padding: 13px;
			font-family: 'Inter', sans-serif;
			font-weight: 600;
			font-size: .9rem;
			cursor: pointer;
			transition: background .2s, transform .15s;
		}
		.auth-submit:hover { background: #c1000f; transform: translateY(-1px); }
		.auth-footer {
			text-align: center;
			margin-top: 22px;
			font-size: .85rem;
			color: var(--muted);
		}
		.auth-footer a { color: var(--accent); font-weight: 600; }
	</style>
</head>
<body>
	<div class="auth-page">
		<div class="auth-card">
			<div class="auth-logo">ItraDB</div>
			<p class="auth-subtitle">Create your account with an invite passkey</p>

			<?php if (!empty($error_message)): ?>
				<div class="error-msg"><?= htmlspecialchars($error_message) ?></div>
			<?php endif; ?>

			<form class="auth-form" action="AcCreate_logic.php" method="POST">
				<div class="auth-field">
					<label for="passkey">Passkey</label>
					<input type="text" id="passkey" name="passkey" value="<?= $passkey ?>" required>
				</div>

				<div class="auth-field">
					<label for="name">Name</label>
					<input type="text" id="name" name="name" required>
				</div>

				<div class="auth-field">
					<label for="username">Username</label>
					<input type="text" id="username" name="username" required autocomplete="username">
				</div>

				<div class="auth-field">
					<label for="password">Password</label>
					<input type="password" id="password" name="password" required autocomplete="new-password">
				</div>

				<button type="submit" class="auth-submit">Create account</button>
			</form>
		</div>
	</div>
</body>
</html>