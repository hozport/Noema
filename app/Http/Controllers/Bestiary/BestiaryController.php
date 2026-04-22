<?php

namespace App\Http\Controllers\Bestiary;

use App\Http\Controllers\Controller;
use App\Models\Worlds\World;
use App\Support\BestiaryAlphabet;
use App\Support\DatabaseDriver;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BestiaryController extends Controller
{
    public function index(Request $request, World $world)
    {
        $this->authorizeWorld($world);

        $script = $request->query('script', BestiaryAlphabet::SCRIPT_CYR);
        if (! in_array($script, [BestiaryAlphabet::SCRIPT_LAT, BestiaryAlphabet::SCRIPT_CYR], true)) {
            $script = BestiaryAlphabet::SCRIPT_CYR;
        }

        $navLetters = BestiaryAlphabet::navLetters($script);
        $navColumnCount = (int) ceil(count($navLetters) / 2);

        $creatures = $world->creatures()
            ->with('world.user')
            ->orderBy('name')
            ->get();

        $byLetter = $creatures->groupBy(fn ($c) => BestiaryAlphabet::bucketFor($c->name, $script));

        $counts = [];
        foreach ($navLetters as $letter) {
            $counts[$letter] = $byLetter->get($letter, collect())->count();
        }

        $requestedLetter = $request->query('letter');
        if ($requestedLetter !== null && in_array($requestedLetter, $navLetters, true)) {
            $letter = $requestedLetter;
        } else {
            $letter = collect($navLetters)->first(fn ($L) => ($counts[$L] ?? 0) > 0)
                ?? BestiaryAlphabet::defaultLetter($script);
        }

        $q = trim((string) $request->query('q', ''));
        $searchQuery = $q === '' ? null : $q;

        if ($searchQuery !== null) {
            if (DatabaseDriver::defaultIsPostgres()) {
                $selectedCreatures = $world->creatures()
                    ->with('world.user')
                    ->where('name', 'ilike', DatabaseDriver::likeContainsPattern($searchQuery))
                    ->orderBy('name')
                    ->get();
            } else {
                $selectedCreatures = $creatures
                    ->filter(fn ($c) => mb_stripos($c->name, $searchQuery, 0, 'UTF-8') !== false)
                    ->sortBy(fn ($c) => mb_strtolower($c->name, 'UTF-8'))
                    ->values();
            }
        } else {
            $selectedCreatures = $byLetter->get($letter, collect())->sortBy(
                fn ($c) => mb_strtolower($c->name, 'UTF-8')
            )->values();
        }

        $totalCreatures = $creatures->count();
        $letterCount = $counts[$letter] ?? 0;

        $speciesSuggestions = $world->creatures()
            ->whereNotNull('species_kind')
            ->where('species_kind', '!=', '')
            ->distinct()
            ->orderBy('species_kind')
            ->pluck('species_kind')
            ->values();

        $allCreatures = $creatures;

        $recentCreatures = $world->creatures()
            ->with('world.user')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('bestiary.index', compact(
            'world',
            'script',
            'navLetters',
            'navColumnCount',
            'letter',
            'counts',
            'selectedCreatures',
            'totalCreatures',
            'letterCount',
            'speciesSuggestions',
            'allCreatures',
            'recentCreatures',
            'searchQuery'
        ));
    }

    public function pdf(World $world)
    {
        $this->authorizeWorld($world);

        $creatures = $world->creatures()->orderBy('name')->get();

        $html = view('bestiary.bestiary-pdf', compact('world', 'creatures'))->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $slug = Str::slug(Str::ascii($world->name));
        if ($slug === '') {
            $slug = 'world-'.$world->id;
        }
        $filename = $slug.'-bestiary.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function authorizeWorld(World $world): void
    {
        if ($world->user_id !== auth()->id()) {
            abort(403);
        }
        if (! $world->onoff) {
            abort(404);
        }
    }
}
