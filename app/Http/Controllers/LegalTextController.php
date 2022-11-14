<?php

namespace App\Http\Controllers;

use App\Models\LegalText;

class LegalTextController extends Controller
{
    public function terms()
    {
        return view('pages.legal-texts.terms', [
            'terms' => LegalText::terms(app()->getLocale())
        ]);
    }

    public function privacy()
    {
        return view('pages.legal-texts.privacy', [
            'privacy' => LegalText::privacy(app()->getLocale())
        ]);
    }
}
