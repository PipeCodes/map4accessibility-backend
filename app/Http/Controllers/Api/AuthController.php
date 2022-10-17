<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppUserResource;
use App\Http\Resources\AuthResource;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Traits\UploadTrait;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;

    /**
     *
     *
     * @OA\Schema(
     *     schema="requestLoginObject",
     *     type="object",
     *     @OA\Property(property="email", format="email"),
     *     @OA\Property(property="password")
     * )
     *
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"appUser"},
     *     summary="login AppUser",
     *     description="",
     *     operationId="loginUser",
     *     security={},
     *     @OA\RequestBody(
     *         description=" object",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/requestLoginObject")
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
    public function login(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|string|email',
                    'password' => 'required|string',
                ]
            );

            if ($validateUser->fails()) {
                return $this->respondError($validateUser->errors(), 401);
            }

            $user = AppUser::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->respondUnAuthorized();
            }

            return $this->respondWithResource(new AuthResource(
                new AppUserResource($user),
                [
                    'token' => $user->createToken($request->email)->plainTextToken,
                    'type' => 'bearer',
                ],
            ));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"appUser"},
     *     summary="Create AppUser",
     *     description="",
     *     operationId="createUser",
    *      security={"api_key_security"},
     *     @OA\RequestBody(
     *         description="Create AppUser object",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AppUser")
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
    public function register(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users',
                    'password' => 'required|string|min:6',
                    'avatar' => 'image|mimes:jpg,jpeg,png|max:2048',
                    'terms_accepted' => 'required',
                ]
            );

            if ($validateUser->fails()) {
                return $this->respondError($validateUser->errors(), 401);
            }

            $existsUser = AppUser::where('email', $request->email)->first();

            if (isset($existsUser)) {
                return $this->respondError(__('api.user_exists_error'), 409);
            }

            AppUser::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'terms_accepted' => $request->boolean('terms_accepted'),
                'avatar' => $this->upload($request, 'avatar', 'images/app-users'),
            ]);

            // we need this to access the relationships
            $user = AppUser::where('email', $request->email)->first();

            return $this->respondWithResource(new AuthResource(
                new AppUserResource($user),
                [
                    'token' => $user->createToken($request->email)->plainTextToken,
                    'type' => 'bearer',
                ],
            ), __('api.create_user_success'), 201);
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }
}
