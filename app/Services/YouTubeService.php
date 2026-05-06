<?php

declare(strict_types=1);

namespace App\Services;

use DateInterval;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class YouTubeService
{
    public function getPlaylistItems(string $playlistId): array
    {
        $cacheKey = "youtube_playlist_{$playlistId}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($playlistId) {
            $apiKey = config('services.youtube.api_key');

            if ($apiKey) {
                // Fetch basic items
                $response = Http::get('https://www.googleapis.com/youtube/v3/playlistItems', [
                    'part' => 'snippet,contentDetails',
                    'playlistId' => $playlistId,
                    'maxResults' => 50,
                    'key' => $apiKey,
                ]);

                if (! $response->successful()) {
                    throw new Exception('Erro ao buscar playlist no YouTube.');
                }

                $items = $response->json('items', []);
                $videoIds = collect($items)->map(fn ($item) => $item['contentDetails']['videoId'])->filter()->toArray();

                if (empty($videoIds)) {
                    return [];
                }

                // Fetch durations
                $videosResponse = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'contentDetails',
                    'id' => implode(',', $videoIds),
                    'key' => $apiKey,
                ]);

                $durations = [];
                if ($videosResponse->successful()) {
                    foreach ($videosResponse->json('items', []) as $video) {
                        $durations[$video['id']] = $this->parseYouTubeDuration($video['contentDetails']['duration'] ?? 'PT0S');
                    }
                }

                $result = [];
                foreach ($items as $item) {
                    $videoId = $item['contentDetails']['videoId'] ?? null;
                    if (! $videoId) {
                        continue;
                    }

                    $title = $item['snippet']['title'] ?? 'Unknown';
                    // Ignorar vídeos deletados ou privados
                    if ($title === 'Private video' || $title === 'Deleted video') {
                        continue;
                    }

                    $result[] = [
                        'videoId' => $videoId,
                        'title' => $title,
                        'thumbnail' => $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['default']['url'] ?? '',
                        'duration' => $durations[$videoId] ?? 200,
                    ];
                }

                return $result;
            }

            // Mock Data fallback
            // return [
            //     [
            //         'videoId' => 'dQw4w9WgXcQ',
            //         'title' => 'Rick Astley - Never Gonna Give You Up',
            //         'thumbnail' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            //         'duration' => 212,
            //     ],
            //     [
            //         'videoId' => 'fJ9rUzIMcZQ',
            //         'title' => 'Queen - Bohemian Rhapsody',
            //         'thumbnail' => 'https://i.ytimg.com/vi/fJ9rUzIMcZQ/hqdefault.jpg',
            //         'duration' => 359,
            //     ],
            //     [
            //         'videoId' => 'L_jWHffIx5E',
            //         'title' => 'Smash Mouth - All Star',
            //         'thumbnail' => 'https://i.ytimg.com/vi/L_jWHffIx5E/hqdefault.jpg',
            //         'duration' => 200,
            //     ],
            //     [
            //         'videoId' => 'hT_nvWreIhg',
            //         'title' => 'OneRepublic - Counting Stars',
            //         'thumbnail' => 'https://i.ytimg.com/vi/hT_nvWreIhg/hqdefault.jpg',
            //         'duration' => 283,
            //     ],
            //     [
            //         'videoId' => '09R8_2nJtjg',
            //         'title' => 'Maroon 5 - Sugar',
            //         'thumbnail' => 'https://i.ytimg.com/vi/09R8_2nJtjg/hqdefault.jpg',
            //         'duration' => 301,
            //     ]
            // ];
        });
    }

    public function searchVideos(string $query, ?string $pageToken = null): array
    {
        $apiKey = config('services.youtube.api_key');
        if (! $apiKey) {
            return ['items' => [], 'nextPageToken' => null];
        }

        // Add negative filters to the query
        $negativeFilters = ' - Topic';
        // $negativeFilters = ' -live -karaoke -cover -acustico -acústico -tributo -"ao vivo"';
        $fullQuery = $query . $negativeFilters;

        $params = [
            'part' => 'snippet',
            'q' => $fullQuery,
            'type' => 'video',
            'maxResults' => 10,
            'key' => $apiKey,
            'videoCategoryId' => '10', // Music category
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $response = Http::get('https://www.googleapis.com/youtube/v3/search', $params);

        if (! $response->successful()) {
            return ['items' => [], 'nextPageToken' => null];
        }

        $items = $response->json('items', []);
        $nextPageToken = $response->json('nextPageToken');
        $videoIds = collect($items)->map(fn ($item) => $item['id']['videoId'] ?? null)->filter()->toArray();

        if (empty($videoIds)) {
            return ['items' => [], 'nextPageToken' => null];
        }

        // Fetch durations
        $videosResponse = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'contentDetails',
            'id' => implode(',', $videoIds),
            'key' => $apiKey,
        ]);

        $durations = [];
        if ($videosResponse->successful()) {
            foreach ($videosResponse->json('items', []) as $video) {
                $durations[$video['id']] = $this->parseYouTubeDuration($video['contentDetails']['duration'] ?? 'PT0S');
            }
        }

        $result = [];
        foreach ($items as $item) {
            $videoId = $item['id']['videoId'] ?? null;
            if (! $videoId) {
                continue;
            }

            $title = $item['snippet']['title'] ?? 'Unknown';

            $result[] = [
                'videoId' => $videoId,
                'title' => html_entity_decode($title, ENT_QUOTES),
                'thumbnail' => $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['default']['url'] ?? '',
                'duration' => $durations[$videoId] ?? 200,
            ];
        }

        return [
            'items' => $result,
            'nextPageToken' => $nextPageToken,
        ];
    }

    public function extractPlaylistId(string $url): ?string
    {
        if (preg_match('/[?&]list=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getPlaylistDetails(string $playlistId): ?array
    {
        $cacheKey = "youtube_playlist_details_{$playlistId}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($playlistId) {
            $apiKey = config('services.youtube.api_key');

            if (! $apiKey) {
                return null;
            }

            $response = Http::get('https://www.googleapis.com/youtube/v3/playlists', [
                'part' => 'snippet',
                'id' => $playlistId,
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $items = $response->json('items', []);
            if (empty($items)) {
                return null;
            }

            $snippet = $items[0]['snippet'];

            return [
                'id' => $playlistId,
                'title' => $snippet['title'] ?? 'Playlist',
                'thumbnail' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'] ?? null,
            ];
        });
    }

    private function parseYouTubeDuration(string $duration): int
    {
        try {
            $interval = new DateInterval($duration);

            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (Exception $e) {
            return 200;
        }
    }
}
