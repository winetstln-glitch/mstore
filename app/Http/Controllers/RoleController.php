<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:role.view', only: ['index']),
            new Middleware('permission:role.create', only: ['create', 'store']),
            new Middleware('permission:role.edit', only: ['edit', 'update']),
            new Middleware('permission:role.delete', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::withCount('users')->latest()->paginate(10);
        return view('roles.index', compact('roles'));
    }

    private function getStandardPermissions()
    {
        $allPermissions = Permission::all();
        
        $nocGroups = [
            'Dashboard', 'Customer Management', 'Ticket Management', 'Router Management', 
            'OLT Management', 'ODC Management', 'ODP Management', 'HTB Management', 
            'PPPoE', 'Radius', 'Map', 'Network Monitor', 'Profile', 'Notification'
        ];

        $technicianNames = [
            'dashboard.view', 'ticket.view', 'ticket.edit', 'installation.view', 'installation.edit',
            'attendance.view', 'attendance.create', 'attendance.report', 'map.view', 
            'odp.view', 'odp.edit', 'odc.edit', 'leave.view', 'leave.create', 'schedule.view', 
            'profile.view', 'profile.update', 'notification.view', 'notification.manage',
            'inventory.view', 'inventory.pickup'
        ];

        $coordinatorNames = [
            'dashboard.view', 'inventory.view', 'inventory.pickup', 'map.view', 
            'profile.view', 'profile.update', 'notification.view', 'notification.manage', 
            'finance.view', 'pppoe.view', 'pppoe.manage', 'hotspot.view', 'hotspot.manage'
        ];
        
        // Finance: Full Management for some, View for others
        $financeManageGroups = ['Finance', 'Investor Management', 'Package Management', 'Inventory (Alat & Material)', 'Profile', 'Notification', 'ATK Cashier'];
        $financeViewNames = [
            'dashboard.view', 'customer.view', 'ticket.view', 'installation.view', 
            'technician.view', 'coordinator.view', 'region.view', 'attendance.view', 
            'attendance.report', 'leave.view', 'schedule.view', 'map.view', 'olt.view', 
            'odc.view', 'odp.view', 'htb.view', 'router.view', 'genieacs.view', 
            'chat.view', 'telegram.view', 'calculator.view', 'setting.view',
            'atk.view', 'atk.report'
        ];

        // Management: View All, Manage most business aspects, restricted from System admin
        $managementGroups = [
            'Dashboard', 'Customer Management', 'Ticket Management', 'Installation Management',
            'Technician Management', 'Attendance', 'ODC Management', 'ODP Management',
            'HTB Management', 'OLT Management', 'Router Management', 'Finance',
            'Hotspot', 'PPPoE', 'Map', 'Leave Management', 'Schedule Management', 'Network Monitor',
            'Inventory (Alat & Material)', 'Coordinator Management', 'Investor Management',
            'Region Management', 'Package Management', 'Utilities', 'Profile', 'Notification',
            'ATK Cashier', 'Car Wash', 'WhatsApp', 'Telegram'
        ];

        return [
            'Administrator' => $allPermissions->pluck('id')->values()->toArray(),
            'Management' => $allPermissions->whereIn('group', $managementGroups)->pluck('id')->values()->toArray(),
            'Network Operations Center' => $allPermissions->whereIn('group', $nocGroups)->pluck('id')->values()->toArray(),
            'Technician' => $allPermissions->whereIn('name', $technicianNames)->pluck('id')->values()->toArray(),
            'Coordinator' => $allPermissions->whereIn('name', $coordinatorNames)->pluck('id')->values()->toArray(),
            'Finance Staff' => $allPermissions->filter(function($perm) use ($financeManageGroups, $financeViewNames) {
                return in_array($perm->group, $financeManageGroups) || in_array($perm->name, $financeViewNames);
            })->pluck('id')->values()->toArray(),
            'Customer' => [],
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::all()->groupBy('group');
        $standardPermissions = $this->getStandardPermissions();
        return view('roles.create', compact('permissions', 'standardPermissions'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $name = Str::slug($validated['label']);
        
        if (Role::where('name', $name)->exists()) {
            return back()->withErrors(['label' => 'A role with this name (slug: ' . $name . ') already exists. Please choose a different label.'])->withInput();
        }

        $role = Role::create([
            'name' => $name,
            'label' => $validated['label'],
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', __('Role created successfully.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy('group');
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        $standardPermissions = $this->getStandardPermissions();
        
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions', 'standardPermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Don't update name for admin role to prevent system lockout
        // For all roles, we only update the label, not the internal name (slug) to maintain consistency
        $role->update([
            'label' => $validated['label'],
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('roles.index')->with('success', __('Role updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return back()->with('error', __('Cannot delete Admin role.'));
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', __('Cannot delete role that is assigned to users.'));
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', __('Role deleted successfully.'));
    }
}
