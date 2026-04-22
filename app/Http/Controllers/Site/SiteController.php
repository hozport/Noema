<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SiteController extends Controller
{
    public function home(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('worlds.index');
        }

        return view('site.home');
    }

    public function about(): View
    {
        return view('site.stub', ['title' => __('site.nav.about')]);
    }

    public function documentation(): View
    {
        return view('site.stub', ['title' => __('site.nav.documentation')]);
    }

    public function roadmap(): View
    {
        return view('site.stub', ['title' => __('site.nav.roadmap')]);
    }

    public function privacy(): View
    {
        return view('site.stub', ['title' => __('site.legal_links.privacy')]);
    }

    public function consent(): View
    {
        return view('site.stub', ['title' => __('site.legal_links.consent')]);
    }

    public function legal(): View
    {
        return view('site.stub', ['title' => __('site.legal_links.legal')]);
    }

    public function register(): View
    {
        return view('site.stub', ['title' => __('site.nav.register')]);
    }

    public function svgViewer(): View
    {
        return view('site.svg-viewer');
    }

    /**
     * Инструмент конвертации и подбора цветов (CSS: HEX, RGB, HSL).
     */
    public function colorTool(): View
    {
        return view('site.color-tool');
    }
}
