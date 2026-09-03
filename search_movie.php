<?php

session_start();
include_once('config.php');
include_once('API_controll.php');

kick(array('is_error' => True, 'm' => "Later Addition :)"), "login.php");

$query = trim($_GET['movie_string'] ?? $_GET['q'] ?? '');
$includeAdult = ($_GET['adult'] ?? 'false') === 'true';
$page = max(1, (int) ($_GET['page'] ?? 1));
$typeFilter = $_GET['type'] ?? 'all';
$sort = $_GET['sort'] ?? 'relevance';

if (!in_array($typeFilter, ['all', 'movie', 'tv'], true)) $typeFilter = 'all';
if (!in_array($sort, ['relevance', 'rating', 'newest'], true)) $sort = 'relevance';

$search = search_titles($query, $includeAdult, $page);
$results = $search['results'];

if ($typeFilter !== 'all') {
    $results = array_values(array_filter($results, fn($item) => $item['media_type'] === $typeFilter));
}

if ($sort === 'rating') {
    usort($results, fn($a, $b) => $b['vote_average'] <=> $a['vote_average']);
} elseif ($sort === 'newest') {
    usort($results, fn($a, $b) => strcmp($b['date'] ?: '0000-00-00', $a['date'] ?: '0000-00-00'));
}

function h($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function result_url(array $item): string {
    if ($item['media_type'] === 'tv') {
        return 'tvshows.php?id=' . (int) $item['id'];
    }
    return 'movie.php?movie=' . rawurlencode($item['title']) . '&id=' . (int) $item['id'];
}
function query_url(array $overrides = []): string {
    global $query, $includeAdult, $page, $typeFilter, $sort;
    $params = array_merge([
        'movie_string' => $query,
        'adult' => $includeAdult ? 'true' : 'false',
        'page' => $page,
        'type' => $typeFilter,
        'sort' => $sort,
    ], $overrides);
    return 'search_movie.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $query !== '' ? 'Search: ' . h($query) : 'Search' ?> — ItraDB</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#090a0e;--panel:#11131a;--panel2:#181b24;--text:#f4f5f7;--muted:#969baa;--line:rgba(255,255,255,.09);--accent:#e50914;--accent2:#ff5a36}
*{box-sizing:border-box}html{background:var(--bg)}body{margin:0;background:radial-gradient(circle at 20% 0,rgba(229,9,20,.13),transparent 28rem),var(--bg);color:var(--text);font-family:Inter,sans-serif;min-height:100vh}a{text-decoration:none;color:inherit}
.nav{height:72px;position:sticky;top:0;z-index:20;padding:0 clamp(18px,4vw,58px);display:flex;align-items:center;gap:28px;background:rgba(9,10,14,.82);backdrop-filter:blur(16px);border-bottom:1px solid var(--line)}
.logo{font-family:'Bebas Neue',sans-serif;font-size:2rem;letter-spacing:.5px;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;color:transparent;white-space:nowrap}.nav-links{display:flex;gap:20px;color:#c7cad1;font-size:.88rem}.nav-links a:hover{color:#fff}
.searchbar{margin-left:auto;display:flex;width:min(520px,55vw);height:40px}.searchbar input{width:100%;border:1px solid var(--line);border-right:0;border-radius:10px 0 0 10px;background:#14161d;color:#fff;padding:0 14px;outline:none}.searchbar input:focus{border-color:rgba(229,9,20,.6)}.searchbar button{border:0;border-radius:0 10px 10px 0;background:var(--accent);color:#fff;font-weight:700;padding:0 18px;cursor:pointer}
main{width:min(1320px,calc(100% - 36px));margin:0 auto;padding:46px 0 70px}.eyebrow{color:var(--accent2);font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase}.heading{display:flex;justify-content:space-between;gap:20px;align-items:end;margin:7px 0 25px}.heading h1{font-size:clamp(1.55rem,3vw,2.5rem);margin:0}.count{color:var(--muted);font-size:.84rem;margin-top:7px}
.toolbar{display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:24px}.filters,.sort{display:flex;gap:8px;align-items:center}.pill,select{border:1px solid var(--line);background:var(--panel);color:#c9ccd3;border-radius:999px;padding:9px 13px;font:inherit;font-size:.78rem;cursor:pointer}.pill.active{background:#fff;color:#111;border-color:#fff;font-weight:700}select{outline:none;padding-right:28px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px}.card{background:var(--panel);border:1px solid var(--line);border-radius:14px;overflow:hidden;transition:transform .22s,border-color .22s,box-shadow .22s;min-width:0}.card:hover{transform:translateY(-5px);border-color:rgba(255,255,255,.18);box-shadow:0 16px 36px rgba(0,0,0,.34)}.poster{aspect-ratio:2/3;background:linear-gradient(145deg,#181b24,#0f1015);position:relative;overflow:hidden}.poster img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s}.card:hover .poster img{transform:scale(1.035)}.fallback{height:100%;display:grid;place-items:center;text-align:center;color:#737886;padding:18px}.badge{position:absolute;top:10px;left:10px;background:rgba(8,9,13,.82);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.13);border-radius:999px;padding:5px 8px;text-transform:uppercase;font-size:.62rem;font-weight:800;letter-spacing:.08em}.rating{position:absolute;right:10px;bottom:10px;border-radius:999px;padding:5px 8px;background:rgba(8,9,13,.82);font-size:.7rem;font-weight:700}.info{padding:13px 13px 15px}.title{font-size:.91rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.meta{display:flex;justify-content:space-between;gap:8px;color:var(--muted);font-size:.72rem;margin-top:6px}.overview{font-size:.72rem;line-height:1.45;color:#afb3be;margin:10px 0 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.open{display:flex;align-items:center;justify-content:center;margin-top:12px;padding:9px 10px;border-radius:9px;background:#232631;font-size:.76rem;font-weight:700;transition:.2s}.open:hover{background:var(--accent)}
.empty{border:1px dashed rgba(255,255,255,.13);background:rgba(255,255,255,.025);border-radius:18px;padding:70px 20px;text-align:center;color:var(--muted)}.empty strong{display:block;color:#fff;font-size:1.2rem;margin-bottom:8px}.pagination{display:flex;justify-content:center;align-items:center;gap:10px;margin-top:38px}.page-btn{border:1px solid var(--line);background:var(--panel);padding:10px 14px;border-radius:10px;font-size:.78rem}.page-btn:hover{background:var(--panel2)}.page-status{color:var(--muted);font-size:.75rem}
@media(max-width:700px){.nav-links{display:none}.nav{gap:14px}.searchbar{width:100%}.searchbar button{padding:0 12px}.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}main{padding-top:28px}.heading{align-items:start;flex-direction:column}.overview{display:none}}
</style>
</head>
<body>
<nav class="nav">
    <a class="logo" href="home.php">ItraDB</a>
    <div class="nav-links"><a href="home.php">Home</a><a href="tvshows.php">TV Shows</a><a href="list.php">My List</a></div>
    <form class="searchbar" action="search_movie.php" method="get">
        <input type="search" name="movie_string" value="<?= h($query) ?>" placeholder="Search movies and TV shows…" required>
        <input type="hidden" name="adult" value="false">
        <button type="submit">Search</button>
    </form>
</nav>
<main>
    <div class="eyebrow">Search</div>
    <div class="heading">
        <div>
            <h1><?= $query !== '' ? 'Results for “' . h($query) . '”' : 'Find something to watch' ?></h1>
            <div class="count"><?= $query !== '' ? number_format((int)$search['total_results']) . ' TMDB matches · ' . count($results) . ' shown on this page' : 'Search across movies and television.' ?></div>
        </div>
    </div>

    <?php if ($query !== ''): ?>
    <div class="toolbar">
        <div class="filters">
            <a class="pill <?= $typeFilter === 'all' ? 'active' : '' ?>" href="<?= h(query_url(['type'=>'all','page'=>1])) ?>">All</a>
            <a class="pill <?= $typeFilter === 'movie' ? 'active' : '' ?>" href="<?= h(query_url(['type'=>'movie','page'=>1])) ?>">Movies</a>
            <a class="pill <?= $typeFilter === 'tv' ? 'active' : '' ?>" href="<?= h(query_url(['type'=>'tv','page'=>1])) ?>">TV</a>
        </div>
        <form class="sort" method="get">
            <input type="hidden" name="movie_string" value="<?= h($query) ?>">
            <input type="hidden" name="adult" value="<?= $includeAdult ? 'true' : 'false' ?>">
            <input type="hidden" name="type" value="<?= h($typeFilter) ?>">
            <label for="sort" style="font-size:.75rem;color:var(--muted)">Sort</label>
            <select id="sort" name="sort" onchange="this.form.submit()">
                <option value="relevance" <?= $sort==='relevance'?'selected':'' ?>>Relevance</option>
                <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Rating</option>
                <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
            </select>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($query === ''): ?>
        <div class="empty"><strong>Start with a title.</strong>Movies and TV shows are searched together, then routed to the correct page.</div>
    <?php elseif (empty($results)): ?>
        <div class="empty"><strong>No matches on this page.</strong>Try a broader title or switch the Movies / TV filter.</div>
    <?php else: ?>
    <div class="grid">
        <?php foreach ($results as $item):
            $year = $item['date'] ? substr($item['date'], 0, 4) : '—';
            $url = result_url($item);
        ?>
        <article class="card">
            <a href="<?= h($url) ?>">
                <div class="poster">
                    <?php if ($item['poster_url']): ?>
                        <img src="<?= h($item['poster_url']) ?>" alt="<?= h($item['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="fallback">No poster available<br><?= h($item['title']) ?></div>
                    <?php endif; ?>
                    <span class="badge"><?= $item['media_type'] === 'tv' ? 'TV' : 'Movie' ?></span>
                    <?php if ($item['vote_average'] > 0): ?><span class="rating">★ <?= number_format($item['vote_average'], 1) ?></span><?php endif; ?>
                </div>
                <div class="info">
                    <div class="title" title="<?= h($item['title']) ?>"><?= h($item['title']) ?></div>
                    <div class="meta"><span><?= h($year) ?></span><span><?= $item['media_type'] === 'tv' ? 'Series' : 'Film' ?></span></div>
                    <?php if ($item['overview']): ?><p class="overview"><?= h($item['overview']) ?></p><?php endif; ?>
                    <span class="open"><?= $item['media_type'] === 'tv' ? 'Open show' : 'Open movie' ?> →</span>
                </div>
            </a>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($query !== '' && $search['total_pages'] > 1): ?>
    <nav class="pagination" aria-label="Search pages">
        <?php if ($page > 1): ?><a class="page-btn" href="<?= h(query_url(['page'=>$page-1])) ?>">← Previous</a><?php endif; ?>
        <span class="page-status">Page <?= $page ?> of <?= $search['total_pages'] ?></span>
        <?php if ($page < $search['total_pages']): ?><a class="page-btn" href="<?= h(query_url(['page'=>$page+1])) ?>">Next →</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
</body>
</html>
