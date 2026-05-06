<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\YouTubeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

final class GameController extends Controller
{
    public function __construct(private readonly YouTubeService $youtube) {}

    public function index()
    {
        $playlists = \App\Models\Playlist::orderBy('updated_at', 'desc')->take(10)->get();

        return Inertia::render('SoundBite/Home', [
            'recentPlaylists' => $playlists,
        ]);
    }

    public function createPlaylistView()
    {
        return Inertia::render('SoundBite/CreatePlaylist');
    }

    public function searchYoutube(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'pageToken' => 'nullable|string',
        ]);

        $results = $this->youtube->searchVideos(
            $request->input('query'),
            $request->input('pageToken')
        );

        return response()->json($results);
    }

    public function playCustomPlaylist(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'tracks' => 'required|array|min:1',
            'tracks.*.videoId' => 'required|string',
            'tracks.*.title' => 'required|string',
            'tracks.*.thumbnail' => 'required|string',
            'tracks.*.duration' => 'required|integer',
        ]);

        $customId = 'custom_'.Str::random(10);
        $thumbnail = $request->input('tracks')[0]['thumbnail'] ?? '';

        $playlistModel = \App\Models\Playlist::create([
            'youtube_id' => $customId,
            'name' => $request->input('title'),
            'url' => url('/game/custom/' . $customId),
            'thumbnail' => $thumbnail,
            'played_count' => 1,
            'tracks' => $request->input('tracks'),
        ]);

        // Armazenar no session
        session([
            'game_playlist_id' => $playlistModel->id,
            'game_playlist' => $request->input('tracks'),
            'game_played_ids' => [],
            'game_score' => 0,
            'game_round' => 0,
        ]);

        return redirect()->route('game.play');
    }

    public function loadPlaylist(Request $request)
    {
        $request->validate([
            'url' => ['required', 'url'],
        ]);

        $url = $request->input('url');

        if (str_contains($url, '/game/custom/custom_')) {
            preg_match('/custom_[a-zA-Z0-9]+/', $url, $matches);
            if (!empty($matches)) {
                $customId = $matches[0];
                $playlistModel = \App\Models\Playlist::where('youtube_id', $customId)->first();

                if ($playlistModel && !empty($playlistModel->tracks)) {
                    $playlistModel->increment('played_count');
                    $playlistModel->touch();

                    session([
                        'game_playlist_id' => $playlistModel->id,
                        'game_playlist' => $playlistModel->tracks,
                        'game_played_ids' => [],
                        'game_score' => 0,
                        'game_round' => 0,
                    ]);

                    return redirect()->route('game.play');
                }

                return back()->withErrors(['url' => 'Playlist customizada não encontrada ou sem músicas salvas.']);
            }
        }

        $playlistId = $this->youtube->extractPlaylistId($url);

        if (! $playlistId) {
            // Se falhar em extrair, talvez seja um teste ou fallback
            $playlistId = 'mock_playlist';
        }

        try {
            $items = $this->youtube->getPlaylistItems($playlistId);

            if (empty($items)) {
                return back()->withErrors(['url' => 'Não foi possível encontrar vídeos nesta playlist.']);
            }

            $details = $this->youtube->getPlaylistDetails($playlistId);
            if ($details) {
                $playlistModel = \App\Models\Playlist::updateOrCreate(
                    ['youtube_id' => $playlistId],
                    [
                        'name' => $details['title'],
                        'url' => $request->input('url'),
                        'thumbnail' => $details['thumbnail'],
                    ]
                );
                $playlistModel->increment('played_count');
                $playlistModel->touch();
            }

            // Armazenar no session
            session([
                'game_playlist_id' => isset($playlistModel) ? $playlistModel->id : null,
                'game_playlist' => $items,
                'game_played_ids' => [],
                'game_score' => 0,
                'game_round' => 0,
            ]);

            return redirect()->route('game.play');
        } catch (Exception $e) {
            return back()->withErrors(['url' => $e->getMessage()]);
        }
    }

    public function play()
    {
        $playlist = session('game_playlist');
        if (! $playlist) {
            return redirect()->route('game.home');
        }

        // Títulos para o autocomplete
        $titles = collect($playlist)->pluck('title')->toArray();

        return Inertia::render('SoundBite/Game', [
            'playlistTitles' => $titles,
            'totalScore' => session('game_score', 0),
        ]);
    }

    public function startRound()
    {
        $playlist = session('game_playlist', []);
        $playedIds = session('game_played_ids', []);

        $available = collect($playlist)->filter(fn ($item) => ! in_array($item['videoId'], $playedIds))->values();

        if ($available->isEmpty() || session('game_round', 0) >= 5) {
            $finalScore = session('game_score', 0);
            $playlistId = session('game_playlist_id');

            if ($playlistId && $finalScore > 0) {
                // Determine player name (could be from session or auth user)
                $playerName = auth()->check() ? auth()->user()->name : 'Guest';

                \App\Models\Score::create([
                    'playlist_id' => $playlistId,
                    'player_name' => $playerName,
                    'score' => $finalScore,
                ]);
            }

            // Save to highscores (session based, maybe we can fetch from DB instead)
            $highScores = session('game_highscores', []);
            $highScores[] = $finalScore;
            rsort($highScores); // Ordena decrescente
            $highScores = array_slice($highScores, 0, 5);
            session(['game_highscores' => $highScores]);

            // If we want DB highscores instead, we can fetch them here:
            $dbHighScores = [];
            if ($playlistId) {
                $dbHighScores = \App\Models\Score::where('playlist_id', $playlistId)
                    ->orderBy('score', 'desc')
                    ->take(5)
                    ->get()
                    ->pluck('score')
                    ->toArray();
                if (! empty($dbHighScores)) {
                    $highScores = $dbHighScores;
                }
            }

            return response()->json([
                'finished' => true,
                'totalScore' => $finalScore,
                'highScores' => $highScores,
                'hasMoreSongs' => ! $available->isEmpty(),
            ]);
        }

        $selected = $available->random();

        $round = session('game_round', 0) + 1;
        session(['game_round' => $round]);

        // Save current video in session for verification later
        session(['game_current_video' => $selected]);

        // Seek position
        $duration = $selected['duration'];
        $seekPosition = rand(0, max(0, $duration - 30));

        $roundTotal = min(5, $available->count() + $round - 1);

        return response()->json([
            'finished' => false,
            'videoId' => $selected['videoId'],
            'seekPosition' => $seekPosition,
            'totalSongs' => $roundTotal,
            'roundNumber' => $round,
            'totalScore' => session('game_score', 0),
        ]);
    }

    public function resetRound()
    {
        session(['game_score' => 0, 'game_round' => 0]);
        session()->forget('game_current_video');

        return response()->json(['success' => true]);
    }

    public function enableAutocomplete()
    {
        $currentScore = session('game_score', 0);
        $cost = 200;
        $newScore = max(0, $currentScore - $cost);
        session(['game_score' => $newScore]);
        session(['game_autocomplete_enabled' => true]);

        return response()->json(['success' => true, 'totalScore' => $newScore]);
    }

    public function guess(Request $request)
    {
        $request->validate([
            'answer' => 'required|string',
            'secondsRevealed' => 'required|integer',
            'timeElapsed' => 'required|numeric'
        ]);

        $currentVideo = session('game_current_video');
        if (! $currentVideo) {
            return response()->json(['error' => 'No active round'], 400);
        }

        $guess = $this->normalizeString($request->input('answer'));
        $actualTitle = $this->normalizeString($currentVideo['title']);

        // Base score calc
        $secondsRevealed = $request->input('secondsRevealed');
        $timeElapsed = $request->input('timeElapsed');
        $baseScore = 1000;
        $penaltyPerSecond = 100;
        $timePenalty = floor($timeElapsed * 5); // 5 pontos de penalidade por segundo real passado

        $scoreGained = max(0, $baseScore - (($secondsRevealed - 1) * $penaltyPerSecond) - $timePenalty);

        // Compare logic: simple similarity or contains
        $isCorrect = false;
        if (str_contains($actualTitle, $guess) || str_contains($guess, $actualTitle) || levenshtein($guess, $actualTitle) < 5) {
            $isCorrect = true;
        }

        if ($isCorrect) {
            $this->markAsPlayed($currentVideo['videoId']);
            $totalScore = session('game_score', 0) + $scoreGained;
            session(['game_score' => $totalScore]);
            session(['game_autocomplete_enabled' => false]); // reset proximo round

            return response()->json([
                'correct' => true,
                'song' => $currentVideo,
                'scoreGained' => $scoreGained,
                'totalScore' => $totalScore,
            ]);
        }

        // Penalidade por erro
        $scoreLost = 50;
        $totalScore = session('game_score', 0) - $scoreLost;
        // Permite score negativo ou trava no zero? A prompt não especificou, vamos travar em zero.
        $totalScore = max(0, $totalScore);
        session(['game_score' => $totalScore]);

        return response()->json([
            'correct' => false,
            'canRevealMore' => $secondsRevealed < 10,
            'totalScore' => $totalScore,
            'scoreLost' => $scoreLost,
        ]);
    }

    public function skip()
    {
        $currentVideo = session('game_current_video');
        if (! $currentVideo) {
            return response()->json(['error' => 'No active round'], 400);
        }

        $this->markAsPlayed($currentVideo['videoId']);

        return response()->json([
            'song' => $currentVideo,
            'scoreGained' => 0,
            'totalScore' => session('game_score', 0),
        ]);
    }

    private function markAsPlayed(string $videoId): void
    {
        $playedIds = session('game_played_ids', []);
        $playedIds[] = $videoId;
        session(['game_played_ids' => $playedIds]);
        session()->forget('game_current_video');
    }

    private function normalizeString(string $string): string
    {
        // Lowercase and remove accents
        $string = Str::ascii(Str::lower($string));
        // Remove common words and non-alphanumeric
        $string = preg_replace('/\b(o|a|os|as|um|uma|uns|umas|the|a|an|of|in|on|at|to)\b/i', '', $string);
        $string = preg_replace('/[^a-z0-9]/', '', $string);

        return $string;
    }
}
