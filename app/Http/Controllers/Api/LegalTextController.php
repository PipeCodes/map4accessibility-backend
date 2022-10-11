<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LegalTextResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\LegalText;

class LegalTextController extends Controller
{
    use ApiResponseTrait;

    public function getLegalText($type)
    {
        try {
            $legalTextLocalized = LegalText::where(
                'type',
                '=',
                $type
            )->where(
                'locale',
                '=',
                app()->getLocale()
            )
                ->first();

            return $this->respondWithResource(new LegalTextResource($legalTextLocalized));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }
}
