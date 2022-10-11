<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppUserResource;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Traits\UploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AppUserController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function getAuthenticated(Request $request)
    {
        try {
            return $this->respondWithResource(new AppUserResource(
                $request->user()
            ));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    public function editAuthenticated(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:255',
                    'old_password' => 'string|min:6',
                    'new_password' => 'string|min:6',
                    'avatar' => 'image|mimes:jpg,jpeg,png|max:2048',
                ]
            );

            if ($validateUser->fails()) {
                return $this->respondError($validateUser->errors(), 401);
            }

            $authenticatedUser = $request->user();
            $authenticatedUser->name = $request->name;

            if ($request->has('old_password') && $request->has('new_password')) {
                // Can be added a extra verification just to check if the new password is differente from the old one
                $newPassword = Hash::make($request->old_password);
                if ($authenticatedUser->password === $newPassword) {
                    $authenticatedUser->password = $newPassword;
                } else {
                    return $this->respondError(__('api.password'), 401);
                }
            }

            if ($request->has('avatar')) {
                $authenticatedUser->avatar = $this->upload($request, 'avatar', 'images/app-users');
            }

            $authenticatedUser->save();

            return $this->respondWithResource(new AppUserResource($authenticatedUser), __('api.update_user_success'), 200);
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }
}
