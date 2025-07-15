<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TahunAjaran;
use App\Models\Semester;

class UpdateSmt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-smt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ajaran = [
            [
                'tahun_ajaran_id' => 2020,
                'nama' => '2020/2021',
                'periode_aktif' => 0,   
                'semester' => [
                    [
                        'semester_id' => 20201,
                        'nama' => '2020/2021 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 0,
                    ],
                    [
                        'semester_id' => 20202,
                        'nama' => '2020/2021 Genap',
                        'semester' => 2,
                        'periode_aktif' => 0,
                    ]
                ],
            ],
            [
                'tahun_ajaran_id' => 2021,
                'nama' => '2021/2022',
                'periode_aktif' => 0,   
                'semester' => [
                    [
                        'semester_id' => 20211,
                        'nama' => '2021/2022 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 1,
                    ],
                    [
                        'semester_id' => 20212,
                        'nama' => '2021/2022 Genap',
                        'semester' => 2,
                        'periode_aktif' => 0,
                    ]
                ],
            ],
            [
                'tahun_ajaran_id' => 2022,
                'nama' => '2022/2023',
                'periode_aktif' => 1,   
                'semester' => [
                    [
                        'semester_id' => 20221,
                        'nama' => '2022/2023 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 0,
                    ],
                    [
                        'semester_id' => 20222,
                        'nama' => '2022/2023 Genap',
                        'semester' => 2,
                        'periode_aktif' => 0,
                    ]
                ],
            ],
            [
                'tahun_ajaran_id' => 2023,
                'nama' => '2023/2024',
                'periode_aktif' => 1,   
                'semester' => [
                    [
                        'semester_id' => 20231,
                        'nama' => '2023/2024 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 0,
                    ],
                    [
                        'semester_id' => 20232,
                        'nama' => '2023/2024 Genap',
                        'semester' => 2,
                        'periode_aktif' => 1,
                    ]
                ],
            ],
            [
                'tahun_ajaran_id' => 2024,
                'nama' => '2024/2025',
                'periode_aktif' => 1,   
                'semester' => [
                    [
                        'semester_id' => 20241,
                        'nama' => '2024/2025 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 0,
                    ],
                    [
                        'semester_id' => 20242,
                        'nama' => '2024/2025 Genap',
                        'semester' => 2,
                        'periode_aktif' => 1,
                    ]
                ],
            ],
            [
                'tahun_ajaran_id' => 2025,
                'nama' => '2025/2026',
                'periode_aktif' => 1,   
                'semester' => [
                    [
                        'semester_id' => 20251,
                        'nama' => '2025/2026 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 0,
                    ],
                    [
                        'semester_id' => 20252,
                        'nama' => '2025/2026 Genap',
                        'semester' => 2,
                        'periode_aktif' => 0,
                    ]
                ],
            ]
        ];
        foreach($ajaran as $a){
            TahunAjaran::updateOrCreate(
                [
                    'tahun_ajaran_id' => $a['tahun_ajaran_id'],
                ],
                [
                    'nama' => $a['nama'],
                    'periode_aktif' => $a['periode_aktif'],
                    'tanggal_mulai' => '2020-07-20',
                    'tanggal_selesai' => '2021-06-01',
                    'last_sync' => now(),
                ]
            );
            foreach($a['semester'] as $semester){
                Semester::updateOrCreate(
                    [
                        'semester_id' => $semester['semester_id'],
                    ],
                    [
                        'tahun_ajaran_id' => $a['tahun_ajaran_id'],
                        'nama' => $semester['nama'],
                        'semester' => $semester['semester'],
                        'periode_aktif' => $semester['periode_aktif'],
                        'tanggal_mulai' => '2020-07-01',
                        'tanggal_selesai' => '2021-12-31',
                        'last_sync' => now(),
                    ]
                );
            }
        }
    }
}
