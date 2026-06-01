<?php

return [
    'permissions' => [
        'Dashboard' => [
            'view dashboard' => 'Akses dashboard',
        ],
        'Change Request' => [
            'view change_request' => 'Melihat daftar change request',
            'view own change_request' => 'Melihat change request milik sendiri',
            'view team change_request' => 'Melihat change request dalam tim/hierarchy role',
            'view all change_request' => 'Melihat semua change request lintas tim',
            'create change_request' => 'Membuat change request',
            'edit change_request' => 'Mengubah change request',
            'delete change_request' => 'Menghapus change request',
            'submit change_request' => 'Submit change request',
            'implement change_request' => 'Melakukan implementasi change request',
        ],
        'Approval' => [
            'view approval' => 'Melihat daftar approval',
            'approve change_request' => 'Menyetujui change request',
            'reject change_request' => 'Menolak change request',
            'manage approval_rules' => 'Mengelola aturan approval',
        ],
        'Schedule' => [
            'view schedule' => 'Melihat jadwal',
            'create schedule' => 'Membuat jadwal',
            'edit schedule' => 'Mengubah jadwal',
        ],
        'Implementation' => [
            'view implementation' => 'Melihat implementasi',
            'create implementation' => 'Membuat log implementasi',
            'edit implementation' => 'Mengubah log implementasi',
        ],
        'Risk Assessment' => [
            'view risk_assessment' => 'Melihat risk assessment',
            'create risk_assessment' => 'Membuat risk assessment',
            'edit risk_assessment' => 'Mengubah risk assessment',
        ],
        'Report' => [
            'view report' => 'Melihat laporan',
            'export report' => 'Export laporan',
        ],
        'User Management' => [
            'view user' => 'Melihat user',
            'create user' => 'Membuat user',
            'edit user' => 'Mengubah user',
            'delete user' => 'Menghapus user',
        ],
      
        'CR Options' => [
            'manage cr_options' => 'Mengelola pilihan Tipe Change, Kategori, dan Prioritas',
        ],
    ],
];
