<?php

namespace App\Http\Controllers\Faction;

use App\Http\Controllers\Controller;
use App\Models\Worlds\World;
use App\Support\BestiaryAlphabet;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FactionsController extends Controller
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

        $factions = $world->factions()
            ->orderBy('name')
            ->get();

        $byLetter = $factions->groupBy(fn ($f) => BestiaryAlphabet::bucketFor($f->name, $script));

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
            $selectedFactions = $factions
                ->filter(fn ($f) => mb_stripos($f->name, $searchQuery, 0, 'UTF-8') !== false)
                ->sortBy(fn ($f) => mb_strtolower($f->name, 'UTF-8'))
                ->values();
        } else {
            $selectedFactions = $byLetter->get($letter, collect())->sortBy(
                fn ($f) => mb_strtolower($f->name, 'UTF-8')
            )->values();
        }

        $totalFactions = $factions->count();
        $allFactions = $factions;

        $allBiographies = $world->biographies()->orderBy('name')->get();

        $recentFactions = $world->factions()
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return view('factions.index', compact(
            'world',
            'script',
            'navLetters',
            'navColumnCount',
            'letter',
            'counts',
            'selectedFactions',
            'totalFactions',
            'allFactions',
            'allBiographies',
            'recentFactions',
            'searchQuery'
        ));
    }

    public function pdf(World $world)
    {
        $this->authorizeWorld($world);

        $factions = $world->factions()->orderBy('name')->get();

        $html = view('factions.factions-index-pdf', compact('world', 'factions'))->render();

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
        $filename = $slug.'-factions.pdf';

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
