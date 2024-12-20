<?php

namespace App\Http\Controllers\Api;

use App\Actions\Place\UpdateEvaluationScore;
use App\Helper\Evaluation;
use App\Helper\PlaceEvaluationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceEvaluationCollection;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Traits\UploadTrait;
use App\Mail\NegativeRate;
use App\Mail\NegativeRateCounty;
use App\Models\AppUser;
use App\Models\CountryResponsible;
use App\Models\CountyEmails;
use App\Models\Place;
use App\Models\PlaceEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PlaceEvaluationController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;

    /**
     * @OA\Post (
     *     path="/place-evaluations/{placeEvaluationId}/media",
     *     tags={"PlaceEvaluation"},
     *     summary="upload a media file for Place Evaluation ID by AppUser AUTH TOKEN",
     *     description="upload a media file for Place Evaluation ID by AppUser AUTH TOKEN",
     *     operationId="attachMediaPlaceEvaluationByAuthenticated",
     *
     *     @OA\Parameter(in="path", name="placeEvaluationId",required=true),
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
    public function attachMediaPlaceEvaluationByAuthenticated(
        Request $request,
        $placeEvaluationId
    ) {
        try {
            $validate = Validator::make(
                array_merge($request->all(), ['placeEvaluationId' => $placeEvaluationId]),
                [
                    'placeEvaluationId' => 'required|string|exists:place_evaluations,id',
                    'media' => 'file|mimetypes:image/webp,image/png,image/jpeg,video/mp4,audio/mpeg,audio/x-wav',
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
                                    'fetch_format' => 'auto',
                                ],
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
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorCreatePlaceEvaluation(Request $request)
    {
        return Validator::make(
            $request->all(),
            [
                'latitude' => ['required', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
                'longitude' => ['required', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
                'google_place_id' => 'string|nullable',
                'name' => 'string|min:3|required',
                'place_type' => 'string|nullable',
                'country_code' => 'string|min:2|nullable',
                'city' => 'string|nullable',
                'address' => 'string|nullable',
                'phone' => 'string|nullable',
                'email' => 'string|email|nullable',
                'website' => 'string|url|nullable',
                'schedule' => 'string|nullable',
                'evaluation' => 'required|in:'.implode(',', Evaluation::values()),
                'comment' => 'string|min:6|nullable',
                'questions_answers' => 'nullable',
                'disabilities' => 'array',
            ]
        );
    }

    /**
     * Returns the validator for the endpoint
     * that is used to delete a Place Evaluation.
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorDeletePlaceEvaluation(Request $request)
    {
        return Validator::make(
            $request->all(),
            [
                'commentId' => ['required'],
            ]
        );
    }

    /**
     * Returns the validator for the endpoint
     * that lists place evaluations.
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function validatorListPlaceEvaluationsByRadiusRequest(
        Request $request
    ) {
        return Validator::make(
            $request->all(),
            [
                'latitude' => [
                    'required',
                    'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/',
                ],
                'longitude' => [
                    'required',
                    'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/',
                ],
                'geo_query_radius' => ['required', 'integer', 'min:1'],
                'country_code' => [
                    'string',
                    'exists:places,country_code',
                ],
                'name' => ['string'],
                'place_type' => ['string'],
                'asc_order_by' => [
                    'string',
                    'in:evaluation,comment,created_at,updated_at',
                ],
                'desc_order_by' => [
                    'exclude_with:asc_order_by',
                    'string',
                    'in:evaluation,comment,created_at,updated_at',
                ],
                'page' => ['integer', 'min:1'],
                'size' => ['integer', 'min:1'],
            ]
        );
    }

    /**
     * Creates a new PlaceEvaluation and also a
     * new Place, in case it does not exist yet
     * in the database.
     *
     * @OA\Schema(
     *      schema="CreatePlaceEvaluationRequest",
     *      type="object",
     *      required={"latitude", "longitude", "name", "evaluation"},
     *
     *      @OA\Property(
     *          property="latitude",
     *          format="decimal",
     *          description="Latitude",
     *          title="Latitude",
     *      ),
     *      @OA\Property(
     *          property="longitude",
     *          format="decimal",
     *          description="Longitude",
     *          title="Longitude",
     *      ),
     *      @OA\Property(
     *          property="google_place_id",
     *          description="Google Place ID",
     *          title="Google Place ID",
     *          type="string"
     *      ),
     *      @OA\Property(
     *          property="name",
     *          description="Place Name",
     *          title="Place name",
     *          type="string",
     *      ),
     *      @OA\Property(
     *          property="place_type",
     *          description="Place Type",
     *          title="Place Type",
     *          type="string",
     *      ),
     *      @OA\Property(
     *          property="country_code",
     *          description="Country Code",
     *          title="Country Code",
     *          type="string",
     *      ),
     *      @OA\Property(
     *          property="city",
     *          description="Place City",
     *          title="Place City",
     *          type="string",
     *      ),
     *      @OA\Property(
     *          property="address",
     *          description="Place Address",
     *          title="Place Address",
     *          type="string",
     *      ),
     *      @OA\Property(
     *          property="phone",
     *          description="Place Phone",
     *          title="Place Phone",
     *          type="string",
     *      ),
     *      @OA\Property(
     *          property="email",
     *          description="Place Email",
     *          title="Place Email",
     *          format="email",
     *          type="string",
     *      ),
     *      @OA\Property(
     *          property="website",
     *          description="Place Website",
     *          title="Place Website",
     *          type="string",
     *      ),
     *      @OA\Property(
     *          property="schedule",
     *          description="Place Schedule",
     *          title="Place Schedule",
     *          type="string",
     *      ),
     *      @OA\Property(
     *          property="evaluation",
     *          type="integer",
     *          minimum=0,
     *          maximum=2,
     *          description="Evaluation (0 = Inaccessible, 1 = Neutral, 2 = Accessible)",
     *          title="Evaluation",
     *      ),
     *      @OA\Property(
     *          property="comment",
     *          description="Comment",
     *          title="Comment"
     *      ),
     *      @OA\Property(
     *          property="questions_answers",
     *          type="object",
     *          description="Questions Answers JSON",
     *          title="Questions Answers JSON",
     *          example={}
     *      ),
     *)
     *
     * @OA\Post(
     *     path="/place-evaluations",
     *     tags={"PlaceEvaluation"},
     *     summary="Create Place Evaluation by AppUser AUTH TOKEN",
     *     description="Create Place Evaluation by AppUser AUTH TOKEN",
     *     operationId="placeEvaluationByAuthenticated",
     *
     *     @OA\RequestBody(
     *
     *          @OA\JsonContent(
     *             ref="#/components/schemas/CreatePlaceEvaluationRequest"
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
     *             @OA\Property(title="result",property="result",type="object",ref="#/components/schemas/PlaceEvaluation"),
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
    public function createPlaceEvaluation(Request $request)
    {
        try {
            $validator = $this->validatorCreatePlaceEvaluation($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $appUser = auth()->user();
            if (! $appUser) {
                return $this->respondNotFound();
            }

            $latitude = number_format($request->get('latitude'), 8);
            $longitude = number_format($request->get('longitude'), 8);

            /**
             * Searches for a place with those coordinates, and if it
             * does not exist, creates it.
             */
            $place = Place::query()
                ->where('latitude', $latitude)
                ->where('longitude', $longitude)
                ->first();

            if (! $place) {
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
            }

            /**
             * Creates the new PlaceEvaluation.
             *
             * @var PlaceEvaluation $placeEvaluation
             */
            $placeEvaluation = PlaceEvaluation::create([
                ...$request->only([
                    'evaluation', 'comment', 'questions_answers',
                ]),
                'app_user_id' => $appUser->id,
                'place_id' => $place->id,
                'status' => PlaceEvaluationStatus::Accepted->value,
            ]);

            /**
             * Sets the new evaluation score of the place in question
             * based on this new evaluation.
             */
            $place = (new UpdateEvaluationScore)($place, $placeEvaluation, $request->get('disabilities') ? $request->get('disabilities') : []);

            /**
             * In case the evaluation is negative, send emails
             * to all responsible people of that place.
             */
            if (
                $placeEvaluation->evaluation
                    === Evaluation::Inaccessible
            ) {

                $listResponsibles = CountryResponsible::query()
                    ->where('country_iso', $place->country_code)
                    ->get()
                    ->pluck('email')
                    ->toArray();

                if (count($listResponsibles) > 0) {
                    Mail::to($listResponsibles)
                        ->send(new NegativeRate($placeEvaluation, $place->email));
                }

                $listEmails = CountyEmails::query()
                    ->where('county_iso', $request->get('county'))
                    ->get()
                    ->pluck('email')
                    ->toArray();

                if (count($listEmails) > 0) {
                    Mail::to($listEmails)
                        ->send(new NegativeRateCounty($placeEvaluation, $place->email));
                }
            }

            return $this->respondWithResource(
                new JsonResource($placeEvaluation->load(['appUser', 'place']))
            );
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * Deletes a PlaceEvaluation
     *
     * @OA\Schema(
     *      schema="DeletePlaceEvaluationRequest",
     *      type="object",
     *      required={"commentId"},
     *
     *      @OA\Property(
     *          property="commentId",
     *          format="decimal",
     *          description="Place Evaluation Id",
     *          title="Place Evaluation Id",
     *      ),
     *)
     *
     * @OA\Delete(
     *     path="/place-evaluations",
     *     tags={"PlaceEvaluation"},
     *     summary="Delete a Place Evaluation by AppUser AUTH TOKEN",
     *     description="Delete a Place Evaluation by AppUser AUTH TOKEN",
     *     operationId="placeEvaluationByAuthenticated",
     *
     *     @OA\RequestBody(
     *
     *          @OA\JsonContent(
     *             ref="#/components/schemas/DeletePlaceEvaluationRequest"
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
     *             @OA\Property(title="result",property="result",type="object",ref="#/components/schemas/PlaceEvaluation"),
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
    public function deletePlaceEvaluation(Request $request)
    {
        try {
            $validator = $this->validatorDeletePlaceEvaluation($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 422);
            }

            $appUser = auth()->user();
            if (! $appUser) {
                return $this->respondNotFound();
            }

            /**
             * Deletes the PlaceEvaluation.
             *
             * @var PlaceEvaluation $placeEvaluation
             */
            PlaceEvaluation::where('id', $request->commentId)
                ->where('app_user_id', $appUser->id)
                ->delete();

            return $this->respondSuccess('place evaluation deleted');
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * @OA\Get (
     *     path="/place-evaluations",
     *     tags={"PlaceEvaluation"},
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     summary="filter for evaluations for the given google_place_id OR coords, place",
     *     description="filter for evaluations for the given google_place_id OR coords, place",
     *     operationId="placeEvaluations",
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
     *         example="evaluation"
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="desc_order_by",
     *         description="Parameter to sort by DESC",
     *         example="evaluation"
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
     *                     @OA\Items(type="object",ref="#/components/schemas/PlaceEvaluation")
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
     */
    public function listPlaceEvaluations(Request $request)
    {
        try {
            $validator =
                $this->validatorListPlaceEvaluationsByRadiusRequest($request);

            if ($validator->fails()) {
                return $this->respondError($validator->errors(), 401);
            }

            $query = PlaceEvaluation::query()
                ->select('*')
                ->with('place', 'appUser')
                ->whereHas('place', function ($query) use ($request) {
                    $this->queryListPlaceEvaluation($request, $query);
                });

            if ($request->has('asc_order_by')) {
                $query->orderBy($request->asc_order_by, 'asc');
            } elseif ($request->has('desc_order_by')) {
                $query->orderBy($request->desc_order_by, 'desc');
            }

            $query->paginate(
                $request->get('size', 20),
                ['*'],
                'page'
            )->withQueryString();

            $countEvaluations = collect([
                'total_accessible' => (clone $query)->where('evaluation', Evaluation::Accessible->value)->count(),
                'total_neutral' => (clone $query)->where('evaluation', Evaluation::Neutral->value)->count(),
                'total_inaccessible' => (clone $query)->where('evaluation', Evaluation::Inaccessible->value)->count(),
            ]);

            $result = $countEvaluations->merge(
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
     * @param  Request|null  $request
     * @return Builder
     */
    protected function queryListPlaceEvaluation(
        Request $request,
        Builder $query = null
    ) {
        $query = $query ?: PlaceEvaluation::query()->select('*');

        if (
            $request->has('latitude') && $request->get('latitude') !== '' &&
            $request->has('longitude') && $request->get('longitude') !== ''
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
        }

        if ($request->has('google_place_id')) {
            $query->where(
                'google_place_id',
                $request->get('google_place_id')
            );
        }

        if (
            $request->has('country_code') &&
            $request->get('country_code') !== ''
        ) {
            $query->where('country_code', $request->get('country_code'));
        }

        if (
            $request->has('name') &&
            $request->get('name') !== ''
        ) {
            $query->where(
                'name', 'like', '%'.$request->get('name').'%'
            );
        }

        if (
            $request->has('place_type') &&
            $request->get('place_type') !== ''
        ) {
            $query->where(
                'place_type', 'like', '%'.$request->get('place_type').'%'
            );
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
     *
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
     *                     @OA\Items(type="object",ref="#/components/schemas/PlaceEvaluation")
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
     */
    public function listPlaceEvaluationsByAppUser(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'page' => ['integer', 'min:1'],
            'size' => ['integer', 'min:1'],
        ]);

        if ($validate->fails()) {
            return $this->respondError($validate->errors(), 422);
        }

        $appUser = $request->user();

        if (! $appUser) {
            return $this->respondNotFound();
        }

        $query = PlaceEvaluation::where('app_user_id', $appUser->id)
            ->latest()
            ->with(['place' => function ($query) {
                $query->with(['mediaEvaluations' => function ($query) {
                    $query->select('file_url', 'file_type');
                }]);
            }]);

        $counts = collect([
            'total_accessible' => (clone $query)->where('evaluation', Evaluation::Accessible->value)->count(),
            'total_neutral' => (clone $query)->where('evaluation', Evaluation::Neutral->value)->count(),
            'total_inaccessible' => (clone $query)->where('evaluation', Evaluation::Inaccessible->value)->count(),
            'total_accepted' => (clone $query)->where('status', PlaceEvaluationStatus::Accepted->value)->count(),
            'total_rejected' => (clone $query)->where('status', PlaceEvaluationStatus::Rejected->value)->count(),
            'total_pending' => (clone $query)->where('status', PlaceEvaluationStatus::Pending->value)->count(),
        ]);

        $result = $counts->merge(
            $query->paginate(
                $request->get('size', 20),
                ['*'],
                'page'
            )->withQueryString()
        );

        return $this->respondWithResource(
            new PlaceEvaluationCollection(
                $result
            )
        );
    }
}
