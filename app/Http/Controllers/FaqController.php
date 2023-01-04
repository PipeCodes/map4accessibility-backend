<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class FaqController extends Controller
{
    public function faqs()
    {
        return view('pages.faqs.index', [
            'faqs' => Faq::faqs(app()->getLocale()),
        ]);
    }
}
