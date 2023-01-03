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
