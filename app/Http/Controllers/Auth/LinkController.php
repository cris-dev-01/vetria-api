<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RequestLinkAction;
use App\Actions\Auth\VerifyLinkAction;
use App\Data\Auth\RequestLinkData;
use App\Data\Auth\VerifyLinkData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestLinkRequest;
use App\Http\Requests\Auth\VerifyLinkRequest;
use App\Http\Resources\Auth\AuthTokenResource;
use Illuminate\Http\JsonResponse;

class LinkController extends Controller
{
    public function request(RequestLinkRequest $request, RequestLinkAction $action): JsonResponse
    {
        $action->handle(RequestLinkData::from($request->validated()));

        return response()->json([
            'message' => 'Si el correo está registrado, recibirás un link de acceso.',
        ], 202);
    }

    public function verify(VerifyLinkRequest $request, VerifyLinkAction $action): AuthTokenResource
    {
        return new AuthTokenResource($action->handle(VerifyLinkData::from($request->validated())));
    }
}
