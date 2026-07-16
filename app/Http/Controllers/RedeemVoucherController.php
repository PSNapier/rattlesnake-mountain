<?php

namespace App\Http\Controllers;

use App\Http\Requests\RedeemVoucherRequest;
use App\Services\WelcomePackageService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class RedeemVoucherController extends Controller
{
    public function __invoke(
        RedeemVoucherRequest $request,
        WelcomePackageService $welcomePackageService
    ): RedirectResponse {
        try {
            $welcomePackageService->redeemVoucher(
                $request->user(),
                $request->validated('voucher'),
                $request->validated('choice')
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['choice' => $exception->getMessage()]);
        }

        return back()->with('success', 'Voucher redeemed.');
    }
}
