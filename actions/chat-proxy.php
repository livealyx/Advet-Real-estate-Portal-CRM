<?php
// FILE: actions/chat-proxy.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);

// Auto-migration check
try {
    $pdo->query("SELECT 1 FROM chat_messages LIMIT 1");
} catch (\Throwable $e) {
    // Table missing, run quick migration
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      session_id VARCHAR(100) NOT NULL,
      user_id INT UNSIGNED DEFAULT NULL,
      role ENUM('user', 'assistant', 'system') NOT NULL,
      content TEXT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (session_id),
      INDEX (created_at),
      CONSTRAINT fk_chat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
}

// ── Auto-purge: delete sessions inactive for more than 1 hour ──────────────
// Runs on every request (piggybacks on existing traffic — no cron needed).
// Deletes all rows belonging to sessions whose last message is older than 1h.
try {
    $pdo->exec("
        DELETE FROM chat_messages
        WHERE session_id IN (
            SELECT session_id FROM (
                SELECT session_id
                FROM   chat_messages
                GROUP  BY session_id
                HAVING MAX(created_at) < NOW() - INTERVAL 1 HOUR
            ) AS expired_sessions
        )
    ");
} catch (\Throwable $e) {
    // Non-fatal: purge failure should never block a chat response
}

if (($settings['ai_enabled'] ?? '0') !== '1') {
    echo json_encode(['success' => false, 'message' => 'AI is currently disabled.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$userMessage = $input['message'] ?? '';
$lsId = $_GET['ls_id'] ?? ($input['ls_id'] ?? null);

// ── Handle Explicit Clear ───────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    unset($_SESSION['chat_session_id']);
    echo json_encode(['success' => true]);
    exit;
}

$sessionId = $_SESSION['chat_session_id'] ?? $lsId;

if (!$sessionId) {
    $sessionId = bin2hex(random_bytes(16));
}
$_SESSION['chat_session_id'] = $sessionId;

if (isset($_GET['action']) && $_GET['action'] === 'load') {
    $sessionId = $_SESSION['chat_session_id'] ?? null;
    if ($sessionId) {
        $stmt = $pdo->prepare("SELECT role, content FROM chat_messages WHERE session_id = ? ORDER BY id ASC LIMIT 50");
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            // Session was purged (expired) — issue a brand-new session ID
            $newSessionId = bin2hex(random_bytes(16));
            $_SESSION['chat_session_id'] = $newSessionId;
            echo json_encode(['success' => true, 'history' => [], 'ls_id' => $newSessionId, 'expired' => true]);
        } else {
            echo json_encode(['success' => true, 'history' => $rows, 'ls_id' => $sessionId, 'expired' => false]);
        }
    } else {
        // No session yet — generate a fresh one
        $newSessionId = bin2hex(random_bytes(16));
        $_SESSION['chat_session_id'] = $newSessionId;
        echo json_encode(['success' => true, 'history' => [], 'ls_id' => $newSessionId, 'expired' => false]);
    }
    exit;
}

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'message' => 'Empty message.']);
    exit;
}

// 1. Save User Message
$stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, user_id, role, content) VALUES (?, ?, 'user', ?)");
$stmt->execute([$sessionId, $_SESSION['user']['id'] ?? null, $userMessage]);

// 2. Build Context (RAG-lite)
$context = "";
$keywords = ['property', 'properties', 'villa', 'villas', 'house', 'houses', 'plot', 'plots', 'buy', 'sell', 'rent', 'price', 'prices', 'location', 'where', 'cost', 'home', 'homes', 'listing', 'listings', 'flat', 'flats', 'bhk', 'apartment', 'apartments', 'visit', 'tour'];
$isPropertyRelated = false;
foreach ($keywords as $kw) {
    if (stripos($userMessage, $kw) !== false) {
        $isPropertyRelated = true;
        break;
    }
}

if ($isPropertyRelated) {
    $context = "\nIMPORTANT CONTEXT ABOUT ADVET BUILDWELL:\n";
    
    try {
        $priceStmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM properties WHERE status='active'");
        $priceRange = $priceStmt->fetch();
        if ($priceRange && $priceRange['min_price']) {
            $context .= "Current Catalog Price Range is strictly: " . formatPrice((float)$priceRange['min_price']) . " to " . formatPrice((float)$priceRange['max_price']) . ".\n";
        }
    } catch (\Throwable $e) {}
    
    // Dynamic property search
    $sql = "SELECT title, slug, location, price, bedrooms, bathrooms, sqft, description FROM properties WHERE status='active'";
    $params = [];
    
    if (preg_match('/(\d)\s*bhk/i', $userMessage, $m)) {
        $sql .= " AND bedrooms = ?";
        $params[] = (int)$m[1];
    }

    // New: Handle location detection in the query (e.g., "at Bhiwadi", "in Gurgaon")
    if (preg_match('/(?:at|in|near)\s+([a-zA-Z\s]+?)(?:\s|$|[\.!\?,])/i', $userMessage, $locMatch)) {
        $locStr = trim($locMatch[1]);
        if (strlen($locStr) >= 3) {
            $sql .= " AND (location LIKE ? OR title LIKE ?)";
            $params[] = "%{$locStr}%";
            $params[] = "%{$locStr}%";
        }
    }
    
    if (preg_match('/lowest|cheap|affordable|budget/i', $userMessage)) {
        $sql .= " ORDER BY price ASC";
    } else {
        $sql .= " ORDER BY created_at DESC";
    }
    
    $sql .= " LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $props = $stmt->fetchAll();
    
    if ($props) {
        $context .= "Available Properties (Top 5):\n";
        foreach ($props as $p) {
            $propUrl = "property/{$p['slug']}"; // Matches .htaccess rewrite rule
            $context .= "- {$p['title']} in {$p['location']} (" . formatPrice((float)$p['price']) . "). Size: {$p['sqft']} sqft. Link: {$propUrl}\nDescription: " . substr(strip_tags($p['description']), 0, 100) . "...\n";
        }
        $context .= "\nIMPORTANT: Always share this link to see the full catalog: [View All Properties](properties)\n";
    } else {
        $context .= "No exact matches found for specific criteria. Suggest visiting our full catalog: [View All Properties](properties)\n";
    }

    // Add FAQ
    $stmtFaq = $pdo->query("SELECT question, answer FROM faqs WHERE status='active' LIMIT 3");
    $faqs = $stmtFaq->fetchAll();
    if ($faqs) {
        $context .= "\nGeneral Info:\n";
        foreach ($faqs as $f) {
            $context .= "Q: {$f['question']}\nA: ".substr($f['answer'], 0, 100)."\n";
        }
    }
}

$isAdmin = (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin');

$systemPrompt = $settings['ai_system_instruction'] ?? "You are Ask Advet, a professional real estate assistant for Advet Buildwell.";
$systemPrompt .= "\n\nHIGHEST PRIORITY: Speak in a natural, professional Indian style. Be warm, direct, and helpful.
- For property recommendations, ALWAYS include a Markdown link using the 'Link' provided in context. Example: [View Property](Link).
- NEVER make up or hallucinate properties or prices. Only recommend exact properties or the 'Current Catalog Price Range' explicitly listed in context.
- Always include the link to the full catalog: [View All Properties](properties).
- If someone says hello, respond with 'Namaste!' or 'Hello! How can I help you today?'
- Keep responses short (max 3 sentences). 
- Use Indian currency units (Lakhs, Crores) if the price is large, or just use the formatted price provided.
- Be efficient and accurate.";

if ($isAdmin) {
    $systemPrompt .= "\n\nADMIN MODE: Use [ACTION: add_faq {\"q\": \"...\", \"a\": \"...\"}] to add FAQs if asked.";
}

if ($context) {
    $systemPrompt .= "\n" . $context;
}

// 3. Get Recent Chat History (Last 10 messages)
// We fetch by ID DESC and then REVERSE to get chronological order including the message just saved.
$stmt = $pdo->prepare("SELECT role, content FROM chat_messages WHERE session_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$sessionId]);
$history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $h) {
    $messages[] = ['role' => $h['role'], 'content' => $h['content']];
}

// 4. Call AI API
$provider = $settings['ai_provider'] ?? 'gemini';
$apiKey   = $settings['ai_api_key'] ?? '';
$model    = $settings['ai_model'] ?? 'gemini-1.5-flash';

$aiResponse = "";

try {
    if ($provider === 'gemini') {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $geminiMessages = [];
        foreach ($messages as $m) {
            if ($m['role'] === 'system') continue; 
            $role = ($m['role'] === 'user') ? 'user' : 'model';
            $geminiMessages[] = ['role' => $role, 'parts' => [['text' => $m['content']]]];
        }
        $payload = [
            'contents' => $geminiMessages,
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]]
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $result = curl_exec($ch);
        $err = curl_error($ch);
        $resObj = json_decode($result, true);
        curl_close($ch);

        if ($err) {
            $aiResponse = "I encountered a connection error: " . $err;
        } elseif (isset($resObj['error'])) {
            $aiResponse = "AI Provider Error: " . ($resObj['error']['message'] ?? 'Unknown API error');
        } else {
            $aiResponse = $resObj['candidates'][0]['content']['parts'][0]['text'] ?? "I'm sorry, I'm having trouble connecting to my brain right now. The response was empty.";
        }
    } elseif ($provider === 'openai') {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['model' => $model, 'messages' => $messages]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer {$apiKey}"]);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $result = curl_exec($ch);
        $err = curl_error($ch);
        $resObj = json_decode($result, true);
        curl_close($ch);

        if ($err) {
            $aiResponse = "OpenAI Connection Error: " . $err;
        } elseif (isset($resObj['error'])) {
            $aiResponse = "OpenAI Provider Error: " . ($resObj['error']['message'] ?? 'Unknown API error');
        } else {
            $aiResponse = $resObj['choices'][0]['message']['content'] ?? "OpenAI call returned empty.";
        }
    } elseif ($provider === 'openrouter') {
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['model' => $model, 'messages' => $messages]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer {$apiKey}"]);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $result = curl_exec($ch);
        $err = curl_error($ch);
        $resObj = json_decode($result, true);
        curl_close($ch);

        if ($err) {
            $aiResponse = "OpenRouter Connection Error: " . $err;
        } elseif (isset($resObj['error'])) {
            $aiResponse = "OpenRouter Provider Error: " . ($resObj['error']['message'] ?? 'Unknown API error');
        } else {
            $aiResponse = $resObj['choices'][0]['message']['content'] ?? "OpenRouter call returned empty.";
        }
    }

    // 5. Re-check PDO (Server could go away during long curl wait)
    $pdo = getPDO();

    // 6. Handle Actions (Admin Only)
    if ($isAdmin && preg_match('/\[ACTION: add_faq (\{.*?\})\]/s', $aiResponse, $matches)) {
        $actParams = json_decode($matches[1], true);
        if (isset($actParams['q'], $actParams['a'])) {
            $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, status) VALUES (?, ?, 'active')");
            $stmt->execute([$actParams['q'], $actParams['a']]);
            $aiResponse = str_replace($matches[0], "✅ System: FAQ added successfully.", $aiResponse);
        }
    }

    // 7. Save AI Response & Return
    $stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, user_id, role, content) VALUES (?, NULL, 'assistant', ?)");
    $stmt->execute([$sessionId, $aiResponse]);

    echo json_encode(['success' => true, 'response' => $aiResponse, 'ls_id' => $sessionId]);

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
