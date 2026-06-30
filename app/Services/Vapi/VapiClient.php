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
    public function createChat(array $payload): array
    {
        return $this->http()->post('/chat', $payload)->throw()->json();
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

    /** @throws RequestException */
    public function getPhoneNumber(string $id): array
    {
        return $this->http()->get("/phone-number/{$id}")->throw()->json();
    }

    /** Delete a phone number (best-effort, ignores 404) */
    public function deletePhoneNumber(string $id): void
    {
        $this->deleteResource("/phone-number/{$id}");
    }

    /** Delete an assistant (best-effort, ignores 404) */
    public function deleteAssistant(string $id): void
    {
        $this->deleteResource("/assistant/{$id}");
    }

    /** Delete a tool (best-effort, ignores 404) */
    public function deleteTool(string $id): void
    {
        $this->deleteResource("/tool/{$id}");
    }

    /** @throws RequestException */
    public function listVoices(): array
    {
        return $this->http()->get('/voices')->throw()->json();
    }

    /** @throws RequestException */
    public function getCall(string $id): array
    {
        return $this->http()->get("/call/{$id}")->throw()->json();
    }

    private function deleteResource(string $uri): void
    {
        try {
            $this->http()->delete($uri)->throw();
        } catch (RequestException $e) {
            if ($e->response?->status() === 404) {
                return;
            }

            throw $e;
        }
    }
}
