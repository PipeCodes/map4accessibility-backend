<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResourceCollection;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Faq;

/**
 * Class FaqControllerController
 *
 * @package  App\Http\Controllers\Api
 */
class FaqController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *  path="/faqs",
     *  operationId="getFaqs",
     *  tags={"FAQ's"},
     *  summary="Get list of FAQs",
     *  description="Returns list of FAQs",
     *
     *  @OA\Parameter(
     *      name="Accept-Language",
     *      in="header",
     *      required=true,
     *      description="Set language parameter",
     *
     *      @OA\Schema(
     *         type="string",
     *         example="en",
     *      )
     *  ),
     *
     *  @OA\Response(response=200, description="Successful operation",
     *
     *    @OA\JsonContent(ref="#/components/schemas/FAQs"),
     *  ),
     * )
     *
     * Display a listing of FAQ.
     *
     * @return JsonResponse
     */
    public function getFaqs()
    {
        try {
            $faqs = Faq::where(
                'locale',
                '=',
                app()->getLocale()
            )->orderBy('order', 'ASC')->get();

            return $this->respondWithResourceCollection(new FaqResourceCollection($faqs));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }
}
