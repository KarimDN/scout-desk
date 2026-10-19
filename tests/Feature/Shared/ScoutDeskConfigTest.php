<?php

namespace Tests\Feature\Shared;

use Tests\TestCase;

class ScoutDeskConfigTest extends TestCase
{
    public function test_only_english_and_arabic_are_supported(): void
    {
        $locales = config('scoutdesk.locales');

        $this->assertSame(['en', 'ar'], $locales);
        $this->assertNotContains('fr', $locales);
    }

    public function test_arabic_is_the_only_rtl_locale_and_is_supported(): void
    {
        $rtl = config('scoutdesk.rtl_locales');

        $this->assertSame(['ar'], $rtl);
        $this->assertSame([], array_diff($rtl, config('scoutdesk.locales')));
    }

    public function test_link_lifetime_and_password_defaults_are_set(): void
    {
        $this->assertSame(72, config('scoutdesk.invitation_lifetime_hours'));
        $this->assertSame(10, config('scoutdesk.password_min_length'));
    }

    public function test_display_timezone_is_a_valid_timezone(): void
    {
        $this->assertContains(
            config('scoutdesk.display_timezone'),
            \DateTimeZone::listIdentifiers()
        );
    }
}