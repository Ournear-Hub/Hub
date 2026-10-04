<?php

namespace Tests\Unit;

use App\Enums\PreferredLanguage;
use App\Enums\RegistrationSource;
use App\Support\RecoveredEnumValues;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecoveredEnumValuesTest extends TestCase
{
    #[Test]
    public function it_maps_legacy_preferred_language_labels(): void
    {
        $this->assertSame('en', RecoveredEnumValues::normalizeValue(PreferredLanguage::class, 'English'));
        $this->assertSame('te', RecoveredEnumValues::normalizeValue(PreferredLanguage::class, 'telugu'));
    }

    #[Test]
    public function it_maps_legacy_registration_source_recovery(): void
    {
        $this->assertSame(
            RegistrationSource::Admin->value,
            RecoveredEnumValues::normalizeValue(RegistrationSource::class, 'legacy_recovery'),
        );
    }

    #[Test]
    public function it_casts_legacy_values_to_backed_enums(): void
    {
        $enum = RecoveredEnumValues::cast(PreferredLanguage::class, 'English');

        $this->assertSame(PreferredLanguage::English, $enum);
    }
}
