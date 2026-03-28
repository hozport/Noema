<?php

namespace App\Http\Middleware;

use App\Support\SiteLocales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('lang')) {
            $lang = $request->query('lang');
            if (SiteLocales::isSupported($lang)) {
                $request->session()->put('locale', $lang);
            }

            return redirect()->to($request->url());
        }

        if (! $request->session()->has('locale')) {
            $request->session()->put('locale', SiteLocales::negotiateFromRequest($request));
        }

        if (! SiteLocales::isSupported($request->session()->get('locale'))) {
            $request->session()->put('locale', SiteLocales::negotiateFromRequest($request));
        }

        $locale = $request->session()->get('locale');
        if (SiteLocales::isSupported($locale)) {
            app()->setLocale($locale);
        } else {
            app()->setLocale(SiteLocales::DEFAULT);
        }

        return $next($request);
    }
}
