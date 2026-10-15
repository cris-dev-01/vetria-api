<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginAction;
use App\Data\Auth\LoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthTokenResource;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, LoginAction $action): AuthTokenResource
    {
        return new AuthTokenResource($action->handle(LoginData::from($request->validated())));
    }
}
