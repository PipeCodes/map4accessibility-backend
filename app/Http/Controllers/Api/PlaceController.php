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
     * Returns the validator for the endpoint
     * that lists places.
     * 
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
     * Returns a list of places within a certain radius,
     * given a center coordinates or a google_place_id.
     *
     * @OA\Schema(
     *     schema="requestPlacesObject",
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
     *     path="/places",
     *     tags={"Places"},
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     summary="filter for places for the given google_place_id OR coords",
     *     description="filter for places for the given google_place_id OR coords",
     *     operationId="listPlaces",
     *     @OA\RequestBody(
     *          @OA\JsonContent(
     *             ref="#/components/schemas/requestPlacesObject"
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
            $validator = $this->validatorListPlacesRequest($request);

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
}
