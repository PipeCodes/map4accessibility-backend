<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LegalTextResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\LegalText;

/**
 * Class LegalTextController
 * @package  App\Http\Controllers\Api
 */
class LegalTextController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *  path="/legal-text/{type}",
     *  operationId="getLegalText",
     *  tags={"Legal Texts"},
     *  summary="Get a legal text",
     *  description="Returns a legal text",
     *  @OA\Parameter(
     *      name="Accept-Language",
     *      in="header",
     *      required=true,
     *      description="Set language parameter",
     *      @OA\Schema(
     *         type="string",
     *         example="en",
     *      )
     *  ),
     *  @OA\Parameter(ref="#/components/parameters/LegalText--type"),
     *  @OA\Response(response=200, description="Successful operation",
     *    @OA\JsonContent(ref="#/components/schemas/LegalText"),
     *  ),
     * )
     *
     * Display a legal text.
     * @return JsonResponse
     */
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
