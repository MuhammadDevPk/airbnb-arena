<?php

namespace App\Tools;

use App\Models\Listing;
use App\Services\EmbeddingService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * ListingSearchTool — Performs semantic vector search on Airbnb listings.
 *
 * Implements the Laravel AI SDK Tool interface so the agent can
 * automatically invoke it when users ask about listings.
 *
 * Uses the mongodb/laravel-mongodb Eloquent vectorSearch() method to find
 * listings that are semantically similar to the user's natural language query.
 * The query is embedded via Voyage AI (through the SDK), then matched
 * against pre-computed listing embeddings stored in MongoDB Atlas.
 */
class ListingSearchTool implements Tool
{
    /** @var array Structured listing data from the last search */
    private array $lastResults = [];

    public function __construct(private EmbeddingService $embeddingService)
    {

    }

    /**
     * Describe what this tool does — the agent reads this to decide when to use it.
     */
    public function description(): Stringable|string
    {
        return 'Search for Airbnb listings using semantic vector search. '
            . 'Use this to find properties matching a natural language description '
            . 'like "cozy apartment in Barcelona with pool" or "family-friendly house near the beach".';

    }

    /**
     * Define the parameters the agent can pass to this tool.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->object()
                ->description('Natural language search query describing the desired listing')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of results to return (default: 5)'),
        ];
    }

    /**
     * Get structured listing data from the last search execution.
     */

    public function getLastResults(): array
    {
        return $this->lastResults;
    }

    /**
     * Execute the search when the agent invokes this tool.
     */

    public function handle(Request $request): Stringable|string
    {
        $query = (string) $request->string('query');
        $limit = $request->integer('limit', 5) ?: 5;

        Log::info('ListingSearchTool: Searching', ['query' => $query, 'limit' => $limit]);

        try {
            // Step 1: Generate a query embedding using Voyage AI (via SDK)
            $queryEmbedding = $this->embeddingService->embedQuery($query);

            // Step 2: Use the Eloquent vectorSearch() method from mongodb/laravel-mongodb
            // Materialize results into a collection once to avoid double cursor iteration
            $results = Listing::vectorSearch(
                index: 'vector_index',
                path: 'embeddings',
                queryVector: $queryEmbedding,
                numCandidates: $limit * 10,
                limit: $limit,
            );

            // Step 3: Single-pass — build both structured frontend data and agent text
            $agentLines = [];
            $this->lastResults = [];

            foreach ($results as $index => $listing) {
                $address = (array) ($listing->address ?? []);
                $reviewScores = (array) ($listing->review_scores ?? []);
                $images = (array) ($listing->images ?? []);
                $price = $listing->price;

                if (is_object($price)) {
                    $price = (string) $price;
                }

                $score = round(($listing->vector_search_score ?? 0) * 100, 1);
                $location = $address['market'] ?? $address['country'] ?? 'Unknown';
                $rating = $reviewScores['review_scores_rating'] ?? 'N/A';
                $summary = $listing->summary ?? '';

                // Structured data for the frontend
                $this->lastResults[] = [
                    'id' => (string) $listing->_id,
                    'name' => $listing->name ?? 'Unnamed',
                    'summary' => $summary,
                    'property_type' => $listing->property_type ?? 'Property',
                    'room_type' => $listing->room_type ?? '',
                    'accommodates' => $listing->accommodates ?? null,
                    'bedrooms' => $listing->bedrooms ?? null,
                    'beds' => $listing->beds ?? null,
                    'bathrooms' => isset($listing->bathrooms) ? (string) $listing->bathrooms : null,
                    'price' => $price,
                    'location' => $location,
                    'country' => $address['country'] ?? '',
                    'street' => $address['street'] ?? '',
                    'image_url' => $images['picture_url'] ?? null,
                    'rating' => $reviewScores['review_scores_rating'] ?? null,
                    'cleanliness' => $reviewScores['review_scores_cleanliness'] ?? null,
                    'score' => $score,
                ];

                // Formatted text for the agent/LLM
                $agentLines[] = sprintf(
                    "%d. **%s** (ID: %s)\n   📍 %s | 🏠 %s | 👥 %s guests | 🛏️ %s beds\n   ⭐ Rating: %s/100 | 💰 $%s/night | 🎯 Match: %s%%\n   %s",
                    $index + 1,
                    $listing->name ?? 'Unnamed',
                    (string) $listing->_id,
                    $location,
                    $listing->property_type ?? 'Property',
                    $listing->accommodates ?? '?',
                    $listing->beds ?? '?',
                    $rating,
                    $price ?? '?',
                    $score,
                    $summary ? mb_substr($summary, 0, 150) . '...' : 'No description'
                );

            }

            if (empty($agentLines)) {
                return "No listings found matching your search. Try a different query.";
            }

            $count = count($agentLines);
            return "Found {$count} matching listings:\n\n" . implode("\n\n", $agentLines);

        } catch (\Exception $e) {
            Log::error('ListingSearchTool error', ['error' => $e->getMessage()]);
            return "Error searching listings: {$e->getMessage()}";
        }
    }
}
