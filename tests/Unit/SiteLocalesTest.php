<?php

namespace Tests\Unit;

use App\Support\SiteLocales;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteLocalesTest extends TestCase
{
    #[Test]
    public function negotiate_prefers_first_supported_language_from_accept_header(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
        ]);

        $this->assertSame('fr', SiteLocales::negotiateFromRequest($request));
    }

    #[Test]
    public function negotiate_skips_unsupported_and_uses_next(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'de,es;q=0.9',
        ]);

        $this->assertSame('es', SiteLocales::negotiateFromRequest($request));
    }

    #[Test]
    public function negotiate_falls_back_to_russian_when_nothing_matches(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'de,zh-CN;q=0.9',
        ]);

        $this->assertSame(SiteLocales::DEFAULT, SiteLocales::negotiateFromRequest($request));
    }

    #[DataProvider('supportedLocalesProvider')]
    #[Test]
    public function negotiate_returns_supported_primary(string $header, string $expected): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => $header,
        ]);

        $this->assertSame($expected, SiteLocales::negotiateFromRequest($request));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function supportedLocalesProvider(): array
    {
        return [
            'english' => ['en-GB', 'en'],
            'spanish' => ['es-MX', 'es'],
            'russian' => ['ru-RU', 'ru'],
        ];
    }

    #[Test]
    public function site_dictionary_keys_exist_for_header_footer_in_all_locales(): void
    {
        $keys = [
            'site.header.aria_nav',
            'site.header.locale_label',
            'site.header.logout',
            'site.nav.about',
            'site.nav.documentation',
            'site.nav.roadmap',
            'site.nav.register',
            'site.footer.aria_nav',
            'site.legal_links.privacy',
            'site.legal_links.consent',
            'site.legal_links.legal',
            'site.pages.stub_message',
            'site.locale_names.ru',
            'site.locale_names.en',
            'site.locale_names.fr',
            'site.locale_names.es',
        ];

        foreach (SiteLocales::SUPPORTED as $locale) {
            $this->app->setLocale($locale);
            foreach ($keys as $key) {
                $value = __($key);
                $this->assertNotSame($key, $value, "Missing translation for [{$key}] in locale [{$locale}]");
            }
        }
    }
}
