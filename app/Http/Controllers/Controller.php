<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use SKAgarwal\GoogleApi\PlacesApi;

/**
 * @OA\Info(
 *     description="Map4Accessibility Backend",
 *     version="0.0.2",
 *     title="Map4Accessibility Backend",
 *     termsOfService="http://swagger.io/terms/",
 *     @OA\Contact(
 *         email="apiteam@swagger.io"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * @OA\Tag(
 *     name="appUser",
 *     description="Operations about App User",
 *     @OA\ExternalDocumentation(
 *         description="Find out more about store",
 *         url="http://swagger.io"
 *     )
 * )
 * @OA\ExternalDocumentation(
 *     description="Find out more about Swagger",
 *     url="http://swagger.io"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected PlacesApi $googlePlaces;

    public function __construct()
    {
        $this->googlePlaces = new PlacesApi(env('GOOGLE_MAPS_API_KEY', false));
    }
}
