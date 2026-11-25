<?php

namespace Tests\Unit;

use App\Services\LegerAkhirService;
use PHPUnit\Framework\TestCase;

class LegerAkhirServiceTest extends TestCase
{
    public function test_aggregates_multi_semester_scores_excluding_pkl_semester_and_rounds_to_two_decimals(): void
    {
        $service = new LegerAkhirService();

        $semesters = [
            ['semester_id' => '20231', 'label' => 'Sem 1', 'nama' => '2023/2024 Ganjil'],
            ['semester_id' => '20232', 'label' => 'Sem 2', 'nama' => '2023/2024 Genap'],
            ['semester_id' => '20241', 'label' => 'Sem 3', 'nama' => '2024/2025 Ganjil'],
            ['semester_id' => '20242', 'label' => 'Sem 4', 'nama' => '2024/2025 Genap'],
            ['semester_id' => '20251', 'label' => 'Sem 5', 'nama' => '2025/2026 Ganjil'],
            ['semester_id' => '20252', 'label' => 'Sem 6', 'nama' => '2025/2026 Genap (PKL)'],
        ];

        $mapelList = [
            ['mata_pelajaran_id' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'kelompok_id' => 1, 'no_urut' => 1],
            ['mata_pelajaran_id' => 'BIN', 'nama_mata_pelajaran' => 'Bahasa Indonesia', 'kelompok_id' => 1, 'no_urut' => 2],
        ];

        $students = [
            [
                'peserta_didik_id' => 'pd-1',
                'nama' => 'ANDI',
                'nisn' => '001',
                'semester_ids' => ['20231', '20232', '20241', '20242', '20251', '20252'],
                // Sem 6 (20252) is PKL -> no regular subject score
                'scores' => [
                    'MTK' => ['20231' => 80, '20232' => 85, '20241' => 90, '20242' => 82, '20251' => 84],
                    'BIN' => ['20231' => 88, '20232' => 89, '20241' => 90, '20242' => 91, '20251' => 92],
                ],
                'nilai_pkl' => 95.5,
                'nilai_ukk' => 92.0,
            ],
        ];

        $result = $service->aggregateLeggerData($students, $semesters, $mapelList);

        $this->assertCount(1, $result['siswa_list']);
        $andi = $result['siswa_list'][0];

        // MTK: (80+85+90+82+84)/5 = 421/5 = 84.2
        $this->assertSame(84.2, $andi['nilai_per_mapel']['MTK']['rata_rata']);
        $this->assertNull($andi['nilai_per_mapel']['MTK']['per_semester']['20252']);

        // BIN: (88+89+90+91+92)/5 = 90.0
        $this->assertSame(90.0, $andi['nilai_per_mapel']['BIN']['rata_rata']);

        // PKL & UKK stored separately, rata_rata_total excludes PKL & UKK: (84.2 + 90.0) / 2 = 87.1
        $this->assertSame(95.5, $andi['nilai_pkl']);
        $this->assertSame(92.0, $andi['nilai_ukk']);
        $this->assertSame(87.1, $andi['rata_rata_total']);
        $this->assertFalse($andi['is_incomplete']);
    }

    public function test_flags_transfer_student_with_fewer_semesters_as_incomplete(): void
    {
        $service = new LegerAkhirService();

        $semesters = [
            ['semester_id' => '20231', 'label' => 'Sem 1', 'nama' => '2023/2024 Ganjil'],
            ['semester_id' => '20232', 'label' => 'Sem 2', 'nama' => '2023/2024 Genap'],
            ['semester_id' => '20241', 'label' => 'Sem 3', 'nama' => '2024/2025 Ganjil'],
        ];

        $mapelList = [
            ['mata_pelajaran_id' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'kelompok_id' => 1, 'no_urut' => 1],
        ];

        $students = [
            [
                'peserta_didik_id' => 'pd-reguler',
                'nama' => 'BUDI',
                'nisn' => '001',
                'semester_ids' => ['20231', '20232', '20241'],
                'scores' => ['MTK' => ['20231' => 80, '20232' => 81, '20241' => 82]],
                'nilai_pkl' => null,
                'nilai_ukk' => null,
            ],
            [
                'peserta_didik_id' => 'pd-pindahan',
                'nama' => 'CITRA',
                'nisn' => '002',
                'semester_ids' => ['20241'],
                'scores' => ['MTK' => ['20241' => 86]],
                'nilai_pkl' => null,
                'nilai_ukk' => null,
            ],
        ];

        $result = $service->aggregateLeggerData($students, $semesters, $mapelList);

        $this->assertSame(3, $result['max_semester_count']);
        $this->assertFalse($result['siswa_list'][0]['is_incomplete']);
        $this->assertTrue($result['siswa_list'][1]['is_incomplete']);
        $this->assertSame(1, $result['siswa_list'][1]['semester_count']);
        $this->assertSame(86.0, $result['siswa_list'][1]['nilai_per_mapel']['MTK']['rata_rata']);
    }

    public function test_rounds_repeating_decimals_to_two_decimal_places(): void
    {
        $service = new LegerAkhirService();

        $semesters = [
            ['semester_id' => '20231', 'label' => 'Sem 1', 'nama' => 'Sem 1'],
            ['semester_id' => '20232', 'label' => 'Sem 2', 'nama' => 'Sem 2'],
            ['semester_id' => '20241', 'label' => 'Sem 3', 'nama' => 'Sem 3'],
        ];

        $mapelList = [
            ['mata_pelajaran_id' => 'MTK', 'nama_mata_pelajaran' => 'Matematika', 'kelompok_id' => 1, 'no_urut' => 1],
        ];

        $students = [
            [
                'peserta_didik_id' => 'pd-1',
                'nama' => 'DEWI',
                'nisn' => '003',
                'semester_ids' => ['20231', '20232', '20241'],
                // (80 + 81 + 85) / 3 = 246 / 3 = 82.0; let's use 80, 81, 84 => 245/3 = 81.6666... => 81.67
                'scores' => ['MTK' => ['20231' => 80, '20232' => 81, '20241' => 84]],
                'nilai_pkl' => null,
                'nilai_ukk' => null,
            ],
        ];

        $result = $service->aggregateLeggerData($students, $semesters, $mapelList);

        $this->assertSame(81.67, $result['siswa_list'][0]['nilai_per_mapel']['MTK']['rata_rata']);
        $this->assertSame(81.67, $result['siswa_list'][0]['rata_rata_total']);
    }

    public function test_detects_semester_genap_from_semester_id(): void
    {
        $service = new LegerAkhirService();

        $this->assertTrue($service->isSemesterGenap('20252'));
        $this->assertTrue($service->isSemesterGenap('20262'));
        $this->assertFalse($service->isSemesterGenap('20251'));
        $this->assertFalse($service->isSemesterGenap('20261'));
        $this->assertFalse($service->isSemesterGenap(''));
    }
}
