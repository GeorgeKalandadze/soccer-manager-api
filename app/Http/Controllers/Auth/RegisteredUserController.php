<?php

namespace App\Http\Controllers\Auth;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Country;
use App\Services\TeamService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TeamService $teamService,
    ) {}

    public function store(RegisterRequest $request): JsonResponse
    {
        $user = $this->userRepository->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $country = Country::findOrFail($request->country_id);
        $this->teamService->createWithPlayers($user, $country);

        event(new Registered($user));

        Auth::login($user);

        return response()->json([
            'message' => 'User registered successfully.',
        ]);
    }
}
