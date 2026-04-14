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
            echo json_encode(['ok' => false, 'error' => 'Unauthenticated']);
            exit;
        }
    }

    private function jsonError(string $msg, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $msg]);
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
     * Body: _token, image_data (base64, no prefix), image_mime
     * Returns JSON: { ok, fields: {...} }
     */
    public function identifySeed(Request $request, array $params = []): void
    {
        $this->requireAuth();

        $token = $request->post('_token', '');
        if (!CSRF::validateToken($token)) {
            $this->jsonError('Invalid CSRF token', 403);
        }

        $imageData = trim($request->post('image_data', ''));
        $imageMime = trim($request->post('image_mime', 'image/jpeg'));

        if ($imageData === '') {
            $this->jsonError('No image data received');
        }

        // Strip data URL prefix if accidentally included
        if (str_contains($imageData, ',')) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        }

        $db       = DB::getInstance();
        $endpoint = rtrim($this->getSetting($db, 'ai.endpoint', 'http://localhost:11434'), '/');
        $model    = $this->getSetting($db, 'ai.vision_model', 'llava');

        $prompt = <<<PROMPT
You are a botanical expert and seed catalog assistant. Analyze this image — it may show a plant, seed packet, seed bag, or loose seeds.

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
  "notes": "Any extra growing tips visible on the packet or known for this variety, or empty string"
}

If you cannot identify the plant or seed, set name to "Unknown" and all other fields to null or empty. Never output anything except the JSON object.
PROMPT;

        $payload = json_encode([
            'model'  => $model,
            'prompt' => $prompt,
            'images' => [$imageData],
            'stream' => false,
        ]);

        $ch = curl_init($endpoint . '/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $raw   = curl_exec($ch);
        $errno = curl_errno($ch);
        $info  = curl_getinfo($ch);
        curl_close($ch);

        if ($errno !== 0 || $raw === false) {
            $this->jsonError('Could not reach AI service at ' . $endpoint . '. Make sure Ollama is running (ollama serve) and the vision model is installed (ollama pull ' . $model . ').');
        }

        if (($info['http_code'] ?? 0) !== 200) {
            $this->jsonError('AI service returned HTTP ' . ($info['http_code'] ?? '?') . '. Is the model "' . $model . '" installed? Run: ollama pull ' . $model);
        }

        $ollamaResp = json_decode($raw, true);
        $text       = trim($ollamaResp['response'] ?? '');

        if ($text === '') {
            $this->jsonError('AI returned an empty response. Try again or use a different vision model.');
        }

        // Strip markdown code fences if the model added them
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', trim($text));
        $text = trim($text);

        // Extract first JSON object
        $jsonStart = strpos($text, '{');
        $jsonEnd   = strrpos($text, '}');
        if ($jsonStart !== false && $jsonEnd !== false) {
            $text = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
        }

        $fields = json_decode($text, true);
        if (!is_array($fields)) {
            $this->jsonError('AI response could not be parsed as JSON. Raw: ' . substr($text, 0, 200));
        }

        // Sanitize / normalise fields before returning
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
            'notes'              => (string)($fields['notes'] ?? ''),
        ];

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'fields' => $safeFields]);
        exit;
    }
}
