<?php

namespace App\Services;

use Laravel\Ai\Embeddings;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    // Voyage-3 uses 1024 dimensions
    private int $dimensions = 1024;

    public function embedMany(array $texts): array
    {
        // The SDK handles baching and API calls for us
        $response = Embeddings::for($texts)
            ->dimensions($this->dimensions)
            ->generate(provider: 'voyageai');

        return $response->embeddings;
    }

    public function embedQuery(string $query): array
    {
        Log::info('EmbeddingService: Generating embedding via Voyage AI provider.');
        $response = Embeddings::for([$query])
            ->dimensions($this->dimensions)
            ->generate(provider: 'voyageai');

        return $response->embeddings[0];
    }
}
