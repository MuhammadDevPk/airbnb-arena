<?php

namespace App\Tools;

use App\Models\Listing;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * ListingDetailsTool — Fetches full listing details by ID.
 *
 * This tool allows the agent to get complete information about a specific
 * listing after finding it via search.
 */
class ListingDetailsTool implements Tool
{
    /**
     * Describe what this tool does.
     */
    public function description(): Stringable|string
    {
        return 'Fetch full details for a specific Airbnb listing by its unique ID.';
    }

    /**
     * Define the parameters the agent can pass to this tool.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()
                ->description('The unique MongoDB ID of the listing (e.g. "10006546")')
                ->required(),
        ];
    }

    /**
     * Execute the tool logic.
     */
    public function handle(Request $request): Stringable|string
    {
        $id = $request->string('id');
        Log::info('[4] ListingDetailsTool: LLM requested details for listing.', ['id' => $id]);

        // Fetch listing from MongoDB
        $listing = Listing::find($id);

        if (!$listing) {
            Log::info('ListingDetailsTool: Listing not found.');
            return "Listing with ID {$id} not found.";
        }

        Log::info('ListingDetailsTool: Returning listing details.');

        // Return a subset of useful information for the LLM
        return json_encode([
            'name' => $listing->name,
            'summary' => $listing->summary,
            'space' => $listing->space,
            'description' => $listing->description,
            'neighborhood_overview' => $listing->neighborhood_overview,
            'notes' => $listing->notes,
            'transit' => $listing->transit,
            'access' => $listing->access,
            'interaction' => $listing->interaction,
            'house_rules' => $listing->house_rules,
            'property_type' => $listing->property_type,
            'room_type' => $listing->room_type,
            'bed_type' => $listing->bed_type,
            'minimum_nights' => $listing->minimum_nights,
            'maximum_nights' => $listing->maximum_nights,
            'accommodates' => $listing->accommodates,
            'bedrooms' => $listing->bedrooms,
            'beds' => $listing->beds,
            'bathrooms' => (string) ($listing->bathrooms ?? ''),
            'amenities' => $listing->amenities,
            'price' => (string) $listing->price,
            'security_deposit' => (string) ($listing->security_deposit ?? ''),
            'cleaning_fee' => (string) ($listing->cleaning_fee ?? ''),
            'extra_people' => (string) ($listing->extra_people ?? ''),
            'guests_included' => (string) ($listing->guests_included ?? ''),
            'address' => $listing->address,
            'review_scores' => $listing->review_scores,
        ], JSON_PRETTY_PRINT);
    }
}
