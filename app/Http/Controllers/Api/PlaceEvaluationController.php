<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppUserResource;
use App\Http\Resources\AuthResource;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Traits\UploadTrait;
use App\Models\AppUser;
use App\Models\PlaceEvaluation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class PlaceEvaluationController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;


    /**
     * @OA\Post(
     *     path="/auth/place-evaluation",
     *     tags={"PlaceEvaluation"},
     *     summary="Create Place Evaluation by AppUser AUTH TOKEN",
     *     description="Create Place Evaluation by AppUser AUTH TOKEN",
     *     operationId="placeEvaluationByAuthenticated",
     *     @OA\RequestBody(
     *          @OA\JsonContent(
     *             ref="#/components/schemas/PlaceEvaluation"
     *         )
     *     ),
     *      @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\Header(
     *             header="X-Rate-Limit",
     *             description="calls per hour allowed by the user",
     *             @OA\Schema(
     *                 type="integer",
     *                 format="int32"
     *             )
     *         ),
     *         @OA\Header(
     *             header="X-Expires-After",
     *             description="date in UTC when token expires",
     *             @OA\Schema(
     *                 type="string",
     *                 format="datetime"
     *             )
     *         ),
     *         @OA\JsonContent(
     *             type="object"
     *         )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Invalid username/password supplied"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Internal error"
     *     ),
     * )
     */
    public function placeEvaluationByAuthenticated(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'google_place_id' => 'integer',
                    'name' => 'string|min:6',
                    'country' => 'required|string|min:1',
                    'latitude' => ['required', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
                    'longitude' => ['required', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
                    'comment' => 'string|min:6',
                    'thumb_direction' => 'required|boolean',
                ]
            );

            if ($validate->fails()) {
                return $this->respondError($validate->errors(), 401);
            }

            /**
             * @var AppUser|null $appUser
             */
            if ($appUser = $request->user()) {

                $dataPlaceEvaluation = array_merge(
                    $request->all(),
                    [
                        'app_user_id' => (string)$appUser->id
                    ]
                );

                /**
                 * @var PlaceEvaluation|null $placeEvaluation
                 */
                if ($placeEvaluation = PlaceEvaluation::create($dataPlaceEvaluation)) {

                    return $this->respondWithResource(new JsonResource($placeEvaluation));
                }
            }

            return $this->respondNotFound();

        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }

    }

    /**
     * @OA\Post (
     *     path="/auth/place-evaluation/{placeEvaluationId}/media",
     *     tags={"PlaceEvaluation"},
     *     summary="upload a media file for Place Evaluation ID by AppUser AUTH TOKEN",
     *     description="upload a media file for Place Evaluation ID by AppUser AUTH TOKEN",
     *     operationId="attachMediaPlaceEvaluationByAuthenticated",
     *     @OA\Parameter(in="path", name="placeEvaluationId",required=true),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  @OA\Property(
     *                      property="media",
     *                      type="string",
     *                      format="binary"
     *                  )
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\Header(
     *             header="X-Rate-Limit",
     *             description="calls per hour allowed by the user",
     *             @OA\Schema(
     *                 type="integer",
     *                 format="int32"
     *             )
     *         ),
     *         @OA\Header(
     *             header="X-Expires-After",
     *             description="date in UTC when token expires",
     *             @OA\Schema(
     *                 type="string",
     *                 format="datetime"
     *             )
     *         ),
     *         @OA\JsonContent(
     *             type="object"
     *         )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Invalid username/password supplied"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Internal error"
     *     ),
     * )
     */
    public function attachMediaPlaceEvaluationByAuthenticated(Request $request, $placeEvaluationId)
    {
        try {

            $validate = Validator::make(
                array_merge($request->all(), ['placeEvaluationId' => $placeEvaluationId]),
                [
                    'placeEvaluationId' => 'required|string|exists:place_evaluations,id',
                    'media' => 'file|mimetypes:image/jpg,image/png,image/jpeg,video/mp4',
                ]
            );

            if ($validate->fails()) {
                return $this->respondError($validate->errors(), 401);
            }

            /**
             * @var AppUser|null $appUser
             */
            if ($appUser = auth()->user()) {
                /**
                 * @var PlaceEvaluation|null $placeEvaluation
                 */
                if ($placeEvaluation = $appUser->placeEvaluation($placeEvaluationId)) {
                    // Cloudinary --> delete old image/video upload
                    $placeEvaluation->detachMedia();
                    // Cloudinary --> type image or video
                    $resourceType = str_contains($request->file('media')->getMimeType(), 'video/') ? 'video' : 'image';
                    // re-hydrate Models
                    $placeEvaluation->fresh()
                        // Cloudinary --> upload media and bind the image to the model record
                        ->attachMedia($request->file('media'),
                            // options array for Cloudinary laravel sdk, see docs of library
                            [
                                'resource_type' => $resourceType,
                                'transformation' => [
                                    'quality' => 'auto',
                                    'fetch_format' => 'auto'
                                ]
                            ]
                        );

                    return $this->respondWithResource(new JsonResource($placeEvaluation));

                }
            }

            return $this->respondNotFound();

        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }

    }

    /**
     *
     * @OA\Schema(
     *     schema="requestPlaceEvaluationsObject",
     *     type="object",
     *     @OA\Property(
     *          property="google_place_id",
     *          format="int64",
     *          description="Google Place id",
     *          title="Google Place id",
     *          example=""
     *     ),
     *     @OA\Property(
     *          property="latitude",
     *          format="decimal",
     *          description="Coord. Latitude",
     *          title="Coord. Latitude",
     *          example=""
     *     ),
     *     @OA\Property(
     *          property="longitude",
     *          format="decimal",
     *          description="Coord. Longitude",
     *          title="Coord. Longitude",
     *          example=""
     *     ),
     *     @OA\Property(
     *          property="geo_query_radius",
     *          format="integer",
     *          description="GeoQuery Radius in Meters",
     *          title="GeoQuery Radius",
     *          example="5"
     *     ),
     *     @OA\Property(
     *          property="country",
     *          description="Country",
     *          title="Country",
     *          example=""
     *     ),
     *     @OA\Property(
     *          property="asc_order_by",
     *          description="Order by :field ASC",
     *          title="Order by :field ASC",
     *          example="thumb_direction"
     *     ),
     *     @OA\Property(
     *          property="desc_order_by",
     *          description=" Order by DESC",
     *          title="Order by :field DESC",
     *          example=""
     *     ),
     *     @OA\Property(
     *          property="page",
     *          format="integer",
     *          description="Page Number",
     *          title="Page Number",
     *          example="1"
     *     ),
     *     @OA\Property(
     *          property="per_page",
     *          format="integer",
     *          description="Per. Page",
     *          title="Per. Page",
     *          example="20"
     *     )
     * )
     *
     * @OA\Post (
     *     path="/place-evaluations",
     *     tags={"PlaceEvaluation"},
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     summary="filter for evaluations for the given google_place_id OR coords, place",
     *     description="filter for evaluations for the given google_place_id OR coords, place",
     *     operationId="placeEvaluations",
     *     @OA\RequestBody(
     *          @OA\JsonContent(
     *             ref="#/components/schemas/requestPlaceEvaluationsObject"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\Header(
     *             header="X-Rate-Limit",
     *             description="calls per hour allowed by the user",
     *             @OA\Schema(
     *                 type="integer",
     *                 format="int32"
     *             )
     *         ),
     *         @OA\Header(
     *             header="X-Expires-After",
     *             description="date in UTC when token expires",
     *             @OA\Schema(
     *                 type="string",
     *                 format="datetime"
     *             )
     *         ),
     *         @OA\JsonContent(
     *             type="object"
     *         )
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Invalid username/password supplied"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Internal error"
     *     ),
     * )
     */
    public function placeEvaluations(Request $request)
    {
        try {

            $validate = Validator::make(
                $request->all(),
                [
                    'google_place_id' => 'required_if:latitude,null|required_if:longitude,null|required_if:country,null|exists:place_evaluations,google_place_id|integer|exclude_with:latitude|exclude_with:longitude',
                    'latitude' => ['required_if:google_place_id,null', 'required_if:country,null', 'exclude_with:google_place_id', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
                    'longitude' => ['required_if:google_place_id,null', 'exclude_with:google_place_id', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
                    'geo_query_radius' => ['integer', 'min:1'],
                    'asc_order_by' => ['string', 'in:name,country,thumb_direction,comment,created_at,updated_at'],
                    'desc_order_by' => ['string', 'in:name,country,thumb_direction,comment,created_at,updated_at', 'exclude_with:asc_order_by'],
                    'country' => ['required_if:google_place_id,null', 'required_if:latitude,null', 'required_if:longitude,null', 'string', 'exists:place_evaluations,country'],
                    'page' => ['integer', 'min:1'],
                    'per_page' => ['integer', 'min:1']
                ]
            );

            if ($validate->fails()) {
                return $this->respondError($validate->errors(), 401);
            }

            /**
             * @var PlaceEvaluation|null $placeEvaluation
             */
            $placeEvaluation = null;

            if ($request->has('google_place_id')) {

                $placeEvaluation = PlaceEvaluation::where('google_place_id', '=', $request->google_place_id);

            } elseif ($request->has('latitude') && $request->has('longitude')) {

                $radius = $request->get('geo_query_radius', env('GEO_QUERY_RADIUS', 5));

                // replace 6371000 (for radius in meters) with 6371 (for radius in kilometer) and 3956 (for radius in miles)
                $placeEvaluation = PlaceEvaluation::selectRaw('*,
                    ( 6371000 * acos( cos( radians(?) ) *
                    cos( radians( latitude ) )
                    * cos( radians( longitude ) - radians(?)
                    ) + sin( radians(?) ) *
                    sin( radians( latitude ) ) )
                    ) AS distance',
                    [$request->latitude, $request->longitude, $request->latitude]
                )->havingRaw("distance < ?", [$radius]);

            }

            if ($request->has('country')) {
                if ($placeEvaluation) {
                    $placeEvaluation->where('country', $request->country);
                }else{
                    $placeEvaluation = PlaceEvaluation::where('country', $request->country);
                }
            }

            if ($placeEvaluation) {

                if ($request->has('asc_order_by')) {
                    $placeEvaluation->orderBy($request->asc_order_by, 'asc');
                } elseif ($request->has('desc_order_by')) {
                    $placeEvaluation->orderBy($request->desc_order_by, 'desc');
                }

                $countThumbDirection = collect(
                    [
                        'count_thumb_up' => (clone $placeEvaluation)->where('thumb_direction', '=', 1)->count(),
                        'count_thumb_down' => (clone $placeEvaluation)->where('thumb_direction', '=', 0)->count(),
                    ]
                );

                $placeEvaluation = $countThumbDirection->merge($placeEvaluation->paginate($request->get('per_page', 20)));

                return $this->respondWithResource(new JsonResource($placeEvaluation));

            }

            return $this->respondNotFound();

        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }

    }

}
