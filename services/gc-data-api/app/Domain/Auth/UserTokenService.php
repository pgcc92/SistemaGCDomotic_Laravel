<?php

namespace App\Domain\Auth;

use Illuminate\Contracts\Encryption\Encrypter;

final class UserTokenService
{
    public const PREFIX = 'u1.';

    public function __construct(
        private readonly Encrypter $crypt,
    ) {
    }

    /** @param array{uid:int,tenant:string,exp:int} $claims */
    public function mint(array $claims): string
    {
        $payload = json_encode($claims, JSON_UNESCAPED_SLASHES);
        return self::PREFIX.$this->crypt->encryptString((string) $payload);
    }

    /** @return array{uid:int,tenant:string,exp:int} */
    public function parse(string $token): array
    {
        if (!str_starts_with($token, self::PREFIX)) {
            throw new \RuntimeException('Invalid token');
        }

        $cipher = substr($token, strlen(self::PREFIX));
        $json = $this->crypt->decryptString($cipher);
        $data = json_decode((string) $json, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid token');
        }

        return [
            'uid' => (int) ($data['uid'] ?? 0),
            'tenant' => (string) ($data['tenant'] ?? 'default'),
            'exp' => (int) ($data['exp'] ?? 0),
        ];
    }
}

