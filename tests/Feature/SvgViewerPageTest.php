<?php

namespace Tests\Feature;

use Tests\TestCase;

class SvgViewerPageTest extends TestCase
{
    public function test_svg_viewer_page_is_public(): void
    {
        $this->get(route('site.svg-viewer'))
            ->assertOk()
            ->assertSee('svg-viewer-input', false)
            ->assertSee('svg-viewer-preview', false);
    }
}
