<?php

namespace App\Http\Controllers;

use App\Services\ModuleManagerService;
use Illuminate\Http\Request;

class ModuleManagerController extends Controller
{
    public function __construct(private ModuleManagerService $moduleManagerService)
    {
    }

    public function index()
    {
        return response()->json([
            'modules' => $this->moduleManagerService->getAllModules(),
        ]);
    }

    public function show(string $alias)
    {
        $module = $this->moduleManagerService->getModule($alias);
        if (!$module) {
            return response()->json(['message' => 'Module not found'], 404);
        }
        return response()->json($module);
    }

    public function enable(string $alias)
    {
        $result = $this->moduleManagerService->enableModule($alias);
        if (!$result) {
            return response()->json(['message' => 'Module not found'], 404);
        }
        return response()->json([
            'message' => 'Module enabled successfully',
            'module' => $this->moduleManagerService->getModule($alias),
        ]);
    }

    public function disable(string $alias)
    {
        $result = $this->moduleManagerService->disableModule($alias);
        if (!$result) {
            return response()->json(['message' => 'Module not found'], 404);
        }
        return response()->json([
            'message' => 'Module disabled successfully',
            'module' => $this->moduleManagerService->getModule($alias),
        ]);
    }
}
