@extends('layouts.cetak')
@section('content')
    @if (!merdeka($pd->kelas->kurikulum->nama_kurikulum))
        <table border="0" width="100%">
            <tr>
                <td style="width: 20%;padding:0px;">Nama Peserta Didik</td>
                <td style="width: 50%">: {{ strtoupper($pd->nama) }}</td>
                <td style="padding:0px;width: 15%">Kelas</td>
                <td style="width: 15%">: {{ $pd->kelas->nama }}</td>
            </tr>
            <tr>
                <td style="padding:0px;">Nomor Induk/NISN</td>
                <td>: {{ $pd->no_induk . ' / ' . $pd->nisn }}</td>
                <td style="padding:0px;">Semester</td>
                <td>: {{ substr($pd->kelas->semester->nama, 10) }}</td>
            </tr>
            <tr>
                <td style="padding:0px;">Sekolah</td>
                <td>: {{ $pd->kelas->sekolah->nama }}</td>
                <td style="padding:0px;">Tahun Pelajaran</td>
                <td>:
                    {{ $pd->kelas->semester->tahun_ajaran_id }}/{{ $pd->kelas->semester->tahun_ajaran_id + 1 }}
                    {{-- str_replace('/','-',substr($pd->kelas->semester->nama,0,9)) --}}
                </td>
            </tr>
            <tr>
                <td style="padding:0px;">Alamat</td>
                <td>: {{ $pd->kelas->sekolah->alamat }}</td>
                <td></td>
                <td></td>
            </tr>
        </table>
    @else
        <table border="0" width="100%">
            <tr>
                <td style="width: 20%;padding:0px;">Nama Peserta Didik</td>
                <td style="width: 50%">: {{ strtoupper($pd->nama) }}</td>
                <td style="padding:0px;width: 15%">Kelas</td>
                <td style="width: 15%">: {{ $pd->kelas->nama }}</td>
            </tr>
            <tr>
                <td style="padding:0px;">Nomor Induk/NISN</td>
                <td>: {{ $pd->no_induk . ' / ' . $pd->nisn }}</td>
                <td style="padding:0px;">Fase</td>
                <td>: {{ $pd->kelas->tingkat == 10 ? 'E' : 'F' }}</td>
            </tr>
            <tr>
                <td style="padding:0px;">Sekolah</td>
                <td>: {{ $pd->kelas->sekolah->nama }}</td>
                <td style="padding:0px;">Semester</td>
                <td>:
                    {{ substr($pd->kelas->semester->nama, 10) }}
                </td>
            </tr>
            <tr>
                <td style="padding:0px;">Alamat</td>
                <td>: {{ $pd->kelas->sekolah->alamat }}</td>
                <td style="padding:0px;">Tahun Pelajaran</td>
                <td>:
                    {{ $pd->kelas->semester->tahun_ajaran_id }}/{{ $pd->kelas->semester->tahun_ajaran_id + 1 }}
                    {{-- str_replace('/','-',substr($pd->kelas->semester->nama,0,9)) --}}
                </td>
            </tr>
        </table>
    @endif
    <br />
    <table class="table table-bordered" border="1">
        <thead>
            <tr>
                <th style="vertical-align:middle;" class="text-center" width="7%">No</th>
                <th style="vertical-align:middle;" class="text-center" width="30%">Mata Pelajaran</th>
                <th style="vertical-align:middle;" class="text-center" width="10%">Nilai Akhir</th>
                <th style="vertical-align:middle;" class="text-center" width="48%">Capaian Kompetensi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $all_pembelajaran = [];
            $get_pembelajaran = [];
            foreach ($set_pembelajaran as $pembelajaran) {
                if (in_array($pembelajaran->mata_pelajaran_id, mapel_agama())) {
                    if (filter_pembelajaran_agama($pd->agama->nama, $pembelajaran->mata_pelajaran->nama)) {
                        $get_pembelajaran[$pembelajaran->pembelajaran_id] = $pembelajaran;
                    }
                } else {
                    $get_pembelajaran[$pembelajaran->pembelajaran_id] = $pembelajaran;
                }
            }
            ?>
            @foreach ($get_pembelajaran as $pembelajaran)
                <?php
                if (merdeka($pd->kelas->kurikulum->nama_kurikulum)) {
                    $nilai_akhir = $pembelajaran->nilai_akhir_kurmer ? number_format($pembelajaran->nilai_akhir_kurmer->nilai, 0) : 0;
                } else {
                    $nilai_akhir = $pembelajaran->nilai_akhir_pengetahuan ? number_format($pembelajaran->nilai_akhir_pengetahuan->nilai, 0) : 0;
                }
                $produktif = [4, 5, 9, 10, 13];
                if (in_array($pembelajaran->kelompok_id, $produktif)) {
                    $produktif = 1;
                } else {
                    $produktif = 0;
                }
                $all_pembelajaran[$pembelajaran->kelompok->nama_kelompok][] = [
                    'deskripsi_mata_pelajaran' => $pembelajaran->single_deskripsi_mata_pelajaran,
                    'nama_mata_pelajaran' => $pembelajaran->nama_mata_pelajaran,
                    'nilai_akhir' => $nilai_akhir,
                ];
                $i = 1;
                ?>
            @endforeach
            @foreach ($all_pembelajaran as $kelompok => $data_pembelajaran)
                @if ($kelompok == 'C1. Dasar Bidang Keahlian' || $kelompok == 'C3. Kompetensi Keahlian')
                    <tr>
                        <td colspan="4" class="strong"><strong style="font-size: 13px;">C. Muatan Peminatan
                                Kejuruan</strong></td>
                    </tr>
                @endif
                <tr>
                    <td colspan="4" class="strong"><strong style="font-size: 13px;">{{ $kelompok }}</strong></td>
                </tr>
                @foreach ($data_pembelajaran as $pembelajaran)
                    <?php
                    $pembelajaran = (object) $pembelajaran;
                    ?>
                    <tr>
                        <td class="text-center" style="vertical-align:middle;">{{ $i++ }}</td>
                        <td style="vertical-align:middle;">{{ $pembelajaran->nama_mata_pelajaran }}</td>
                        <td class="text-center" style="vertical-align:middle;">{{ $pembelajaran->nilai_akhir }}</td>
                        <td style="vertical-align:middle; text-align:justify;">
                            @if ($pembelajaran->deskripsi_mata_pelajaran)
                                @if (
                                    $pembelajaran->deskripsi_mata_pelajaran->deskripsi_pengetahuan &&
                                        $pembelajaran->deskripsi_mata_pelajaran->deskripsi_keterampilan)
                                    {{ $pembelajaran->deskripsi_mata_pelajaran->deskripsi_pengetahuan }}
                                    <div class="kotak">
                                        <hr class="baris">
                                    </div>
                                    {{ $pembelajaran->deskripsi_mata_pelajaran->deskripsi_keterampilan }}
                                @endif
                                @if (
                                    $pembelajaran->deskripsi_mata_pelajaran->deskripsi_pengetahuan &&
                                        !$pembelajaran->deskripsi_mata_pelajaran->deskripsi_keterampilan)
                                    {{ $pembelajaran->deskripsi_mata_pelajaran->deskripsi_pengetahuan }}
                                @endif
                                @if (
                                    !$pembelajaran->deskripsi_mata_pelajaran->deskripsi_pengetahuan &&
                                        $pembelajaran->deskripsi_mata_pelajaran->deskripsi_keterampilan)
                                    {{ $pembelajaran->deskripsi_mata_pelajaran->deskripsi_keterampilan }}
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
@endsection
