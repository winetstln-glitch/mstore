<?php

namespace App\Services;

use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Cache;

class ModuleManagerService
{
    public function getAllModules(): array
    {
        $modules = Module::all();
        $moduleData = [];

        foreach ($modules as $module) {
            $moduleData[] = [
                'name' => $module->getName(),
                'alias' => $module->getLowerName(),
                'description' => $module->get('description'),
                'version' => $module->get('version', '1.0.0'),
                'enabled' => $module->isEnabled(),
                'priority' => $module->get('priority'),
                'providers' => $module->get('providers', []),
                'path' => $module->getPath(),
            ];
        }

        return $moduleData;
    }

    public function getEnabledModules(): array
    {
        return $this->getAllModules();
    }

    public function enableModule(string $alias): bool
    {
        $module = Module::find($alias);
        if ($module) {
            $module->enable();
            Cache::forget('modules.status');
            return true;
        }
        return false;
    }

    public function disableModule(string $alias): bool
    {
        $module = Module::find($alias);
        if ($module) {
            $module->disable();
            Cache::forget('modules.status');
            return true;
        }
        return false;
    }

    public function getModule(string $alias): ?array
    {
        $module = Module::find($alias);
        if (!$module) {
            return null;
        }

        return [
            'name' => $module->getName(),
            'alias' => $module->getLowerName(),
            'description' => $module->get('description'),
            'enabled' => $module->isEnabled(),
            'priority' => $module->get('priority'),
            'providers' => $module->get('providers', []),
        ];
    }
}
