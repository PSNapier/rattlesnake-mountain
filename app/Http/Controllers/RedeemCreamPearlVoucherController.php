<?php

namespace App\Http\Controllers;

use App\Http\Requests\RedeemCreamPearlVoucherRequest;
use App\Services\WelcomePackageService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class RedeemCreamPearlVoucherController extends Controller
{
    public function __invoke(
        RedeemCreamPearlVoucherRequest $request,
        WelcomePackageService $welcomePackageService
    ): RedirectResponse {
        try {
            $welcomePackageService->redeemVoucher(
                $request->user(),
                $request->validated('choice')
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['choice' => $exception->getMessage()]);
        }

        return back()->with('success', 'Voucher redeemed.');
    }
}
