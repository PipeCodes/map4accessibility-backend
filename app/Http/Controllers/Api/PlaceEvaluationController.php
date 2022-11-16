<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceEvaluationCollection;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Traits\UploadTrait;
use App\Models\AppUser;
use App\Models\PlaceEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlaceEvaluationController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;

    /**
     * @param Request $request
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorListPlacesRequest(Request $request)
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
                    'exists:place_evaluations,google_place_id',
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
                'country' => [
                    'required_if:google_place_id,null', 
                    'required_if:latitude,null', 
                    'required_if:longitude,null', 
                    'string', 
                    'exists:place_evaluations,country'
                ],
                'name' => ['string'],
                'place_type' => ['string'],
                'page' => ['integer', 'min:1'],
                'per_page' => ['integer', 'min:1']
            ]
        );
    }

    /**
     * @param Request $request
     * @param Request|null $request
     * @return Builder
     */
    protected function queryListPlaces(Request $request, ?Builder $query = null)
    {
        /**
         * @var Builder $query
         */
        $query = $query ?: PlaceEvaluation::query()->select('*');

        if ($request->has('google_place_id')) {
            $query->where(
                'google_place_id', $request->google_place_id
            );
        } else if ($request->has('latitude') && $request->has('longitude')) {
            $radius = $request->get(
                'geo_query_radius', 
                env('GEO_QUERY_RADIUS', 5)
            );

            /**
             * replace 6371000 (for radius in meters) 
             * with 6371 (for radius in kilometer)
             * and 3956 (for radius in miles)
             */
            $query->selectRaw(
                '
                    ( 6371000 * acos( cos( radians(?) ) *
                    cos( radians( latitude ) )
                    * cos( radians( longitude ) - radians(?)
                    ) + sin( radians(?) ) *
                    sin( radians( latitude ) ) )
                    ) AS distance
                ',
                [$request->latitude, $request->longitude, $request->latitude]
            )->havingRaw("distance < ?", [$radius]);
        }

        if ($request->has('country')) {
            $query->where('country', $request->country);
        }

        if ($request->has('name')) {
            $query->where(
                'name', 'like', '%' . $request->name . '%'
            );
        }

        if ($request->has('place_type')) {
            $query->where(
                'place_type', 'like', '%' . $request->place_type . '%'
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
            $validate = $this->validatorListPlacesRequest($request);

            if ($validate->fails()) {
                return $this->respondError($validate->errors(), 401);
            }

            /**
             * @var Builder
             */
            $query = $this->queryListPlaces($request);

            $countThumbDirection = collect([
                'count_thumb_up' => 
                    (clone $query)->where('thumb_direction', '=', 1)->count(),
                'count_thumb_down' => 
                    (clone $query)->where('thumb_direction', '=', 0)->count(),
            ]);

            $result = $countThumbDirection->merge(
                $query->paginate($request->get('per_page', 20))
            );

            return $this->respondWithResource(new JsonResource($result));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * @OA\Post (
     *     path="/places",
     *     tags={"PlaceEvaluation"},
     *     summary="filter for evaluations for the given google_place_id OR coords, place",
     *     description="filter for evaluations for the given google_place_id OR coords, place",
     *     operationId="places",
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
    public function listPlaces(Request $request)
    {
        try {
            $validate = $this->validatorListPlacesRequest($request);

            if ($validate->fails()) {
                return $this->respondError($validate->errors(), 401);
            }

            /**
             * @var Builder
             */
            $query = $this->queryListPlaces(
                $request,
                PlaceEvaluation::query()->select('google_place_id')
            );

            $query
                ->addSelect(  
                    DB::raw(
                        "SUM(CASE WHEN thumb_direction = 1 THEN 1 ELSE 0 END) AS count_thumbs_up, 
                        SUM(CASE WHEN thumb_direction = 0 THEN 1 ELSE 0 END) AS count_thumbs_down,
                        MIN(name) as name,
                        MIN(country) as country,
                        MIN(place_type) as place_type,
                        MIN(latitude) as latitude,
                        MIN(longitude) as longitude"
                    )
                )
                ->groupBy('google_place_id');

            /**
             * WARNING: Temporary response!
             */
            return [
                'query' => $query->getQuery()->toSql(),
                'results' => $query->get()
            ];




            $countThumbDirection = collect([
                'count_thumb_up' => 
                    (clone $query)->where('thumb_direction', '=', 1)->count(),
                'count_thumb_down' => 
                    (clone $query)->where('thumb_direction', '=', 0)->count(),
            ]);

            $result = $countThumbDirection->merge(
                $query->paginate($request->get('per_page', 20))
            );

            return $this->respondWithResource(new JsonResource($result));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * @OA\Get (
     *     path="/auth/place-evaluation",
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
     *                 @OA\Property(title="data",property="questions",type="array",
     *                     @OA\Items(type="object",ref="#/components/schemas/PlaceEvaluation")
     *                 ),
     *                 @OA\Property(title="links",property="result",type="object",
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
