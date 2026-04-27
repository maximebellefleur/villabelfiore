<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\DB;
use App\Support\CSRF;

class AiController
{
    // ── SSE helpers ───────────────────────────────────────────────────────────

    private function sseLog(string $step, mixed $value): void
    {
        echo 'data: ' . json_encode(['type' => 'log', 'step' => $step, 'value' => $value]) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
    }

    private function sseFail(string $msg, int $code = 400): void
    {
        echo 'data: ' . json_encode(['type' => 'result', 'ok' => false, 'error' => $msg, 'code' => $code]) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
        exit;
    }

    private function sseSuccess(array $fields): void
    {
        echo 'data: ' . json_encode(['type' => 'result', 'ok' => true, 'fields' => $fields]) . "\n\n";
        if (ob_get_level()) ob_flush();
        flush();
        exit;
    }

    private function getSetting(DB $db, string $key, string $default = ''): string
    {
        $row = $db->fetchOne(
            "SELECT setting_value_text FROM settings WHERE setting_key = ? LIMIT 1",
            [$key]
        );
        return $row ? (string)($row['setting_value_text'] ?? $default) : $default;
    }

    /**
     * GET /api/ai/gemini-models
     * Returns the list of Gemini models available for the saved API key.
     */
    public function geminiModels(Request $request, array $params = []): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
            return;
        }
        $db     = DB::getInstance();
        $apiKey = $this->getSetting($db, 'ai.hf_token', '');
        if ($apiKey === '') {
            echo json_encode(['success' => false, 'error' => 'No API key saved. Save your AIza… key first, then click Fetch.']);
            return;
        }
        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($apiKey));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
        $raw  = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ((int)($info['http_code'] ?? 0) !== 200) {
            $d   = json_decode($raw ?: '{}', true);
            $err = $d['error']['message'] ?? ('HTTP ' . ($info['http_code'] ?? '?'));
            echo json_encode(['success' => false, 'error' => $err]);
            return;
        }
        $data   = json_decode($raw, true);
        $models = [];
        foreach ($data['models'] ?? [] as $m) {
            $name = str_replace('models/', '', $m['name'] ?? '');
            if (str_contains($name, 'gemini') && in_array('generateContent', $m['supportedGenerationMethods'] ?? [], true)) {
                $models[] = ['id' => $name, 'display' => $m['displayName'] ?? $name];
            }
        }
        echo json_encode(['success' => true, 'models' => $models]);
    }

    /**
     * POST /api/ai/identify-seed
     * Streams SSE: { type:'log', step, value } events, then a final { type:'result', ok, fields|error }
     */
    public function identifySeed(Request $request, array $params = []): void
    {
        // Disable all output buffering so SSE events flush immediately to the browser
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', false);
        while (ob_get_level() > 0) ob_end_clean();
        ob_implicit_flush(true);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no'); // nginx: disable proxy buffering

        // Padding comment ensures proxy buffers flush immediately (many buffer until ~4 KB)
        echo ': ' . str_repeat('.', 2048) . "\n\n";
        flush();

        try {
            $this->identifySeedInner($request);
        } catch (\Throwable $e) {
            $this->sseFail($e->getMessage(), 500);
        }
    }

    private function identifySeedInner(Request $request): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->sseFail('Unauthenticated', 401);
        }

        $token = $request->post('_token', '');
        if (!CSRF::validateToken($token)) {
            $this->sseFail('Invalid CSRF token', 403);
        }

        $images = [];
        foreach (['', '_2'] as $suffix) {
            $raw  = trim($request->post('image_data' . $suffix, ''));
            $mime = trim($request->post('image_mime' . $suffix, 'image/jpeg'));
            if ($raw === '') continue;
            if (str_contains($raw, ',')) {
                $raw = substr($raw, strpos($raw, ',') + 1);
            }
            $images[] = ['b64' => $raw, 'mime' => $mime];
        }

        if (empty($images)) {
            $this->sseFail('No image data received');
        }

        $db   = DB::getInstance();
        $mode = $this->getSetting($db, 'ai.mode', 'local');

        $extraPromptRaw = $request->post('extra_prompt_override', null);
        $extraPrompt    = $extraPromptRaw !== null
            ? trim($extraPromptRaw)
            : trim($this->getSetting($db, 'ai.extra_prompt', ''));

        $basePrompt = <<<PROMPT
You are a botanical expert and seed catalog assistant. Analyze this image — it may show a plant, seed packet, seed bag, or loose seeds. If multiple images are provided, they show the front and back of the same seed packet.

Respond ONLY with a single JSON object (no markdown fences, no explanation text, just the raw JSON). Use these exact keys:
{
  "name": "Common vegetable/plant name",
  "variety": "Cultivar or variety name, or empty string",
  "botanical_family": "Family name e.g. Solanaceae, or empty string",
  "type": "vegetable|herb|fruit|flower|other",
  "sowing_type": "direct|nursery|both",
  "days_to_germinate": integer or null,
  "days_to_maturity": integer or null,
  "spacing_cm": integer or null,
  "row_spacing_cm": integer or null,
  "sowing_depth_mm": integer or null,
  "sun_exposure": "Full sun|Partial shade|Full shade or empty string",
  "frost_hardy": true or false,
  "companions": ["plant1","plant2"],
  "antagonists": ["plant1","plant2"],
  "yield_per_plant_kg": number or null,
  "planting_months": [array of integers 1–12 for active sowing months, or empty array],
  "harvest_months": [array of integers 1–12 for active harvest months, or empty array],
  "notes": "Any extra growing tips visible on the packet or known for this variety, or empty string"
}

CALENDAR READING — Many seed packets print a row of month initials (e.g. J F M A M J J A S O N D) where some months are highlighted, circled, colored, filled, or printed in bold/dark. Read each row carefully:
- A row labelled "Semis", "Sowing", "Plantation", "À semer", "Sembrar" → fill planting_months with the numbers of the highlighted/active months (1=Jan, 2=Feb, …, 12=Dec).
- A row labelled "Récolte", "Harvest", "Cosecha", "Erntezeit", "À récolter" → fill harvest_months the same way.
If no calendar is visible, return empty arrays.

ICON READING — Interpret pictograms on the packet:
- Sun icon only → "Full sun". Half-sun/partial cloud → "Partial shade". Full cloud/shade icon → "Full shade".
- Thermometer or snow icon → affects frost_hardy.
- Depth arrows/measurements → sowing_depth_mm (convert cm to mm if needed).
- Spacing arrows → spacing_cm (within row) and row_spacing_cm (between rows).
- Germination/clock icon with days → days_to_germinate.
- Harvest/calendar with days → days_to_maturity. If a range is given (e.g. 85–130 days), use the midpoint.

If you cannot identify the plant or seed, set name to "Unknown" and all other fields to null or empty. Never output anything except the JSON object.
PROMPT;

        if ($extraPrompt !== '') {
            $basePrompt .= "\n\nAdditional instructions from the user:\n" . $extraPrompt;
        }

        $this->sseLog('mode',          $mode);
        $this->sseLog('images_count',  count($images));
        $this->sseLog('extra_prompt',  $extraPrompt !== '' ? 'yes (' . strlen($extraPrompt) . ' chars)' : 'none');
        $this->sseLog('prompt_source', $extraPromptRaw !== null ? 'per-upload override' : 'saved setting');

        if ($mode === 'openai') {
            $this->callOpenAI($db, $images, $basePrompt); // codex session token path
        } elseif ($mode === 'huggingface') {
            $endpoint = $this->getSetting($db, 'ai.hf_endpoint', '');
            if (str_contains($endpoint, 'generativelanguage.googleapis.com')) {
                $this->callGeminiNative($db, $images, $basePrompt);
            } else {
                $this->callHuggingFace($db, $images, $basePrompt);
            }
        } else {
            $this->callOllama($db, $images, $basePrompt);
        }
    }

    // ── Ollama (local) ────────────────────────────────────────────────────────

    private function callOllama(DB $db, array $images, string $prompt): void
    {
        $endpoint = rtrim($this->getSetting($db, 'ai.endpoint', 'http://localhost:11434'), '/');
        $model    = $this->getSetting($db, 'ai.vision_model', 'llava');

        $this->sseLog('ollama_endpoint', $endpoint);
        $this->sseLog('ollama_model',    $model);

        $payload = json_encode([
            'model'  => $model,
            'prompt' => $prompt,
            'images' => array_column($images, 'b64'),
            'stream' => false,
        ]);

        $this->sseLog('sending_to_ollama', $endpoint . '/api/generate');

        $ch = curl_init($endpoint . '/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $raw       = curl_exec($ch);
        $errno     = curl_errno($ch);
        $info      = curl_getinfo($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->sseLog('curl_errno', $errno);
        $this->sseLog('http_code',  $info['http_code'] ?? 'n/a');

        if ($errno !== 0 || $raw === false) {
            $this->sseFail('Could not reach Ollama at ' . $endpoint . '. Error: ' . $curlError, 502);
        }

        if (($info['http_code'] ?? 0) !== 200) {
            $this->sseLog('raw_response', substr($raw, 0, 500));
            $this->sseFail('Ollama returned HTTP ' . ($info['http_code'] ?? '?') . '. Is the model "' . $model . '" installed? Run: ollama pull ' . $model, 502);
        }

        $ollamaResp = json_decode($raw, true);
        $text       = trim($ollamaResp['response'] ?? '');

        $this->sseLog('raw_ai_text', substr($text, 0, 600));
        $this->parseAndRespond($text);
    }

    // ── Codex / OpenAI session token ─────────────────────────────────────────

    private function callOpenAI(DB $db, array $images, string $prompt): void
    {
        $apiKey = $this->getSetting($db, 'ai.codex_token', '');
        $model  = $this->getSetting($db, 'ai.codex_model', 'gpt-4o-mini') ?: 'gpt-4o-mini';

        if ($apiKey === '') {
            $this->sseFail('Codex session token is not set. Go to Settings → AI → Codex tab and paste your token.', 400);
        }

        $this->sseLog('codex_model',       $model);
        $this->sseLog('sending_to_openai', 'api.openai.com … ' . $model);

        // Build multimodal content — text + images
        $content = [['type' => 'text', 'text' => $prompt]];
        foreach ($images as $img) {
            $content[] = [
                'type'      => 'image_url',
                'image_url' => [
                    'url'    => 'data:' . $img['mime'] . ';base64,' . $img['b64'],
                    'detail' => 'high',
                ],
            ];
        }

        $payload = json_encode([
            'model'      => $model,
            'messages'   => [['role' => 'user', 'content' => $content]],
            'max_tokens' => 4096,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw     = curl_exec($ch);
        $errno   = curl_errno($ch);
        $info    = curl_getinfo($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $this->sseLog('curl_errno', $errno);
        $this->sseLog('http_code',  $info['http_code'] ?? 'n/a');

        if ($errno !== 0 || $raw === false) {
            $this->sseFail('Could not reach OpenAI API: ' . $curlErr, 502);
        }

        if (($info['http_code'] ?? 0) >= 400) {
            $this->sseLog('raw_response', substr($raw, 0, 800));
            $decoded = json_decode($raw, true);
            $rawErr  = $decoded['error'] ?? null;
            $errMsg  = is_array($rawErr)
                ? ($rawErr['message'] ?? json_encode($rawErr))
                : (is_string($rawErr) ? $rawErr : 'HTTP ' . ($info['http_code'] ?? '?'));
            $this->sseFail($errMsg, (int)($info['http_code'] ?? 502));
        }

        $resp = json_decode($raw, true);
        $text = trim($resp['choices'][0]['message']['content'] ?? '');

        $this->sseLog('raw_ai_text', substr($text, 0, 600));

        if ($text === '') {
            $this->sseFail('OpenAI returned an empty response.', 502);
        }

        $this->parseAndRespond($text);
    }

    // ── Gemini native API (generateContent) ──────────────────────────────────

    // HTTP codes that mean "overloaded/rate-limited — try next model"
    private const GEMINI_RETRY_CODES = [429, 500, 503];

    private function callGeminiNative(DB $db, array $images, string $prompt): void
    {
        $apiKey = $this->getSetting($db, 'ai.hf_token', '');

        if ($apiKey === '') {
            $this->sseFail('Gemini API key is not set. Go to Settings → AI and paste your AIza… key.', 400);
        }

        // ── Step 1: fetch available models for informational logging ──────────
        $this->sseLog('fetching_models', 'Querying Google for available models on this API key…');

        $lh = curl_init('https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($apiKey));
        curl_setopt_array($lh, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
        $listRaw  = curl_exec($lh);
        $listInfo = curl_getinfo($lh);
        curl_close($lh);

        $listData        = json_decode($listRaw ?: '{}', true);
        $availableModels = [];

        if (($listInfo['http_code'] ?? 0) === 200 && !empty($listData['models'])) {
            foreach ($listData['models'] as $m) {
                $name    = str_replace('models/', '', $m['name'] ?? '');
                $methods = $m['supportedGenerationMethods'] ?? [];
                if (str_contains($name, 'gemini') && in_array('generateContent', $methods, true)) {
                    $availableModels[] = $name;
                }
            }
        }

        if (!empty($availableModels)) {
            $this->sseLog('models_available', implode(', ', $availableModels));
        } else {
            $this->sseLog('models_warning', 'Could not retrieve model list (HTTP ' . ($listInfo['http_code'] ?? '?') . ')');
        }

        // ── Step 2: use the USER's saved model list (their choice) ───────────
        $savedListRaw = $this->getSetting($db, 'ai.gemini_model_list', '');
        $userList     = [];
        if ($savedListRaw !== '') {
            $decoded = json_decode($savedListRaw, true);
            if (is_array($decoded)) {
                $userList = array_filter(array_map('trim', $decoded));
            }
        }

        if (!empty($userList)) {
            // User has configured their preferred models — use exactly their list (cap at 5)
            $candidates = array_slice(array_values($userList), 0, 5);
            $this->sseLog('using_user_list', 'Using your saved model priority: ' . implode(' → ', $candidates));

            // Warn if any user-selected model isn't in the available list
            if (!empty($availableModels)) {
                foreach ($candidates as $cm) {
                    if (!in_array($cm, $availableModels, true)) {
                        $this->sseLog('model_not_confirmed', '"' . $cm . '" was not found in available models — will still try it');
                    }
                }
            }
        } else {
            // No user list saved — fall back to auto-selection from available
            $primaryModel = $this->getSetting($db, 'ai.hf_model', 'gemini-2.5-flash') ?: 'gemini-2.5-flash';
            $this->sseLog('no_user_list', 'No model priority saved. Go to Settings → AI → Cloud API → Configure model order. Using "' . $primaryModel . '" as primary.');

            if (!empty($availableModels)) {
                if (in_array($primaryModel, $availableModels, true)) {
                    $ordered = array_values(array_unique(array_merge([$primaryModel], $availableModels)));
                } else {
                    $this->sseLog('primary_not_available', '"' . $primaryModel . '" not confirmed — trying available models');
                    $ordered = $availableModels;
                }
                $candidates = array_slice($ordered, 0, 5);
            } else {
                $candidates = [$primaryModel];
            }
        }

        $this->sseLog('will_try', count($candidates) . ' model(s): ' . implode(' → ', $candidates));

        // ── Step 3: build payload once (identical for every model) ────────────
        $parts = [['text' => $prompt]];
        foreach ($images as $img) {
            $parts[] = ['inline_data' => ['mime_type' => $img['mime'], 'data' => $img['b64']]];
        }
        $payload = json_encode([
            'contents'         => [['parts' => $parts]],
            'generationConfig' => ['maxOutputTokens' => 4096, 'temperature' => 0.1],
        ]);

        $lastErrMsg   = '';
        $lastHttpCode = 0;

        // ── Step 4: loop ──────────────────────────────────────────────────────
        foreach ($candidates as $idx => $model) {
            $this->sseLog('trying_model', '(' . ($idx + 1) . '/' . count($candidates) . ') ' . $model);

            $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/'
                    . $model . ':generateContent?key=' . $apiKey;

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);

            $raw     = curl_exec($ch);
            $errno   = curl_errno($ch);
            $info    = curl_getinfo($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($errno !== 0 || $raw === false) {
                $this->sseFail('Could not reach Gemini API: ' . $curlErr, 502);
            }

            $httpCode = (int)($info['http_code'] ?? 0);
            $this->sseLog('http_code', $httpCode);

            if ($httpCode >= 400) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded[0])) { $decoded = $decoded[0]; }
                $rawErr  = $decoded['error'] ?? null;
                $errMsg  = is_array($rawErr)
                    ? ($rawErr['message'] ?? json_encode($rawErr))
                    : (is_string($rawErr) ? $rawErr : 'HTTP ' . $httpCode);

                if (in_array($httpCode, self::GEMINI_RETRY_CODES, true)) {
                    $this->sseLog('model_skipped', $model . ' → ' . $httpCode . ' (' . $errMsg . ')');
                    $lastErrMsg   = $errMsg;
                    $lastHttpCode = $httpCode;
                    continue; // try next model
                }

                // Non-retriable (401, 400, 404 …) — fail immediately
                $this->sseLog('model_error', $model . ' → ' . $httpCode . ': ' . $errMsg);
                $this->sseFail($errMsg, $httpCode);
            }

            // ── Success ───────────────────────────────────────────────────────
            $resp = json_decode($raw, true);
            $text = trim($resp['candidates'][0]['content']['parts'][0]['text'] ?? '');

            $this->sseLog('model_used',  $model);
            $this->sseLog('raw_ai_text', substr($text, 0, 600));

            if ($text === '') {
                $this->sseFail('Gemini returned an empty response from ' . $model, 502);
            }

            $this->parseAndRespond($text);
        }

        // All models exhausted — show full available list so the user knows what's on their key
        $this->sseLog('all_failed', 'Tried ' . count($candidates) . ' model(s), none responded.');
        $this->sseLog('models_available_recap', implode(', ', $availableModels ?: $candidates));
        $this->sseFail(
            'All ' . count($candidates) . ' Gemini model(s) are currently unavailable. '
            . 'Last error: ' . $lastErrMsg . '. '
            . 'Available on your key: ' . implode(', ', $availableModels ?: $candidates)
            . '. Try again in a moment or change your preferred model in Settings → AI.',
            $lastHttpCode ?: 503
        );
    }

    // ── HuggingFace / OpenRouter ──────────────────────────────────────────────

    private function callHuggingFace(DB $db, array $images, string $prompt): void
    {
        $hfEndpoint = rtrim($this->getSetting($db, 'ai.hf_endpoint', ''), '/');
        $hfModel    = $this->getSetting($db, 'ai.hf_model', '');
        $hfToken    = $this->getSetting($db, 'ai.hf_token', '');

        if ($hfEndpoint === '') {
            $this->sseFail('HuggingFace endpoint URL is not configured. Go to Settings → AI to set it.', 400);
        }

        if (!str_ends_with($hfEndpoint, '/chat/completions')) {
            $hfEndpoint = rtrim($hfEndpoint, '/') . '/v1/chat/completions';
        }

        $this->sseLog('hf_endpoint', $hfEndpoint);
        $this->sseLog('hf_model',    $hfModel ?: '(not set, using tgi)');
        $this->sseLog('hf_token',    $hfToken !== '' ? 'set (' . strlen($hfToken) . ' chars)' : 'not set');

        $content = [['type' => 'text', 'text' => $prompt]];
        foreach ($images as $img) {
            $content[] = [
                'type'      => 'image_url',
                'image_url' => ['url' => 'data:' . $img['mime'] . ';base64,' . $img['b64']],
            ];
        }

        $payload = json_encode([
            'model'      => $hfModel ?: 'tgi',
            'messages'   => [['role' => 'user', 'content' => $content]],
            'max_tokens' => 4096,
        ]);

        $headers = ['Content-Type: application/json'];
        if ($hfToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $hfToken;
        }
        if (str_contains($hfEndpoint, 'openrouter.ai')) {
            $headers[] = 'HTTP-Referer: https://github.com/maximebellefleur/villabelfiore';
            $headers[] = 'X-Title: Rooted';
        }

        $this->sseLog('sending_to_hf', $hfEndpoint);

        $ch = curl_init($hfEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw     = curl_exec($ch);
        $errno   = curl_errno($ch);
        $info    = curl_getinfo($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $this->sseLog('curl_errno', $errno);
        $this->sseLog('http_code',  $info['http_code'] ?? 'n/a');

        if ($errno !== 0 || $raw === false) {
            $this->sseFail('Could not reach HuggingFace endpoint. Error: ' . $curlErr, 502);
        }

        if (($info['http_code'] ?? 0) >= 400) {
            $this->sseLog('raw_response', substr($raw, 0, 800));
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded[0])) { $decoded = $decoded[0]; }
            $rawErr  = $decoded['error'] ?? null;
            $errMsg  = is_array($rawErr)
                ? ($rawErr['message'] ?? json_encode($rawErr))
                : (is_string($rawErr) ? $rawErr : 'HTTP ' . ($info['http_code'] ?? '?'));
            $this->sseFail($errMsg, (int)($info['http_code'] ?? 502));
        }

        $hfResp = json_decode($raw, true);
        $text   = trim($hfResp['choices'][0]['message']['content'] ?? '');

        $this->sseLog('raw_ai_text', substr($text, 0, 600));

        if ($text === '') {
            $this->sseFail('HuggingFace returned an empty response. Check the model supports vision/image input.', 502);
        }

        $this->parseAndRespond($text);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function sanitiseMonths(mixed $raw): array
    {
        if (!is_array($raw)) return [];
        return array_values(array_filter(
            array_map('intval', $raw),
            fn(int $m) => $m >= 1 && $m <= 12
        ));
    }

    private function parseAndRespond(string $text): void
    {
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', trim($text));
        $text = trim($text);

        $jsonStart = strpos($text, '{');
        $jsonEnd   = strrpos($text, '}');
        if ($jsonStart !== false && $jsonEnd !== false) {
            $text = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
        }

        $this->sseLog('parsed_json_attempt', substr($text, 0, 400));

        $fields = json_decode($text, true);
        if (!is_array($fields)) {
            $this->sseLog('json_decode_error', json_last_error_msg());
            $this->sseFail('AI response could not be parsed as JSON. Raw: ' . substr($text, 0, 200), 422);
        }

        $this->sseLog('json_decoded_keys', implode(', ', array_keys($fields)));

        $safeFields = [
            'name'               => (string)($fields['name'] ?? ''),
            'variety'            => (string)($fields['variety'] ?? ''),
            'botanical_family'   => (string)($fields['botanical_family'] ?? ''),
            'type'               => in_array($fields['type'] ?? '', ['vegetable','herb','fruit','flower','other']) ? $fields['type'] : 'vegetable',
            'sowing_type'        => in_array($fields['sowing_type'] ?? '', ['direct','nursery','both']) ? $fields['sowing_type'] : 'direct',
            'days_to_germinate'  => is_numeric($fields['days_to_germinate'] ?? null)  ? (int)$fields['days_to_germinate']  : null,
            'days_to_maturity'   => is_numeric($fields['days_to_maturity'] ?? null)   ? (int)$fields['days_to_maturity']   : null,
            'spacing_cm'         => is_numeric($fields['spacing_cm'] ?? null)         ? (int)$fields['spacing_cm']         : null,
            'row_spacing_cm'     => is_numeric($fields['row_spacing_cm'] ?? null)     ? (int)$fields['row_spacing_cm']     : null,
            'sowing_depth_mm'    => is_numeric($fields['sowing_depth_mm'] ?? null)    ? (int)$fields['sowing_depth_mm']    : null,
            'sun_exposure'       => (string)($fields['sun_exposure'] ?? ''),
            'frost_hardy'        => !empty($fields['frost_hardy']),
            'companions'         => is_array($fields['companions'] ?? null)   ? implode(', ', $fields['companions'])  : (string)($fields['companions'] ?? ''),
            'antagonists'        => is_array($fields['antagonists'] ?? null)  ? implode(', ', $fields['antagonists']) : (string)($fields['antagonists'] ?? ''),
            'yield_per_plant_kg' => is_numeric($fields['yield_per_plant_kg'] ?? null) ? (float)$fields['yield_per_plant_kg'] : null,
            'planting_months'    => self::sanitiseMonths($fields['planting_months'] ?? null),
            'harvest_months'     => self::sanitiseMonths($fields['harvest_months']  ?? null),
            'notes'              => (string)($fields['notes'] ?? ''),
        ];

        $this->sseLog('done', 'Fields sanitised OK');
        $this->sseSuccess($safeFields);
    }
}
