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

        Listing::whereNull('embedding')->limit(20)->get()->each(function ($listing) use ($service) {
            $this->info("Embedding: " . $listing->name);
            $text = $listing->toEmbeddingText();
            $vector = $service->embedQuery($text); // Calls Voayage AI
            $listing->update(['Embedding' => $vector]);
        });

    }
}
