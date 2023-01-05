<?php

namespace App\Http\Controllers\Api;

use App\Helper\PlaceDeletionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\PlaceDeletion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;

class PlaceDeletionController extends Controller
{
    use ApiResponseTrait;

    /**
     * Returns the validator for the endpoint
     * that is used to create a new Place Deletion.
     *
     * @param  Request  $request
     * @return \Illuminate\Validation\Validator
     */
    protected function validator(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'place_id' => 'required|exists:places,id',
            'app_user_id' => 'required|exists:app_users,id',
        ]);
    }

    /**
     * Handles the incoming request and redirects it to the proper action.
     *
     * @OA\Post(
     *     path="/places/delete",
     *     tags={"Places"},
     *     summary="Creates a deletion for a Place",
     *     description="Creates a deletion for a Place",
     *     operationId="deletePlace",
     *     @OA\RequestBody(
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                  property="place_id",
     *                  description="Place id",
     *                  title="Place id",
     *                  example="",
     *                  type="integer"
     *             ),
     *             @OA\Property(
     *                  property="app_user_id",
     *                  description="App User id",
     *                  title="App User id",
     *                  example="",
     *                  type="integer"
     *             ),
     *         ),
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
     *             type="object",
     *             @OA\Property(type="boolean",title="success",property="success",example="true",readOnly="true"),
     *             @OA\Property(type="string",title="message",property="message",example="null",readOnly="true"),
     *             @OA\Property(title="result",property="result",type="object",ref="#/components/schemas/PlaceDeletion"),
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
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return $this->respondError($validator->errors(), 422);
        }

        $deletion = PlaceDeletion::query()
            ->where('place_id', $request->get('place_id'))
            ->where('app_user_id', $request->get('app_user_id'))
            ->first();

        if ($deletion) {
            return $this->closePlaceDeletion(deletion: $deletion);
        }

        return $this->storePlaceDeletion(
            placeId: $request->get('place_id'),
            appUserId: $request->get('app_user_id')
        );
    }

    /**
     * Creates a new pending place deletion.
     *
     * @param  int  $placeId
     * @param  int  $appUserId
     * @return \Illuminate\Http\JsonResponse
     */
    protected function storePlaceDeletion(
        int $placeId,
        int $appUserId
    ): \Illuminate\Http\JsonResponse {
        return $this->respondWithResource(
            new JsonResource(
                PlaceDeletion::create([
                    'place_id' => $placeId,
                    'app_user_id' => $appUserId,
                    'status' => PlaceDeletionStatus::Pending,
                ])
            )
        );
    }

    /**
     * Closes the PlaceDeletion and returns it.
     *
     * @param  PlaceDeletion  $deletion
     * @return \Illuminate\Http\JsonResponse
     */
    protected function closePlaceDeletion(
        PlaceDeletion $deletion
    ): \Illuminate\Http\JsonResponse {
        if ($deletion->status === PlaceDeletionStatus::Closed) {
            return $this->respondError(
                __('api.place_deletion_already_closed'), 422
            );
        }

        $result = $deletion->close();

        if (! $result) {
            return $this->respondError(
                __('api.place_deletion_already_closed'), 422
            );
        }

        return $this->respondWithResource(new JsonResource($result));
    }
}
