<?php

namespace App\Http\Controllers\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Profile\ChangePasswordRequest;
use App\Http\Requests\V1\Profile\UpdateProfileRequest;
use App\Http\Resources\V1\Profile\UserProfileResource;
use App\Services\Profile\ChangePasswordRequestDTO;
use App\Services\Profile\ProfileService;
use App\Services\Profile\UserUpdateProfileRequestDTO;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(private readonly ProfileService $userService) {}

    /**
     * @OA\Patch(
     *     path="/api/v1/profile/change-password",
     *     summary="Change User Password",
     *     description="Allow the user to change their password.",
     *     operationId="changePassword",
     *     tags={"Profile"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/ChangePasswordRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", example="Password reset successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid current password or new password validation failure.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", example="Invalid current password.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties={"type": "array", "items": {"type": "string"}}
     *             )
     *         )
     *     )
     * )
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $validated = $request->validated();

        $this->userService->changePassword(
            resolve(ChangePasswordRequestDTO::class)
                ->setUserId(Auth::id())
                ->setNewPassword($validated['new_password'])
                ->setOldPassword($validated['old_password'])
        );

        return response([
            'status' => __('passwords.reset'),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/profile/update-profile",
     *     summary="Update User Profile",
     *     description="Allow the user to update their profile information.",
     *     operationId="updateProfile",
     *     tags={"Profile"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/UpdateProfileRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", example="Profile updated successfully.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response=422,
     *          description="Validation error",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="The given data was invalid."),
     *              @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *          )
     *      ),
     * )
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $validated = $request->validated();
        $this->userService->updateProfile(
            resolve(UserUpdateProfileRequestDTO::class)
                ->setUserId(Auth::id())
                ->setFirstName($validated['first_name'])
                ->setLastName($validated['last_name'])
                ->setMobile($validated['mobile'])
                ->setNationalCode($validated['national_code'])

        );

        return response([
            'status' => __('user.profile.updated'),
            'data' => new UserProfileResource(Auth::user())
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/profile/show",
     *     summary="Get User Profile",
     *     description="Retrieve the profile information of the authenticated user.",
     *     tags={"Profile"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully.",
     *
     *         @OA\JsonContent(ref="#/components/schemas/UserProfileResource")
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function show()
    {
        return new UserProfileResource(Auth::user());
    }
}
