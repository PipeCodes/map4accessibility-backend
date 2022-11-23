<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceEvaluationCollection;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Traits\UploadTrait;
use App\Models\AppUser;
use App\Models\Place;
use App\Models\PlaceEvaluation;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Cloudinary;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary as FacadesCloudinary;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;

class PlaceEvaluationController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;

    /**
     * @OA\Post (
     *     path="/auth/place-evaluations/{placeEvaluationId}/media",
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
                        ->attachMedia(
                            $request->file('media'),
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
     * Returns the validator for the endpoint 
     * that is used to create a new Place Evaluation.
     * 
     * @param Request $request
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorCreatePlaceEvaluation(Request $request)
    {
        return Validator::make(
            $request->all(),
            [
                'google_place_id' => 'integer',
                'name' => 'string|min:6',
                'country_code' => 'required|string|min:1',
                'latitude' => ['required', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
                'longitude' => ['required', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
                'comment' => 'string|min:6',
                'thumb_direction' => 'required|boolean',
            ]
        );
    }

    /**
     * Returns the validator for the endpoint
     * that lists place evaluations.
     * 
     * @param Request $request
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorListPlaceEvaluationsRequest(Request $request)
    {
        return Validator::make(
            $request->all(),
            [
                'google_place_id' => [
                    'required_if:latitude,null',
                    'required_if:longitude,null',
                    'required_if:country,null',
                    'exclude_with:latitude',
                    'exclude_with:longitude',
                    'exists:places,google_place_id',
                    'integer'
                ],
                'latitude' => [
                    'exclude_with:google_place_id', 
                    'required_if:google_place_id,null', 
                    'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'
                ],
                'longitude' => [
                    'exclude_with:google_place_id', 
                    'required_if:google_place_id,null', 
                    'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'
                ],
                'geo_query_radius' => ['integer', 'min:1'],
                'asc_order_by' => [
                    'string', 
                    'in:name,country,thumb_direction,comment,created_at,updated_at'
                ],
                'desc_order_by' => [
                    'exclude_with:asc_order_by',
                    'string', 
                    'in:name,country,thumb_direction,comment,created_at,updated_at'
                ],
                'country_code' => [
                    'required_if:google_place_id,null', 
                    'required_if:latitude,null', 
                    'required_if:longitude,null', 
                    'string', 
                    'exists:places,country_code'
                ],
                'name' => ['string'],
                'place_type' => ['string'],
                'page' => ['integer', 'min:1'],
                'size' => ['integer', 'min:1']
            ]
        );
    }

    /**
     * Creates a new PlaceEvaluation and also a
     * new Place, in case it does not exist yet
     * in the database.
     * 
     * @OA\Post(
     *     path="/auth/place-evaluations",
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
     *             type="object",
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",ref="#/components/schemas/PlaceEvaluation"), 
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createPlaceEvaluation(Request $request)
    {
        try {
            $validator = $this->validatorCreatePlaceEvaluation($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $appUser = auth()->user();
            if (!$appUser) {
                return $this->respondNotFound();
            }

            $place = Place::query()
                ->where('latitude', $request->get('latitude'))
                ->where('longitude', $request->get('longitude'))
                ->firstOrCreate($request->only([
                    'latitude', 'longitude', 'google_place_id',
                    'name', 'country_code', 'place_type'
                ]));

            $placeEvaluation = PlaceEvaluation::create([
                ...$request->only([
                    'thumb_direction', 'comment', 'question_answers'
                ]),
                'app_user_id' => $appUser->id,
                'place_id' => $place->id
            ]);

            return $this->respondWithResource(new JsonResource($placeEvaluation));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
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
     *          property="name",
     *          description="Name",
     *          title="Name",
     *          example=""
     *     ),
     *     @OA\Property(
     *          property="place_type",
     *          description="Place Type",
     *          title="Place Type",
     *          example=""
     *     ),
     *     @OA\Property(
     *          property="country_code",
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
     *          property="size",
     *          format="integer",
     *          description="Number of results per page",
     *          title="Number of results per page",
     *          example="20"
     *     )
     * )
     *
     * @OA\Get (
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
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",
     *                 @OA\Property(title="data",property="data",type="array",
     *                     @OA\Items(type="object",ref="#/components/schemas/PlaceEvaluation")
     *                 ),
     *                 @OA\Property(title="links",property="links",type="object",
     *                     @OA\Property(
     *                         property="first",
     *                         format="string",
     *                         description="First Page",
     *                         title="First Page",
     *                         example="http://www.example.com?page=1&size=10"
     *                     ),
     *                     @OA\Property(
     *                         property="last",
     *                         format="string",
     *                         description="Last Page",
     *                         title="Last Page",
     *                         example="http://www.example.com?page=1&size=10"
     *                     ),
     *                     @OA\Property(
     *                         property="prev",
     *                         format="string",
     *                         description="Previous Page",
     *                         title="Previous Page",
     *                         example="http://www.example.com?page=1&size=10"
     *                     ),
     *                     @OA\Property(
     *                         property="next",
     *                         format="string",
     *                         description="Next Page",
     *                         title="Next Page",
     *                         example="http://www.example.com?page=1&size=10"
     *                     ),
     *                 )
     *             )
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
    public function listPlaceEvaluations(Request $request)
    {
        try {
            $validator = $this->validatorListPlaceEvaluationsRequest($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 401);
            }

            $query = PlaceEvaluation::query()
                ->select('*')
                ->with('place', 'appUser')
                ->whereHas('place', function ($query) use ($request) {
                    $this->queryListPlaceEvaluation($request, $query);
                });

            $query->paginate(
                $request->get('size', 20),
                ['*'],
                'page'
            )->withQueryString();
            

            $countThumbDirection = collect([
                'total_thumbs_up' => 
                    (clone $query)->where('thumb_direction', '=', 1)->count(),
                'total_thumbs_down' => 
                    (clone $query)->where('thumb_direction', '=', 0)->count(),
            ]);

            $result = $countThumbDirection->merge(
                $query->paginate(
                    $request->get('size', 20),
                    ['*'],
                    'page'
                )->withQueryString()
            );

            return $this->respondWithResourceCollection(
                new PlaceEvaluationCollection($result)
            );
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param Request|null $request
     * @return Builder
     */
    protected function queryListPlaceEvaluation(
        Request $request, 
        ?Builder $query = null
    ) {
        $query = $query ?: PlaceEvaluation::query()->select('*');

        if ($request->has('google_place_id')) {
            $query->where(
                'google_place_id', 
                $request->get('google_place_id')
            );
        } else if (
            $request->has('latitude') && 
            $request->has('longitude')
        ) {
            $radius = $request->get(
                'geo_query_radius', 
                env('GEO_QUERY_RADIUS', 5)
            );

            $query->selectRaw(
                '(6371000 * ACOS(COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) *
                SIN(RADIANS(latitude)))) AS distance', [
                    $request->latitude, 
                    $request->longitude, 
                    $request->latitude
                ]
            )->havingRaw("distance < ?", [$radius]);
        }

        if ($request->has('country_code')) {
            $query->where('country_code', $request->get('country_code'));
        }

        if ($request->has('name')) {
            $query->where(
                'name', 'like', '%' . $request->get('name') . '%'
            );
        }

        if ($request->has('place_type')) {
            $query->where(
                'place_type', 'like', '%' . $request->get('place_type') . '%'
            );
        }

        if ($request->has('asc_order_by')) {
            $query->orderBy($request->asc_order_by, 'asc');
        } else if ($request->has('desc_order_by')) {
            $query->orderBy($request->desc_order_by, 'desc');
        }

        return $query;
    }

    /**
     * @OA\Get (
     *     path="/auth/place-evaluations",
     *     tags={"PlaceEvaluation"},
     *     summary="Lists all place evaluations made by the app user that is currently logged in.",
     *     description="Lists all place evaluations made by the app user that is currently logged in.",
     *     operationId="placeEvaluationsByAppUser",
     *     @OA\Parameter(
     *         in="query",
     *         name="page",
     *         description="Page",
     *         example="1"
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="size",
     *         description="Quantity of comments to return",
     *         example="10"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",
     *                 @OA\Property(title="data",property="data",type="array",
     *                     @OA\Items(type="object",ref="#/components/schemas/PlaceEvaluation")
     *                 ),
     *                 @OA\Property(title="links",property="links",type="object",
     *                     @OA\Property(
     *                         property="first",
     *                         format="string",
     *                         description="First Page",
     *                         title="First Page",
     *                         example="http://www.example.com?page=1&size=10"
     *                     ),
     *                     @OA\Property(
     *                         property="last",
     *                         format="string",
     *                         description="Last Page",
     *                         title="Last Page",
     *                         example="http://www.example.com?page=1&size=10"
     *                     ),
     *                     @OA\Property(
     *                         property="prev",
     *                         format="string",
     *                         description="Previous Page",
     *                         title="Previous Page",
     *                         example="http://www.example.com?page=1&size=10"
     *                     ),
     *                     @OA\Property(
     *                         property="next",
     *                         format="string",
     *                         description="Next Page",
     *                         title="Next Page",
     *                         example="http://www.example.com?page=1&size=10"
     *                     ),
     *                 )
     *             )
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
    public function listPlaceEvaluationsByAppUser(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'page' => ['integer', 'min:1'],
            'size' => ['integer', 'min:1']
        ]);

        if ($validate->fails()) {
            return $this->respondError($validate->errors(), 422);
        }

        $appUser = $request->user();

        if (!$appUser) {
            return $this->respondNotFound();
        }

        return $this->respondWithResourceCollection(
            new PlaceEvaluationCollection(
                $appUser->placeEvaluations()->paginate(
                    $request->query('size', 10),
                    ['*'],
                    'page'
                )->withQueryString()
            )
        );
    }
}
