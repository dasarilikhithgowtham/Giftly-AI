
<?php
// Giftly AI - Premium Gift Recommendation Form

$dbHost = getenv("DB_HOST") ?: "localhost";
$dbUser = getenv("DB_USER") ?: "root";
$dbPassword = getenv("DB_PASSWORD") ?: "";
$dbName = getenv("DB_NAME") ?: "ai_gift_db";
$dbPort = getenv("DB_PORT") ?: "3306";

$recentChats = [];

try {
    $sidebarPdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

    $recentStmt = $sidebarPdo->query(
        "SELECT id, recipient_name, occasion, conversation_title, created_at
         FROM gift_history
         ORDER BY created_at DESC, id DESC LIMIT 20"
    );

    $recentChats = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    $recentChats = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giftly AI | Discover the Perfect Gift</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --bg:#090914;
    --panel:rgba(23,22,43,.88);
    --panel2:#121225;
    --purple:#a78bfa;
    --pink:#f472b6;
    --orange:#fb923c;
    --text:#f7f5ff;
    --muted:#a5a2bd;
    --border:rgba(255,255,255,.09);
    --gradient:linear-gradient(110deg,#8b5cf6,#d946ef,#ec4899);
}

* {margin:0;padding:0;box-sizing:border-box}

body {
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

body::before,body::after {
    content:"";
    position:fixed;
    width:420px;height:420px;
    border-radius:50%;
    filter:blur(130px);
    opacity:.15;
    pointer-events:none;
    z-index:-1;
}
body::before {background:#9333ea;top:-180px;left:-140px}
body::after {background:#ec4899;right:-180px;bottom:-150px}

nav {
    height:76px;
    padding:0 8%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:1px solid var(--border);
    background:rgba(9,9,20,.65);
    backdrop-filter:blur(15px);
    position:sticky;
    top:0;
    z-index:10;
}

.logo {
    font-family:'Outfit',sans-serif;
    font-size:25px;
    font-weight:800;
    letter-spacing:-1px;
}
.logo span {color:var(--pink)}

.nav-tag {
    font-size:12px;
    color:#d8caff;
    border:1px solid rgba(167,139,250,.3);
    background:rgba(167,139,250,.08);
    border-radius:30px;
    padding:9px 15px;
}

.hero {
    max-width:850px;
    margin:auto;
    text-align:center;
    padding:65px 20px 38px;
}

.hero-badge {
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:9px 17px;
    background:rgba(167,139,250,.1);
    border:1px solid rgba(167,139,250,.25);
    border-radius:30px;
    color:#d8caff;
    font-size:12px;
    font-weight:700;
    margin-bottom:23px;
}

.hero h1 {
    font-family:'Outfit',sans-serif;
    font-size:clamp(37px,6vw,65px);
    line-height:1.1;
    letter-spacing:-2px;
    margin-bottom:19px;
}

.gradient-text {
    background:var(--gradient);
    background-clip:text;
    -webkit-background-clip:text;
    color:transparent;
}

.hero p {
    max-width:630px;
    margin:auto;
    color:var(--muted);
    font-size:15px;
    line-height:1.8;
}

.container {
    max-width:900px;
    margin:0 auto 70px;
    padding:0 20px;
}

.form-card {
    background:var(--panel);
    border:1px solid var(--border);
    border-radius:24px;
    padding:36px;
    box-shadow:0 25px 90px rgba(0,0,0,.35);
    backdrop-filter:blur(18px);
}

.progress-area {margin-bottom:35px}

.progress-top {
    display:flex;
    justify-content:space-between;
    color:var(--muted);
    font-size:12px;
    margin-bottom:10px;
}

.progress-track {
    height:5px;
    background:#302b43;
    border-radius:10px;
    overflow:hidden;
}

.progress-bar {
    height:100%;
    width:0;
    background:var(--gradient);
    border-radius:10px;
    transition:width .3s;
}

.section {margin-bottom:38px}

.section-heading {
    display:flex;
    align-items:center;
    gap:14px;
    border-bottom:1px solid var(--border);
    padding-bottom:17px;
    margin-bottom:23px;
}

.section-icon {
    width:45px;height:45px;
    display:grid;
    place-items:center;
    background:rgba(167,139,250,.12);
    border:1px solid rgba(167,139,250,.18);
    border-radius:14px;
    font-size:21px;
    flex-shrink:0;
}

.section-heading h2 {
    font-family:'Outfit',sans-serif;
    font-size:19px;
}

.section-heading p {
    color:var(--muted);
    font-size:12px;
    margin-top:4px;
}

.form-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:19px;
}

.form-group {margin-bottom:19px}
.full-width {grid-column:1/-1}

label {
    display:block;
    color:#e8e3f5;
    font-size:13px;
    font-weight:700;
    margin-bottom:9px;
}

.required {color:var(--pink)}
.optional {color:var(--muted);font-size:11px;font-weight:400}

input,select,textarea {
    width:100%;
    padding:14px 15px;
    background:#100f1e;
    color:white;
    border:1px solid #38334d;
    border-radius:11px;
    font-family:inherit;
    font-size:13px;
    outline:none;
    transition:border .2s,box-shadow .2s,background .2s;
}

input:focus,select:focus,textarea:focus {
    border-color:var(--purple);
    box-shadow:0 0 0 3px rgba(167,139,250,.12);
    background:#151329;
}

input::placeholder,textarea::placeholder {color:#77738b}
select {cursor:pointer}
select option {background:#171626;color:white}
input[type=date] {color-scheme:dark}
textarea {resize:vertical;min-height:110px;line-height:1.6}

.field-hint {
    font-size:11px;
    color:var(--muted);
    margin-top:7px;
}

.interest-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:13px;
}

.interest-count {
    font-size:11px;
    color:var(--purple);
    background:rgba(167,139,250,.1);
    padding:6px 10px;
    border-radius:20px;
}

.interest-grid {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
}

.interest-option {position:relative;margin:0}

.interest-option input {
    position:absolute;
    opacity:0;
    width:1px;
    height:1px;
}

.interest-option span {
    display:flex;
    justify-content:center;
    align-items:center;
    gap:7px;
    min-height:48px;
    padding:10px 7px;
    background:#100f1e;
    border:1px solid #38334d;
    border-radius:11px;
    color:#c7c2d8;
    font-size:12px;
    cursor:pointer;
    transition:.2s;
    text-align:center;
}

.interest-option span:hover {
    border-color:var(--purple);
    transform:translateY(-2px);
}

.interest-option input:checked+span {
    background:rgba(167,139,250,.15);
    border-color:var(--purple);
    color:#e3d7ff;
    box-shadow:inset 0 0 0 1px rgba(167,139,250,.15);
}

.interest-option input:focus-visible+span {
    outline:2px solid var(--pink);
    outline-offset:3px;
}

.budget-box {
    background:rgba(167,139,250,.055);
    border:1px solid rgba(167,139,250,.16);
    padding:18px;
    border-radius:15px;
}

.budget-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}

.budget-label {
    font-size:12px;
    color:#c4b5fd;
    margin-bottom:8px;
    font-weight:700;
}

.budget-preview {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-top:14px;
    padding-top:13px;
    border-top:1px solid var(--border);
    color:var(--muted);
    font-size:12px;
}

.budget-preview strong {color:#f0abfc}

.generate-btn {
    width:100%;
    padding:17px;
    border:none;
    border-radius:13px;
    background:var(--gradient);
    color:white;
    font-family:'Outfit',sans-serif;
    font-size:17px;
    font-weight:800;
    cursor:pointer;
    transition:.25s;
    box-shadow:0 10px 30px rgba(168,85,247,.2);
}

.generate-btn:hover {
    transform:translateY(-3px);
    box-shadow:0 15px 35px rgba(168,85,247,.35);
}

.generate-btn:disabled {
    opacity:.7;
    cursor:wait;
    transform:none;
}

.form-note {
    text-align:center;
    color:var(--muted);
    font-size:11px;
    line-height:1.7;
    margin-top:15px;
}

footer {
    text-align:center;
    padding:25px;
    border-top:1px solid var(--border);
    color:#77738a;
    font-size:12px;
}
footer span {color:var(--pink)}


/* Giftly AI workspace sidebar */
.sidebar{position:fixed;inset:0 auto 0 0;width:258px;background:#0e0d1b;border-right:1px solid var(--border);padding:22px 15px;z-index:30;display:flex;flex-direction:column;transition:transform .25s}
.sidebar-brand{display:flex;align-items:center;gap:10px;padding:2px 8px 24px;font:800 21px 'Outfit',sans-serif;color:white;text-decoration:none}
.sidebar-brand img{width:38px;height:38px;object-fit:contain;border-radius:10px}
.sidebar-brand span{color:var(--pink)}
.new-chat{display:flex;align-items:center;justify-content:center;gap:9px;padding:13px 14px;border:1px solid rgba(167,139,250,.35);border-radius:12px;color:#eee8ff;text-decoration:none;background:rgba(139,92,246,.13);font-weight:700;font-size:13px;margin-bottom:25px}
.new-chat:hover{background:rgba(139,92,246,.24)}
.sidebar-label{font-size:10px;letter-spacing:1.4px;font-weight:800;color:#85819e;padding:0 10px 10px}
.recent-list{overflow-y:auto;display:flex;flex-direction:column;gap:5px}
.recent-link{display:block;padding:11px 10px;border-radius:9px;text-decoration:none;color:#d7d2e7;font-size:12px;line-height:1.45;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.recent-link:hover{background:#1b1930;color:white}
.recent-link small{display:block;color:#85819e;font-size:10px;margin-top:3px}
.no-history{padding:10px;color:#85819e;font-size:12px;line-height:1.6}
.sidebar-foot{margin-top:auto;padding:14px 9px 0;border-top:1px solid var(--border);color:#77738a;font-size:10px;line-height:1.6}
.sidebar-toggle{display:none;position:fixed;left:13px;top:14px;z-index:45;background:#211c38;color:white;border:1px solid #51446f;border-radius:9px;padding:9px 11px;font-size:18px}
nav,.hero,.container,footer{
    width:calc(100% - 258px);
    margin-left:258px;
    margin-right:0;
}
.hero{
    max-width:none;
    padding-left:24px;
    padding-right:24px;
}
.container{
    max-width:none;
    display:flex;
    justify-content:center;
    padding-left:24px;
    padding-right:24px;
}
.container > *{
    width:100%;
    max-width:900px;
}
@media(max-width:850px){
    .sidebar{transform:translateX(-100%)}
    .sidebar.open{transform:translateX(0);box-shadow:20px 0 70px rgba(0,0,0,.55)}
    .sidebar-toggle{display:block}
    nav,.hero,.container,footer{
        width:100%;
        margin-left:0;
        margin-right:0;
    }
    nav{padding-left:60px}
    .hero{padding-left:17px;padding-right:17px}
    .container{padding-left:20px;padding-right:20px}
}

@media(max-width:650px) {
    nav {padding:0 5%;height:68px}
    .logo {font-size:22px}
    .nav-tag {font-size:10px;padding:7px 10px}
    .hero {padding:45px 17px 30px}
    .hero h1 {letter-spacing:-1px}
    .hero p {font-size:13px}
    .form-card {padding:23px 17px;border-radius:18px}
    .form-grid {grid-template-columns:1fr;gap:0}
    .full-width {grid-column:auto}
    .interest-grid {grid-template-columns:repeat(2,1fr)}
    .section-heading h2 {font-size:17px}
    .budget-grid {grid-template-columns:1fr 1fr;gap:10px}
}

@media(max-width:380px) {
    .nav-tag {max-width:130px;text-align:center}
    .budget-grid {grid-template-columns:1fr}


/* =====================================
   GIFTLY AI - RESPONSIVE LAYOUT
===================================== */

/* Tablet */
@media (max-width: 850px) {

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.open {
        transform: translateX(0);
        box-shadow: 20px 0 70px rgba(0,0,0,.55);
    }

    .sidebar-toggle {
        display: block;
    }

    nav,
    .hero,
    .container,
    footer {
        width: 100%;
        margin-left: 0;
        margin-right: 0;
    }

    nav {
        padding-left: 65px;
    }

    .hero {
        padding-left: 17px;
        padding-right: 17px;
    }

    .container {
        padding-left: 20px;
        padding-right: 20px;
    }
}

/* Mobile */
@media (max-width: 650px) {

    html,
    body {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        margin: 0;
        overflow-x: hidden;
    }

    nav {
        width: 100%;
        height: 65px;
        padding: 0 12px 0 70px !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        box-sizing: border-box;
        overflow: hidden;
    }

    nav .logo {
        font-size: 20px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    nav .nav-tag {
        font-size: 9px;
        padding: 7px 8px;
        max-width: 45%;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .sidebar-toggle {
        position: fixed;
        top: 11px;
        left: 12px;
        width: 42px;
        height: 42px;
        z-index: 1000;
        display: grid;
        place-items: center;
    }

    .hero,
    .container,
    footer {
        width: 100%;
        max-width: 100%;
        margin-left: 0;
        margin-right: 0;
        box-sizing: border-box;
    }

    .hero {
        padding: 40px 17px 30px;
    }

    .hero h1 {
        font-size: clamp(32px, 8vw, 42px);
        letter-spacing: -1px;
    }

    .hero p {
        font-size: 13px;
        line-height: 1.7;
    }

    .container {
        padding: 0 14px 40px;
    }

    .form-card {
        width: 100%;
        padding: 22px 16px;
        border-radius: 18px;
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .full-width {
        grid-column: auto;
    }

    .interest-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .section-heading h2 {
        font-size: 17px;
    }

    .budget-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
}

/* Small phones */
@media (max-width: 380px) {

    nav .logo {
        font-size: 18px;
    }

    nav .nav-tag {
        max-width: 115px;
    }

    .budget-grid {
        grid-template-columns: 1fr;
    }
}

/* =====================================
   END RESPONSIVE LAYOUT
===================================== */


@media (max-width: 650px) {
    nav {
        height: 115px !important;
        padding: 0 15px 10px 15px !important;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
    }

    nav .logo {
        font-size: 22px;
        margin: 0;
    }

    nav .nav-tag {
        display: none;
    }

    .sidebar-toggle {
        top: 10px;
        left: 12px;
    }
}
</style>
</head>

<body>
<button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Open menu">☰</button>
<aside class="sidebar" id="giftlySidebar">
    <a class="sidebar-brand" href="index.php">
        <img src="assets/giftly-logo.png" alt="Giftly AI logo">
        <span>Giftly<span>AI.</span></span>
    </a>
    <a class="new-chat" href="index.php">＋ <span>New Gift Search</span></a>
    <div class="sidebar-label">RECENT HISTORY</div>
    <div class="recent-list">
        <?php if (!empty($recentChats)): ?>
            <?php foreach ($recentChats as $chat): ?>
                <a class="recent-link" href="result.php?history_id=<?php echo (int)$chat['id']; ?>" title="<?php echo htmlspecialchars($chat['recipient_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($chat['conversation_title'] ?: ('Gift ideas for ' . $chat['recipient_name']), ENT_QUOTES, 'UTF-8'); ?>
                    <small><?php echo htmlspecialchars($chat['occasion'] ?: 'Gift search', ENT_QUOTES, 'UTF-8'); ?> · <?php echo date('d M', strtotime($chat['created_at'])); ?></small>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-history">Your gift searches will appear here after you generate your first recommendations.</div>
        <?php endif; ?>
    </div>
    <div class="sidebar-foot">Your personal AI gift discovery space.</div>
</aside>

<nav>
    <div class="logo">Giftly<span>AI.</span> 🎁</div>
    <div class="nav-tag">✨ Powered by Artificial Intelligence</div>
</nav>

<section class="hero">
    <div class="hero-badge">🎀 YOUR PERSONAL AI GIFT ASSISTANT</div>
    <h1>Find Gifts That<br><span class="gradient-text">Make Moments Special.</span></h1>
    <p>
        Every person is unique. Every gift should be too.
        Tell us about someone special, and let AI discover thoughtful
        gift ideas tailored to their personality and interests.
    </p>
</section>

<main class="container">

<form action="result.php" method="POST" class="form-card" id="giftForm">

    <div class="progress-area">
        <div class="progress-top">
            <span>Your gift discovery journey</span>
            <span id="progressText">0% completed</span>
        </div>
        <div class="progress-track">
            <div class="progress-bar" id="progressBar"></div>
        </div>
    </div>

    <!-- RECIPIENT DETAILS -->
    <section class="section">

        <div class="section-heading">
            <div class="section-icon">👤</div>
            <div>
                <h2>Who is the gift for?</h2>
                <p>Let's get to know your special person.</p>
            </div>
        </div>

        <div class="form-grid">

            <div class="form-group full-width">
                <label>Recipient Name <span class="required">*</span></label>
                <input type="text" name="recipient_name"
                    placeholder="Enter recipient's name"
                    maxlength="100" required>
            </div>

            <div class="form-group">
                <label>Relationship <span class="required">*</span></label>
                <select name="relationship" required>
                    <option value="">Select relationship</option>
                    <option value="Friend">Friend</option>
                    <option value="Father">Father</option>
                    <option value="Mother">Mother</option>
                    <option value="Brother">Brother</option>
                    <option value="Sister">Sister</option>
                    <option value="Partner">Partner</option>
                    <option value="Husband">Husband</option>
                    <option value="Wife">Wife</option>
                    <option value="Teacher">Teacher</option>
                    <option value="Colleague">Colleague</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Recipient Age <span class="required">*</span></label>
                <input type="number" name="recipient_age"
                    placeholder="Enter age" min="1" max="120" required>
            </div>

            <div class="form-group">
                <label>Gender <span class="optional">(Optional)</span></label>
                <select name="gender">
                    <option value="">Prefer not to say</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

        </div>
    </section>

    <!-- OCCASION -->
    <section class="section">

        <div class="section-heading">
            <div class="section-icon">🎉</div>
            <div>
                <h2>What's the occasion?</h2>
                <p>Every special moment deserves a special gift.</p>
            </div>
        </div>

        <div class="form-grid">

            <div class="form-group">
                <label>Occasion <span class="required">*</span></label>
                <select name="occasion" required>
                    <option value="">Select occasion</option>
                    <option value="Birthday">Birthday</option>
                    <option value="Anniversary">Anniversary</option>
                    <option value="Wedding">Wedding</option>
                    <option value="Graduation">Graduation</option>
                    <option value="Festival">Festival</option>
                    <option value="Farewell">Farewell</option>
                    <option value="Thank You">Thank You</option>
                    <option value="Just Because">Just Because</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Gift Date <span class="optional">(Optional)</span></label>
                <input type="date" name="gift_date"
                    min="<?php echo date('Y-m-d'); ?>">
            </div>

        </div>
    </section>

    <!-- INTERESTS -->
    <section class="section">

        <div class="section-heading">
            <div class="section-icon">💖</div>
            <div>
                <h2>What do they love?</h2>
                <p>Choose interests to help AI understand them better.</p>
            </div>
        </div>

        <div class="form-group">

            <div class="interest-header">
                <label style="margin:0">
                    Their Interests <span class="required">*</span>
                </label>
                <span class="interest-count" id="interestCount">0 selected</span>
            </div>

            <div class="interest-grid" id="interestGrid">

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Technology">
                    <span>💻 Technology</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Gaming">
                    <span>🎮 Gaming</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Photography">
                    <span>📸 Photography</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Fashion">
                    <span>👗 Fashion</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Music">
                    <span>🎵 Music</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Sports">
                    <span>⚽ Sports</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Books">
                    <span>📚 Books</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Travel">
                    <span>✈️ Travel</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Art">
                    <span>🎨 Art</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Fitness">
                    <span>🏋️ Fitness</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Cooking">
                    <span>🍳 Cooking</span>
                </label>

                <label class="interest-option">
                    <input type="checkbox" name="interests[]" value="Nature">
                    <span>🌿 Nature</span>
                </label>

            </div>
        </div>

        <div class="form-group">
            <label>Personality <span class="required">*</span></label>
            <select name="personality" required>
                <option value="">Select personality</option>
                <option value="Creative">Creative</option>
                <option value="Introvert">Introvert</option>
                <option value="Extrovert">Extrovert</option>
                <option value="Adventurous">Adventurous</option>
                <option value="Simple">Simple</option>
                <option value="Professional">Professional</option>
                <option value="Tech-savvy">Tech-savvy</option>
            </select>
        </div>

        <div class="form-group">
            <label>Additional Information <span class="optional">(Optional)</span></label>
            <textarea name="additional_info"
                placeholder="Tell us about their hobbies, favourite things, likes, dislikes or anything else that can help AI suggest a meaningful gift..."
                maxlength="1000" id="additionalInfo"></textarea>
            <div class="field-hint"><span id="charCount">0</span>/1000 characters</div>
        </div>

    </section>

    <!-- BUDGET AND PREFERENCES -->
    <section class="section">

        <div class="section-heading">
            <div class="section-icon">🎁</div>
            <div>
                <h2>Gift Preferences</h2>
                <p>Let's find something meaningful within your budget.</p>
            </div>
        </div>

        <div class="form-group budget-box">

            <label>Your Budget (₹) <span class="required">*</span></label>

            <div class="budget-grid">

                <div>
                    <div class="budget-label">Minimum Budget</div>
                    <input type="number" name="min_budget"
                        id="minBudget" placeholder="e.g. 500"
                        min="1" max="10000000" step="1" required>
                </div>

                <div>
                    <div class="budget-label">Maximum Budget</div>
                    <input type="number" name="max_budget"
                        id="maxBudget" placeholder="e.g. 2000"
                        min="1" max="10000000" step="1" required>
                </div>

            </div>

            <div class="budget-preview">
                <span>Your selected budget range</span>
                <strong id="budgetPreview">₹ — to ₹ —</strong>
            </div>

        </div>

        <div class="form-grid">

            <div class="form-group">
                <label>Gift Type</label>
                <select name="gift_type">
                    <option value="Any">Any Type</option>
                    <option value="Physical Gift">Physical Gift</option>
                    <option value="Digital Gift">Digital Gift</option>
                    <option value="Experience">Experience</option>
                    <option value="Handmade">Handmade</option>
                </select>
            </div>

            <div class="form-group">
                <label>Gift Preference</label>
                <select name="gift_preference">
                    <option value="Any">Any Preference</option>
                    <option value="Practical">Practical</option>
                    <option value="Luxury">Luxury</option>
                    <option value="Unique">Unique</option>
                    <option value="Personalized">Personalized</option>
                    <option value="Budget-friendly">Budget-friendly</option>
                </select>
            </div>

        </div>
    </section>

    <button type="submit" class="generate-btn" id="generateBtn">
        ✨ Discover My Perfect Gift Ideas
    </button>

    <p class="form-note">
        🤖 Powered by AI &nbsp; • &nbsp; Personalized recommendations
        <br>Every great gift begins with understanding someone special.
    </p>

</form>
</main>

<footer>
    Made with <span>♥</span> and AI | Giftly AI © <?php echo date('Y'); ?>
</footer>

<script>
const form = document.getElementById('giftForm');
const interestInputs = document.querySelectorAll('input[name="interests[]"]');
const interestCount = document.getElementById('interestCount');
const minBudget = document.getElementById('minBudget');
const maxBudget = document.getElementById('maxBudget');
const budgetPreview = document.getElementById('budgetPreview');
const additionalInfo = document.getElementById('additionalInfo');
const charCount = document.getElementById('charCount');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');

function updateInterests() {
    const selected = [...interestInputs].filter(input => input.checked);
    interestCount.textContent = selected.length + ' selected';
    updateProgress();
}

function updateBudget() {
    const min = minBudget.value;
    const max = maxBudget.value;

    budgetPreview.textContent =
        '₹ ' + (min ? Number(min).toLocaleString('en-IN') : '—') +
        ' to ₹ ' + (max ? Number(max).toLocaleString('en-IN') : '—');
}

function updateProgress() {
    const fields = [
        'recipient_name','relationship','recipient_age',
        'occasion','personality','min_budget','max_budget'
    ];

    let completed = 0;

    fields.forEach(name => {
        const field = form.querySelector('[name="' + name + '"]');
        if (field && field.value.trim() !== '') completed++;
    });

    if ([...interestInputs].some(input => input.checked)) completed++;

    const percentage = Math.round((completed / 8) * 100);
    progressBar.style.width = percentage + '%';
    progressText.textContent = percentage + '% completed';
}

interestInputs.forEach(input => {
    input.addEventListener('change', updateInterests);
});

form.querySelectorAll('input,select,textarea').forEach(field => {
    field.addEventListener('input', updateProgress);
    field.addEventListener('change', updateProgress);
});

minBudget.addEventListener('input', updateBudget);
maxBudget.addEventListener('input', updateBudget);

additionalInfo.addEventListener('input', () => {
    charCount.textContent = additionalInfo.value.length;
});

form.addEventListener('submit', function(event) {
    const selected = [...interestInputs].some(input => input.checked);

    if (!selected) {
        event.preventDefault();
        alert('Please select at least one interest to get personalized recommendations.');
        document.getElementById('interestGrid').scrollIntoView({
            behavior:'smooth',
            block:'center'
        });
        return;
    }

    const min = Number(minBudget.value);
    const max = Number(maxBudget.value);

    if (min <= 0 || max <= 0 || min > max) {
        event.preventDefault();
        alert('Please enter a valid budget. Maximum budget must be greater than or equal to minimum budget.');
        maxBudget.focus();
        return;
    }

    const button = document.getElementById('generateBtn');
    button.disabled = true;
    button.textContent = '✨ Finding the perfect gifts...';
});

updateInterests();
updateBudget();

const sidebarToggle = document.getElementById('sidebarToggle');
const giftlySidebar = document.getElementById('giftlySidebar');
if (sidebarToggle && giftlySidebar) {
    sidebarToggle.addEventListener('click', () => giftlySidebar.classList.toggle('open'));
    document.addEventListener('click', event => {
        if (window.innerWidth <= 850 && giftlySidebar.classList.contains('open') &&
            !giftlySidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
            giftlySidebar.classList.remove('open');
        }
    });
}

</script>

</body>
</html>
