<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRoleCapabilitiesRequest;
use App\Services\RoleCapabilityService;
use Illuminate\Http\RedirectResponse;

class RoleCapabilityController extends Controller
{
    public function update(
        UpdateRoleCapabilitiesRequest $request,
        RoleCapabilityService $roleCapabilities,
    ): RedirectResponse {
        $roleCapabilities->sync($request->matrix());

        return redirect()->back()->with('success', 'Role capabilities updated.');
    }
}
