<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Listing extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'listingsAndReviews';

    protected $fillable = [
        'name',
        'summary',
        'description',
        'property_type',
        'room_type',
        'accommodates',
        'bedrooms',
        'beds',
        'price',
        'amenities',
        'address',
        'review_scores',
        'embedding',
    ];

    public function toEmbeddingText(): string
    {
        $market = $this->address['market'] ?? $this->address['country'] ?? '';

        return implode('. ', array_filter([
            $this->name,
            $this->summary,
            $this->property_type ? "Property type: {$this->property_type}" : null,
            $market ? "Location: {$market}" : null,
        ]));
    }
}
