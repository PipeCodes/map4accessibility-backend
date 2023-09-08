<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceRateSettingsResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\CountryResponsible;
use App\Models\RateQuestion;

/**
 * Class RateSettingsController
 *
 * @package  App\Http\Controllers\Api
 */
class RateSettingsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *  path="/place-rate-settings",
     *  operationId="getPlaceRateSettings",
     *  tags={"Settings"},
     *  summary="Get the settings for the place rating",
     *  description="Returns the settings for the place rating",
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
     *    @OA\JsonContent(ref="#/components/schemas/PlaceRateSettingsResponse"),
     *  ),
     * )
     *
     * @OA\Schema(
     *   schema="PlaceRateSettingsResponse",
     *   title="Place Rate Settings Response",
     *
     *   @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *   @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *   @OA\Property(title="result",property="result",type="object",
     *      @OA\Property(title="questions",property="questions",type="array",
     *
     *          @OA\Items(type="object",ref="#/components/schemas/RateQuestion")
     *      ),
     *
     *      @OA\Property(title="country_responsibles",property="country_responsibles",type="array",
     *
     *          @OA\Items(type="object",ref="#/components/schemas/CountryResponsible")
     *      ),
     *   )
     * )
     *
     * Returns the place rate settings
     *
     * @return JsonResponse
     */
    public function getPlaceRateSettings()
    {
        try {
            $questions = RateQuestion::with('answers')
                ->orderBy('question_type')
                ->get();

            $countryResponsibles = CountryResponsible::all();

            return $this->respondWithResource(
                new PlaceRateSettingsResource(
                    collect([
                        'questions' => $questions,
                        'country_responsibles' => $countryResponsibles,
                    ])
                )
            );
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }
}
