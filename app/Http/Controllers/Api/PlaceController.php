<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Traits\UploadTrait;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;

    /**
     * Basic validation rules for the places list.
     *
     * @return array
     */
    protected function validationRulesListPlaces(): array
    {
        return [
            'asc_order_by' => [
                'string', 
                'in:thumbs_up_count,thumbs_down_count,name,country_code,place_type,created_at,updated_at'
            ],
            'desc_order_by' => [
                'exclude_with:asc_order_by',
                'string', 
                'in:thumbs_up_count,thumbs_down_count,name,country_code,place_type,created_at,updated_at'
            ],
            'country_code' => [
                'string', 
                'exists:places,country_code'
            ],
            'name' => ['string'],
            'place_type' => ['string'],
            'page' => ['integer', 'min:1'],
            'size' => ['integer', 'min:1']
        ];
    }

    /**
     * Returns the validator for the endpoint
     * that lists places.
     * 
     * @param Request $request
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorListPlacesRequest(Request $request)
    {
        return Validator::make(
            $request->all(), $this->validationRulesListPlaces()
        );
    }

    /**
     * Returns the validator for the endpoint
     * that lists places.
     * 
     * @param Request $request
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorListPlacesByRadiusRequest(Request $request)
    {
        return Validator::make(
            $request->all(), [
                'latitude' => [
                    'required', 
                    'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'
                ],
                'longitude' => [
                    'required', 
                    'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'
                ],
                'geo_query_radius' => ['integer', 'min:1'],
                ...$this->validationRulesListPlaces()
            ]
        );
    }

    /**
     * Returns a list of places.
     *
     * @OA\Get (
     *     path="/places",
     *     tags={"Places"},
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     summary="List of places",
     *     description="List of places",
     *     operationId="listPlaces",
     *     @OA\Parameter(
     *         in="query",
     *         name="name",
     *         description="Name of a Place",
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="country_code",
     *         description="Country Code of a country",
     *         example="PT"
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="place_type",
     *         description="Place Type",
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="asc_order_by",
     *         description="Parameter to sort by ASC",
     *         example="country_code"
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="desc_order_by",
     *         description="Parameter to sort by DESC",
     *         example="country_code"
     *     ),
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
     *                     @OA\Items(type="object",ref="#/components/schemas/Place")
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
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function listPlaces(Request $request)
    {
        try {
            $validator = $this->validatorListPlacesByRadiusRequest($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $query = $this->queryListPlaces(
                $request,
                Place::query()
                    ->select('*')
                    ->with('medias', function ($query) {
                        $query->select('file_url', 'file_name');
                    })
                    ->withCount([
                        'placeEvaluations as thumbs_up_count' => 
                            function ($query) {
                                $query->where('thumb_direction', 1);
                            },
                        'placeEvaluations as thumbs_down_count' => 
                            function ($query) {
                                $query->where('thumb_direction', 0);
                            }
                    ])
            );

            $places = $query->paginate(
                $request->get('size', 20),
                ['*'],
                'page'
            )->withQueryString();
            
            list($totalThumbsUp, $totalThumbsDown) = 
                $this->totalEvaluationsListPlaces($places->items());

            $result = collect([
                'total_thumbs_up' => $totalThumbsUp,
                'total_thumbs_down' => $totalThumbsDown,
            ])->merge($places);
            
            return $this->respondWithResource(new JsonResource($result));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * Returns a list of places within a certain radius,
     * given a center coordinates or a google_place_id.
     *
     * @OA\Get (
     *     path="/places-by-radius",
     *     tags={"Places"},
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     summary="filter for places for the given google_place_id OR coords",
     *     description="filter for places for the given google_place_id OR coords",
     *     operationId="listPlacesByRadius",
     *     @OA\Parameter(
     *         in="query",
     *         name="latitude",
     *         description="Latitude",
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="longitude",
     *         description="Longitude",
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="geo_query_radius",
     *         description="Radius (in meters) to search across",
     *         example="2000"
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="name",
     *         description="Name of a Place",
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="country_code",
     *         description="Country Code of a country",
     *         example="PT"
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="place_type",
     *         description="Place Type",
     *         example=""
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="asc_order_by",
     *         description="Parameter to sort by ASC",
     *         example="country_code"
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="desc_order_by",
     *         description="Parameter to sort by DESC",
     *         example="country_code"
     *     ),
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
     *                     @OA\Items(type="object",ref="#/components/schemas/Place")
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
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function listPlacesByRadius(Request $request)
    {
        try {
            $validator = $this->validatorListPlacesByRadiusRequest($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $query = $this->queryListPlaces(
                $request,
                Place::query()
                    ->select('*')
                    ->with('medias', function ($query) {
                        $query->select('file_url', 'file_name');
                    })
                    ->withCount([
                        'placeEvaluations as thumbs_up_count' => 
                            function ($query) {
                                $query->where('thumb_direction', 1);
                            },
                        'placeEvaluations as thumbs_down_count' => 
                            function ($query) {
                                $query->where('thumb_direction', 0);
                            }
                    ])
            );

            $places = $query->paginate(
                $request->get('size', 20),
                ['*'],
                'page'
            )->withQueryString();
            
            list($totalThumbsUp, $totalThumbsDown) = 
                $this->totalEvaluationsListPlaces($places->items());

            $result = collect([
                'total_thumbs_up' => $totalThumbsUp,
                'total_thumbs_down' => $totalThumbsDown,
            ])->merge($places);
            
            return $this->respondWithResource(new JsonResource($result));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * Adds several conditions to a query to the
     * "places" table, given the parameters existent in
     * the request.
     */
    protected function queryListPlaces(Request $request, $query = null)
    {
        $query = $query ?: Place::query()->select('*');

        if (
            $request->has('latitude') && $request->get('latitude') !== "" &&
            $request->has('longitude') && $request->get('longitude')
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

        if (
            $request->has('country_code') && 
            $request->get('country_code') !== ""
        ) {
            $query->where('country_code', $request->get('country_code'));
        }

        if (
            $request->has('name') &&
            $request->get('name') !== ""
        ) {
            $query->where(
                'name', 'like', '%' . $request->get('name') . '%'
            );
        }

        if (
            $request->has('place_type') &&
            $request->get('place_type') !== ""
        ) {
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
     * Calculates the total of thumbs up and thumbs down
     * evaluations that a list of places has.
     *
     * @param array $places
     * @return array
     */
    protected function totalEvaluationsListPlaces(array $places)
    {
        $totalThumbsUp = $totalThumbsDown = 0;
        
        collect($places)
            ->each(function (Place $place) use (
                &$totalThumbsUp, 
                &$totalThumbsDown
            ) {
                $totalThumbsUp += $place->thumbs_up_count;
                $totalThumbsDown += $place->thumbs_down_count;
            });
        
        return [$totalThumbsUp, $totalThumbsDown];
    }

    /**
     * @OA\Get (
     *     path="/places/google/{id}",
     *     tags={"Places"},
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     summary="Get Place by a Google Place ID",
     *     description="Get Place by a Google Place ID",
     *     operationId="getPlaceByPlaceId",
     *     @OA\Parameter(
     *         parameter="Place--google_place_id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         description="Google Place ID",
     *         @OA\Schema(
     *             type="string",
     *             example="123456",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",ref="#/components/schemas/Place"),
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
    public function getPlaceByGooglePlaceId(Request $request, string $id)
    {
        try {
            $validator = Validator::make(
                $request->all(), [
                    'id' => [
                        'exists:places,google_place_id',
                        'integer'
                    ]
                ]
            );

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $place = Place::query()
                ->select('*')
                ->where('google_place_id', $id)
                ->with('medias', function ($query) {
                    $query->select('file_url', 'file_name');
                })
                ->withCount([
                    'placeEvaluations as thumbs_up_count' => 
                        function ($query) {
                            $query->where('thumb_direction', 1);
                        },
                    'placeEvaluations as thumbs_down_count' => 
                        function ($query) {
                            $query->where('thumb_direction', 0);
                        }
                ])
                ->first();

            if (!$place) {
                return $this->respondNotFound();
            }
            
            return $this->respondWithResource(new JsonResource($place));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }
}
