# RBAC (Role-Based Access Control) - Fitur Dinamis & Fleksibel

## Deskripsi

Sistem RBAC yang telah diupdate mendukung:
- **Role Hierarchy**: Roles dapat memiliki parent-child relationship untuk organisasi yang lebih baik
- **Dynamic Permissions**: Permissions dapat dibuat dan dimodifikasi kapan saja tanpa hardcoding
- **Granular Control**: Admin dapat mengatur permissions untuk setiap role secara independen
- **Role Categorization**: Permissions dikelompokkan berdasarkan kategori untuk kemudahan management

## Database Schema

### roles table (Extended)
```sql
- id: bigint (Primary Key)
- name: string (unique dengan guard_name)
- guard_name: string (default: 'web')
- parent_id: bigint nullable (Foreign Key ke roles.id)
- description: string nullable
- level: integer (default: 1)
- created_at, updated_at, deleted_at
```

### permissions table (Extended)
```sql
- id: bigint (Primary Key)
- name: string (unique dengan guard_name)
- guard_name: string (default: 'web')
- description: string nullable
- category: string nullable
- created_at, updated_at, deleted_at
```

## Models

### App\Models\Role
Extends `Spatie\Permission\Models\Role` dengan menambahkan:

**Relationships:**
- `parent()` - Parent role (jika ada)
- `children()` - Sub-roles (jika ada)
- `ancestors()` - Koleksi semua parent roles
- `descendants()` - Koleksi semua child roles

**Scopes:**
- `roots()` - Hanya roles tanpa parent
- `withAncestors()` - Include parent dan ancestors

**Attributes:**
- `hierarchy` - Array ancestors dari root sampai current role

**Methods:**
```php
$role->parent; // Get parent role
$role->children; // Get sub-roles
$role->ancestors(); // Get all parent roles
$role->descendants(); // Get all child roles
$role->level; // Get hierarchy level (1 = root)
```

### App\Models\Permission
Extends `Spatie\Permission\Models\Permission` dengan menambahkan:

**Scopes:**
- `byCategory($category)` - Filter by category
- `active()` - Exclude soft-deleted

## Controllers

### RoleController
**Routes:**
- `GET /roles` - Daftar roles
- `GET /roles/create` - Form tambah role
- `POST /roles` - Simpan role baru
- `GET /roles/{role}` - Detail role
- `GET /roles/{role}/edit` - Form edit role
- `PUT /roles/{role}` - Update role
- `DELETE /roles/{role}` - Hapus role

**Features:**
- Create roles dengan optional parent
- Assign/update permissions per role
- View role hierarchy dan sub-roles
- Delete role (dengan validasi: tidak bisa punya children atau users)

### PermissionController
**Routes:**
- `GET /permissions` - Daftar permissions
- `GET /permissions/create` - Form tambah permission
- `POST /permissions` - Simpan permission baru
- `GET /permissions/{permission}/edit` - Form edit permission
- `PUT /permissions/{permission}` - Update permission
- `DELETE /permissions/{permission}` - Hapus permission

**Features:**
- Create permissions dengan category dan description
- Categorize permissions untuk better organization
- Delete permission (dengan validasi: tidak bisa digunakan roles)

## Usage

### Membuat Role Baru
```php
$role = Role::create([
    'name' => 'senior_engineer',
    'guard_name' => 'web',
    'description' => 'Senior Engineer dengan akses penuh development',
    'parent_id' => null, // optional
    'level' => 1,
]);

// Assign permissions
$role->syncPermissions(['create_change_request', 'approve_change_request']);
```

### Membuat Role dengan Parent (Hierarchy)
```php
$parent = Role::findByName('engineer');

$child = Role::create([
    'name' => 'junior_engineer',
    'guard_name' => 'web',
    'description' => 'Junior Engineer',
    'parent_id' => $parent->id,
    'level' => $parent->level + 1,
]);
```

### Assign Roles ke User
```php
$user = User::find(1);
$user->assignRole('engineer'); // assign single role
$user->assignRole(['engineer', 'approver']); // assign multiple roles
```

### Check Permission
```php
// User model sudah punya trait HasRoles dari Spatie
if ($user->can('create_change_request')) {
    // User punya permission
}

// Di controller
$this->authorize('create change_request');

// Di blade template
@if(auth()->user()->can('view_approval'))
    Visible only for users with view_approval permission
@endif
```

### Query Relationships
```php
// Get role dengan ancestors
$role = Role::with('ancestors')->find(1);

// Get role dengan descendants
$role = Role::with('descendants')->find(1);

// Get all root roles
$rootRoles = Role::roots()->get();

// Get role dengan sub-roles
$role = Role::with('children')->find(1);
```

## Default Roles & Hierarchy

Sistem dilengkapi dengan 7 roles default yang dapat disesuaikan:

1. **admin** (Level 1)
   - Akses penuh ke semua fitur
   - Parent: None

2. **manager** (Level 1)
   - Mengelola team dan approval
   - Parent: None

3. **lead_engineer** (Level 1)
   - Implementasi dan approval dengan tanggung jawab
   - Parent: None

4. **engineer** (Level 2)
   - Implementasi change request
   - Parent: lead_engineer

5. **requester** (Level 1)
   - Membuat change requests
   - Parent: None

6. **approver** (Level 1)
   - Approval workflows
   - Parent: None

7. **viewer** (Level 1)
   - Read-only access
   - Parent: None

## Default Permissions

### Categories:
- **User Management**: view, create, edit, delete users
- **Role Management**: manage roles, view role details
- **Permission Management**: manage permissions
- **Approval**: view, approve, reject change requests
- **Approval Rules**: manage approval rules
- **Change Request**: create, view, edit, delete, submit, implement
- **Risk Assessment**: create, edit, view
- **Schedule**: view, create, edit
- **Implementation**: view, create, edit
- **Reports**: view, export
- **Dashboard**: view

## Seeding

### Jalankan Seeder
```bash
# Run all seeders (including RolePermissionSeeder)
php artisan db:seed

# Or run specific seeder
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=UserSeeder
```

### Test Credentials
Seeder membuat test users dengan berbagai roles:

| Username | Password | Role | Email |
|----------|----------|------|-------|
| admin | password | admin | admin@sikece.local |
| manager | password | manager | manager@sikece.local |
| lead_engineer | password | lead_engineer | lead@sikece.local |
| engineer | password | engineer | engineer@sikece.local |
| jr_engineer | password | engineer | jr.engineer@sikece.local |
| approver | password | approver | approver@sikece.local |
| requester | password | requester | requester@sikece.local |
| viewer | password | viewer | viewer@sikece.local |

## Authorization Policy

Sistem menggunakan gate dan policies dari Spatie Permission:

### Di Controller (Base authorization)
```php
public function approve(Request $request, ChangeRequest $cr)
{
    $this->authorize('approve change_request');
    // ...
}
```

### Gate Bypass (Admin)
Di `AppServiceProvider`, admin role di-bypass untuk check semua permissions:
```php
Gate::before(function ($user, $ability) {
    if ($user->hasRole('admin')) {
        return true;
    }
});
```

## Management Interface

### Roles Management
- List semua roles dengan filter dan search
- View hierarchy dengan parent-child relationships
- Create role baru dengan optional parent
- Edit role: nama, deskripsi, parent, permissions
- Delete role (dengan validasi)
- Lihat detail role: permissions, users, sub-roles

### Permissions Management
- List permissions dengan kategori filter
- Create permission baru dengan kategori dan deskripsi
- Edit permission: nama, kategori, deskripsi
- Delete permission (dengan validasi)
- Lihat role yang menggunakan permission tersebut

## Best Practices

1. **Naming Convention**
   - Roles: lowercase, underscore-separated (e.g., senior_engineer)
   - Permissions: action_object format (e.g., create_change_request, view_approval)

2. **Hierarchy**
   - Gunakan parent-child relationship untuk organisasi role yang logis
   - Hindari circular hierarchy
   - Level tidak boleh melebihi 10

3. **Permissions**
   - Kelompokkan dalam kategori yang meaningful
   - Berikan deskripsi yang jelas untuk setiap permission
   - Gunakan granular permissions untuk kontrol yang lebih baik

4. **Security**
   - Admin role can bypass semua checks
   - Soft-delete digunakan untuk roles dan permissions untuk audit trail
   - Permissions di-cache untuk performa

## Troubleshooting

### Cache Issue
Jika permission berubah tapi tidak ter-apply:
```bash
php artisan cache:clear
php artisan config:clear
```

### Role Not Working
```bash
# Check role dengan permissions
php artisan tinker
>>> Role::with('permissions')->find(1)
>>> User::find(1)->roles()->get()
>>> User::find(1)->permissions()->get()
```

### Permission Not Assigned
```bash
# Check permission assignment
>>> Role::find(1)->permissions()->get()
>>> Permission::where('name', 'permission_name')->first()
```

## File Structure

```
app/
  Models/
    Role.php              # Extended role model dengan hierarchy
    Permission.php        # Extended permission model
    User.php              # Already has HasRoles trait

  Http/Controllers/
    RoleController.php    # CRUD roles dengan permissions
    PermissionController.php # CRUD permissions

database/
  migrations/
    2026_04_30_000005_add_hierarchy_to_roles.php  # Add hierarchy columns

  seeders/
    RolePermissionSeeder.php  # Default roles dan permissions
    UserSeeder.php            # Test users dengan berbagai roles

resources/views/
  role/
    index.blade.php       # List roles
    create.blade.php      # Form create role
    edit.blade.php        # Form edit role
    show.blade.php        # Detail role

  permission/
    index.blade.php       # List permissions
    create.blade.php      # Form create permission
    edit.blade.php        # Form edit permission

routes/
  web.php                 # Routes for roles dan permissions
```

## Migration

Jika upgrade dari RBAC lama:
1. Run migration: `php artisan migrate`
2. Backup roles dan permissions dari database
3. Update User::role column (sudah maintained di UserSeeder)
4. Run seeder untuk setup hierarchy: `php artisan db:seed --class=RolePermissionSeeder`

## References

- [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission/v6/introduction)
- [Laravel Authorization](https://laravel.com/docs/authorization)
