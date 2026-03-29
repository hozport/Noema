<?php

namespace Tests\Unit;

use App\Support\BiographyKinship;
use PHPUnit\Framework\TestCase;

class BiographyKinshipTest extends TestCase
{
    public function test_display_label_for_custom_uses_custom_text(): void
    {
        $this->assertSame('Кума', BiographyKinship::displayLabel(BiographyKinship::CUSTOM, 'Кума'));
    }

    public function test_display_label_for_preset(): void
    {
        $this->assertSame('Мать', BiographyKinship::displayLabel(BiographyKinship::MOTHER, null));
    }

    public function test_display_label_for_brother_extended_family(): void
    {
        $this->assertSame('Брат', BiographyKinship::displayLabel(BiographyKinship::BROTHER, null));
        $this->assertSame('Тётя', BiographyKinship::displayLabel(BiographyKinship::AUNT, null));
    }
}
