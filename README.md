Sistem Informasi Manajemen Change Request Berbasis Web
1. Gambaran Umum Proyek
Proyek ini berupa aplikasi web untuk mengelola permintaan perubahan IT, mulai dari pengajuan, review, approval, penjadwalan implementasi, pencatatan hasil implementasi, sampai dokumentasi rollback atau penutupan change.
Dalam konteks perkantoran, “change” bisa berupa perubahan konfigurasi jaringan, update aplikasi, migrasi server, perubahan firewall rule, deployment fitur baru, update database, perubahan akses user, atau maintenance sistem.
Ide ini sesuai dengan panduan capstone karena menghasilkan produk sistem informasi yang menyelesaikan masalah nyata dan dapat menunjukkan proses lengkap: analisis, perancangan, implementasi, serta pengujian. Panduan juga menekankan bahwa capstone harus menghasilkan produk orisinal yang dibuat dari tahap analisis sampai pengujian, serta menyelesaikan masalah spesifik yang jelas dan nyata. 
________________________________________
2. Masalah yang Diangkat
Contoh rumusan masalah:
Proses pengelolaan change request IT di lingkungan perkantoran sering dilakukan melalui email, chat, spreadsheet, atau dokumen terpisah. Hal ini menyebabkan sulitnya melacak status approval, risiko perubahan, jadwal implementasi, pihak yang bertanggung jawab, serta dokumentasi hasil perubahan. Akibatnya, perubahan IT berpotensi tidak terdokumentasi dengan baik, menimbulkan gangguan layanan, atau menyulitkan audit operasional.
Masalah ini cukup kuat karena di dunia IT operations, perubahan yang tidak terkontrol bisa menyebabkan:
•	downtime layanan; 
•	konflik jadwal maintenance; 
•	approval tidak terdokumentasi; 
•	rollback plan tidak tersedia; 
•	kesulitan mencari histori perubahan; 
•	sulit membuat laporan change success/failure; 
•	risiko operasional meningkat. 
________________________________________
3. Tujuan Proyek
Tujuan utamanya:
Membangun sistem informasi manajemen change request berbasis web untuk membantu proses pengajuan, persetujuan, pemantauan, dokumentasi, dan pelaporan perubahan layanan IT secara lebih terstruktur.
Tujuan rinci:
1.	Membantu user atau tim IT mengajukan change request secara terdokumentasi. 
2.	Membantu IT Lead/Manager melakukan review dan approval perubahan. 
3.	Menyediakan pencatatan risk assessment dan rollback plan. 
4.	Menyediakan monitoring status change dari pengajuan sampai selesai. 
5.	Menghasilkan laporan perubahan IT berdasarkan periode, status, kategori, dan tingkat risiko. 
________________________________________
4. Pengguna Sistem
Sistem ini bisa memiliki 4 jenis pengguna:
Role	Fungsi
Requester	Mengajukan permintaan perubahan IT
Engineer/Implementer	Melaksanakan change yang sudah disetujui
Approver/IT Lead	Meninjau, menyetujui, atau menolak change
Admin	Mengelola user, kategori change, SLA, dan master data
Untuk capstone, role bisa disederhanakan menjadi Requester, Approver, dan Admin/Engineer agar implementasinya realistis.
________________________________________
5. Jenis Change Request
Sistem dapat mengelompokkan change menjadi beberapa tipe:
Jenis Change	Contoh
Standard Change	Penambahan akses VPN, update minor aplikasi, restart service terjadwal
Normal Change	Upgrade aplikasi, perubahan konfigurasi firewall, migrasi database
Emergency Change	Perubahan mendesak karena insiden, patch security kritikal, perbaikan layanan down
Untuk versi capstone, cukup gunakan 3 kategori tersebut agar sistem terlihat profesional tetapi tetap sederhana.
________________________________________
6. Fitur Utama Sistem
A. Login dan Manajemen Role
Pengguna masuk ke sistem sesuai peran.
Fitur:
•	login; 
•	logout; 
•	role-based access; 
•	halaman dashboard berbeda untuk requester, approver, dan engineer. 
________________________________________
B. Form Pengajuan Change Request
Requester mengisi data perubahan.
Field yang disarankan:
•	judul change; 
•	deskripsi perubahan; 
•	alasan perubahan; 
•	sistem/layanan terdampak; 
•	tipe change; 
•	kategori change; 
•	prioritas; 
•	tingkat risiko; 
•	jadwal rencana implementasi; 
•	estimasi durasi; 
•	PIC implementasi; 
•	potensi dampak; 
•	rollback plan; 
•	lampiran dokumen pendukung. 
Contoh data:
Judul: Perubahan konfigurasi firewall untuk akses aplikasi internal
Tipe: Normal Change
Risiko: Medium
Layanan terdampak: Aplikasi CRM Internal
Jadwal: Sabtu, 22.00–23.00
Rollback plan: Mengembalikan konfigurasi firewall ke rule sebelumnya jika koneksi gagal.
________________________________________
C. Workflow Approval
Setelah diajukan, change masuk ke proses approval.
Contoh alur status:
1.	Draft 
2.	Submitted 
3.	Under Review 
4.	Approved 
5.	Rejected 
6.	Scheduled 
7.	Implementation 
8.	Completed 
9.	Failed/Rollback 
10.	Closed 
Workflow sederhana untuk capstone bisa dibuat seperti ini:
Submitted → Reviewed → Approved/Rejected → Implemented → Closed
________________________________________
D. Risk Assessment
Setiap change perlu dinilai risikonya.
Contoh parameter:
•	dampak terhadap layanan; 
•	jumlah user terdampak; 
•	kompleksitas teknis; 
•	kemungkinan gagal; 
•	ketersediaan rollback plan. 
Contoh level risiko:
•	Low; 
•	Medium; 
•	High. 
Sistem bisa menghitung risiko secara sederhana, misalnya berdasarkan skor 1–5 dari beberapa parameter.
Contoh:
Parameter	Skor
Dampak layanan	4
Kompleksitas	3
Jumlah user terdampak	4
Kemungkinan gagal	3
Rollback tersedia	1
Total skor menentukan risiko:
•	5–8 = Low; 
•	9–14 = Medium; 
•	15–20 = High. 
________________________________________
E. Jadwal Implementasi Change
Engineer atau approver dapat menetapkan jadwal implementasi.
Fitur:
•	tanggal implementasi; 
•	jam mulai; 
•	jam selesai; 
•	estimasi downtime; 
•	PIC implementasi; 
•	catatan jadwal; 
•	status pelaksanaan. 
Ini penting karena di kantor, change biasanya perlu dilakukan di luar jam operasional atau pada maintenance window.
________________________________________
F. Dokumentasi Implementasi
Setelah change dilakukan, engineer mengisi hasil implementasi.
Field:
•	waktu mulai aktual; 
•	waktu selesai aktual; 
•	hasil implementasi; 
•	kendala; 
•	evidence/screenshot; 
•	status akhir: berhasil, gagal, rollback; 
•	catatan post-implementation review. 
Contoh:
Implementasi berhasil. Rule firewall telah ditambahkan dan aplikasi CRM dapat diakses dari subnet kantor cabang. Tidak ditemukan gangguan pada layanan lain.
________________________________________
G. Dashboard Monitoring
Dashboard bisa menampilkan:
•	total change request; 
•	change pending approval; 
•	change approved; 
•	change rejected; 
•	change completed; 
•	change failed/rollback; 
•	change berdasarkan risiko; 
•	change berdasarkan kategori; 
•	jadwal change terdekat. 
Ini akan sangat membantu untuk presentasi karena hasilnya visual.
________________________________________
H. Laporan
Laporan yang bisa dibuat:
•	laporan change request per periode; 
•	laporan change berdasarkan status; 
•	laporan change berdasarkan risiko; 
•	laporan change berdasarkan layanan terdampak; 
•	laporan change berhasil vs gagal; 
•	laporan aktivitas per PIC. 
Fitur tambahan:
•	export PDF; 
•	export Excel; 
•	cetak laporan. 
Untuk capstone, export PDF/Excel boleh dijadikan fitur tambahan, bukan wajib.
________________________________________
7. Batasan Ruang Lingkup
Agar realistis untuk satu semester, ruang lingkup sebaiknya dibatasi.
Contoh batasan:
1.	Sistem hanya digunakan untuk pengelolaan change request IT internal. 
2.	Approval hanya dilakukan satu tingkat oleh IT Lead/Manager. 
3.	Notifikasi hanya ditampilkan di dashboard, belum menggunakan email/WhatsApp asli. 
4.	Risk assessment menggunakan metode skoring sederhana. 
5.	Sistem tidak terintegrasi langsung dengan sistem ticketing, monitoring server, atau Active Directory. 
6.	Pengujian menggunakan black-box testing dan user acceptance testing sederhana. 
Batasan ini penting karena dalam panduan, ruang lingkup proyek perlu menjelaskan cakupan dan batasan proyek yang dilakukan. 
________________________________________
8. Rancangan Modul Sistem
Struktur modul yang disarankan:
Modul	Isi
Modul Login	Autentikasi dan role pengguna
Modul User	Data requester, engineer, approver
Modul Change Request	Form pengajuan dan daftar change
Modul Approval	Review, approve, reject
Modul Risk Assessment	Penilaian risiko change
Modul Schedule	Jadwal implementasi
Modul Implementation Result	Hasil implementasi dan rollback
Modul Dashboard	Ringkasan statistik change
Modul Report	Laporan change request
________________________________________
9. Contoh Entitas Database
Minimal tabel yang bisa dibuat:
1.	users 
o	id_user 
o	nama 
o	email 
o	password 
o	role 
2.	change_requests 
o	id_change 
o	title 
o	description 
o	change_type 
o	category 
o	priority 
o	risk_level 
o	affected_service 
o	impact 
o	reason 
o	rollback_plan 
o	status 
o	requester_id 
o	approver_id 
o	created_at 
3.	change_schedules 
o	id_schedule 
o	id_change 
o	planned_start 
o	planned_end 
o	actual_start 
o	actual_end 
o	pic_id 
4.	approvals 
o	id_approval 
o	id_change 
o	approver_id 
o	approval_status 
o	approval_note 
o	approved_at 
5.	implementation_logs 
o	id_log 
o	id_change 
o	implementer_id 
o	result_status 
o	result_note 
o	evidence_file 
o	created_at 
6.	risk_assessments 
o	id_risk 
o	id_change 
o	impact_score 
o	complexity_score 
o	user_impact_score 
o	failure_probability_score 
o	total_score 
o	risk_level 
________________________________________
10. Contoh Alur Sistem
Alur Pengajuan Change
1.	Requester login. 
2.	Requester membuat change request. 
3.	Sistem menyimpan data dengan status Submitted. 
4.	Approver menerima daftar change yang perlu direview. 
5.	Approver melihat detail change, risiko, dampak, dan rollback plan. 
6.	Approver menyetujui atau menolak. 
7.	Jika disetujui, engineer menjadwalkan dan melaksanakan perubahan. 
8.	Engineer mengisi hasil implementasi. 
9.	Change ditutup dengan status Closed.

