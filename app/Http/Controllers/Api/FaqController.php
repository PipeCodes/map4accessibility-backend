<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResourceCollection;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Faq;

class FaqController extends Controller
{
    use ApiResponseTrait;

    public function getFaqs()
    {
        try {
            $faqs = Faq::where(
                'locale',
                '=',
                app()->getLocale()
            )->get();

            return $this->respondWithResourceCollection(new FaqResourceCollection($faqs));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }
}
