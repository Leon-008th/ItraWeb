<?php
session_start();
include_once('config.php');
include_once('API_controll.php');

unset($_SESSION['viewedMovieURL']);

if (empty($_SESSION['username'])) {
    $_SESSION['message'] = 'Not Authorised.';
    header('Location: login.php');
    exit();
}

function h($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

$username = $_SESSION['username'];
$pfp_url = $defaultpfp ?? '';
try {
    $stmt = $conn->prepare('SELECT name, pfp_url FROM users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    if (!empty($userData['pfp_url'])) $pfp_url = $userData['pfp_url'];
} catch (Throwable $e) {
    // Keep the page usable with the default avatar if profile lookup fails.
}

$showId = max(0, (int) ($_GET['id'] ?? 0));
$seasonNumber = max(0, (int) ($_GET['season'] ?? 1));
$show = $showId ? tv_details($showId) : [];

if ($show) {
    $seasons = array_values(array_filter($show['seasons'] ?? [], fn($s) => (int)($s['season_number'] ?? -1) >= 0));
    if (!empty($seasons)) {
        $available = array_map(fn($s) => (int)$s['season_number'], $seasons);
        if (!in_array($seasonNumber, $available, true)) $seasonNumber = $available[0];
    }
    $season = tv_season($showId, $seasonNumber);
} else {
    $hero_tv = popular_tv(8);
    $popular = popular_tv(16);
    $trending = trending_tv(16);
    $top_rated = top_rated_tv(16);
    $airing_today = airing_today_tv(16);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $show ? h($show['name'] ?? 'TV Show') : 'TV Shows' ?> — ItraDB</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#08090d;--surface:#11131a;--surface2:#191c25;--text:#f5f5f7;--muted:#969baa;--line:rgba(255,255,255,.09);--red:#e50914;--orange:#ff5938}
*{box-sizing:border-box}html{background:var(--bg)}body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,sans-serif;min-height:100vh}a{text-decoration:none;color:inherit}button,input,select{font:inherit}
.nav{height:72px;position:fixed;top:0;left:0;right:0;z-index:50;padding:0 clamp(18px,4vw,58px);display:flex;align-items:center;gap:28px;background:linear-gradient(to bottom,rgba(5,6,9,.94),rgba(5,6,9,.55),transparent);transition:.25s}.nav.scrolled{background:rgba(8,9,13,.91);backdrop-filter:blur(16px);border-bottom:1px solid var(--line)}.logo{font-family:'Bebas Neue',sans-serif;font-size:2rem;background:linear-gradient(135deg,var(--red),var(--orange));-webkit-background-clip:text;color:transparent}.links{display:flex;gap:20px;font-size:.86rem;color:#cacdd4}.links a.active,.links a:hover{color:#fff}.nav-right{margin-left:auto;display:flex;align-items:center;gap:12px}.search{width:min(340px,32vw);height:38px;border-radius:9px;border:1px solid rgba(255,255,255,.14);background:rgba(16,18,24,.78);color:#fff;padding:0 12px;outline:none}.avatar{width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid rgba(255,255,255,.18)}
.hero{height:min(78vh,720px);min-height:540px;position:relative;overflow:hidden}.slide{position:absolute;inset:0;opacity:0;transition:opacity .65s;pointer-events:none}.slide.active{opacity:1;pointer-events:auto}.hero-bg{position:absolute;inset:0;background-size:cover;background-position:center 22%;transform:scale(1.01)}.shade{position:absolute;inset:0;background:linear-gradient(90deg,rgba(6,7,10,.98) 0%,rgba(6,7,10,.72) 40%,rgba(6,7,10,.14) 72%),linear-gradient(0deg,var(--bg) 0%,transparent 34%)}.hero-content{position:absolute;left:clamp(22px,6vw,88px);bottom:16%;width:min(610px,75vw)}.kicker{display:inline-flex;align-items:center;gap:8px;text-transform:uppercase;letter-spacing:.12em;font-size:.67rem;font-weight:800;color:#ff7660}.kicker:before{content:'';width:7px;height:7px;border-radius:50%;background:var(--red)}.hero h1{font-family:'Bebas Neue';font-size:clamp(3.6rem,7vw,6.6rem);line-height:.88;letter-spacing:.015em;margin:13px 0}.meta{display:flex;gap:14px;align-items:center;color:#c4c7cf;font-size:.8rem}.rating{color:#ffd86b;font-weight:700}.overview{color:#c6c8cf;line-height:1.6;font-size:.9rem;max-width:620px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}.actions{display:flex;gap:10px;margin-top:22px}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:9px;padding:11px 17px;font-size:.82rem;font-weight:800;cursor:pointer}.btn.primary{background:#fff;color:#0d0e12}.btn.secondary{background:rgba(255,255,255,.12);color:#fff;backdrop-filter:blur(8px)}.btn:hover{filter:brightness(.92)}.dots{position:absolute;z-index:5;right:clamp(20px,5vw,70px);bottom:15%;display:flex;gap:7px}.dot{width:34px;height:5px;border:0;border-radius:99px;background:rgba(255,255,255,.27);cursor:pointer}.dot.active{background:#fff}
.catalog{width:min(1440px,calc(100% - 36px));margin:-20px auto 0;position:relative;z-index:4;padding-bottom:60px}.section{margin:34px 0}.section-head{display:flex;justify-content:space-between;align-items:end;margin-bottom:13px}.section h2{margin:0;font-size:1.15rem}.section-sub{font-size:.72rem;color:var(--muted)}.rail-wrap{position:relative}.rail{display:grid;grid-auto-flow:column;grid-auto-columns:clamp(150px,14vw,205px);gap:12px;overflow-x:auto;scrollbar-width:none;padding:4px 2px 16px}.rail::-webkit-scrollbar{display:none}.card{position:relative;aspect-ratio:2/3;border-radius:12px;overflow:hidden;background:var(--surface);border:1px solid var(--line);cursor:pointer;transition:.22s}.card:hover{transform:translateY(-5px);border-color:rgba(255,255,255,.2);box-shadow:0 16px 32px rgba(0,0,0,.35)}.card img{width:100%;height:100%;object-fit:cover;display:block}.no-poster{height:100%;display:grid;place-items:center;text-align:center;color:#757b89;padding:15px}.card-grad{position:absolute;inset:35% 0 0;background:linear-gradient(transparent,rgba(7,8,11,.94))}.card-info{position:absolute;left:12px;right:12px;bottom:12px}.card-title{font-size:.78rem;font-weight:800}.card-meta{font-size:.65rem;color:#b5b8c0;margin-top:4px}.arrow{position:absolute;z-index:3;top:42%;width:36px;height:54px;border:1px solid rgba(255,255,255,.1);background:rgba(7,8,11,.78);color:#fff;border-radius:10px;cursor:pointer}.arrow.prev{left:-10px}.arrow.next{right:-10px}
.modal-backdrop{position:fixed;inset:0;z-index:90;background:rgba(0,0,0,.72);display:none;place-items:center;padding:20px;backdrop-filter:blur(8px)}.modal-backdrop.open{display:grid}.modal{width:min(760px,100%);background:#11131a;border:1px solid var(--line);border-radius:18px;overflow:hidden;box-shadow:0 30px 90px rgba(0,0,0,.65);position:relative}.modal-img{width:100%;height:300px;object-fit:cover;background:#171922}.modal-body{padding:22px}.modal h3{font-size:1.6rem;margin:0 0 8px}.modal p{color:#b8bbc4;line-height:1.55;font-size:.86rem}.modal-close{position:absolute;right:12px;top:12px;width:36px;height:36px;border:0;border-radius:50%;background:rgba(0,0,0,.72);color:#fff;cursor:pointer}.modal-actions{display:flex;gap:10px;margin-top:17px}
.detail-hero{min-height:520px;position:relative;padding:150px clamp(20px,6vw,90px) 70px;display:flex;align-items:flex-end;background-size:cover;background-position:center}.detail-hero:before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(6,7,10,.97),rgba(6,7,10,.66) 50%,rgba(6,7,10,.25)),linear-gradient(0deg,var(--bg),transparent 55%)}.detail-content{position:relative;z-index:1;max-width:760px}.detail-content h1{font-family:'Bebas Neue';font-size:clamp(3.5rem,8vw,7rem);line-height:.9;margin:12px 0}.genres{display:flex;flex-wrap:wrap;gap:7px;margin-top:14px}.genre{border:1px solid var(--line);background:rgba(255,255,255,.06);padding:6px 9px;border-radius:999px;font-size:.7rem;color:#ced0d6}.detail-main{width:min(1180px,calc(100% - 36px));margin:0 auto;padding:38px 0 70px}.season-bar{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px}.season-bar h2{margin:0}.season-select{background:var(--surface2);border:1px solid var(--line);color:#fff;border-radius:10px;padding:9px 12px}.episodes{display:grid;gap:10px}.episode{display:grid;grid-template-columns:150px 1fr auto;gap:16px;align-items:center;background:var(--surface);border:1px solid var(--line);border-radius:13px;padding:10px}.episode-img{width:150px;aspect-ratio:16/9;object-fit:cover;border-radius:8px;background:#20232d}.ep-num{color:var(--orange);font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.episode h3{font-size:.92rem;margin:4px 0 5px}.episode p{color:var(--muted);font-size:.72rem;line-height:1.45;margin:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.airdate{font-size:.68rem;color:var(--muted);white-space:nowrap}
.footer{border-top:1px solid var(--line);color:#747987;padding:28px clamp(20px,4vw,58px);font-size:.72rem;display:flex;justify-content:space-between}
@media(max-width:720px){.links{display:none}.search{width:44vw}.hero{min-height:600px}.hero-content{bottom:13%;width:calc(100% - 44px)}.overview{-webkit-line-clamp:4}.dots{display:none}.catalog{margin-top:0}.episode{grid-template-columns:95px 1fr}.episode-img{width:95px}.airdate{display:none}.detail-hero{min-height:580px}.footer{gap:10px;flex-direction:column}}
</style>
</head>
<body>
<nav class="nav" id="mainNav">
    <a class="logo" href="home.php">ItraDB</a>
    <div class="links"><a href="home.php">Home</a><a class="active" href="tvshows.php">TV Shows</a><a href="list.php">My List</a></div>
    <div class="nav-right">
        <form action="search_movie.php" method="get"><input class="search" name="movie_string" type="search" placeholder="Search movies & TV…" required><input type="hidden" name="adult" value="false"></form>
        <?php if ($pfp_url): ?><a href="profile.php"><img class="avatar" src="<?= h($pfp_url) ?>" alt="Profile"></a><?php endif; ?>
    </div>
</nav>

<?php if ($show):
    $backdrop = image_url($show['backdrop_path'] ?? null, 'original') ?: image_url($show['poster_path'] ?? null, 'w780') ?: '';
    $firstYear = !empty($show['first_air_date']) ? substr($show['first_air_date'],0,4) : '';
    $lastYear = !empty($show['last_air_date']) ? substr($show['last_air_date'],0,4) : '';
?>
<section class="detail-hero" style="background-image:url('<?= h($backdrop) ?>')">
    <div class="detail-content">
        <div class="kicker">TV Series</div>
        <h1><?= h($show['name'] ?? 'Unknown show') ?></h1>
        <div class="meta">
            <?php if (!empty($show['vote_average'])): ?><span class="rating">★ <?= number_format((float)$show['vote_average'],1) ?></span><?php endif; ?>
            <?php if ($firstYear): ?><span><?= h($firstYear) ?><?= $lastYear && $lastYear !== $firstYear ? '–'.h($lastYear) : '' ?></span><?php endif; ?>
            <?php if (!empty($show['number_of_seasons'])): ?><span><?= (int)$show['number_of_seasons'] ?> season<?= (int)$show['number_of_seasons']===1?'':'s' ?></span><?php endif; ?>
            <?php if (!empty($show['status'])): ?><span><?= h($show['status']) ?></span><?php endif; ?>
        </div>
        <?php if (!empty($show['overview'])): ?><p class="overview"><?= h($show['overview']) ?></p><?php endif; ?>
        <div class="genres"><?php foreach (($show['genres'] ?? []) as $g): ?><span class="genre"><?= h($g['name'] ?? '') ?></span><?php endforeach; ?></div>
        <div class="actions"><a class="btn secondary" href="tvshows.php">← Back to TV</a><?php if (!empty($show['homepage'])): ?><a class="btn primary" href="<?= h($show['homepage']) ?>" target="_blank" rel="noopener">Official site</a><?php endif; ?></div>
    </div>
</section>
<main class="detail-main">
    <div class="season-bar">
        <div><div class="kicker">Episodes</div><h2><?= h($season['name'] ?? ('Season '.$seasonNumber)) ?></h2></div>
        <?php if (!empty($seasons)): ?>
        <form method="get">
            <input type="hidden" name="id" value="<?= $showId ?>">
            <select class="season-select" name="season" onchange="this.form.submit()">
                <?php foreach ($seasons as $s): $sn=(int)$s['season_number']; ?>
                    <option value="<?= $sn ?>" <?= $sn===$seasonNumber?'selected':'' ?>><?= h($s['name'] ?? ('Season '.$sn)) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>
    <div class="episodes">
        <?php foreach (($season['episodes'] ?? []) as $ep):
            $still = image_url($ep['still_path'] ?? null, 'w300');
        ?>
        <article class="episode">
            <?php if ($still): ?><img class="episode-img" src="<?= h($still) ?>" alt="<?= h($ep['name'] ?? '') ?>" loading="lazy"><?php else: ?><div class="episode-img"></div><?php endif; ?>
            <div><div class="ep-num">Episode <?= (int)($ep['episode_number'] ?? 0) ?></div><h3><?= h($ep['name'] ?? 'Untitled episode') ?></h3><p><?= h($ep['overview'] ?? 'No episode description available.') ?></p></div>
            <div class="airdate"><?= h($ep['air_date'] ?? '') ?><?php if (!empty($ep['vote_average'])): ?><br>★ <?= number_format((float)$ep['vote_average'],1) ?><?php endif; ?></div>
        </article>
        <?php endforeach; ?>
        <?php if (empty($season['episodes'])): ?><div style="color:var(--muted);padding:30px 0">No episode data is available for this season.</div><?php endif; ?>
    </div>
</main>

<?php else: ?>
<?php if (!empty($hero_tv)): ?>
<section class="hero" id="hero">
    <?php foreach ($hero_tv as $i=>$m): $heroImage=$m['backdrop_url'] ?: $m['poster_url'] ?: ''; ?>
    <div class="slide <?= $i===0?'active':'' ?>">
        <div class="hero-bg" style="background-image:url('<?= h($heroImage) ?>')"></div><div class="shade"></div>
        <div class="hero-content">
            <div class="kicker">Popular now</div><h1><?= h($m['name']) ?></h1>
            <div class="meta"><span class="rating">★ <?= number_format((float)$m['vote_average'],1) ?></span><?php if ($m['first_air_date']): ?><span><?= h(substr($m['first_air_date'],0,4)) ?></span><?php endif; ?><span>TV Series</span></div>
            <?php if ($m['overview']): ?><p class="overview"><?= h($m['overview']) ?></p><?php endif; ?>
            <div class="actions"><a class="btn primary" href="tvshows.php?id=<?= (int)$m['id'] ?>">View episodes</a><button class="btn secondary" type="button" onclick='openModal(<?= json_encode($m, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP) ?>)'>More info</button></div>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="dots"><?php foreach ($hero_tv as $i=>$m): ?><button class="dot <?= $i===0?'active':'' ?>" onclick="goSlide(<?= $i ?>)" aria-label="Slide <?= $i+1 ?>"></button><?php endforeach; ?></div>
</section>
<?php endif; ?>

<main class="catalog">
<?php
function render_tv_row(string $id,string $title,string $subtitle,array $shows): void {
    if (!$shows) return;
?>
<section class="section"><div class="section-head"><h2><?= h($title) ?></h2><span class="section-sub"><?= h($subtitle) ?></span></div><div class="rail-wrap"><button class="arrow prev" onclick="scrollRow('<?= h($id) ?>',-1)">‹</button><div class="rail" id="<?= h($id) ?>">
<?php foreach ($shows as $m): $year=$m['first_air_date']?substr($m['first_air_date'],0,4):'—'; ?>
<article class="card" onclick='openModal(<?= json_encode($m, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP) ?>)'>
<?php if ($m['poster_url']): ?><img src="<?= h($m['poster_url']) ?>" alt="<?= h($m['name']) ?>" loading="lazy"><?php else: ?><div class="no-poster"><?= h($m['name']) ?></div><?php endif; ?><div class="card-grad"></div><div class="card-info"><div class="card-title"><?= h($m['name']) ?></div><div class="card-meta"><?= h($year) ?> · ★ <?= number_format((float)$m['vote_average'],1) ?></div></div></article>
<?php endforeach; ?>
</div><button class="arrow next" onclick="scrollRow('<?= h($id) ?>',1)">›</button></div></section>
<?php }
render_tv_row('popular','Popular Right Now','Most watched on TMDB',$popular);
render_tv_row('trending','Trending This Week','What people are talking about',$trending);
render_tv_row('airing','Airing Today','New episodes today',$airing_today);
render_tv_row('rated','Top Rated','Highest audience scores',$top_rated);
?>
</main>

<div class="modal-backdrop" id="modalBackdrop" onclick="closeModal(event)"><div class="modal"><button class="modal-close" onclick="closeModal()">×</button><img class="modal-img" id="modalImg" src="" alt=""><div class="modal-body"><h3 id="modalTitle"></h3><div class="meta"><span class="rating" id="modalRating"></span><span id="modalYear"></span></div><p id="modalOverview"></p><div class="modal-actions"><a class="btn primary" id="modalOpen" href="#">View episodes</a><button class="btn secondary" onclick="closeModal()">Close</button></div></div></div></div>
<?php endif; ?>

<footer class="footer"><span>ItraDB</span><span>TV metadata powered by TMDB</span></footer>
<script>
const nav=document.getElementById('mainNav');addEventListener('scroll',()=>nav.classList.toggle('scrolled',scrollY>40),{passive:true});
<?php if (!$show): ?>
const slides=[...document.querySelectorAll('.slide')],dots=[...document.querySelectorAll('.dot')];let current=0,timer;
function goSlide(i){if(!slides.length)return;slides[current].classList.remove('active');dots[current]?.classList.remove('active');current=(i+slides.length)%slides.length;slides[current].classList.add('active');dots[current]?.classList.add('active');clearInterval(timer);timer=setInterval(()=>goSlide(current+1),6500)}if(slides.length)timer=setInterval(()=>goSlide(current+1),6500);
function scrollRow(id,dir){document.getElementById(id)?.scrollBy({left:dir*720,behavior:'smooth'})}
function openModal(m){document.getElementById('modalTitle').textContent=m.name||'Unknown show';document.getElementById('modalOverview').textContent=m.overview||'No description available.';document.getElementById('modalRating').textContent=m.vote_average?'★ '+Number(m.vote_average).toFixed(1):'';document.getElementById('modalYear').textContent=(m.first_air_date||'').slice(0,4);const img=document.getElementById('modalImg');if(m.backdrop_url||m.poster_url){img.src=m.backdrop_url||m.poster_url;img.style.display='block'}else img.style.display='none';document.getElementById('modalOpen').href='tvshows.php?id='+encodeURIComponent(m.id);document.getElementById('modalBackdrop').classList.add('open');document.body.style.overflow='hidden'}
function closeModal(e){if(e&&e.target!==document.getElementById('modalBackdrop'))return;document.getElementById('modalBackdrop')?.classList.remove('open');document.body.style.overflow=''}document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
<?php endif; ?>
</script>
</body>
</html>
