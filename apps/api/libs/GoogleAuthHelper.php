<?php
// Google Authentication Helper

class GoogleAuthHelper {
    /**
     * Verifies Google ID token via Google Tokeninfo API.
     * Returns payload on success, throws HttpException on failure.
     */
    public static function verifyIdToken($credential, $clientId) {
        if (empty($credential)) {
            throw new HttpException('Google credential is required', 400);
        }

        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Keep ssl verification for safety
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new HttpException('Token Google tidak valid', 401);
        }

        $payload = json_decode($response, true);
        if (!$payload || !isset($payload['email'])) {
            throw new HttpException('Email tidak ditemukan dari akun Google', 401);
        }

        // Verify audience (client ID)
        if (!empty($clientId) && isset($payload['aud']) && $payload['aud'] !== $clientId) {
            throw new HttpException('Audience Google Token tidak cocok', 401);
        }

        // Verify expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new HttpException('Token Google telah kedaluwarsa', 401);
        }

        return [
            'email' => strtolower($payload['email']),
            'name' => $payload['name'] ?? ''
        ];
    }
}
