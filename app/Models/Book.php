<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'isbn',
        'title',
        'author',
        'publisher',
        'price',
        'published_date',
        'cover_url',
        'description',
    ];

    protected $casts = [
        'price' => 'integer',
        'published_date' => 'date',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(BookReview::class);
    }
}