<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class SpamProtectionService
{
    protected const URL_REGEX = '/(?:https?:\/\/|www\.)/i';

    protected const EMAIL_REGEX = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';

    protected const SPAM_PATTERNS = [
        '/\bseo\b/i',
        '/\bsearch engine optimization\b/i',
        '/\bbacklink(?:s)?\b/i',
        '/\bguest post(?:ing)?\b/i',
        '/\bdomain authority\b/i',
        '/\bwebsite (?:design|redesign|development)\b/i',
        '/\bdigital marketing\b/i',
        '/\bgrow your business\b/i',
        '/\breferral partner(?:s)?\b/i',
        '/\bpotential referral partner(?:s)?\b/i',
        '/\bsmall suggestion\b/i',
        '/\bpartnership (?:opportunity|proposal)\b/i',
        '/\bcollaboration (?:opportunity|proposal)\b/i',
        '/\bquick question\b/i',
        '/\bincrease (?:your )?(?:sales|traffic)\b/i',
        '/\blead generation\b/i',
    ];

    public static function honeypotTriggered(Request $request, string $field): bool
    {
        $value = trim((string) $request->input($field, ''));
        return $value !== '';
    }

    public static function hitRateLimit(string $scope, string $identifier, int $maxAttempts, int $decaySeconds): ?int
    {
        $key = $scope . ':' . sha1(strtolower(trim($identifier)));

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return RateLimiter::availableIn($key);
        }

        RateLimiter::hit($key, $decaySeconds);
        return null;
    }

    public static function isDuplicate(string $scope, string $subject, string $message, int $ttlSeconds = 3600): bool
    {
        $payload = self::normalize($subject) . '|' . self::normalize($message);
        $key = $scope . ':dup:' . sha1($payload);
        return !Cache::add($key, now()->timestamp, $ttlSeconds);
    }

    public static function detectSpamReason(string $subject, string $message): ?string
    {
        $subject = self::normalize($subject);
        $message = self::normalize($message);
        $content = trim($subject . ' ' . $message);

        if ($content === '') {
            return null;
        }

        $urlCount = preg_match_all(self::URL_REGEX, $content);
        if ($urlCount >= 2) {
            return 'too_many_links';
        }

        $emailCount = preg_match_all(self::EMAIL_REGEX, $content);
        if ($emailCount >= 2) {
            return 'too_many_contacts';
        }

        foreach (self::SPAM_PATTERNS as $pattern) {
            if (preg_match($pattern, $content)) {
                return 'promotional_content';
            }
        }

        return null;
    }

    protected static function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
