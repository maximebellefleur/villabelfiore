<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\DB;
use App\Support\CSRF;

class AiController
{
    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Unauthenticated']);
            exit;
        }
    }

    private function jsonError(string $msg, int $code = 400, array $debug = []): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $msg, 'debug' => $debug]);
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
     * POST /api/ai/identify-seed
     * Body: _token, image_data (base64), image_mime, [image_data_2, image_mime_2]
     * Returns JSON: { ok, fields: {...}, debug: [...] }
     */
    public function identifySeed(Request $request, array $params = []): void
    {
        $debug = [];
        try {
            $this->identifySeedInner($request, $debug);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'debug' => $debug]);
            exit;
        }
    }

    private function identifySeedInner(Request $request, array &$debug): void
    {
        $this->requireAuth();

        $token = $request->post('_token', '');
        if (!CSRF::validateToken($token)) {
            $this->jsonError('Invalid CSRF token', 403);
        }

        // Collect up to 2 images (front + back of packet)
        $images = [];
        foreach (['', '_2'] as $suffix) {
            $raw  = trim($request->post('image_data' . $suffix, ''));
            $mime = trim($request->post('image_mime' . $suffix, 'image/jpeg'));
            if ($raw === '') continue;
            // Strip data URL prefix if accidentally included
            if (str_contains($raw, ',')) {
                $raw = substr($raw, strpos($raw, ',') + 1);
            }
            $images[] = ['b64' => $raw, 'mime' => $mime];
        }

        if (empty($images)) {
            $this->jsonError('No image data received');
        }

        $db   = DB::getInstance();
        $mode = $this->getSetting($db, 'ai.mode', 'local');

        // extra_prompt_override from POST takes precedence over saved setting (per-upload edit)
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

        $debug[] = ['step' => 'mode',         'value' => $mode];
        $debug[] = ['step' => 'images_count',  'value' => count($images)];
        $debug[] = ['step' => 'extra_prompt',  'value' => $extraPrompt !== '' ? 'yes (' . strlen($extraPrompt) . ' chars)' : 'none'];
        $debug[] = ['step' => 'prompt_source', 'value' => $extraPromptRaw !== null ? 'per-upload override' : 'saved setting'];

        if ($mode === 'huggingface') {
            $this->callHuggingFace($db, $images, $basePrompt, $debug);
        } else {
            $this->callOllama($db, $images, $basePrompt, $debug);
        }
    }

    // ── Ollama (local) ────────────────────────────────────────────────────────


    private function callOllama(DB $db, array $images, string $prompt, array &$debug): void
    {
        $endpoint = rtrim($this->getSetting($db, 'ai.endpoint', 'http://localhost:11434'), '/');
        $model    = $this->getSetting($db, 'ai.vision_model', 'llava');

        $debug[] = ['step' => 'ollama_endpoint', 'value' => $endpoint];
        $debug[] = ['step' => 'ollama_model',    'value' => $model];

        $payload = json_encode([
            'model'  => $model,
            'prompt' => $prompt,
            'images' => array_column($images, 'b64'),
            'stream' => false,
        ]);

        $debug[] = ['step' => 'sending_to_ollama', 'value' => $endpoint . '/api/generate'];

        $ch = curl_init($endpoint . '/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $raw   = curl_exec($ch);
        $errno = curl_errno($ch);
        $info  = curl_getinfo($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        $debug[] = ['step' => 'curl_errno', 'value' => $errno];
        $debug[] = ['step' => 'http_code',  'value' => $info['http_code'] ?? 'n/a'];

        if ($errno !== 0 || $raw === false) {
            $this->jsonError('Could not reach Ollama at ' . $endpoint . '. Error: ' . $curlError . '. Make sure Ollama is running (ollama serve) and the vision model is installed (ollama pull ' . $model . ').', 502, $debug);
        }

        if (($info['http_code'] ?? 0) !== 200) {
            $debug[] = ['step' => 'raw_response', 'value' => substr($raw, 0, 500)];
            $this->jsonError('Ollama returned HTTP ' . ($info['http_code'] ?? '?') . '. Is the model "' . $model . '" installed? Run: ollama pull ' . $model, 502, $debug);
        }

        $ollamaResp = json_decode($raw, true);
        $text       = trim($ollamaResp['response'] ?? '');

        $debug[] = ['step' => 'raw_ai_text', 'value' => substr($text, 0, 600)];

        $this->parseAndRespond($text, $debug);
    }

    // ── HuggingFace Inference API (OpenAI-compatible chat completions) ────────

    private function callHuggingFace(DB $db, array $images, string $prompt, array &$debug): void
    {
        $hfEndpoint = rtrim($this->getSetting($db, 'ai.hf_endpoint', ''), '/');
        $hfModel    = $this->getSetting($db, 'ai.hf_model', '');
        $hfToken    = $this->getSetting($db, 'ai.hf_token', '');

        if ($hfEndpoint === '') {
            $this->jsonError('HuggingFace endpoint URL is not configured. Go to Settings → AI to set it.', 400, $debug);
        }

        // Normalise: append /v1/chat/completions if the URL doesn't already end with it
        if (!str_ends_with($hfEndpoint, '/chat/completions')) {
            $hfEndpoint = rtrim($hfEndpoint, '/') . '/v1/chat/completions';
        }

        $debug[] = ['step' => 'hf_endpoint', 'value' => $hfEndpoint];
        $debug[] = ['step' => 'hf_model',    'value' => $hfModel ?: '(not set, using tgi)'];
        $debug[] = ['step' => 'hf_token',    'value' => $hfToken !== '' ? 'set (' . strlen($hfToken) . ' chars)' : 'not set'];

        // Build multimodal content — text prompt + all images
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
            'max_tokens' => 1024,
        ]);

        $headers = ['Content-Type: application/json'];
        if ($hfToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $hfToken;
        }
        // OpenRouter requires HTTP-Referer to identify the app (recommended even on free tier)
        if (str_contains($hfEndpoint, 'openrouter.ai')) {
            $headers[] = 'HTTP-Referer: https://github.com/maximebellefleur/villabelfiore';
            $headers[] = 'X-Title: Rooted';
        }

        $debug[] = ['step' => 'sending_to_hf', 'value' => $hfEndpoint];

        $ch = curl_init($hfEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $raw      = curl_exec($ch);
        $errno    = curl_errno($ch);
        $info     = curl_getinfo($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        $debug[] = ['step' => 'curl_errno', 'value' => $errno];
        $debug[] = ['step' => 'http_code',  'value' => $info['http_code'] ?? 'n/a'];

        if ($errno !== 0 || $raw === false) {
            $this->jsonError('Could not reach HuggingFace endpoint. Error: ' . $curlErr, 502, $debug);
        }

        if (($info['http_code'] ?? 0) >= 400) {
            $debug[] = ['step' => 'raw_response', 'value' => substr($raw, 0, 800)];
            $decoded = json_decode($raw, true);
            // Gemini wraps errors in an array: [{"error":{...}}]
            if (is_array($decoded) && isset($decoded[0])) {
                $decoded = $decoded[0];
            }
            $rawErr  = $decoded['error'] ?? null;
            $errMsg  = is_array($rawErr)
                ? ($rawErr['message'] ?? json_encode($rawErr))
                : (is_string($rawErr) ? $rawErr : 'HTTP ' . ($info['http_code'] ?? '?'));
            $this->jsonError($errMsg, (int)($info['http_code'] ?? 502), $debug);
        }

        $hfResp = json_decode($raw, true);
        $text   = trim($hfResp['choices'][0]['message']['content'] ?? '');

        $debug[] = ['step' => 'raw_ai_text', 'value' => substr($text, 0, 600)];

        if ($text === '') {
            $this->jsonError('HuggingFace returned an empty response. Check the model supports vision/image input.', 502, $debug);
        }

        $this->parseAndRespond($text, $debug);
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

    // ── Parse AI text → JSON → sanitised fields ───────────────────────────────

    private function parseAndRespond(string $text, array $debug): void
    {
        // Strip markdown code fences
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', trim($text));
        $text = trim($text);

        // Extract first JSON object
        $jsonStart = strpos($text, '{');
        $jsonEnd   = strrpos($text, '}');
        if ($jsonStart !== false && $jsonEnd !== false) {
            $text = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
        }

        $debug[] = ['step' => 'parsed_json_attempt', 'value' => substr($text, 0, 400)];

        $fields = json_decode($text, true);
        if (!is_array($fields)) {
            $debug[] = ['step' => 'json_decode_error', 'value' => json_last_error_msg()];
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'AI response could not be parsed as JSON. Raw: ' . substr($text, 0, 200), 'debug' => $debug]);
            exit;
        }

        $debug[] = ['step' => 'json_decoded_keys', 'value' => implode(', ', array_keys($fields))];

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

        $debug[] = ['step' => 'done', 'value' => 'Fields sanitised OK'];

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'fields' => $safeFields, 'debug' => $debug]);
        exit;
    }
}
