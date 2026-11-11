
<?php
// =====================================================
// GIFLTY AI - SMART GIFT RESULTS
// =====================================================

$apiKey = getenv("GEMINI_API_KEY") ?: "";
$model = "gemini-3.5-flash-lite";


$error = "";
$success = false;
$pdo = null;
$historyId = null;
$gifts = [];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function shopLink($shop, $gift) {
    $query = urlencode($gift);
    $urls = [
        "Amazon" => "https://www.amazon.in/s?k=" . $query,
        "Flipkart" => "https://www.flipkart.com/search?q=" . $query,
        "Myntra" => "https://www.myntra.com/" . $query,
        "Meesho" => "https://www.meesho.com/search?q=" . $query,
        "Blinkit" => "https://blinkit.com/s/?q=" . $query
    ];
    return $urls[$shop] ?? "#";
}

function giftIcon($category) {
    $icons = [
        "technology" => "💻",
        "gaming" => "🎮",
        "fashion" => "👗",
        "photography" => "📸",
        "music" => "🎵",
        "books" => "📚",
        "travel" => "✈️",
        "sports" => "⚽",
        "fitness" => "🏋️",
        "art" => "🎨",
        "food" => "🍫",
        "experience" => "🎟️",
        "personalized" => "🎁"
    ];

    foreach ($icons as $key => $icon) {
        if (stripos($category, $key) !== false) {
            return $icon;
        }
    }

    return "🎁";
}


$isHistoryView = false;
$aiData = [];
$recipientName = $recipientAge = $relationship = $gender = $occasion = "";
$giftDate = $personality = $additionalInfo = $minBudget = $maxBudget = "";
$interests = [];
$giftType = "Any";
$giftPreference = "Any";

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["history_id"])) {
    $requestedId = filter_var($_GET["history_id"], FILTER_VALIDATE_INT);
    if (!$requestedId || $requestedId < 1) {
        http_response_code(400);
        $error = "Invalid gift history reference.";
    } else {
        try {
            $dbHost = getenv("DB_HOST") ?: "localhost";
            $dbPort = getenv("DB_PORT") ?: "3306";
            $dbName = getenv("DB_NAME") ?: "ai_gift_db";
            $dbUser = getenv("DB_USER") ?: "root";
            $dbPassword = getenv("DB_PASSWORD") ?: "";

$pdo = new PDO(
    "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
    $dbUser,
    $dbPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
            $historyStmt = $pdo->prepare("SELECT * FROM gift_history WHERE id = :id LIMIT 1");
            $historyStmt->execute([":id" => $requestedId]);
            $saved = $historyStmt->fetch(PDO::FETCH_ASSOC);
            if (!$saved) {
                http_response_code(404);
                $error = "This saved gift search could not be found.";
            } else {
                $aiData = json_decode($saved["ai_suggestions"] ?? "", true);
                if (!is_array($aiData) || empty($aiData["gifts"]) || !is_array($aiData["gifts"])) {
                    $error = "The saved recommendations are unavailable or corrupted.";
                } else {
                    $recipientName = $saved["recipient_name"] ?? "";
                    $recipientAge = $saved["recipient_age"] ?? "";
                    $relationship = $saved["relationship"] ?? "";
                    $gender = $saved["gender"] ?? "";
                    $occasion = $saved["occasion"] ?? "";
                    $giftDate = $saved["gift_date"] ?? "";
                    $interests = array_filter(array_map('trim', explode(',', $saved["interests"] ?? "")));
                    $personality = $saved["personality"] ?? "";
                    $additionalInfo = $saved["additional_info"] ?? "";
                    $minBudget = $saved["min_budget"] ?? "";
                    $maxBudget = $saved["max_budget"] ?? "";
                    $giftType = $saved["gift_type"] ?? "Any";
                    $giftPreference = $saved["gift_preference"] ?? "Any";
                    $gifts = $aiData["gifts"];
                    $historyId = (int)$saved["id"];
                    $success = true;
                    $isHistoryView = true;
                }
            }
        } catch (PDOException $ex) {
            $error = "Could not load saved gift history. Check your database connection.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" && !$isHistoryView && !isset($_GET["history_id"])) {
    header("Location: index.php");
    exit;
}

if (!$isHistoryView && $_SERVER["REQUEST_METHOD"] === "POST") {

// -----------------------------------------------------
// 1. RECEIVE FORM DATA
// -----------------------------------------------------

$recipientName = trim($_POST["recipient_name"] ?? "");
$recipientAge = $_POST["recipient_age"] ?? "";
$relationship = $_POST["relationship"] ?? "";
$gender = $_POST["gender"] ?? "";
$occasion = $_POST["occasion"] ?? "";
$giftDate = $_POST["gift_date"] ?? "";
$interests = $_POST["interests"] ?? [];
$personality = $_POST["personality"] ?? "";
$additionalInfo = trim($_POST["additional_info"] ?? "");
$minBudget = $_POST["min_budget"] ?? "";
$maxBudget = $_POST["max_budget"] ?? "";
$giftType = $_POST["gift_type"] ?? "Any";
$giftPreference = $_POST["gift_preference"] ?? "Any";

// -----------------------------------------------------
// 2. VALIDATE INPUT
// -----------------------------------------------------

if (
    $recipientName === "" ||
    $relationship === "" ||
    $recipientAge === "" ||
    $occasion === "" ||
    $personality === "" ||
    $minBudget === "" ||
    $maxBudget === ""
) {
    $error = "Please fill in all required fields.";
}

elseif (
    !preg_match("/^[\p{L}\p{M} .'-]+$/u", $recipientName) ||
    strlen($recipientName) > 100
) {
    $error = "Please enter a valid recipient name.";
}

elseif (
    !filter_var($recipientAge, FILTER_VALIDATE_INT) ||
    $recipientAge < 1 ||
    $recipientAge > 120
) {
    $error = "Please enter a valid age.";
}

elseif (
    !is_numeric($minBudget) ||
    !is_numeric($maxBudget) ||
    $minBudget <= 0 ||
    $maxBudget < $minBudget ||
    $maxBudget > 10000000
) {
    $error = "Please enter a valid budget range.";
}

elseif (!is_array($interests) || count($interests) < 1 || count($interests) > 12) {
    $error = "Please select at least one interest.";
}

elseif (strlen($additionalInfo) > 1000) {
    $error = "Additional information is too long.";
}

elseif (
    $giftDate !== "" &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $giftDate)
) {
    $error = "Please enter a valid gift date.";
}

$allowedInterests = [
    "Technology", "Gaming", "Photography", "Fashion",
    "Music", "Sports", "Books", "Travel", "Art",
    "Fitness", "Cooking", "Nature"
];

if ($error === "" && count(array_diff($interests, $allowedInterests)) > 0) {
    $error = "Invalid interest selection.";
}


 // -----------------------------------------------------
// 3. CONNECT TO DATABASE
// -----------------------------------------------------

if ($error === "") {
    try {
        $dbHost = getenv("DB_HOST") ?: "localhost";
        $dbPort = getenv("DB_PORT") ?: "3306";
        $dbName = getenv("DB_NAME") ?: "ai_gift_db";
        $dbUser = getenv("DB_USER") ?: "root";
        $dbPassword = getenv("DB_PASSWORD") ?: "";

        $pdo = new PDO(
            "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
            $dbUser,
            $dbPassword,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

    } catch (PDOException $ex) {
        $error = "Database connection failed. Check database settings.";
    }
}

// -----------------------------------------------------
// 4. PREPARE GEMINI PROMPT
// -----------------------------------------------------

if ($error === "") {

    $interestsText = implode(", ", $interests);

    $prompt = "
You are Giftly AI, an expert personalized gift recommendation assistant.

Recommend exactly 6 different gifts.

RECIPIENT:
Name: $recipientName
Age: $recipientAge
Relationship: $relationship
Gender: $gender
Occasion: $occasion
Gift date: $giftDate
Interests: $interestsText
Personality: $personality
Additional information: $additionalInfo
Minimum budget: INR $minBudget
Maximum budget: INR $maxBudget
Gift type: $giftType
Preference: $giftPreference

RULES:
1. Recommend realistic gifts suitable for the recipient.
2. Keep all prices within the provided INR budget.
3. Avoid repeating similar products.
4. Keep descriptions short, maximum 20 words.
5. Give a short reason, maximum 20 words.
6. Include a category.
7. Prices are estimates, not verified live prices.
8. Do not invent product URLs or claim to have checked availability.
9. Return valid JSON only.

Return this structure:
{
  \"introduction\": \"A short sentence\",
  \"gifts\": [
    {
      \"name\": \"Gift name\",
      \"category\": \"Category\",
      \"price\": 1500,
      \"description\": \"Short description\",
      \"reason\": \"Why this gift suits them\",
      \"personalization\": \"One short personalization idea\"
    }
  ],
  \"top_pick\": \"Name of the best gift\"
}
";

    // -------------------------------------------------
    // 5. CALL GEMINI API
    // -------------------------------------------------

    if (trim($apiKey) === "") {
    $error = "Gemini API configuration is missing.";
   }
     elseif (!function_exists("curl_init")) {
        $error = "PHP cURL is not enabled. Enable the cURL extension in XAMPP.";
    } else {

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/"
            . $model . ":generateContent";

        $requestData = [
            "contents" => [[
                "role" => "user",
                "parts" => [["text" => $prompt]]
            ]],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 3000,
                "responseMimeType" => "application/json"
            ]
        ];

        $jsonData = json_encode(
            $requestData,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        $curl = curl_init($apiUrl);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Content-Length: " . strlen($jsonData),
                "x-goog-api-key: " . trim($apiKey),
                "Expect:"
            ],
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 90
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            $error = "Connection failed: " . $curlError;
        } else {

            $responseData = json_decode($response, true);

            if ($httpCode !== 200) {
                $apiMessage = $responseData["error"]["message"]
                    ?? "Unknown Gemini API error.";
                $error = "Gemini API Error ($httpCode): " . $apiMessage;
            } else {

                $aiText = $responseData["candidates"][0]["content"]["parts"][0]["text"] ?? "";

                $aiData = json_decode($aiText, true);

                if (
                    !is_array($aiData) ||
                    !isset($aiData["gifts"]) ||
                    !is_array($aiData["gifts"]) ||
                    count($aiData["gifts"]) !== 6
                ) {
                    $error = "AI returned an invalid gift list. Please try again.";
                } else {
                    $gifts = $aiData["gifts"];

                    foreach ($gifts as $gift) {
                        if (
                            !isset($gift["name"], $gift["price"], $gift["description"], $gift["reason"]) ||
                            !is_numeric($gift["price"]) ||
                            $gift["price"] < $minBudget ||
                            $gift["price"] > $maxBudget
                        ) {
                            $error = "AI returned an incomplete gift or a price outside your budget. Please try again.";
                            $gifts = [];
                            break;
                        }
                    }

                    if ($error === "") {
                        $success = true;
                    }
                }
            }
        }
    }
}

// -----------------------------------------------------
// 6. SAVE RESULT IN EXISTING TABLE
// -----------------------------------------------------

if ($success) {
    try {
        $interestsText = implode(", ", $interests);
        $aiSuggestions = json_encode(
            $aiData,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        $sql = "INSERT INTO gift_history (
            recipient_name, recipient_age, relationship, gender,
            occasion, gift_date, interests, personality,
            additional_info, min_budget, max_budget,
            gift_type, gift_preference, ai_suggestions
        ) VALUES (
            :recipient_name, :recipient_age, :relationship, :gender,
            :occasion, :gift_date, :interests, :personality,
            :additional_info, :min_budget, :max_budget,
            :gift_type, :gift_preference, :ai_suggestions
        )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":recipient_name" => $recipientName,
            ":recipient_age" => $recipientAge,
            ":relationship" => $relationship,
            ":gender" => $gender,
            ":occasion" => $occasion,
            ":gift_date" => $giftDate !== "" ? $giftDate : null,
            ":interests" => $interestsText,
            ":personality" => $personality,
            ":additional_info" => $additionalInfo,
            ":min_budget" => $minBudget,
            ":max_budget" => $maxBudget,
            ":gift_type" => $giftType,
            ":gift_preference" => $giftPreference,
            ":ai_suggestions" => $aiSuggestions
        ]);

        $historyId = $pdo->lastInsertId();

    } catch (PDOException $ex) {
        $error = "Suggestions were generated, but saving them failed. Please check your database.";
        $success = false;
    }
 }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giftly AI | Your Gift Ideas</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:#090914;color:#f7f5ff;min-height:100vh;padding:25px 18px}
.container{max-width:1100px;margin:auto}
.navbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:45px}
.logo{font:800 25px 'Outfit';color:white}
.logo span{color:#f472b6}
.back{color:#c4b5fd;text-decoration:none;border:1px solid #38334d;padding:10px 15px;border-radius:9px;font-size:13px}
.hero{text-align:center;margin-bottom:35px}
.hero .emoji{font-size:48px;margin-bottom:12px}
.hero h1{font:800 clamp(30px,5vw,45px) 'Outfit';margin-bottom:12px}
.gradient{background:linear-gradient(90deg,#a78bfa,#f472b6,#fb923c);background-clip:text;-webkit-background-clip:text;color:transparent}
.hero p{color:#aaa6bd;line-height:1.7;font-size:14px}
.profile{background:#171626;border:1px solid #343047;border-radius:16px;padding:22px;margin-bottom:28px}
.profile h2{font:700 19px 'Outfit';margin-bottom:18px}
.details{display:grid;grid-template-columns:repeat(3,1fr);gap:17px}
.detail small{display:block;color:#aaa6bd;font-size:11px;margin-bottom:6px}
.detail strong{font-size:13px}
.intro{color:#c9c3dc;margin:10px 0 22px;line-height:1.6;font-size:14px}
.toolbar{display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between;align-items:center;margin-bottom:18px}
.toolbar p{color:#aaa6bd;font-size:13px}
.actions{display:flex;gap:8px;flex-wrap:wrap}
.btn{border:1px solid #39334f;background:#171626;color:#eee8ff;padding:9px 13px;border-radius:9px;cursor:pointer;text-decoration:none;font-size:12px}
.btn:hover{border-color:#a78bfa}
.cards{display:grid;grid-template-columns:repeat(2,1fr);gap:17px}
.gift-card{position:relative;background:linear-gradient(145deg,#1b1930,#131221);border:1px solid #38334d;border-radius:17px;padding:21px;transition:.2s}
.gift-card:hover{transform:translateY(-3px);border-color:#8b5cf6}
.gift-top{display:flex;gap:14px;align-items:center;margin-bottom:15px}
.gift-icon{width:58px;height:58px;flex-shrink:0;display:grid;place-items:center;border-radius:15px;background:linear-gradient(140deg,rgba(139,92,246,.22),rgba(236,72,153,.18));font-size:28px}
.gift-number{font-size:10px;color:#b8a8ed;letter-spacing:1px;font-weight:800}
.gift-card h3{font:700 18px 'Outfit';margin:4px 0}
.category{font-size:11px;color:#aaa6bd}
.price{font:800 22px 'Outfit';color:#86efac;margin:13px 0}
.price-note{font-size:10px;color:#aaa6bd}
.description{font-size:13px;color:#ddd7eb;line-height:1.55;margin-bottom:10px}
.reason{font-size:12px;color:#bdb4d5;line-height:1.55;margin-bottom:10px}
.personal{font-size:11px;color:#f9a8d4;background:rgba(244,114,182,.08);padding:10px;border-radius:9px;margin-bottom:15px}
.shop-label{font-size:11px;color:#aaa6bd;margin-bottom:9px}
.shop-links{display:flex;gap:7px;flex-wrap:wrap}
.shop-links a{font-size:11px;text-decoration:none;color:#ddd6fe;border:1px solid #494064;border-radius:7px;padding:7px 9px}
.shop-links a:hover{background:rgba(167,139,250,.15);border-color:#a78bfa}
.card-actions{display:flex;gap:8px;margin-top:15px}
.card-actions button{flex:1}
.fav.active{color:#f9a8d4;border-color:#ec4899;background:rgba(236,72,153,.1)}
.compare.active{color:#86efac;border-color:#22c55e}
.top-pick{display:inline-block;background:rgba(251,191,36,.12);color:#fcd34d;border:1px solid rgba(251,191,36,.3);border-radius:20px;padding:5px 9px;font-size:10px;margin-bottom:8px}
.bottom-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin:30px 0}
.primary{background:linear-gradient(100deg,#8b5cf6,#ec4899);border:0;color:white;padding:13px 20px;border-radius:10px;text-decoration:none;font-weight:700;font-size:13px}
.compare-panel{display:none;background:#171626;border:1px solid #8b5cf6;padding:18px;border-radius:13px;margin-top:22px}
.compare-panel.show{display:block}
.compare-panel h3{margin-bottom:10px}
.compare-panel p{color:#c7c2d8;font-size:13px;line-height:1.7}
.error{background:rgba(239,68,68,.1);border:1px solid #ef4444;color:#fca5a5;padding:20px;border-radius:13px;line-height:1.7;margin:30px 0}
footer{text-align:center;color:#77738a;font-size:11px;padding:30px;border-top:1px solid #302d43;margin-top:35px}
@media(max-width:700px){.cards{grid-template-columns:1fr}.details{grid-template-columns:repeat(2,1fr)}.navbar{margin-bottom:30px}}
@media print{body{background:white;color:black;padding:0}.navbar,.toolbar,.shop-links,.card-actions,.bottom-actions,.compare-panel,footer{display:none!important}.profile,.gift-card{background:white;color:black;border:1px solid #ccc;break-inside:avoid}.detail small,.category,.description,.reason,.price-note{color:#444}.cards{grid-template-columns:1fr 1fr}.hero p{color:#444}}

/* ===== GIFTLY AI MOBILE RESPONSIVE FIX ===== */

@media (max-width: 700px) {
    body {
        padding: 18px 14px;
        overflow-x: hidden;
    }

    .container {
        width: 100%;
        max-width: 100%;
    }

    /* The page uses .navbar, not the nav element selector. */
    .navbar {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: center;
        gap: 14px;
        margin-bottom: 34px;
    }

    .navbar .logo {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        width: 100%;
        font-size: 23px;
        line-height: 1.2;
        white-space: nowrap;
    }

    .brand-mark {
        display: inline-grid;
        place-items: center;
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border-radius: 10px;
        background: rgba(167,139,250,.13);
        font-size: 23px;
    }

    .navbar .back {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 46px;
        padding: 11px 14px;
        text-align: center;
        white-space: normal;
        line-height: 1.35;
    }

    .hero {
        margin-bottom: 28px;
    }

    .hero .emoji {
        font-size: 43px;
    }

    .hero h1 {
        font-size: clamp(29px, 8vw, 40px);
        line-height: 1.12;
    }

    .hero p {
        font-size: 14px;
        padding: 0 4px;
    }

    .profile {
        padding: 20px 17px;
        margin-bottom: 24px;
    }

    .details {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 14px;
    }

    .detail {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .intro {
        font-size: 14px;
        margin: 8px 0 20px;
    }

    .toolbar {
        align-items: flex-start;
        flex-direction: column;
        gap: 13px;
    }

    .toolbar p {
        line-height: 1.5;
    }

    .actions {
        width: 100%;
    }

    .actions .btn {
        flex: 1 1 auto;
        min-height: 42px;
    }

    .cards {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .gift-card {
        padding: 19px 16px;
    }

    .gift-card h3 {
        font-size: 17px;
        overflow-wrap: anywhere;
    }

    .bottom-actions {
        gap: 10px;
    }

    footer {
        padding: 24px 10px;
        line-height: 1.6;
    }
}

@media (max-width: 380px) {
    .navbar .logo {
        font-size: 21px;
    }

    .details {
        gap: 16px 10px;
    }

    .detail strong {
        font-size: 12px;
    }

    .card-actions {
        flex-direction: column;
    }

    .card-actions button {
        min-height: 42px;
    }
}

/* ===== END MOBILE RESPONSIVE FIX ===== */

</style>
</head>
<body>
<div class="container">

<nav class="navbar">
    <div class="logo"><span class="brand-mark" aria-hidden="true">🎁</span><span>Giftly<span>AI.</span></span></div>
    <a href="index.php" class="back">＋ New Gift Search</a>
</nav>

<?php if ($error !== ""): ?>

<section class="hero">
    <div class="emoji">⚠️</div>
    <h1>Something went wrong</h1>
    <p>We couldn't complete your gift recommendations.</p>
</section>
<div class="error"><?php echo e($error); ?></div>
<div class="bottom-actions">
    <a href="index.php" class="primary">Try Again</a>
</div>

<?php elseif ($success): ?>

<section class="hero">
    <div class="emoji">🎉</div>
    <h1>Your Gifts Are <span class="gradient">Ready!</span></h1>
    <p>Six thoughtful ideas, selected for <?php echo e($recipientName); ?>.</p>
</section>

<section class="profile">
    <h2>🎀 Recipient Profile</h2>
    <div class="details">
        <div class="detail"><small>Recipient</small><strong><?php echo e($recipientName); ?></strong></div>
        <div class="detail"><small>Relationship</small><strong><?php echo e($relationship); ?></strong></div>
        <div class="detail"><small>Age</small><strong><?php echo e($recipientAge); ?> years</strong></div>
        <div class="detail"><small>Occasion</small><strong><?php echo e($occasion); ?></strong></div>
        <div class="detail"><small>Budget</small><strong>₹<?php echo e(number_format((float)$minBudget)); ?> – ₹<?php echo e(number_format((float)$maxBudget)); ?></strong></div>
        <div class="detail"><small>Gift Type</small><strong><?php echo e($giftType); ?></strong></div>
    </div>
</section>

<p class="intro"><?php echo e($aiData["introduction"] ?? "Here are your personalized gift recommendations."); ?></p>

<div class="toolbar">
    <p>✨ 6 personalized recommendations · Estimated INR prices</p>
    <div class="actions">
        <button class="btn" onclick="window.print()">🖨 Print</button>
        <button class="btn" onclick="shareResults()">↗ Share</button>
    </div>
</div>

<section class="cards">
<?php foreach ($gifts as $index => $gift): ?>
    <?php
        $name = (string)$gift["name"];
        $isTop = strcasecmp($name, (string)($aiData["top_pick"] ?? "")) === 0;
        $category = (string)($gift["category"] ?? "Gift");
        $price = (float)$gift["price"];
    ?>
    <article class="gift-card"
        data-name="<?php echo e($name); ?>"
        data-price="<?php echo e(number_format($price, 2, '.', '')); ?>"
        data-description="<?php echo e($gift["description"]); ?>"
        data-reason="<?php echo e($gift["reason"]); ?>">

        <?php if ($isTop): ?>
            <span class="top-pick">🏆 AI TOP PICK</span>
        <?php endif; ?>

        <div class="gift-top">
            <div class="gift-icon"><?php echo giftIcon($category); ?></div>
            <div>
                <div class="gift-number">GIFT <?php echo $index + 1; ?></div>
                <h3><?php echo e($name); ?></h3>
                <div class="category"><?php echo e($category); ?></div>
            </div>
        </div>

        <div class="price">₹<?php echo e(number_format($price)); ?>
            <span class="price-note">estimated</span>
        </div>

        <p class="description"><?php echo e($gift["description"]); ?></p>
        <p class="reason">💡 <?php echo e($gift["reason"]); ?></p>

        <?php if (!empty($gift["personalization"])): ?>
            <div class="personal">
                🎀 <strong>Personalize it:</strong>
                <?php echo e($gift["personalization"]); ?>
            </div>
        <?php endif; ?>

        <div class="shop-label">FIND THIS GIFT ON</div>
        <div class="shop-links">
            <?php foreach (["Amazon","Flipkart","Myntra","Meesho","Blinkit"] as $shop): ?>
                <a href="<?php echo e(shopLink($shop, $name)); ?>"
                   target="_blank" rel="noopener noreferrer">
                    <?php echo e($shop); ?> ↗
                </a>
            <?php endforeach; ?>
        </div>

        <div class="card-actions">
            <button class="btn fav" onclick="toggleFavourite(this)">♡ Favourite</button>
            <button class="btn compare" onclick="toggleCompare(this)">＋ Compare</button>
        </div>
    </article>
<?php endforeach; ?>
</section>

<div class="compare-panel" id="comparePanel">
    <h3>⚖️ Gift Comparison</h3>
    <div id="compareContent"></div>
</div>

<div class="bottom-actions">
    <a href="index.php" class="primary">🎁 Find Another Gift</a>
    <button class="btn" onclick="window.print()">Download / Print PDF</button>
</div>

<?php endif; ?>

<footer>Made with ♥ and AI | Giftly AI © <?php echo date('Y'); ?></footer>
</div>

<script>
const comparison = [];

function toggleFavourite(button) {
    button.classList.toggle('active');
    const active = button.classList.contains('active');
    button.textContent = active ? '♥ Saved' : '♡ Favourite';

    const card = button.closest('.gift-card');
    const name = card.dataset.name;

    let favourites = JSON.parse(localStorage.getItem('giftlyFavourites') || '[]');

    if (active && !favourites.includes(name)) {
        favourites.push(name);
    } else if (!active) {
        favourites = favourites.filter(item => item !== name);
    }

    localStorage.setItem('giftlyFavourites', JSON.stringify(favourites));
}

function toggleCompare(button) {
    const card = button.closest('.gift-card');
    const name = card.dataset.name;
    const existing = comparison.findIndex(item => item.name === name);

    if (existing >= 0) {
        comparison.splice(existing, 1);
        button.classList.remove('active');
        button.textContent = '＋ Compare';
    } else {
        if (comparison.length >= 3) {
            alert('You can compare up to 3 gifts at a time.');
            return;
        }

        comparison.push({
            name: name,
            price: card.dataset.price,
            description: card.dataset.description,
            reason: card.dataset.reason
        });

        button.classList.add('active');
        button.textContent = '✓ Added';
    }

    renderComparison();
}

function renderComparison() {
    const panel = document.getElementById('comparePanel');
    const content = document.getElementById('compareContent');

    if (comparison.length === 0) {
        panel.classList.remove('show');
        content.innerHTML = '';
        return;
    }

    panel.classList.add('show');
    content.innerHTML = comparison.map(item =>
        '<p><strong>' + escapeHtml(item.name) +
        '</strong> — ₹' + Number(item.price).toLocaleString('en-IN') +
        '<br>' + escapeHtml(item.description) +
        '<br><span style="color:#aaa6bd">' + escapeHtml(item.reason) +
        '</span></p><br>'
    ).join('');
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, char => ({
        '&':'&amp;',
        '<':'&lt;',
        '>':'&gt;',
        '"':'&quot;',
        "'":'&#039;'
    })[char]);
}

function shareResults() {
    const text = 'My Giftly AI gift recommendations:\n' +
        [...document.querySelectorAll('.gift-card')]
        .map(card => card.dataset.name + ' - ₹' +
            Number(card.dataset.price).toLocaleString('en-IN'))
        .join('\n');

    if (navigator.share) {
        navigator.share({title:'Giftly AI Recommendations',text:text});
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(text)
            .then(() => alert('Gift list copied!'));
    } else {
        alert(text);
    }
}

// Restore saved favourites on this browser.
const savedFavourites = JSON.parse(localStorage.getItem('giftlyFavourites') || '[]');

document.querySelectorAll('.gift-card').forEach(card => {
    if (savedFavourites.includes(card.dataset.name)) {
        const button = card.querySelector('.fav');
        button.classList.add('active');
        button.textContent = '♥ Saved';
    }
});
</script>
</body>
</html>
