<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Services\EmbeddingService;
use App\Models\Listing;

#[Signature('app:populate-embeddings')]
#[Description('Command description')]
class PopulateEmbeddings extends Command
{
    /**
     * Execute the console command.
     */

    public function handle()
    {
        $service = new EmbeddingService();

        $listings = Listing::whereNull('embedding')->limit(50)->get();

        if ($listings->isEmpty()) {
            $this->info("All listings already have embeddings!");
            return;
        }

        // 2. Prepare the text for all listings in this batch
        $texts = $listings->map(fn($l) => $l->toEmbeddingText())->toArray();

        try {

            $this->info("Sending batch of " . count($texts) . " to Voyage AI...");

            // 3. Get all embeddings in ONE single request
            $vectors = $service->embedMany($texts);

            foreach ($listings as $index => $listing) {
                $listing->update(['embedding' => $vectors[$index]]);
            }

            $this->info("Successfully updated " . $listings->count() . " listings!");

        } catch (\Exception $e) {

            $this->error("Error: " . $e->getMessage());

        }

    }
}
