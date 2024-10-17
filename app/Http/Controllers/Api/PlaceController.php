<?php

namespace App\Http\Controllers\Api;

use App\Helper\Evaluation;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Traits\UploadTrait;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use SKAgarwal\GoogleApi\Exceptions\GooglePlacesApiException;

class PlaceController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;

    /**
     * Basic validation rules for the places list.
     */
    protected function validationRulesListPlaces(): array
    {
        return [
            'asc_order_by' => [
                'string',
                'in:accessible_count,inaccessible_count,ratio_up_down,ratio_down_up,name,country_code,place_type,created_at,updated_at',
            ],
            'desc_order_by' => [
                'exclude_with:asc_order_by',
                'string',
                'in:accessible_count,inaccessible_count,ratio_up_down,ratio_down_up,name,country_code,place_type,created_at,updated_at',
            ],
            'country_code' => [
                'string',
                'exists:places,country_code',
            ],
            'name' => ['string'],
            'place_type' => ['string'],
            'disabilities' => ['string'],
            'page' => ['integer', 'min:1'],
            'size' => ['integer', 'min:1'],
        ];
    }

    /**
     * Returns the validator for the endpoint
     * that is used to create a new Place.
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorCreatePlace(Request $request)
    {
        return Validator::make($request->all(), [
            'latitude' => [
                'required',
                'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/',
            ],
            'longitude' => [
                'required',
                'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/',
            ],
            'google_place_id' => 'string|nullable',
            'name' => 'string|min:3|nullable',
            'place_type' => 'string|nullable',
            'country_code' => 'string|min:2|nullable',
            'city' => 'string|nullable',
            'address' => 'string|nullable',
            'phone' => 'string|nullable',
            'email' => 'string|email|nullable',
            'website' => 'string|url|nullable',
            'schedule' => 'string|nullable',
        ]);
    }

    /**
     * Returns the validator for the endpoint
     * that lists places.
     *
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
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorListPlacesByRadiusRequest(Request $request)
    {
        return Validator::make(
            $request->all(), [
                'latitude' => [
                    'required',
                    'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/',
                ],
                'longitude' => [
                    'required',
                    'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/',
                ],
                'geo_query_radius' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                ...$this->validationRulesListPlaces(),
            ]
        );
    }

    /**
     * Returns a list of places.
     *
     * @OA\Get (
     *     path="/places",
     *     tags={"Places"},
     *     summary="List of places",
     *     description="List of places",
     *     operationId="listPlaces",
     *
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
     *         name="disabilities",
     *         description="for search by on disabilities",
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
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",
     *                 @OA\Property(title="data",property="data",type="array",
     *
     *                     @OA\Items(type="object",ref="#/components/schemas/Place")
     *                 ),
     *
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
     *
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
     * @return JsonResponse
     */
    public function listPlaces(Request $request)
    {
        try {
            $validator = $this->validatorListPlacesRequest($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $basicRatioQuery = '(select count(*) from `place_evaluations` where `places`.`id` = `place_evaluations`.`place_id` and `place_evaluations`.`deleted_at` is null and `place_evaluations`.`evaluation` = ?)';

            $query = $this->queryListPlaces(
                $request,
                Place::query()
                    ->select('places.*')
                    ->selectRaw("ifnull($basicRatioQuery / $basicRatioQuery, $basicRatioQuery / 1) as `ratio_accessible_inaccessible`", [Evaluation::Accessible->value, Evaluation::Inaccessible->value, Evaluation::Accessible->value])
                    ->selectRaw("ifnull($basicRatioQuery / $basicRatioQuery, $basicRatioQuery / 1) as `ratio_inaccessible_accessible`", [Evaluation::Inaccessible->value, Evaluation::Accessible->value, Evaluation::Inaccessible->value])
                    ->with(['mediaEvaluations' => function ($query) {
                        $query->select('file_url', 'file_name');
                    }])
                    ->withCount([
                        'placeEvaluations as accessible_count' => function ($query) {
                            $query->where('evaluation', Evaluation::Accessible->value);
                        },
                        'placeEvaluations as neutral_count' => function ($query) {
                            $query->where('evaluation', Evaluation::Neutral->value);
                        },
                        'placeEvaluations as inaccessible_count' => function ($query) {
                            $query->where('evaluation', Evaluation::Inaccessible->value);
                        },
                    ])
            );

            $places = $query->paginate(
                $request->get('size', 20),
                ['*'],
                'page'
            )->withQueryString();

            [$totalAccessible, $totalNeutral, $totalInaccessible] =
                $this->totalEvaluationsListPlaces($places->items());

            $googlePlacesResult = [];
            if ((int) $request->get('page', 1) === 1) {
                $googlePlacesResult = $this->googlePlacesTextSearch(
                    $places->pluck('google_place_id')->toArray(),
                    $request->get('name', ''),
                    ['type' => $request->get('place_type', '')]
                );
            }

            $result = collect([
                'total_accessible' => $totalAccessible,
                'total_neutral' => $totalNeutral,
                'total_inaccessible' => $totalInaccessible,
            ])->merge([$places->concat($googlePlacesResult)]);

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
     *     path="/places/radius",
     *     tags={"Places"},
     *     summary="filter for places for the given google_place_id OR coords",
     *     description="filter for places for the given google_place_id OR coords",
     *     operationId="listPlacesByRadius",
     *
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
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",
     *                 @OA\Property(title="data",property="data",type="array",
     *
     *                     @OA\Items(type="object",ref="#/components/schemas/Place")
     *                 ),
     *
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
     *
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
     * @return JsonResponse
     */
    public function listPlacesByRadius(Request $request)
    {
        try {
            $googlePlacesResult = collect([]);
            $validator = $this->validatorListPlacesByRadiusRequest($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $basicRatioQuery = '(select count(*) from `place_evaluations` where `places`.`id` = `place_evaluations`.`place_id` and `place_evaluations`.`deleted_at` is null and `place_evaluations`.`evaluation` = ?)';

            $query = $this->queryListPlaces(
                $request,
                Place::query()
                    ->select('*')
                    ->selectRaw("ifnull($basicRatioQuery / $basicRatioQuery, $basicRatioQuery / 1) as `ratio_accessible_inaccessible`", [Evaluation::Accessible->value, Evaluation::Inaccessible->value, Evaluation::Accessible->value])
                    ->selectRaw("ifnull($basicRatioQuery / $basicRatioQuery, $basicRatioQuery / 1) as `ratio_inaccessible_accessible`", [Evaluation::Inaccessible->value, Evaluation::Accessible->value, Evaluation::Inaccessible->value])
                    ->with('mediaEvaluations', function ($query) {
                        $query->select('file_url', 'file_name');
                    })
                    ->withCount([
                        'placeEvaluations as accessible_count' => function ($query) {
                            $query->where('evaluation', Evaluation::Accessible->value);
                        },
                        'placeEvaluations as neutral_count' => function ($query) {
                            $query->where('evaluation', Evaluation::Neutral->value);
                        },
                        'placeEvaluations as inaccessible_count' => function ($query) {
                            $query->where('evaluation', Evaluation::Inaccessible->value);
                        },
                    ])
            );

            $places = $query->paginate(
                $request->get('size', 20),
                ['*'],
                'page'
            )->withQueryString();

            [$totalAccessible, $totalNeutral, $totalInaccessible] =
                $this->totalEvaluationsListPlaces($places->items());

            if ((int) $request->get('page', 1) === 1) {
                $googlePlacesResult = $this->googlePlacesNearbySearch(
                    $places->pluck('google_place_id')->toArray(),
                    ($request->latitude.','.$request->longitude),
                    $request->get('geo_query_radius', env('GEO_QUERY_RADIUS', 5)),
                    ['type' => $request->get('place_type', '')]
                );
            }

            $result = collect([
                'total_accessible' => $totalAccessible,
                'total_neutral' => $totalNeutral,
                'total_inaccessible' => $totalInaccessible,
            ])->merge([$places->concat($googlePlacesResult)]);

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
            $request->has('latitude') && $request->get('latitude') !== '' &&
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
                    $request->latitude,
                ]
            )->havingRaw('distance < ?', [$radius]);
        } else {
            if (
                $request->has('disabilities') &&
                $request->get('disabilities') !== ''
            ) {
                $disabilitiesArr = explode(',', $request->get('disabilities'));
                $query->whereNotIn('places.disabilities', $disabilitiesArr);
            } else {
                // every place that doesn't have a disability problem
                $query->where(
                    function ($query) {
                        return $query->where('places.disabilities', '')
                            ->orWhere('places.disabilities', '[]');
                    });
            }
        }

        if (
            $request->has('country_code') &&
            $request->get('country_code') !== ''
        ) {
            $query->where('places.country_code', $request->get('country_code'));
        }

        if (
            $request->has('name') &&
            $request->get('name') !== ''
        ) {
            $query->where(
                function ($query) use ($request) {
                    return $query->where(
                        'places.name', 'like', '%'.$request->get('name').'%'
                    )->orWhere(
                        'places.city', 'like', '%'.$request->get('name').'%'
                    );
                });
        }

        if (
            $request->has('place_type') &&
            $request->get('place_type') !== ''
        ) {
            $query->where(
                'places.place_type', 'like', '%'.$request->get('place_type').'%'
            );
        }

        if ($request->has('asc_order_by')) {
            $query->orderBy($request->asc_order_by, 'asc');
        } elseif ($request->has('desc_order_by')) {
            $query->orderBy($request->desc_order_by, 'desc');
        }

        return $query;
    }

    /**
     * Calculates the total of
     * evaluations that a list of places has.
     *
     * @return array
     */
    protected function totalEvaluationsListPlaces(array $places)
    {
        $totalAccessible = $totalNeutral = $totalInaccessible = 0;

        collect($places)
            ->each(function (Place $place) use (
                &$totalAccessible,
                &$totalNeutral,
                &$totalInaccessible,
            ) {
                $totalAccessible += $place->accessible_count;
                $totalNeutral += $place->neutral_count;
                $totalInaccessible += $place->inaccessible_count;
            });

        return [$totalAccessible, $totalNeutral, $totalInaccessible];
    }

    /**
     * @OA\Get (
     *     path="/places/{id}",
     *     tags={"Places"},
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     summary="Get Place by its ID",
     *     description="Get Place its ID",
     *     operationId="getPlaceById",
     *
     *     @OA\Parameter(
     *         parameter="Place--id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         description="Place ID",
     *
     *         @OA\Schema(
     *             type="string",
     *             example="1",
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",ref="#/components/schemas/Place"),
     *         )
     *     ),
     *
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
     * @return JsonResponse
     */
    public function getPlaceById(Request $request, string $id)
    {
        try {
            $validator = Validator::make(
                $request->all(), [
                    'id' => [
                        'exists:places,id',
                        'integer',
                    ],
                ]
            );

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $place = Place::query()
                ->select('*')
                ->where('id', $id)
                ->with('placeDeletion')
                ->with('placeEvaluations')
                ->with('placeEvaluations.appUser')
                ->with('mediaEvaluations', function ($query) {
                    $query->select('file_url', 'file_type');
                })
                ->withCount([
                    'placeEvaluations as accessible_count' => function ($query) {
                        $query->where('evaluation', Evaluation::Accessible->value);
                    },
                    'placeEvaluations as neutral_count' => function ($query) {
                        $query->where('evaluation', Evaluation::Neutral->value);
                    },
                    'placeEvaluations as inaccessible_count' => function ($query) {
                        $query->where('evaluation', Evaluation::Inaccessible->value);
                    },
                ])
                ->first();

            if (! $place) {
                return $this->respondNotFound();
            }

            return $this->respondWithResource(new JsonResource($place));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
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
     *     operationId="getPlaceByGooglePlaceId",
     *
     *     @OA\Parameter(
     *         parameter="Place--google_place_id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         description="Google Place ID",
     *
     *         @OA\Schema(
     *             type="string",
     *             example="123456",
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",ref="#/components/schemas/Place"),
     *         )
     *     ),
     *
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
     * @return JsonResponse
     */
    public function getPlaceByGooglePlaceId(Request $request, string $id)
    {
        try {
            $validator = Validator::make(
                $request->all(), [
                    'id' => [
                        'exists:places,google_place_id',
                        'integer',
                    ],
                ]
            );

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $place = Place::query()
                ->select('*')
                ->where('google_place_id', $id)
                ->with('placeDeletion')
                ->with('placeEvaluations')
                ->with('placeEvaluations.appUser')
                ->with('mediaEvaluations', function ($query) {
                    $query->select('file_url', 'file_name');
                })
                ->withCount([
                    'placeEvaluations as accessible_count' => function ($query) {
                        $query->where('evaluation', Evaluation::Accessible->value);
                    },
                    'placeEvaluations as neutral_count' => function ($query) {
                        $query->where('evaluation', Evaluation::Neutral->value);
                    },
                    'placeEvaluations as inaccessible_count' => function ($query) {
                        $query->where('evaluation', Evaluation::Inaccessible->value);
                    },
                ])
                ->first();

            if (! $place) {
                return $this->respondNotFound();
            }

            return $this->respondWithResource(new JsonResource($place));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * Creates a new Place.
     *
     * @OA\Post(
     *     path="/places",
     *     tags={"Places"},
     *     summary="Create a new Place",
     *     description="Create a new Place",
     *     operationId="createPlace",
     *
     *     @OA\RequestBody(
     *
     *          @OA\JsonContent(
     *             ref="#/components/schemas/Place"
     *         )
     *     ),
     *
     *      @OA\Response(
     *         response=200,
     *         description="successful operation",
     *
     *         @OA\Header(
     *             header="X-Rate-Limit",
     *             description="calls per hour allowed by the user",
     *
     *             @OA\Schema(
     *                 type="integer",
     *                 format="int32"
     *             )
     *         ),
     *
     *         @OA\Header(
     *             header="X-Expires-After",
     *             description="date in UTC when token expires",
     *
     *             @OA\Schema(
     *                 type="string",
     *                 format="datetime"
     *             )
     *         ),
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",ref="#/components/schemas/Place"),
     *         )
     *     ),
     *
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
     * @return JsonResponse
     */
    public function createPlace(Request $request)
    {
        try {
            $validator = $this->validatorCreatePlace($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $latitude = number_format($request->get('latitude'), 8);
            $longitude = number_format($request->get('longitude'), 8);

            $exists = Place::query()
                ->where('latitude', $latitude)
                ->where('longitude', $longitude)
                ->exists();

            if ($exists) {
                return $this->respondError(
                    'Place with given coords already exists',
                    409
                );
            }

            $request['disabilities'] = [];

            $place = Place::create($request->only([
                'latitude',
                'longitude',
                'google_place_id',
                'name',
                'place_type',
                'country_code',
                'city',
                'address',
                'phone',
                'email',
                'website',
                'schedule',
                'disabilities',
            ]));

            return $this->respondWithResource(new JsonResource($place));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * @OA\Post (
     *     path="/places/{placeId}/media",
     *     tags={"Places"},
     *     summary="upload a media file for Place ID by AppUser AUTH TOKEN",
     *     description="upload a media file for Place ID by AppUser AUTH TOKEN",
     *     operationId="attachMediaToPlaces",
     *
     *     @OA\Parameter(in="path", name="placeId", required=true),
     *
     *     @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                  @OA\Property(
     *                      property="media",
     *                      type="string",
     *                      format="binary"
     *                  )
     *             )
     *         )
     *     ),
     *
     *      @OA\Response(
     *         response=200,
     *         description="successful operation",
     *
     *         @OA\Header(
     *             header="X-Rate-Limit",
     *             description="calls per hour allowed by the user",
     *
     *             @OA\Schema(
     *                 type="integer",
     *                 format="int32"
     *             )
     *         ),
     *
     *         @OA\Header(
     *             header="X-Expires-After",
     *             description="date in UTC when token expires",
     *
     *             @OA\Schema(
     *                 type="string",
     *                 format="datetime"
     *             )
     *         ),
     *
     *         @OA\JsonContent(
     *             type="object"
     *         )
     *     ),
     *
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
    public function attachMediaToPlace(
        Request $request,
        $placeId
    ) {
        try {
            $validator = Validator::make([
                ...$request->all(),
                'placeId' => $placeId,
            ], [
                'placeId' => 'required|string|exists:places,id',
                'media' => 'required|file|mimetypes:image/webp,image/png,image/jpeg,video/mp4,audio/mpeg,audio/x-wav',
            ]);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            /** @var Place $place */
            $place = Place::find($placeId);

            $place->detachMedia();
            $resourceType = str_contains(
                haystack: $request->file('media')->getMimeType(),
                needle: 'video/'
            ) ? 'video' : 'image';

            $place->fresh()
                ->attachMedia(
                    file: $request->file('media'),
                    options: [
                        'resource_type' => $resourceType,
                        'transformation' => [
                            'quality' => 'auto',
                            'fetch_format' => 'auto',
                        ],
                    ]
                );

            return $this->respondWithResource(new JsonResource($place));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    protected function googlePlacesNearbySearch(array $localhostGooglePlacesIds, string $location, int $radius = 50000, array $params = []): Collection
    {
        $googlePlacesResult = collect([]);
        $googlePlacesAPIResult = collect([]);
        $googlePlacesNextPage = false;
        $radius = $radius ?? 50000; // radius default

        do {
            try {
                $googlePlacesAPIResponse = $this->googlePlaces->nearbySearch($location, $radius, $params);

                $googlePlacesNextPage = $googlePlacesAPIResponse->get('next_page_token', false);
                $params['pagetoken'] = $googlePlacesNextPage;

                $googlePlacesAPIResponse = $googlePlacesAPIResponse->get('results', [])?->whereNotIn('place_id', $localhostGooglePlacesIds);
                $googlePlacesAPIResult = $googlePlacesAPIResult->concat($googlePlacesAPIResponse);

                if ($googlePlacesAPIResult !== null) {
                    $googlePlacesResult = $googlePlacesAPIResult->map(function ($item, $key) {
                        return collect(
                            [
                                'id' => null,
                                'google_place_id' => $item['place_id'],
                                'name' => $item['name'],
                                'place_type' => $item['types'],
                                'latitude' => $item['geometry']['location']['lat'],
                                'longitude' => $item['geometry']['location']['lng'],
                            ]);
                    });
                }
                if ($googlePlacesNextPage) {
                    sleep(2);
                }
            } catch (GooglePlacesApiException $e) {
                $googlePlacesNextPage = false;
            }
        } while (! empty($googlePlacesNextPage));

        return $googlePlacesResult;
    }

    /**
     * @param  string  $location
     * @param  int  $radius
     */
    protected function googlePlacesTextSearch(array $localhostGooglePlacesIds, string $query, array $params = []): Collection
    {
        $googlePlacesResult = collect([]);
        $googlePlacesAPIResult = collect([]);
        $googlePlacesNextPage = false;

        do {
            try {
                $googlePlacesAPIResponse = $this->googlePlaces->textSearch($query, $params);

                $googlePlacesNextPage = $googlePlacesAPIResponse->get('next_page_token', false);
                $params['pagetoken'] = $googlePlacesNextPage;

                $googlePlacesAPIResponse = $googlePlacesAPIResponse->get('results', [])?->whereNotIn('place_id', $localhostGooglePlacesIds);
                $googlePlacesAPIResult = $googlePlacesAPIResult->concat($googlePlacesAPIResponse);

                if ($googlePlacesAPIResult !== null) {
                    $googlePlacesResult = $googlePlacesAPIResult->map(function ($item, $key) {
                        return collect(
                            [
                                'id' => null,
                                'google_place_id' => $item['place_id'],
                                'name' => $item['name'],
                                'place_type' => $item['types'],
                                'latitude' => $item['geometry']['location']['lat'],
                                'longitude' => $item['geometry']['location']['lng'],
                                'address' => $item['formatted_address'],
                            ]);
                    });
                }
                if ($googlePlacesNextPage) {
                    sleep(2);
                }
            } catch (GooglePlacesApiException $e) {
                $googlePlacesNextPage = false;
            }
        } while (! empty($googlePlacesNextPage));

        return $googlePlacesResult;
    }
}
