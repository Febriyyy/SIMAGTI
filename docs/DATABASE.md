# Struktur Database KP Monitor

Dokumen ini menjelaskan struktur database (MySQL, nama database `kp_monitor`).
Diagram di bawah memakai Mermaid: buka dengan Markdown Preview di VS Code atau Antigravity.

## Prinsip

- Semua relasi memakai foreign key. Data penting memakai soft delete (`deleted_at`), jadi tidak ada data KP yang hilang permanen.
- Aturan bisnis dijaga di database dengan constraint unik, bukan hanya di kode.
- Pengaturan yang bisa berubah (skala nilai) disimpan di tabel, bukan di kode.
- Riwayat disimpan terpisah (status penempatan, status konversi, versi skala nilai).
- Nama tabel memakai bahasa Inggris. Tampilan aplikasi memakai bahasa Indonesia.

## Aturan bisnis yang dijaga struktur

| Aturan | Cara dijaga |
|---|---|
| Satu mahasiswa hanya punya satu penempatan aktif | `internships.active_student_id` unik; terisi hanya saat status `diajukan` atau `berjalan` |
| Satu meeting per penempatan per minggu | unik `weekly_meetings(internship_id, week_number)` |
| Mentor/ketua divisi tidak ganda pada satu penempatan | unik `internship_staff(internship_id, user_id, role)` |
| Akses mentor dan ketua divisi per penempatan | tabel `internship_staff` |
| KP selesai: data hanya bisa dibaca | `internships.locked_at` terisi otomatis; task dan bukti ditolak |
| Huruf mutu terkunci permanen | `assessments.letter_grade` + `grade_scale_version`; penilaian tervalidasi tidak bisa diubah |
| Perubahan skala tidak mengubah nilai lama | `grade_scales` berversi; nilai menyimpan versi yang dipakai |
| Satu penilaian akhir per penempatan | unik `assessments(internship_id)` |
| Satu sertifikat per penempatan, nomor unik | unik `certificates(internship_id)`, `number`, `verification_token` |
| Satu langkah persetujuan per tahap | unik `internship_approvals(internship_id, step)` |
| NIM dan NIDN tidak boleh kembar | unik `users.nim`, `lecturer_profiles.nidn` |

## Diagram relasi

```mermaid
erDiagram
    users ||--o| student_profiles : punya
    users ||--o| lecturer_profiles : punya
    users ||--o| company_staff_profiles : punya
    users ||--o{ student_skills : menguasai
    skills ||--o{ student_skills : dikuasai
    study_programs ||--o{ student_profiles : berisi
    study_programs ||--o{ lecturer_profiles : berisi

    companies ||--o{ company_divisions : memiliki
    companies ||--o{ company_staff_profiles : mempekerjakan
    companies ||--o{ job_postings : membuka
    job_postings }o--o{ skills : membutuhkan
    companies ||--o{ invitations : mengundang

    users ||--o{ internships : "mahasiswa"
    companies ||--o{ internships : menerima
    company_divisions ||--o{ internships : menempatkan
    academic_periods ||--o{ internships : mencakup
    internships ||--o{ internship_staff : "mentor dan ketua divisi"
    users ||--o{ internship_staff : bertugas
    internships ||--o{ internship_approvals : disetujui
    internships ||--o{ internship_status_histories : riwayat
    internships ||--o{ invitations : untuk

    internships ||--o{ weekly_tasks : memuat
    internships ||--o{ weekly_meetings : menjadwalkan
    weekly_tasks ||--o{ task_evidences : bukti
    weekly_tasks ||--o{ task_comments : komentar
    files ||--o{ task_evidences : berkas
    users ||--o{ task_templates : memiliki
    task_templates ||--o{ task_template_items : berisi

    assessment_rubrics ||--o{ rubric_aspects : terdiri
    assessment_rubrics ||--o{ assessments : dipakai
    internships ||--o| assessments : dinilai
    assessments ||--o{ assessment_scores : skor
    rubric_aspects ||--o{ assessment_scores : aspek
    internships ||--o| defense_scores : sidang

    internships ||--o| conversion_candidates : direkomendasikan
    conversion_candidates ||--o{ conversion_histories : riwayat

    document_folders ||--o{ document_folders : subfolder
    document_folders ||--o{ documents : berisi
    files ||--o{ documents : berkas
    users ||--o{ letter_requests : mengajukan
    certificate_templates ||--o{ certificates : dipakai
    internships ||--o| certificates : diterbitkan

    users ||--o{ activity_logs : melakukan
```

## Kamus data

### 1. Akun dan akses

| Tabel | Isi | Kolom penting |
|---|---|---|
| `users` | Semua akun | `role` (mahasiswa, mentor, ketua_divisi, dospem, kaprodi, biro), `nim` unik, `is_active`, `last_login_at`, soft delete |
| `student_profiles` | Profil mahasiswa | `study_program_id`, `entry_year` (angkatan), `gpa` |
| `lecturer_profiles` | Profil dosen, kaprodi | `nidn` unik, `study_program_id` |
| `company_staff_profiles` | Profil mentor dan ketua divisi | `company_id`, `division_id`, `job_title` |
| `invitations` | Undangan email untuk mentor/ketua divisi KP mandiri | `token` unik, `role`, `expires_at`, `accepted_at` |

### 2. Data master

| Tabel | Isi | Kolom penting |
|---|---|---|
| `study_programs` | Program studi | `code` unik |
| `academic_periods` | Periode akademik | `is_active` |
| `skills` | Daftar skill | `name` unik, `category` |
| `student_skills` | Skill tiap mahasiswa (banyak per mahasiswa) | unik (`user_id`, `skill_id`), `level` |
| `companies` | Perusahaan mitra dan KP mandiri | `status` (mitra, mandiri_menunggu, mandiri_terverifikasi), `verified_by` |
| `company_divisions` | Divisi per perusahaan | unik (`company_id`, `name`) |
| `settings` | Pengaturan sistem | `key` unik, `value` (JSON) |
| `files` | Metadata semua berkas | `disk`, `path`, `mime_type`, `size`, `checksum` |

### 3. Penempatan dan persetujuan

| Tabel | Isi | Kolom penting |
|---|---|---|
| `internships` | Satu baris = satu penempatan magang (pusat semua fitur) | `track` (lowongan, mandiri), `status` (diajukan, berjalan, selesai, ditolak), `active_student_id` unik, `locked_at` |
| `internship_staff` | Mentor dan ketua divisi per penempatan | `role` (mentor, ketua_divisi) |
| `internship_approvals` | Persetujuan KP mandiri | `step` (biro_verification, kaprodi_approval), `status` (pending, approved, rejected) |
| `internship_status_histories` | Riwayat perubahan status | `from_status`, `to_status`, `changed_by` |

### 4. Lowongan

| Tabel | Isi |
|---|---|
| `job_postings` | Pengumuman lowongan (`status`: draft, published, closed; `deadline`, `quota`) |
| `job_posting_skill` | Skill yang dibutuhkan lowongan |

### 5. Task mingguan

| Tabel | Isi | Kolom penting |
|---|---|---|
| `weekly_tasks` | Task Senin sampai Jumat | `week_number`, `day`, `status` (todo, in_progress, completed), `completed_at` |
| `task_evidences` | Bukti pengerjaan (banyak per task) | `type` (link, file), `url`, `file_id` |
| `task_comments` | Percakapan komentar per task | `user_id`, `body` |
| `weekly_meetings` | Jadwal meeting mingguan | `day`, `time`, `url`; unik per penempatan per minggu |
| `task_templates`, `task_template_items` | Template task milik mentor untuk disalin | `is_shared`, `week_number`, `day` |

### 6. Penilaian

| Tabel | Isi | Kolom penting |
|---|---|---|
| `grade_scales` | Skala huruf berversi | unik (`version`, `grade`), `min_score` |
| `assessment_rubrics`, `rubric_aspects` | Rubrik dan aspek (disiplin, komunikasi, kemampuan teknis, kerja tim) | `weight` (persen) |
| `assessments` | Penilaian akhir per penempatan | `status` (draft, submitted, validated), `final_score`, `letter_grade`, `grade_scale_version` |
| `assessment_scores` | Skor per aspek | unik (`assessment_id`, `rubric_aspect_id`) |
| `defense_scores` | Nilai sidang dari kaprodi | `score`, `letter_grade`, `defended_at` |

### 7. Konversi karyawan

| Tabel | Isi |
|---|---|
| `conversion_candidates` | Status: direkomendasikan, ditawari, diterima, ditolak (satu per penempatan) |
| `conversion_histories` | Riwayat tiap perubahan status |

### 8. Dokumen dan sertifikat

| Tabel | Isi |
|---|---|
| `document_folders`, `documents` | Folder dan berkas template dari kaprodi (surat, laporan, pendukung) |
| `letter_requests` | Permintaan surat pengantar (`status`: diajukan, diproses, selesai, ditolak) |
| `certificate_templates` | Template sertifikat (`scope`: kampus atau perusahaan; logo, tanda tangan, stempel) |
| `certificates` | Sertifikat terbit: `number` unik, `verification_token` unik untuk QR, `status` (draft, terbit, dicabut) |

### 9. Sistem

| Tabel | Isi |
|---|---|
| `activity_logs` | Siapa melakukan apa dan kapan (`action`, `ip_address`, `properties`) |
| `notifications` | Notifikasi standar Laravel |

## Seeder

- `MasterSeeder`: program studi, periode, skill, skala nilai versi 1, rubrik 4 aspek. Aman dijalankan di server sungguhan.
- `DemoSeeder`: akun demo, perusahaan, penempatan, task, penilaian, sertifikat, dan lainnya untuk demo. Jangan dijalankan di server sungguhan.
