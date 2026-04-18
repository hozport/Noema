<?php

namespace Tests\Unit;

use App\Models\Worlds\WorldMapSprite;
use PHPUnit\Framework\TestCase;

class WorldMapSpriteMarkupLinkTest extends TestCase
{
    public function test_title_qualifies_requires_non_empty_trimmed_title(): void
    {
        $this->assertFalse(WorldMapSprite::titleQualifiesForMarkupEntityLink(null));
        $this->assertFalse(WorldMapSprite::titleQualifiesForMarkupEntityLink(''));
        $this->assertFalse(WorldMapSprite::titleQualifiesForMarkupEntityLink('   '));
    }

    public function test_title_qualifies_rejects_placeholder_pattern(): void
    {
        $this->assertFalse(WorldMapSprite::titleQualifiesForMarkupEntityLink('объект на карте #1'));
        $this->assertFalse(WorldMapSprite::titleQualifiesForMarkupEntityLink('Объект на карте  #42'));
    }

    public function test_title_qualifies_accepts_real_names(): void
    {
        $this->assertTrue(WorldMapSprite::titleQualifiesForMarkupEntityLink('Столица'));
        $this->assertTrue(WorldMapSprite::titleQualifiesForMarkupEntityLink('Объект на карте без номера'));
    }
}
