<?php

namespace App\Services;

class AlRajhiEncryptionService
{
    private string $key;
    private string $iv;

    public function __construct()
    {
        $this->key = (string) config('services.alrajhi.encryption_key');
        $this->iv = str_pad((string) config('services.alrajhi.iv'), 16, "\0");
    }

    /**
     * Encrypt data using AES-256-CBC (hex encoded, as required by the gateway)
     */
    public function encrypt(string $data): string
    {
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );

        return bin2hex((string) $encrypted);
    }

    /**
     * Decrypt hex encoded AES-256-CBC data. Returns false on failure.
     */
    public function decrypt(string $data): string|false
    {
        $binaryData = @hex2bin(trim($data));

        if ($binaryData === false) {
            return false;
        }

        return openssl_decrypt(
            $binaryData,
            'aes-256-cbc',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );
    }
}
