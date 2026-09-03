<?php

require __DIR__ . '/vendor/autoload.php';

use Cloudinary\Cloudinary;

$cloudinary = new Cloudinary([
	'cloud' => [
		'cloud_name' => 'bfleeuws',
		'api_key' => '415272939945986',
		'api_secret' => 'OtC1uPs_DGldW_acbNUyzEqJIgE'
	]

]);

$defaultpfp = "https://res.cloudinary.com/bfleeuws/image/upload/v1784809620/white_pfp_q2b3qi.png";

date_default_timezone_set('Europe/Berlin');
$curr_date = new DateTime(date('F j, Y'));

$server = "localhost";
$dbname = "moviedb";
$username = "root";
$pass = "";

$server_url = "http://localhost/ItraWeb/";

$vidSrcURL_m = "https://vidsrc.to/embed/movie/";

$api_key = "15e3fbfe58ab74b74452af229c3cd091";
$auth_B  = "Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIxNWUzZmJmZTU4YWI3NGI3NDQ1MmFmMjI5YzNjZDA5MSIsIm5iZiI6MTc4MTY0MTA4NS42MTksInN1YiI6IjZhMzFhZjdkM2I4MDhkM2ZkY2QzODlmOCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.bqet-vcz2dPRX4DOYBw5Slu7Sh5i9ZCZlzu61Esnoro";
$api_url = "https://api.themoviedb.org/";
$img_base = "https://image.tmdb.org/t/p/";

try {
	$conn = new PDO("mysql:host={$server};dbname={$dbname}", $username, $pass);
} catch (PDOException $e) {
	echo "Error!" . $e->getMessage();
}

function kick($is_messaged, $url) {
	if ($is_messaged['is_error'] == True) {
		$_SESSION['message'] = $is_messaged['m'];
	}
	header("Location: {$url}");
	exit();
}

?>