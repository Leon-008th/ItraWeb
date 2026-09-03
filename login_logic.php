<?php

session_start();
include_once("config.php");

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

try {
    $sql = "SELECT id, password, is_banned, is_active, is_banned_till, is_banned_reason FROM users WHERE username = :username";
    $insertSql = $conn->prepare($sql);

    $insertSql->execute([
        ":username" => $username
    ]);
    
    $data = $insertSql->fetch();

    if (!$data) {
        kick(array('is_error' => True, 'm' => "Invalid username or password"), "login.php");
    }

    $is_deactivated = False;
    if (!empty($data['is_banned_till'])) {
        $date_till_is_banned = new DateTime($data['is_banned_till']);

        $is_banned_reason = $data['is_banned_reason'];

        if ($curr_date > $date_till_is_banned) {

            try {
                $sql = "UPDATE users SET is_banned_till = :duration, is_banned_reason = :reason WHERE username = :username";
                $insertSql = $conn->prepare($sql);
                $insertSql->execute([
                    ":duration" => NULL,
                    ":reason" => NULL,
                    ":username" => $username
                ]);
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        } elseif ($curr_date < $date_till_is_banned) {
            $is_deactivated = True;
        }
    } else {
        $date_till_is_banned = NULL;
    }

    if ($data && password_verify($password, $data['password']) && $data['is_banned'] == 0 && $data['is_active'] == 0 && !$is_deactivated) {
        
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $data['id'];
        $_SESSION['username'] = $username;
        session_write_close();

        $sql = "UPDATE users SET is_active = :is_active WHERE id = :id";
        $insertSql = $conn->prepare($sql);
        $insertSql->execute([
            ":is_active" => 1,
            ":id" => $data['id']
        ]);
        
        header("Location: home.php");
        exit();
        
    } elseif ($data['is_banned'] == 1) {
        kick(array('is_error' => True, 'm' => "You are banned, appeal ban to admins"), "login.php");
    } elseif ($is_deactivated) {
        kick(array('is_error' => True, 'm' => "Your account has been deactivated untill {$date_till_is_banned->format('F j, Y')} for {$is_banned_reason}"), "login.php");
    } elseif ($data['is_active'] == 1) {
        kick(array('is_error' => True, 'm' => "Acount already in use"), "login.php");
    } else {
        kick(array('is_error' => True, 'm' => "Incorrect username or password"), "login.php");
    }
    
} catch (Exception $e) {
    $_SESSION['message'] = "An error occurred. Please try again.";
    session_write_close();
    header("Location: login.php");
    exit;
}