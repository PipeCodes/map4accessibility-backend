<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppUserResource;
use App\Http\Resources\AuthResource;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Traits\UploadTrait;
use App\Models\AppUser;
use App\Mail\EmailConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
     *     operationId="login",
     *     security={
     *          {"api_key_security": {}}
     *      },
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

            $user = AppUser::with('accountStatus')->where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->respondUnAuthorized();
            } elseif (!$user->accountStatus || 'active' !== $user->accountStatus->slug) {
                // Only AppUsers with confirmed email can login
                return $this->respondUnAuthorized(__('validation.appusers_confirmed_email'));
            }

            return $this->respondWithResource(new AuthResource(
                new AppUserResource($user),
                [
                    'token' => $user->createToken($user->email)->plainTextToken,
                    'type' => 'bearer',
                ],
            ));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     *
     * @OA\Schema(
     *     schema="requestLoginByProviderObject",
     *     type="object",
     *     @OA\Property(property="email", format="email"),
     *     @OA\Property(property="auth_type", example="facebook"),
     *     @OA\Property(type="string", property="auth_code", example="123")
     * )
     *
     * @OA\Post(
     *     path="/auth/login-by-provider",
     *     tags={"appUser"},
     *     summary="login provider AppUser",
     *     description="",
     *     operationId="loginByProvider",
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     @OA\RequestBody(
     *         description=" object",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/requestLoginByProviderObject")
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
     *          description="Invalid provider data supplied"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Internal error"
     *     ),
     * )
     */
    public function loginByProvider(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'email' => 'required|string|email',
                    'auth_type' => 'required|string',
                    'auth_code' => 'required|string',
                ]
            );

            if ($validate->fails()) {
                return $this->respondError($validate->errors(), 401);
            }

            $user = AppUser::with('accountStatus')
                ->where('email', $request->email)->where("auth_providers->$request->auth_type", $request->auth_code)
                ->first();

            if (!$user) {
                return $this->respondUnAuthorized();
            } elseif (!$user->accountStatus || 'active' !== $user->accountStatus->slug) {
                // Only AppUsers with confirmed email can login
                return $this->respondUnAuthorized(__('validation.appusers_confirmed_email'));
            }

            return $this->respondWithResource(new AuthResource(
                new AppUserResource($user),
                [
                    'token' => $user->createToken($user->email)->plainTextToken,
                    'type' => 'Bearer',
                ],
            ));
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     *
     *
     * @OA\Schema(
     *     schema="requestpasswordRecoverObject",
     *     type="object",
     *     @OA\Property(property="email", format="email")
     * )
     *
     * @OA\Post(
     *     path="/auth/password-recover",
     *     tags={"appUser"},
     *     summary="password recover AppUser",
     *     description="",
     *     operationId="passwordRecover",
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     @OA\RequestBody(
     *         description=" object",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/requestpasswordRecoverObject")
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
     *          description="Invalid API KEY supplied"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Internal error"
     *     ),
     * )
     */
    public function passwordRecover(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|string|email'
                ]
            );

            if ($validateUser->fails()) {
                return $this->respondError($validateUser->errors(), 401);
            }

            $status = Password::broker('app_users')->sendResetLink($request->only('email'));

            return $this->respondSuccess('success');
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     *
     *
     * @OA\Schema(
     *     schema="requestCheckEmailObject",
     *     type="object",
     *     @OA\Property(property="email", format="email")
     * )
     *
     * @OA\Post(
     *     path="/auth/check-email",
     *     tags={"appUser"},
     *     summary="check-email AppUser",
     *     description="",
     *     operationId="checkEmail",
     *     security={
     *          {"api_key_security": {}}
     *      },
     *     @OA\RequestBody(
     *         description=" object",
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/requestCheckEmailObject")
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
     *          response=409,
    *          description="Duplicate content"
     *     ),
     *     @OA\Response(
     *          response=401,
     *          description="Invalid API KEY supplied"
     *     ),
     *     @OA\Response(
     *          response=400,
     *          description="Internal error"
     *     ),
     * )
     */
    public function checkEmail(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email|unique:app_users,email'
                ]
            );

            if ($validateUser->fails()) {
                return $this->respondErrorDuplicate();
            }

            return $this->respondSuccess('email not found');
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
     *     operationId="register",
     *     security={
     *          {"api_key_security": {}}
     *      },
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
                    'surname' => 'required|string|max:255',
                    'birthdate' => 'date|max:255',
                    'email' => 'required|string|email|max:255|unique:users',
                    'password' => 'required_without:auth_providers|string|min:6',
                    'disabilities' => 'array',
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

            AppUser::create(
                array_merge(
                    $request->all(),
                    [
                        'password' => Hash::make($request->password)
                    ]
                )
            );

            // we need this to access the relationships
            $user = AppUser::where('email', $request->email)->first();

            Mail::to($user->email)->send(new EmailConfirmation($user));

            return $this->respondWithResource(new AuthResource(
                new AppUserResource($user),
                [
                    'token' => $user->createToken($request->email)->plainTextToken,
                    'type' => 'Bearer',
                ],
            ), __('api.create_user_success'), 201);
        } catch (\Throwable $th) {
            return $this->respondInternalError($th->getMessage());
        }
    }

    /**
     * @OA\Get (
     *     path="/auth/profile",
     *     tags={"appUser"},
     *     summary="Get AppUser Data",
     *     description="",
     *     operationId="getAuthenticated",
     *      @OA\Response(
     *         response=200,
     *         description="successful get data",
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
     *         @OA\JsonContent(ref="#/components/schemas/AppUser")
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
}
