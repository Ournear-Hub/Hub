<?php

namespace Tests\Unit;

use App\Support\ReadablePdfFixture;
use PHPUnit\Framework\TestCase;

class ReadablePdfFixtureTest extends TestCase
{
    public function test_minimal_one_page_pdf_is_structurally_valid(): void
    {
        $analysis = ReadablePdfFixture::analyze(ReadablePdfFixture::minimalOnePage());

        $this->assertTrue($analysis['starts_with_pdf']);
        $this->assertGreaterThanOrEqual(1, $analysis['page_type_markers']);
        $this->assertTrue($analysis['contains_eof']);
        $this->assertTrue($analysis['structurally_valid']);
    }
}
