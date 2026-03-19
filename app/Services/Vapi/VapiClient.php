<?php

namespace App\Services\Vapi;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class VapiClient
{
    public function __construct(
        private readonly ?string $token = null,
        private readonly ?string $baseUrl = null,
    ) {
    }

    private function http()
    {
        $token = $this->token ?? config('services.vapi.key');
        $base = rtrim($this->baseUrl ?? config('services.vapi.base_url'), '/');

        return Http::baseUrl($base)
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    /** @throws RequestException */
    public function createTool(array $payload): array
    {
        return $this->http()->post('/tool', $payload)->throw()->json();
    }

    /** @throws RequestException */
    public function updateTool(string $id, array $payload): array
    {
        return $this->http()->patch("/tool/{$id}", $payload)->throw()->json();
    }

    /** @throws RequestException */
    public function createAssistant(array $payload): array
    {
        return $this->http()->post('/assistant', $payload)->throw()->json();
    }

    /** @throws RequestException */
    public function updateAssistant(string $id, array $payload): array
    {
        return $this->http()->patch("/assistant/{$id}", $payload)->throw()->json();
    }

    /** @throws RequestException */
    public function createPhoneNumber(array $payload): array
    {
        return $this->http()->post('/phone-number', $payload)->throw()->json();
    }

    /** @throws RequestException */
    public function updatePhoneNumber(string $id, array $payload): array
    {
        return $this->http()->patch("/phone-number/{$id}", $payload)->throw()->json();
    }

    /** Delete a phone number (best-effort, ignores 404) */
    public function deletePhoneNumber(string $id): void
    {
        $this->http()->delete("/phone-number/{$id}")->throw();
    }

    /** @throws RequestException */
    public function listVoices(): array
    {
        return $this->http()->get('/voices')->throw()->json();
    }
}