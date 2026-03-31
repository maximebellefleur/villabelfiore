<?php

namespace App\Support;

/**
 * CSRF token generation and validation.
 * Token is stored in $_SESSION['_csrf_token'].
 */
class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Get the current token, generating one if it doesn't exist.
     */
    public static function getToken(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = self::generate();
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Generate a new token and store it in the session.
     */
    public static function generateToken(): string
    {
        $token = self::generate();
        $_SESSION[self::SESSION_KEY] = $token;
        return $token;
    }

    /**
     * Validate a token against the session-stored token.
     */
    public static function validateToken(string $token): bool
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? '';
        return $stored !== '' && hash_equals($stored, $token);
    }

    /**
     * Validate the current request's CSRF token.
     * Checks $_POST['_token'] and X-CSRF-Token header.
     */
    public static function verifyRequest(): bool
    {
        $token = $_POST['_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        return self::validateToken($token);
    }

    /**
     * Validate a given token; abort with 403 if invalid.
     * Used by controllers: CSRF::validate($token).
     */
    public static function validate(string $token): void
    {
        if (!self::validateToken($token)) {
            http_response_code(403);
            echo '<h1>403 — Invalid or missing CSRF token.</h1>';
            exit;
        }
    }

    /**
     * Return an HTML hidden input field with the current token.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::getToken(), ENT_QUOTES) . '">';
    }

    private static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }
}
