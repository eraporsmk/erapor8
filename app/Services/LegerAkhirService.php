<?php

namespace App\Services;

use App\Models\AnggotaRombel;
use App\Models\NilaiAkhir;
use App\Models\NilaiPkl;
use App\Models\NilaiUkk;
use App\Models\PesertaDidik;
use App\Models\RombonganBelajar;
use App\Models\Semester;

class LegerAkhirService
{
    /**
     * Mata pelajaran ID khusus PKL di eRapor SMK yang disajikan pada kolom PKL terpisah.
     */
    private const MAPEL_PKL_ID = '800001000';

    /**
     * Mengambil dan mengagregasi Leger Nilai Akhir (Kelas 10-12) untuk satu rombel kelas 12.
     */
    public function getLeggerAkhir(string $rombonganBelajarId, string $sekolahId, string $semesterId): array
    {
        $rombel = RombonganBelajar::with(['sekolah', 'kurikulum', 'wali_kelas'])
            ->findOrFail($rombonganBelajarId);
        $semester = Semester::find($semesterId);

        $siswaRecords = PesertaDidik::whereHas('anggota_rombel', function ($query) use ($rombonganBelajarId) {
            $query->where('rombongan_belajar_id', $rombonganBelajarId);
        })->orderByRaw('LOWER(nama) ASC')->get();

        $pdIds = $siswaRecords->pluck('peserta_didik_id')->all();
        if (empty($pdIds)) {
            return [
                'rombel' => $rombel,
                'tahun_ajaran' => $semester?->nama ?? '-',
                'semesters' => [],
                'max_semester_count' => 0,
                'mapel_list' => [],
                'siswa_list' => [],
            ];
        }

        // Ambil riwayat anggota_rombel reguler (1) & pilihan (16) lintas tingkat 10-12
        $anggotaHistory = AnggotaRombel::query()
            ->whereIn('peserta_didik_id', $pdIds)
            ->where('sekolah_id', $sekolahId)
            ->where('semester_id', '<=', $semesterId)
            ->whereHas('rombongan_belajar', function ($query) {
                $query->whereIn('jenis_rombel', [1, 16])
                    ->whereIn('tingkat', [10, 11, 12]);
            })
            ->get(['anggota_rombel_id', 'peserta_didik_id', 'semester_id']);

        $allSemesterIds = $anggotaHistory->pluck('semester_id')->unique()->sort()->values()->all();
        $semesterModels = Semester::whereIn('semester_id', $allSemesterIds)->get()->keyBy('semester_id');

        $semesters = [];
        foreach ($allSemesterIds as $idx => $semId) {
            $semesters[] = [
                'semester_id' => (string) $semId,
                'label' => 'Sem ' . ($idx + 1),
                'nama' => $semesterModels->get($semId)?->nama ?? (string) $semId,
            ];
        }

        $anggotaIds = $anggotaHistory->pluck('anggota_rombel_id')->all();
        $anggotaToPd = $anggotaHistory->pluck('peserta_didik_id', 'anggota_rombel_id')->all();
        $anggotaToSem = $anggotaHistory->pluck('semester_id', 'anggota_rombel_id')->all();

        $nilaiAkhirRows = NilaiAkhir::query()
            ->whereIn('anggota_rombel_id', $anggotaIds)
            ->whereIn('kompetensi_id', [1, 2, 4])
            ->whereHas('pembelajaran', function ($query) {
                $query->whereNotNull('kelompok_id')
                    ->whereNotNull('no_urut')
                    ->whereNull('induk_pembelajaran_id')
                    ->where('mata_pelajaran_id', '!=', self::MAPEL_PKL_ID);
            })
            ->with(['pembelajaran:pembelajaran_id,mata_pelajaran_id,nama_mata_pelajaran,kelompok_id,no_urut'])
            ->get();

        // Kumpulkan daftar mapel unik & kelompokkan nilai mentah per siswa -> mapel -> semester -> kompetensi
        $mapelCatalog = [];
        $rawScores = [];

        foreach ($nilaiAkhirRows as $row) {
            $pembelajaran = $row->pembelajaran;
            if (! $pembelajaran) {
                continue;
            }

            $mapelId = (string) $pembelajaran->mata_pelajaran_id;
            if (! isset($mapelCatalog[$mapelId])) {
                $mapelCatalog[$mapelId] = [
                    'mata_pelajaran_id' => $mapelId,
                    'nama_mata_pelajaran' => $pembelajaran->nama_mata_pelajaran,
                    'kelompok_id' => (int) $pembelajaran->kelompok_id,
                    'no_urut' => (int) $pembelajaran->no_urut,
                ];
            }

            $pdId = $anggotaToPd[$row->anggota_rombel_id] ?? null;
            $semId = $anggotaToSem[$row->anggota_rombel_id] ?? null;
            if (! $pdId || ! $semId) {
                continue;
            }

            $rawScores[$pdId][$mapelId][(string) $semId][(int) $row->kompetensi_id] = (float) $row->nilai;
        }

        // Urutkan mapel berdasarkan kelompok_id asc, no_urut asc
        $mapelList = array_values($mapelCatalog);
        usort($mapelList, function (array $a, array $b): int {
            return [$a['kelompok_id'], $a['no_urut'], $a['nama_mata_pelajaran']]
                <=> [$b['kelompok_id'], $b['no_urut'], $b['nama_mata_pelajaran']];
        });

        // Ambil nilai PKL dan UKK secara batch
        $pklByPd = NilaiPkl::whereIn('peserta_didik_id', $pdIds)
            ->get(['peserta_didik_id', 'nilai'])
            ->groupBy('peserta_didik_id');

        $ukkByPd = NilaiUkk::whereIn('peserta_didik_id', $pdIds)
            ->get(['peserta_didik_id', 'nilai'])
            ->groupBy('peserta_didik_id');

        $semesterIdsByPd = [];
        foreach ($anggotaHistory as $ar) {
            $semesterIdsByPd[$ar->peserta_didik_id][(string) $ar->semester_id] = true;
        }

        $students = [];
        foreach ($siswaRecords as $siswa) {
            $pdId = $siswa->peserta_didik_id;
            $scores = [];

            foreach ($rawScores[$pdId] ?? [] as $mapelId => $semEntries) {
                foreach ($semEntries as $semId => $kompetensiScores) {
                    $resolved = $this->resolveSemesterScore($kompetensiScores);
                    if ($resolved !== null) {
                        $scores[$mapelId][$semId] = $resolved;
                    }
                }
            }

            $pklRows = $pklByPd->get($pdId);
            $nilaiPkl = ($pklRows && $pklRows->isNotEmpty())
                ? round((float) $pklRows->avg('nilai'), 2)
                : null;

            $ukkRows = $ukkByPd->get($pdId);
            $nilaiUkk = ($ukkRows && $ukkRows->isNotEmpty())
                ? round((float) $ukkRows->avg('nilai'), 2)
                : null;

            $students[] = [
                'peserta_didik_id' => $pdId,
                'nama' => $siswa->nama,
                'nisn' => $siswa->nisn,
                'semester_ids' => array_keys($semesterIdsByPd[$pdId] ?? []),
                'scores' => $scores,
                'nilai_pkl' => $nilaiPkl,
                'nilai_ukk' => $nilaiUkk,
            ];
        }

        $aggregated = $this->aggregateLeggerData($students, $semesters, $mapelList);

        return [
            'rombel' => $rombel,
            'tahun_ajaran' => $semester?->nama ?? '-',
            'semesters' => $semesters,
            'max_semester_count' => $aggregated['max_semester_count'],
            'mapel_list' => $mapelList,
            'siswa_list' => $aggregated['siswa_list'],
        ];
    }

    /**
     * Mengagregasi data nilai siswa lintas semester, menandai siswa pindahan, dan menghitung rata-rata 2 desimal.
     */
    public function aggregateLeggerData(array $students, array $semesters, array $mapelList): array
    {
        $maxSemesterCount = 0;
        foreach ($students as $student) {
            $count = count(array_unique($student['semester_ids'] ?? []));
            if ($count > $maxSemesterCount) {
                $maxSemesterCount = $count;
            }
        }

        $siswaList = [];
        foreach ($students as $student) {
            $semesterCount = count(array_unique($student['semester_ids'] ?? []));
            $nilaiPerMapel = [];
            $mapelAverages = [];

            foreach ($mapelList as $mapel) {
                $mapelId = (string) $mapel['mata_pelajaran_id'];
                $perSemester = [];
                $validScores = [];

                foreach ($semesters as $sem) {
                    $semId = (string) $sem['semester_id'];
                    $val = $student['scores'][$mapelId][$semId] ?? null;
                    $perSemester[$semId] = $val !== null ? round((float) $val, 2) : null;
                    if ($val !== null) {
                        $validScores[] = (float) $val;
                    }
                }

                $avg = ! empty($validScores)
                    ? round(array_sum($validScores) / count($validScores), 2)
                    : null;

                $nilaiPerMapel[$mapelId] = [
                    'per_semester' => $perSemester,
                    'rata_rata' => $avg,
                ];

                if ($avg !== null) {
                    $mapelAverages[] = $avg;
                }
            }

            $rataRataTotal = ! empty($mapelAverages)
                ? round(array_sum($mapelAverages) / count($mapelAverages), 2)
                : null;

            $siswaList[] = [
                'peserta_didik_id' => $student['peserta_didik_id'],
                'nama' => $student['nama'],
                'nisn' => $student['nisn'],
                'semester_count' => $semesterCount,
                'is_incomplete' => $semesterCount < $maxSemesterCount,
                'nilai_per_mapel' => $nilaiPerMapel,
                'nilai_pkl' => $student['nilai_pkl'] !== null ? round((float) $student['nilai_pkl'], 2) : null,
                'nilai_ukk' => $student['nilai_ukk'] !== null ? round((float) $student['nilai_ukk'], 2) : null,
                'rata_rata_total' => $rataRataTotal,
            ];
        }

        return [
            'max_semester_count' => $maxSemesterCount,
            'siswa_list' => $siswaList,
        ];
    }

    /**
     * Mengecek apakah semester_id merupakan semester Genap (berakhiran '2').
     */
    public function isSemesterGenap(string $semesterId): bool
    {
        return $semesterId !== '' && str_ends_with($semesterId, '2');
    }

    /**
     * Memilih nilai akhir Kurmer (kompetensi 4) atau rata-rata K13 (kompetensi 1 & 2).
     */
    private function resolveSemesterScore(array $kompetensiScores): ?float
    {
        if (isset($kompetensiScores[4])) {
            return (float) $kompetensiScores[4];
        }

        $k13 = array_filter([
            $kompetensiScores[1] ?? null,
            $kompetensiScores[2] ?? null,
        ], fn ($v) => $v !== null);

        if (empty($k13)) {
            return null;
        }

        return array_sum($k13) / count($k13);
    }
}
