<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\DemoWallet\Actions\EnsureDemoWallet;
use App\Domain\DemoWallet\Actions\ResetDemoAllowance;
use App\Domain\DemoWallet\Enums\DemoTransactionType;
use App\Domain\DemoWallet\Models\DemoWallet;
use App\Http\Controllers\Controller;
use App\Http\Resources\DemoWalletResource;
use App\Http\Resources\DemoWalletTransactionResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Demo wallets for the signed-in player. There are deliberately no deposit,
 * withdrawal, transfer, purchase or conversion endpoints.
 */
class DemoWalletController extends Controller
{
    public function index(Request $request, EnsureDemoWallet $ensure): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ensure($user);

        $wallets = $user->demoWallets()->orderBy('created_at')->get();

        return ApiResponse::success(DemoWalletResource::collection($wallets)->resolve($request));
    }

    public function transactions(Request $request, DemoWallet $wallet): JsonResponse
    {
        Gate::authorize('view', $wallet);

        $validated = $request->validate([
            'type' => ['nullable', Rule::enum(DemoTransactionType::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $page = $wallet->transactions()
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->orderByDesc('created_at')
            ->orderByDesc('reference')
            ->paginate((int) ($validated['per_page'] ?? 25));

        return ApiResponse::success(
            DemoWalletTransactionResource::collection($page->getCollection())->resolve($request),
            meta: ['pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ]],
        );
    }

    public function reset(Request $request, DemoWallet $wallet, ResetDemoAllowance $reset): JsonResponse
    {
        Gate::authorize('reset', $wallet);

        /** @var User $user */
        $user = $request->user();
        $reset($wallet, $user);

        return ApiResponse::success((new DemoWalletResource($wallet->refresh()))->resolve($request));
    }
}
