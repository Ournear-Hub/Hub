<?php

namespace App\Support;

final class WhatsAppCloudConfig
{
    public static function provider(): string
    {
        $provider = strtolower(trim((string) config('whatsapp.provider', 'unconfigured')));

        return $provider !== '' ? $provider : 'unconfigured';
    }

    public static function usesCloud(): bool
    {
        return in_array(self::provider(), ['cloud', 'meta', 'whatsapp_cloud'], true);
    }

    public static function isReady(): bool
    {
        if (! self::usesCloud()) {
            return false;
        }

        return self::graphVersion() !== ''
            && self::accessToken() !== ''
            && self::phoneNumberId() !== '';
    }

    public static function graphBaseUrl(): string
    {
        $url = rtrim((string) config('whatsapp.graph.base_url', 'https://graph.facebook.com'), '/');

        return $url !== '' ? $url : 'https://graph.facebook.com';
    }

    public static function graphVersion(): string
    {
        $version = trim((string) config('whatsapp.graph.version', ''));
        $version = ltrim($version, '/');

        return $version;
    }

    public static function accessToken(): string
    {
        return trim((string) config('whatsapp.access_token', ''));
    }

    public static function phoneNumberId(): string
    {
        return trim((string) config('whatsapp.phone_number_id', ''));
    }

    public static function businessAccountId(): string
    {
        return trim((string) config('whatsapp.business_account_id', ''));
    }

    public static function appSecret(): string
    {
        return trim((string) config('whatsapp.app_secret', ''));
    }

    public static function verifyToken(): string
    {
        return trim((string) config('whatsapp.verify_token', ''));
    }

    public static function timeoutSeconds(): int
    {
        return max(3, (int) config('whatsapp.graph.timeout', 15));
    }

    public static function messagesEndpoint(): string
    {
        return self::graphBaseUrl().'/'.self::graphVersion().'/'.self::phoneNumberId().'/messages';
    }
}
