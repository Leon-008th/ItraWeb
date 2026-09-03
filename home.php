<?php
session_start();

include_once("config.php");
include_once("API_controll.php");

unset($_SESSION['viewedMovieURL']);

if (empty($_SESSION['username'])) {
    $_SESSION['message'] = "Not Authorised.";
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

try {
    $sql = "SELECT name, pfp_url FROM users WHERE username = :username";
    $insertSql = $conn->prepare($sql);
    $insertSql->execute([
        ":username" => $username
    ]);

    $userData = $insertSql->fetch();
} catch (Exception $e) {
    echo "Error!" . $e->getMessage();
    exit();
}

$pfp_url = $userData['pfp_url'];

if ($pfp_url == 0) {
    $pfp_url = $defaultpfp;
}

// Fetch all data upfront
$hero_movies    = popular_movies(8);
$hero           = $hero_movies[0] ?? null;
# $continued      = watch_history(1, 12);
$popular        = popular_movies(12);
$top_rated      = top_rated_movies(12);
$upcoming       = upcoming_movies(12);
$trending       = trending_movies(12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ItraDB — Stream Movies</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────────── -->
<nav class="nav" id="mainNav">
    <span class="nav-logo">ItraDB</span>
    <ul class="nav-links">
        <li><a href="home.php">Home</a></li>
    </ul>
    <div class="nav-right">
        <!--<form action="API_controll.php" method="GET">
            <input class="nav-search" type="text" name="movie_name"
                   placeholder="Search titles…" required>
            <input type="hidden" name="request" value="search">
            <input type="hidden" name="adult"   value="false">
        </form>-->

        <!-- Search movies/series ~~ LATER UPDATE -->

        <div class="profile">
            <div class="profilePhoto">
                <a href="profile.php"><img class="avatar avatar-sm" src="<?= $pfp_url ?>"></a>
            </div>
        </div>
    </div>
</nav>

<!-- ── Hero ───────────────────────────────────────────── -->
<?php if (!empty($hero_movies)): ?>
<section class="hero" id="hero">

    <?php foreach ($hero_movies as $idx => $m): ?>
    <div class="hero-slide <?= $idx === 0 ? 'active' : '' ?>"
         data-id="<?= (int)$m['id'] ?>"
         data-slide="<?= $idx ?>">

        <div class="hero-bg"
        	<?php $heroImage = $m['backdrop_url'] ?: $m['poster_url'] ?: ''; ?>
             style="background-image: url('<?= htmlspecialchars($heroImage) ?>')">
        </div>
        <div class="hero-gradient"></div>

        <div class="hero-content">
            <span class="hero-badge">
                <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor">
                    <circle cx="5" cy="5" r="5"/>
                </svg>
                Now Popular
            </span>

            <h1 class="hero-title"><?= htmlspecialchars($m['title']) ?></h1>

            <div class="hero-meta">
                <span class="hero-rating">
                    ★ <?= number_format((float)($m['vote_average'] ?? 0), 1) ?>
                </span>
                <?php if (!empty($m['release_date'])): ?>
                <span><?= substr($m['release_date'], 0, 4) ?></span>
                <?php endif; ?>
            </div>

            <p class="hero-overview"><?= htmlspecialchars($m['overview'] ?? '') ?></p>

            <div class="hero-actions">
                <a href="movie.php?movie=<?=htmlspecialchars($m['title'])?>&id=<?=(int)$m['id']?>"
                   class="btn-watch" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    Watch Now
                </a>
                <button class="btn-info"
                        onclick="openModal(<?= htmlspecialchars(json_encode($m)) ?>)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="8"/>
                        <line x1="12" y1="11" x2="12" y2="17"/>
                    </svg>
                    More Info
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Thumbnail rail -->
    <div class="hero-rail">
        <?php foreach (array_slice($hero_movies, 0, 6) as $idx => $m): ?>
        <div class="hero-thumb <?= $idx === 0 ? 'active' : '' ?>"
             data-target="<?= $idx ?>" onclick="goSlide(<?= $idx ?>)">
            <?php if ($m['poster_url']): ?>
            <img src="<?= htmlspecialchars($m['poster_url']) ?>"
                 alt="<?= htmlspecialchars($m['title']) ?>">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

</section>
<?php endif; ?>

<!-- ── Rows below hero ────────────────────────────────── -->
<main style="margin-top: 40px;">

<?php
/* Helper: render a horizontal movie row */
function render_row(string $id, string $title, array $movies, bool $show_progress = false): void {
    if (empty($movies)) return;
    ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title"><?= htmlspecialchars($title) ?></h2>
            <span class="section-link">See all &rsaquo;</span>
        </div>
        <div class="row-wrap">
            <button class="row-btn prev" onclick="scrollRow('<?= $id ?>', -1)" aria-label="Previous">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <div class="row" id="<?= $id ?>">
                <?php foreach ($movies as $m):
                    $poster = $m['poster_url'] ?? null;
                    $mid    = (int)($m['id'] ?? $m['movie_id'] ?? 0);
                    $title_esc = htmlspecialchars($m['title'] ?? 'Unknown');
                    $year = !empty($m['release_date']) ? substr($m['release_date'], 0, 4) : '';
                ?>
                <div class="card" onclick="openModal(<?= htmlspecialchars(json_encode($m)) ?>)">
                    <?php if ($poster): ?>
                    <img src="<?= htmlspecialchars($poster) ?>"
                         alt="<?= $title_esc ?>" loading="lazy">
                    <?php else: ?>
                    <div class="card-no-img">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none"
                             stroke="rgba(255,255,255,.3)" stroke-width="1.5">
                            <rect x="2" y="3" width="20" height="14" rx="2"/>
                            <path d="M8 21h8M12 17v4"/>
                        </svg>
                        <span><?= $title_esc ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="card-overlay">
                        <div>
                            <div class="card-title"><?= $title_esc ?></div>
                            <?php if ($year): ?><div class="card-year"><?= $year ?></div><?php endif; ?>
                        </div>
                        <a href="movie.php?movie=<?=htmlspecialchars($m['title'])?>&id=<?=(int)$m['id']?>"
                           class="card-play" target="_blank" rel="noopener"
                           onclick="event.stopPropagation()" aria-label="Play">
                            <svg viewBox="0 0 24 24" fill="white"><path d="M8 5v14l11-7z"/></svg>
                        </a>
                    </div>

                    <?php if ($show_progress): ?>
                    <div class="card-progress">
                        <div class="card-progress-fill"
                             style="width: <?= rand(15, 85) ?>%"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="row-btn next" onclick="scrollRow('<?= $id ?>', 1)" aria-label="Next">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
            </button>
        </div>
    </section>
    <?php
}

// Continue watching row (top, only shown if DB has history)
if (!empty($continued)) {
    render_row('row-continue', 'Continue Watching', $continued, true);
}

render_row('row-popular',   'Popular Right Now',  $popular);
render_row('row-trending',  'Trending This Week',  $trending);
render_row('row-toprated',  'All-Time Greats',     $top_rated);
render_row('row-upcoming',  'Coming Soon',         $upcoming);
?>

</main>

<!-- ── Footer ─────────────────────────────────────────── -->
<footer class="footer">
    <span class="footer-logo">ItraDB</span>
    <span>Powered by TMDB &amp; VidSrc</span>
</footer>

<!-- ── Modal ──────────────────────────────────────────── -->
<div class="modal-backdrop" id="modalBackdrop" onclick="closeModal(event)">
    <div class="modal" id="modal">
        <button class="modal-close" onclick="closeModal()" aria-label="Close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
        <img class="modal-backdrop-img" id="modalImg" src="" alt="">
        <div class="modal-gradient"></div>
        <div class="modal-body">
            <h2 class="modal-title" id="modalTitle"></h2>
            <div class="modal-meta">
                <span class="modal-rating" id="modalRating"></span>
                <span id="modalYear"></span>
            </div>
            <p class="modal-overview" id="modalOverview"></p>
            <div class="modal-actions">
                <a id="modalWatch" href="#" target="_blank" rel="noopener" class="btn-watch">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    Watch Now
                </a>
            </div>
        </div>
    </div>
</div>

<script>
/* ── Navbar scroll tint ─── */
const nav = document.getElementById('mainNav');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
}, { passive: true });

/* ── Hero carousel ──────── */
const slides  = document.querySelectorAll('.hero-slide');
const thumbs  = document.querySelectorAll('.hero-thumb');
let current   = 0;
let autoTimer = null;

function goSlide(idx) {
    slides[current].classList.remove('active');
    thumbs[current]?.classList.remove('active');
    current = (idx + slides.length) % slides.length;
    slides[current].classList.add('active');
    thumbs[current]?.classList.add('active');
    resetAuto();
}

function resetAuto() {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => goSlide(current + 1), 6000);
}

resetAuto();

/* ── Row scroll buttons ─── */
function scrollRow(id, dir) {
    const el = document.getElementById(id);
    el.scrollBy({ left: dir * 600, behavior: 'smooth' });
}

/* Drag-to-scroll for rows */
document.querySelectorAll('.row').forEach(row => {
    let startX, scrollLeft, isDragging = false;
    row.addEventListener('mousedown', e => {
        isDragging = true;
        startX     = e.pageX - row.offsetLeft;
        scrollLeft = row.scrollLeft;
        row.style.cursor = 'grabbing';
    });
    row.addEventListener('mousemove', e => {
        if (!isDragging) return;
        e.preventDefault();
        const x = e.pageX - row.offsetLeft;
        row.scrollLeft = scrollLeft - (x - startX) * 1.2;
    });
    ['mouseup','mouseleave'].forEach(ev =>
        row.addEventListener(ev, () => { isDragging = false; row.style.cursor = 'grab'; })
    );
});

/* ── Modal ──────────────── */
function openModal(m) {
    document.getElementById('modalTitle').textContent    = m.title || '';
    document.getElementById('modalOverview').textContent = m.overview || 'No description available.';
    document.getElementById('modalRating').textContent   = m.vote_average ? '★ ' + parseFloat(m.vote_average).toFixed(1) : '';
    document.getElementById('modalYear').textContent     = (m.release_date || '').slice(0, 4);

    const img = document.getElementById('modalImg');
    if (m.backdrop_url) {
        img.src   = m.backdrop_url;
        img.style.display = 'block';
    } else if (m.poster_url) {
        img.src   = m.poster_url;
        img.style.display = 'block';
    } else {
        img.style.display = 'none';
    }

    const mid = m.id || m.movie_id || '';
    const encodedTitle = encodeURIComponent(m.title || '');
    document.getElementById('modalWatch').href = `movie.php?movie=${encodedTitle}&id=${mid}`;

    document.getElementById('modalBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(e) {
    if (e && e.target !== document.getElementById('modalBackdrop')) return;
    document.getElementById('modalBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('modalBackdrop').classList.remove('open');
        document.body.style.overflow = '';
    }
});
</script>

</body>
</html>