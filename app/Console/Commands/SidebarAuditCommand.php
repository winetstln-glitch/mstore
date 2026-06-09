<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Support\Sidebar\SidebarMenu;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class SidebarAuditCommand extends Command
{
    protected $signature = 'sidebar:audit';

    protected $description = 'Audit konsistensi sidebar menu, route permission, dan permission database.';

    public function handle(): int
    {
        $menu = SidebarMenu::tree();
        $menuLeaves = $this->flattenMenuLeaves($menu);

        $warnings = 0;

        $this->line('Sidebar Audit');
        $this->newLine();

        $routePermissionMap = $this->routePermissionMap();
        $menuPermissionMap = $this->menuPermissionMap($menuLeaves);

        foreach ($menuLeaves as $leaf) {
            $routeName = $leaf['route'] ?? null;
            $menuPermissions = $leaf['permissions'] ?? [];
            $roles = $leaf['roles'] ?? [];

            if (! $routeName) {
                continue;
            }

            if (empty($menuPermissions) && empty($roles)) {
                $warnings++;
                $this->warn('[WARNING] Menu tanpa permission/role');
                $this->line($leaf['label'] ?? $routeName);
                $this->newLine();
                continue;
            }

            if (! array_key_exists($routeName, $routePermissionMap)) {
                $warnings++;
                $this->warn('[WARNING] Route menu tidak ditemukan');
                $this->line(($leaf['label'] ?? $routeName).': '.$routeName);
                $this->newLine();
                continue;
            }

            $routePermissions = $routePermissionMap[$routeName];
            if (empty($routePermissions) && ! empty($menuPermissions)) {
                $warnings++;
                $this->warn('[WARNING] Route tanpa permission');
                $this->line(($leaf['label'] ?? $routeName).': '.$routeName);
                $this->line('Sidebar: '.implode(', ', $menuPermissions));
                $this->line('Route: (none)');
                $this->newLine();
                continue;
            }

            if (! empty($menuPermissions) && ! empty($routePermissions)) {
                $menuSet = collect($menuPermissions)->unique()->values()->sort()->values()->all();
                $routeSet = collect($routePermissions)->unique()->values()->sort()->values()->all();

                if ($menuSet !== $routeSet) {
                    $warnings++;
                    $this->warn('[WARNING] Permission mismatch');
                    $this->line(($leaf['label'] ?? $routeName).': '.$routeName);
                    $this->line('Sidebar: '.implode(', ', $menuSet));
                    $this->line('Route: '.implode(', ', $routeSet));
                    $this->newLine();
                }
            }
        }

        $unused = $this->unusedPermissions($routePermissionMap, $menuPermissionMap);
        if ($unused->isNotEmpty()) {
            $warnings++;
            $this->warn('[WARNING] Permission tidak digunakan (route + sidebar)');
            foreach ($unused->sort()->values()->all() as $p) {
                $this->line('- '.$p);
            }
            $this->newLine();
        }

        $duplicates = $this->duplicatePermissions();
        if ($duplicates->isNotEmpty()) {
            $warnings++;
            $this->warn('[WARNING] Duplicate permission di database');
            foreach ($duplicates->sort()->values()->all() as $p) {
                $this->line('- '.$p);
            }
            $this->newLine();
        }

        $this->info($warnings === 0 ? 'OK' : 'DONE WITH WARNINGS');

        return $warnings === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function flattenMenuLeaves(array $menu): array
    {
        $leaves = [];

        foreach ($menu as $section) {
            foreach (($section['items'] ?? []) as $item) {
                $this->walkMenu($item, $leaves);
            }
        }

        return $leaves;
    }

    private function walkMenu(array $item, array &$leaves): void
    {
        if (($item['type'] ?? null) === 'link') {
            $leaves[] = $item;
            return;
        }

        foreach (($item['children'] ?? []) as $child) {
            $this->walkMenu($child, $leaves);
        }
    }

    private function routePermissionMap(): array
    {
        $map = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! is_string($name) || $name === '') {
                continue;
            }

            $middlewares = $route->gatherMiddleware();
            $permissions = [];

            foreach ($middlewares as $mw) {
                if (! is_string($mw)) {
                    continue;
                }

                if (str_starts_with($mw, 'permission:')) {
                    $raw = (string) substr($mw, strlen('permission:'));
                    $parts = array_filter(array_map('trim', explode('|', $raw)));
                    $permissions = array_merge($permissions, $parts);
                }
            }

            $map[$name] = array_values(array_unique($permissions));
        }

        return $map;
    }

    private function menuPermissionMap(array $menuLeaves): array
    {
        $used = [];
        foreach ($menuLeaves as $leaf) {
            foreach (($leaf['permissions'] ?? []) as $p) {
                $used[$p] = true;
            }
        }

        return $used;
    }

    private function unusedPermissions(array $routePermissionMap, array $menuPermissionMap): Collection
    {
        if (! Schema::hasTable('permissions')) {
            return collect();
        }

        $used = collect(array_keys($menuPermissionMap));
        foreach ($routePermissionMap as $perms) {
            foreach ($perms as $p) {
                $used->push($p);
            }
        }
        $used = $used->unique()->filter()->values();

        $dbPermissions = Permission::query()->pluck('name')->filter()->values();

        return $dbPermissions->diff($used);
    }

    private function duplicatePermissions(): Collection
    {
        if (! Schema::hasTable('permissions')) {
            return collect();
        }

        return Permission::query()
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name')
            ->filter()
            ->values();
    }
}
