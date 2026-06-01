# Role Settings & Assignment - Fitur User Management per Role

## Deskripsi

Fitur Role Settings & Assignment memungkinkan admin untuk:
- Melihat daftar roles utama (requester, approver, engineer, lead_engineer, manager)
- Melihat users yang terdaftar di setiap role
- Menambah/menghapus users ke/dari roles
- Bulk assign multiple users ke satu role
- Sync role assignments dengan satu form

## Routes

### Role Settings Management
```
GET  /role-settings              - Dashboard role settings (list semua roles dengan users)
GET  /role-settings/{role}       - Detail role dengan user assignment interface
POST /role-settings/{role}/sync  - Sync (bulk update) user assignments untuk role
POST /role-settings/{role}/bulk-assign    - Bulk assign multiple users ke role
POST /role-settings/{role}/bulk-remove    - Bulk remove multiple users dari role
POST /role-settings/{role}/assign-user    - Assign single user ke role
POST /role-settings/{role}/remove-user    - Remove single user dari role
```

## Controller

### RoleSettingController
Location: `app/Http/Controllers/RoleSettingController.php`

**Methods:**

1. **index()** - Tampilkan dashboard role settings
   - List roles utama (requester, approver, engineer, etc)
   - Tampilkan users per role
   - Tampilkan statistik

2. **show(Role $role)** - Tampilkan detail role dengan user assignment
   - Tampilkan users yang sudah punya role ini
   - Tampilkan users yang belum punya role ini
   - Form untuk assign/remove users

3. **assignUser(Request $request, Role $role)** - Single assign
   - Assign user single ke role
   - Validasi user tidak sudah punya role ini

4. **removeUser(Request $request, Role $role)** - Single remove
   - Remove user dari role
   - Validasi user memiliki role ini

5. **bulkAssign(Request $request)** - Bulk assign multiple users
   - Assign multiple users sekaligus ke role
   - Return count berapa users berhasil ditambahkan

6. **bulkRemove(Request $request)** - Bulk remove multiple users
   - Remove multiple users dari role
   - Return count berapa users berhasil dihapus

7. **sync(Request $request, Role $role)** - Sync role assignment
   - Sinkronkan user assignments berdasarkan checked state di form
   - Tambah users yang di-check, hapus yang tidak di-check

## Views

### 1. role_setting/index.blade.php
**Dashboard Role Settings**

Menampilkan:
- Statistik (total users, active users, total roles, key roles)
- Grid card untuk setiap role:
  - Role name dengan icon
  - Level
  - User count
  - Preview users (max 3)
  - Button "Manage Users"
- Quick reference penjelasan setiap role

Features:
- Responsive grid layout
- Different colors untuk setiap role type
- Icons untuk visual distinction
- Quick link ke manage page

### 2. role_setting/show.blade.php
**Role Assignment Detail Page**

Layout 2 kolom:

**Left Side - Current Users:**
- List users yang sudah punya role ini
- Checkbox untuk sync form
- User info (name, username, email, status)
- Active/Inactive badge
- Submit button untuk simpan perubahan

**Right Side - Add Users:**
- Search box untuk cari users
- List users yang belum punya role ini
- Checkbox untuk bulk select
- Badge showing current roles
- Submit button untuk bulk assign

Features:
- Real-time search (JavaScript filtering)
- Scrollable list
- Status badges (Active/Inactive)
- Multiple role display per user
- Clear button untuk reset search

## Usage

### Access Role Settings
```
URL: /role-settings
Permission: manage roles
```

### Assign User ke Role
1. Go to `/role-settings`
2. Click "Manage Users" pada role yang diinginkan
3. Di sebelah kanan, cari user
4. Check user yang mau ditambahkan
5. Click "Tambahkan Terpilih"

### Remove User dari Role
1. Go to `/role-settings/{role}`
2. Uncheck user di sebelah kiri
3. Click "Simpan Perubahan"

### Bulk Assign
1. Di halaman role detail
2. Cari multiple users di sebelah kanan
3. Check semua user yang mau ditambahkan
4. Click "Tambahkan Terpilih"

### Sync (Update multiple sekaligus)
1. Edit checkbox state users
2. Click "Simpan Perubahan"
3. System akan:
   - Add users yang di-check dan belum punya role
   - Remove users yang tidak di-check tapi punya role

## Data Models

### Role
```php
$role = Role::find(1);
$role->users();           // Get all users dengan role ini
$role->users()->count();  // Count users
```

### User
```php
$user = User::find(1);
$user->assignRole('requester');     // Assign role
$user->removeRole('requester');     // Remove role
$user->hasRole('requester');        // Check role
$user->roles();                      // Get all roles
```

## Authorization

Semua routes di-protect dengan:
```php
$this->authorize('manage roles');
```

Hanya users dengan permission `manage roles` (admin, manager) yang bisa access.

## Responses

### Success Messages
- "✓ [Username] berhasil ditambahkan ke role [Role name]"
- "✓ [Username] berhasil dihapus dari role [Role name]"
- "✓ [Count] users berhasil ditambahkan ke role [Role name]"
- "✓ [Count] users berhasil dihapus dari role [Role name]"
- "✓ Role assignment berhasil disinkronkan."

### Warning Messages
- "⚠ User sudah memiliki role ini."
- "⚠ User tidak memiliki role ini."

## Key Features

### 1. Dashboard Overview
- Quick view semua roles utama
- User count per role
- Preview users
- One-click access ke detail

### 2. User Assignment
- Tampilkan side-by-side (current vs available)
- Real-time search filter
- Bulk operations
- Sync dengan checkbox state

### 3. Validation
- Cek duplikasi role assignment
- Cek user exists
- Cek role exists
- Authorization check

### 4. Responsive
- Grid layout untuk dashboard
- Mobile-friendly forms
- Scrollable list untuk many users
- Proper spacing dan readability

## Best Practices

1. **Bulk Assignment**
   - Gunakan bulk assign untuk menambah multiple users sekaligus
   - Lebih efficient daripada satu per satu

2. **Search Function**
   - Gunakan search untuk cari user dengan cepat
   - Filter by username, name, atau email

3. **Validation**
   - Sistem otomatis cek duplikasi
   - User tidak bisa di-assign role 2x

4. **Audit Trail**
   - Lihat di logs kalau ada access
   - Timestamps tercatat di database

## File Structure

```
app/Http/Controllers/
  └── RoleSettingController.php

resources/views/role_setting/
  ├── index.blade.php       # Dashboard
  └── show.blade.php        # Detail & Assignment

routes/
  └── web.php               # Role settings routes
```

## Integration dengan Existing System

- Menggunakan existing `Role` dan `User` models
- Menggunakan Spatie Permission's `assignRole()` dan `removeRole()`
- Share authorization dengan role management
- Integrate dengan notification system (optional: notify user when assigned)

## Future Enhancements

1. Notification ketika user di-assign role
2. Activity log untuk track semua assignment changes
3. CSV export untuk user list per role
4. Bulk export/import user assignments
5. Role templates untuk quick setup
6. Approval workflow untuk role assignments
