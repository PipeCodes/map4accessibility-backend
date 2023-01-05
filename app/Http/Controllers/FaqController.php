<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class FaqController extends Controller
{
    public function faqs()
    {
        $faqs = Faq::faqs(app()->getLocale());

        return view('pages.faqs.index', ['faqs' => $faqs]);
    }
}
