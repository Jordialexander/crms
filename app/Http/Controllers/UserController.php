<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view user');
        $query = User::with('roles');
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q)=>$q->where('name','like',"%{$s}%")->orWhere('username','like',"%{$s}%")->orWhere('email','like',"%{$s}%"));
        }
        $users = $query->latest()->paginate(15)->withQueryString();
        return view('user.index', [
            'title'       => 'Manajemen User',
            'breadcrumbs' => ['Dashboard'=>route('dashboard'),'User'=>'#'],
            'users'       => $users,
        ]);
    }

    public function create()
    {
        $this->authorize('create user');
        $roles = Role::with('parent')->orderBy('level')->orderBy('name')->get();
        return view('user.create', [
            'title'       => 'Tambah User',
            'breadcrumbs' => ['Dashboard'=>route('dashboard'),'User'=>route('user.index'),'Tambah'=>'#'],
            'roles'       => $roles,
            'user'        => null,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create user');
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'username'  => 'required|string|max:100|unique:users',
            'email'     => 'nullable|email|unique:users',
            'password'  => 'required|string|min:8|confirmed',
            'role'      => 'required|exists:roles,name',
            'is_active' => 'nullable|boolean',
            'notify_email' => 'nullable|boolean',
        ]);
        $user = User::create([
            'name'      => $validated['name'],
            'username'  => $validated['username'],
            'email'     => $validated['email'] ?? null,
            'password'  => Hash::make($validated['password']),
            'role'      => $validated['role'],
            'is_active' => $request->boolean('is_active',true),
            'notify_email' => $request->boolean('notify_email', true),
        ]);
        $user->assignRole($validated['role']);
        return redirect()->route('user.index')->with('success','User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->authorize('edit user');
        $roles = Role::with('parent')->orderBy('level')->orderBy('name')->get();
        return view('user.edit', [
            'title'       => 'Edit User',
            'breadcrumbs' => ['Dashboard'=>route('dashboard'),'User'=>route('user.index'),$user->name=>'#'],
            'roles'       => $roles,
            'user'        => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('edit user');
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'username'  => 'required|string|max:100|unique:users,username,'.$user->id,
            'email'     => 'nullable|email|unique:users,email,'.$user->id,
            'password'  => 'nullable|string|min:8|confirmed',
            'role'      => 'required|exists:roles,name',
            'is_active' => 'nullable|boolean',
            'notify_email' => 'nullable|boolean',
        ]);
        $data = [
            'name'      => $validated['name'],
            'username'  => $validated['username'],
            'email'     => $validated['email'] ?? null,
            'role'      => $validated['role'],
            'is_active' => $request->boolean('is_active',true),
            'notify_email' => $request->boolean('notify_email', true),
        ];
        if (!empty($validated['password'])) $data['password'] = Hash::make($validated['password']);
        $user->update($data);
        $user->syncRoles([$validated['role']]);
        return redirect()->route('user.index')->with('success','User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete user');
        $currentUser = Auth::user();
        $currentUserId = $currentUser?->getAuthIdentifier();
        if ($user->getKey() === $currentUserId) {
            return redirect()->route('user.index')->with('error','Tidak dapat menghapus akun sendiri.');
        }
        $user->delete();
        return redirect()->route('user.index')->with('success','User berhasil dihapus.');
    }
}
