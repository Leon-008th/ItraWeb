<?php

include_once("config.php");

$user_request = $_GET["request"] ?? null;
if ($user_request === "search") {
    $movie_string = urlencode($_GET['movie_name'] ?? '');
    $adult        = $_GET['adult'] ?? 'false';
    header("Location: search_movie.php?movie_string={$movie_string}&adult={$adult}");
    exit();
}

function tmdb_get(string $endpoint): array {
    global $api_url, $auth_B;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $api_url . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [$auth_B, "accept: application/json"],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    $data = json_decode($response, true);
    return $data['results'] ?? $data;
}

function fetch(string $endpoint, int $limit = 10): array {
    global $img_base;

    $results = tmdb_get($endpoint);
    $fields  = ['id', 'title', 'overview', 'poster_path', 'backdrop_path',
                 'release_date', 'vote_average'];
    $movies  = [];

    foreach (array_slice($results, 0, $limit) as $movie) {
        $item = [];
        foreach ($fields as $f) {
            $item[$f] = $movie[$f] ?? null;
        }
        // Build full image URLs
        $item['poster_url']   = $item['poster_path']
            ? $img_base . "w342" . $item['poster_path']
            : null;
        $item['backdrop_url'] = $item['backdrop_path']
            ? $img_base . "w1280" . $item['backdrop_path']
            : null;
        $movies[] = $item;
    }
    return $movies;
}

function fetch_tv(string $endpoint, int $limit = 10): array {
    global $img_base;

    $results = tmdb_get($endpoint);
    $fields  = ['id', 'name', 'overview', 'poster_path', 'backdrop_path',
                 'last_air_date', 'vote_average'];
    $movies  = [];

    foreach (array_slice($results, 0, $limit) as $movie) {
        $item = [];
        foreach ($fields as $f) {
            $item[$f] = $movie[$f] ?? null;
        }
        // Build full image URLs
        $item['poster_url']   = $item['poster_path']
            ? $img_base . "w342" . $item['poster_path']
            : null;
        $item['backdrop_url'] = $item['backdrop_path']
            ? $img_base . "w1280" . $item['backdrop_path']
            : null;
        $movies[] = $item;
    }
    return $movies;
}

function popular_movies(int $limit = 10): array {
    return fetch("3/movie/popular?language=en-US&page=1", $limit);
}

function top_rated_movies(int $limit = 10): array {
    return fetch("3/movie/top_rated?language=en-US&page=1", $limit);
}

function upcoming_movies(int $limit = 10): array {
    return fetch("3/movie/upcoming?language=en-US&page=1", $limit);
}

function trending_movies(int $limit = 10): array {
    return fetch("3/trending/movie/week?language=en-US", $limit);
}

// TV SHOWS

function popular_tv(int $limit = 10) {
    return fetch_tv("/3/tv/popular?language=en-US", $limit);
}

function top_rated_tv(int $limit = 10) {
    return fetch_tv("/3/tv/top_rated?language=en-US", $limit);
}

function trending_tv(int $limit = 10): array {
    return fetch_tv("/trending/tv/week?language=en-US", $limit);
}

function watch_history(int $user_id = 1, int $limit = 10): array {
    global $pdo, $img_base;

    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare(
            "SELECT movie_id, title, poster_path, watched_at
               FROM watch_history
              WHERE user_id = :uid
              ORDER BY watched_at DESC
              LIMIT :lim"
        );
        $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,   PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['poster_url'] = $r['poster_path']
                ? $img_base . "w342" . $r['poster_path']
                : null;
        }
        return $rows;
    } catch (Exception $e) {
        return [];
    }
}

function createPosterURL($path) {
    $posterURL = "https://image.tmdb.org/t/p/w342{$path}";
    return $posterURL;
}