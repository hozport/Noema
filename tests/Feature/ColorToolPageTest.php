<?php

namespace Tests\Feature;

use Tests\TestCase;

class ColorToolPageTest extends TestCase
{
    public function test_color_tool_page_is_public(): void
    {
        $this->get(route('site.color-tool'))
            ->assertOk()
            ->assertSee('color-tool-input', false)
            ->assertSee('color-tool-picker', false);
    }
}
