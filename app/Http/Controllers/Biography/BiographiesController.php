<?php

namespace App\Http\Controllers\Biography;

use App\Http\Controllers\Controller;
use App\Models\Worlds\World;
use App\Support\BestiaryAlphabet;
use App\Support\FactionType;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BiographiesController extends Controller
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

        $biographies = $world->biographies()
            ->with('world.user')
            ->orderBy('name')
            ->get();

        $byLetter = $biographies->groupBy(fn ($b) => BestiaryAlphabet::bucketFor($b->name, $script));

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
            $selectedBiographies = $biographies
                ->filter(fn ($b) => mb_stripos($b->name, $searchQuery, 0, 'UTF-8') !== false)
                ->sortBy(fn ($b) => mb_strtolower($b->name, 'UTF-8'))
                ->values();
        } else {
            $selectedBiographies = $byLetter->get($letter, collect())->sortBy(
                fn ($b) => mb_strtolower($b->name, 'UTF-8')
            )->values();
        }

        $totalBiographies = $biographies->count();

        $allBiographies = $biographies;

        $recentBiographies = $world->biographies()
            ->with('world.user')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $raceFactions = $world->factions()->where('type', FactionType::RACE)->orderBy('name')->get();
        $peopleFactions = $world->factions()->where('type', FactionType::PEOPLE)->orderBy('name')->get();
        $countryFactions = $world->factions()->where('type', FactionType::COUNTRY)->orderBy('name')->get();
        $membershipFactions = $world->factions()
            ->whereNotIn('type', FactionType::biographyDedicatedTypes())
            ->orderBy('name')
            ->get();

        return view('biographies.index', compact(
            'world',
            'script',
            'navLetters',
            'navColumnCount',
            'letter',
            'counts',
            'selectedBiographies',
            'totalBiographies',
            'allBiographies',
            'recentBiographies',
            'searchQuery',
            'raceFactions',
            'peopleFactions',
            'countryFactions',
            'membershipFactions'
        ));
    }

    public function pdf(World $world)
    {
        $this->authorizeWorld($world);

        $biographies = $world->biographies()->with('raceFaction')->orderBy('name')->get();

        $html = view('biographies.biographies-pdf', compact('world', 'biographies'))->render();

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
        $filename = $slug.'-biographies.pdf';

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
