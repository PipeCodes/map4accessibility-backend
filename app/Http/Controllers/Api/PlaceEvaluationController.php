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
                    'place_id"' => 'int64',
                    'google_place_id"' => 'int64',
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

}
