<?php

namespace Tests\Feature\Shared;

use App\Shared\Support\LocalizedDate;
use Tests\TestCase;

class LocalizedDateTest extends TestCase
{
    public function test_english_date_format(): void
    {
        app()->setLocale('en');

        $this->assertSame('15 January 2026', LocalizedDate::date('2026-01-15'));
    }

    public function test_arabic_date_uses_arabic_month_names_and_western_digits(): void
    {
        app()->setLocale('ar');

        $this->assertMatchesRegularExpression(
            '/^15 \p{Arabic}+(?: \p{Arabic}+)* 2026$/u',
            LocalizedDate::date('2026-01-15')
        );
    }

    public function test_timestamps_are_shown_in_the_display_timezone(): void
    {
        app()->setLocale('en');

        // 23:30 UTC on 15 January is 01:30 on 16 January in Beirut (UTC+2 in winter).
        $this->assertSame('16 January 2026, 01:30', LocalizedDate::dateTime('2026-01-15 23:30:00'));
    }

    public function test_date_only_values_are_not_shifted(): void
    {
        app()->setLocale('en');

        $this->assertSame('15 January 2026', LocalizedDate::date('2026-01-15 23:30:00'));
    }

    public function test_empty_values_give_an_empty_string(): void
    {
        $this->assertSame('', LocalizedDate::date(null));
        $this->assertSame('', LocalizedDate::dateTime(null));
    }

    public function test_locale_settings_are_consistent(): void
    {
        $locales = config('scoutdesk.locales');

        $this->assertContains(config('scoutdesk.default_locale'), $locales);
        $this->assertSame($locales, array_keys(config('scoutdesk.carbon_locales')));
    }
}