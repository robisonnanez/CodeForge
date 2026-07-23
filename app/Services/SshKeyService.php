<?php

namespace App\Services;

use App\Models\UserSshKey;
use RuntimeException;

class SshKeyService
{
    /**
     * @return array{public_key: string, algorithm: string, fingerprint: string}
     */
    public function inspectPublicKey(string $publicKey): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($publicKey));

        if (! is_string($normalized) || $normalized === '') {
            throw new RuntimeException('The SSH public key is empty.');
        }

        if (! preg_match('/^(ssh-(rsa|ed25519)|ecdsa-sha2-nistp(256|384|521)) ([A-Za-z0-9+\/=]+)(?: .+)?$/', $normalized, $matches)) {
            throw new RuntimeException('The SSH public key format is invalid.');
        }

        $decoded = base64_decode($matches[4], true);

        if ($decoded === false || strlen($decoded) < 32) {
            throw new RuntimeException('The SSH public key payload is invalid.');
        }

        return [
            'public_key' => $normalized,
            'algorithm' => $matches[1],
            'fingerprint' => 'SHA256:'.rtrim(base64_encode(hash('sha256', $decoded, true)), '='),
        ];
    }

    /**
     * @return array{id: int, name: string, algorithm: string, fingerprint: string, last_used_at: mixed, revoked_at: mixed, created_at: mixed}
     */
    public function metadata(UserSshKey $key): array
    {
        return [
            'id' => $key->id,
            'name' => $key->name,
            'algorithm' => $key->algorithm,
            'fingerprint' => $key->fingerprint,
            'last_used_at' => $key->last_used_at,
            'revoked_at' => $key->revoked_at,
            'created_at' => $key->created_at,
        ];
    }
}
