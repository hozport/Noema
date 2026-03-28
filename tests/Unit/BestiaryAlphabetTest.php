<?php

namespace Tests\Unit;

use App\Support\BestiaryAlphabet;
use PHPUnit\Framework\TestCase;

class BestiaryAlphabetTest extends TestCase
{
    public function test_cyrillic_name_is_not_bucketed_under_latin_letter(): void
    {
        $lat = BestiaryAlphabet::SCRIPT_LAT;
        $this->assertSame('A', BestiaryAlphabet::bucketFor('Anubis', $lat));
        $this->assertSame(BestiaryAlphabet::OTHER_BUCKET, BestiaryAlphabet::bucketFor('Анубис', $lat));
    }

    public function test_latin_name_is_not_bucketed_under_cyrillic_letter(): void
    {
        $cyr = BestiaryAlphabet::SCRIPT_CYR;
        $this->assertSame('А', BestiaryAlphabet::bucketFor('Анубис', $cyr));
        $this->assertSame(BestiaryAlphabet::OTHER_BUCKET, BestiaryAlphabet::bucketFor('Anubis', $cyr));
    }

    public function test_digits_bucket(): void
    {
        $this->assertSame('0-9', BestiaryAlphabet::bucketFor('7 гномов', BestiaryAlphabet::SCRIPT_CYR));
        $this->assertSame('0-9', BestiaryAlphabet::bucketFor('7 gnomes', BestiaryAlphabet::SCRIPT_LAT));
    }
}
