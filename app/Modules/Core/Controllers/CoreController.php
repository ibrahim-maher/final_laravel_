<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\CoreService;
use Illuminate\Http\Request;

class CoreController extends Controller
{
    protected $coreService;

    public function __construct(CoreService $coreService)
    {
        $this->coreService = $coreService;
    }

    public function settings()
    {
        $settings = $this->coreService->getSystemSettings();
        return view('core.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $result = $this->coreService->updateSystemSettings($request->all());
        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    public function systemInfo()
    {
        $info = $this->coreService->getSystemInfo();
        return response()->json($info);
    }
}