<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Score extends Model
{
    /** @use HasFactory<\Database\Factories\ScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'playlist_id',
        'player_name',
        'score',
    ];

    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }
}
