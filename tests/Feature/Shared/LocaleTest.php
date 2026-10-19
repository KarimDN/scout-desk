<?php

namespace Tests\Feature\Shared;

use Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_default_locale_is_english_and_ltr(): void
    {
        $this->get('/demo/i18n')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('vendor/bootstrap/bootstrap.min.css', false)
            ->assertDontSee('bootstrap.rtl', false);
    }

    public function test_switching_language_stores_the_locale_in_the_session(): void
    {
        $this->post('/locale', ['locale' => 'ar'])
            ->assertRedirect()
            ->assertSessionHas('locale', 'ar');
    }

    public function test_arabic_session_gives_an_rtl_layout(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->get('/demo/i18n')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('vendor/bootstrap/bootstrap.rtl.min.css', false)
            ->assertSee('فحص اللغة والتخطيط');
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->post('/locale', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale')
            ->assertSessionMissing('locale');
    }

    public function test_unsupported_locale_in_session_falls_back_to_the_default(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->get('/demo/i18n')
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false);
    }

    public function test_language_switcher_lists_only_supported_languages(): void
    {
        $this->get('/demo/i18n')
            ->assertSee('English')
            ->assertSee('العربية')
            ->assertDontSee('Français');
    }

    public function test_user_text_and_email_directions_are_set(): void
    {
        $this->get('/demo/i18n')
            ->assertSee('dir="auto"', false)
            ->assertSee('dir="ltr"', false);
    }
}