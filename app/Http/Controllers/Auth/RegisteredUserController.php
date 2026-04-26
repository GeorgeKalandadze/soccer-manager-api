<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Country;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly TeamService $teamService) {}

    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
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
