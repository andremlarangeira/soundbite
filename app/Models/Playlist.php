<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Playlist extends Model
{
    protected $fillable = [
        'youtube_id',
        'name',
        'url',
        'thumbnail',
        'played_count',
        'tracks',
    ];

    protected function casts(): array
    {
        return [
            'tracks' => 'array',
        ];
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }
}
