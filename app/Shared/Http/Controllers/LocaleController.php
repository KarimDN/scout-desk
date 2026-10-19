<?php

namespace App\Shared\Http\Controllers;

use App\Shared\Http\Requests\SwitchLocaleRequest;
use Illuminate\Http\RedirectResponse;

class LocaleController
{
    public function update(SwitchLocaleRequest $request): RedirectResponse
    {
        $request->session()->put('locale', $request->validated('locale'));

        return back();
    }
}