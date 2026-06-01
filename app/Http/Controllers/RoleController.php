<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage roles');

        $search = $request->filled('search') ? $request->search : null;

        // Load all roles with relations for tree building
        $allRoles = Role::with(['parent', 'children'])->withCount('users')->orderBy('level')->orderBy('name')->get();

        if ($search) {
            // On search: flat filtered list, no tree
            $filtered = $allRoles->filter(fn($r) =>
                str_contains(strtolower($r->name), strtolower($search)) ||
                str_contains(strtolower($r->description ?? ''), strtolower($search))
            );
            return view('role.index', [
                'title'       => 'Manajemen Roles',
                'breadcrumbs' => ['Dashboard' => route('dashboard'), 'Roles' => '#'],
                'roots'       => collect(),
                'flatRoles'   => $filtered,
                'search'      => $search,
            ]);
        }

        // Build recursive tree starting from root roles
        $roots = $allRoles->whereNull('parent_id')->values();

        return view('role.index', [
            'title'       => 'Manajemen Roles',
            'breadcrumbs' => ['Dashboard' => route('dashboard'), 'Roles' => '#'],
            'roots'       => $roots,
            'flatRoles'   => collect(),
            'search'      => null,
        ]);
    }

    public function create()
    {
        $this->authorize('manage roles');

        $parentRoles = Role::whereNull('parent_id')->get();
        $permissions = $this->permissionCatalog();
        
        return view('role.create', [
            'title' => 'Tambah Role',
            'breadcrumbs' => ['Dashboard' => route('dashboard'), 'Roles' => route('role.index'), 'Tambah' => '#'],
            'role' => null,
            'parentRoles' => $parentRoles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage roles');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'role_type' => 'nullable|in:engineer,approver,requester',
            'parent_id' => 'nullable|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($this->abilityKeys())],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $level = 1;
        
        if ($parentId) {
            $parent = Role::find($parentId);
            $level = $parent->level + 1;
        }

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'description' => $validated['description'],
            'role_type' => $validated['role_type'] ?? null,
            'parent_id' => $parentId,
            'level' => $level,
            'abilities' => array_values(array_unique($validated['permissions'] ?? [])),
        ]);

        return redirect()->route('role.index')->with('success', 'Role berhasil ditambahkan.');
    }

    public function edit(Role $role)
    {
        $this->authorize('manage roles');
        
        $parentRoles = Role::where('id', '!=', $role->id)
                           ->whereNull('parent_id')
                           ->orWhere('parent_id', '!=', $role->id)
                           ->get();

        $permissions = $this->permissionCatalog();
        $rolePermissions = $role->abilities ?? [];
        
        return view('role.edit', [
            'title' => 'Edit Role: ' . $role->name,
            'breadcrumbs' => ['Dashboard' => route('dashboard'), 'Roles' => route('role.index'), $role->name => '#'],
            'role' => $role,
            'parentRoles' => $parentRoles,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('manage roles');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:500',
            'role_type' => 'nullable|in:engineer,approver,requester',
            'parent_id' => 'nullable|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($this->abilityKeys())],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $level = 1;
        
        if ($parentId) {
            $parent = Role::find($parentId);
            $level = $parent->level + 1;
        }

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'role_type' => $validated['role_type'] ?? null,
            'parent_id' => $parentId,
            'level' => $level,
            'abilities' => array_values(array_unique($validated['permissions'] ?? [])),
        ]);

        return redirect()->route('role.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function show(Role $role)
    {
        $this->authorize('manage roles');

        $role->load('parent', 'children');
        $catalog = $this->permissionCatalog();
        $abilityMeta = [];
        foreach ($catalog as $category => $abilities) {
            foreach ($abilities as $ability => $label) {
                $abilityMeta[$ability] = ['category' => $category, 'label' => $label];
            }
        }

        $groupedAbilities = collect($role->abilities ?? [])->map(function ($ability) use ($abilityMeta) {
            return [
                'key' => $ability,
                'category' => $abilityMeta[$ability]['category'] ?? 'Lainnya',
                'label' => $abilityMeta[$ability]['label'] ?? $ability,
            ];
        })->groupBy('category');
        
        return view('role.show', [
            'title' => 'Detail Role: ' . $role->name,
            'breadcrumbs' => ['Dashboard' => route('dashboard'), 'Roles' => route('role.index'), $role->name => '#'],
            'role' => $role,
            'groupedAbilities' => $groupedAbilities,
        ]);
    }

    public function destroy(Role $role)
    {
        $this->authorize('manage roles');
        
        if ($role->children()->exists()) {
            return redirect()->route('role.index')
                           ->with('error', 'Tidak dapat menghapus role yang memiliki sub-role.');
        }

        if ($role->users()->exists()) {
            return redirect()->route('role.index')
                           ->with('error', 'Tidak dapat menghapus role yang masih digunakan user.');
        }

        $role->delete();
        
        return redirect()->route('role.index')->with('success', 'Role berhasil dihapus.');
    }

    private function permissionCatalog(): array
    {
        return config('rbac.permissions', []);
    }

    private function abilityKeys(): array
    {
        $keys = [];
        foreach ($this->permissionCatalog() as $abilities) {
            $keys = array_merge($keys, array_keys($abilities));
        }

        return $keys;
    }
}
