@extends('layouts.cetak')
@section('content')
    <table border="0" width="100%">
        <tr>
            <td style="width: 25%;padding-top:5px; padding-bottom:5px; padding-left:0px;">Nama Peserta Didik</td>
            <td style="width: 1%;" class="text-center">:</td>
            <td style="width: 74%">{{ strtoupper($pd->nama) }}</td>
        </tr>
        <tr>
            <td style="padding-top:5px; padding-bottom:5px; padding-left:0px;">Nomor Induk/NISN</td>
            <td class="text-center">:</td>
            <td>{{ $pd->no_induk . ' / ' . $pd->nisn }}</td>
        </tr>
        <tr>
            <td style="padding-top:5px; padding-bottom:5px; padding-left:0px;">Kelas</td>
            <td class="text-center">:</td>
            <td>{{ $pd->kelas->nama }}</td>
        </tr>
        <tr>
            <td style="padding-top:5px; padding-bottom:5px; padding-left:0px;">Tahun Ajaran</td>
            <td class="text-center">:</td>
            <td>
                {{ $pd->kelas->semester->tahun_ajaran_id }}/{{ $pd->kelas->semester->tahun_ajaran_id + 1 }}
                {{-- str_replace('/','-',substr($pd->kelas->semester->nama,0,9)) --}}
            </td>
        </tr>
        <tr>
            <td style="padding-top:5px; padding-bottom:5px; padding-left:0px;">Semester</td>
            <td class="text-center">:</td>
            <td>{{ substr($pd->kelas->semester->nama, 10) }}</td>
        </tr>
    </table>
    <br />
    <?php
    if ($pd->kelas->tingkat == 10) {
        if (merdeka($pd->kelas->kurikulum->nama_kurikulum)) {
            $huruf_ekskul = 'B';
            $huruf_absen = 'C';
            $huruf_kenaikan = 'D';
        } else {
            if ($pd->prakerin->count()) {
                $huruf_ekskul = 'D';
                $huruf_absen = 'E';
                $huruf_kenaikan = 'F';
            } else {
                $huruf_ekskul = 'C';
                $huruf_absen = 'D';
                $huruf_kenaikan = 'E';
            }
        }
    } else {
        if (merdeka($pd->kelas->kurikulum->nama_kurikulum)) {
            if ($pd->prakerin->count()) {
                $huruf_ekskul = 'D';
                $huruf_absen = 'E';
                $huruf_kenaikan = 'F';
            } else {
                if ($pd->pd_pkl) {
                    $huruf_ekskul = 'B';
                    $huruf_absen = 'C';
                    $huruf_kenaikan = 'D';
                } else {
                    $huruf_ekskul = 'C';
                    $huruf_absen = 'D';
                    $huruf_kenaikan = 'E';
                }
            }
        } else {
            if ($pd->prakerin->count()) {
                $huruf_ekskul = 'D';
                $huruf_absen = 'E';
                $huruf_kenaikan = 'F';
            } else {
                $huruf_ekskul = 'C';
                $huruf_absen = 'D';
                $huruf_kenaikan = 'E';
            }
        }
    }
    ?>
    @if ($pd->kelas->tingkat != 10 && $pd->prakerin->count())
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 2px;" style="vertical-align: middle;">No</th>
                    <th style="width: 300px;" style="vertical-align: middle;">Mitra DU/DI</th>
                    <th style="width: 200px;" style="vertical-align: middle;">Lokasi</th>
                    <th style="width: 100px;" style="vertical-align: middle;">Lamanya<br>(bulan)</th>
                    <th style="width: 100px;" style="vertical-align: middle;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @if ($pd->prakerin->count())
                    @foreach ($pd->prakerin as $prakerin)
                        <tr>
                            <td style="vertical-align: middle;">{{ $loop->iteration }}</td>
                            <td>{{ $prakerin->mitra_prakerin }}</td>
                            <td style="vertical-align: middle;">{{ $prakerin->lokasi_prakerin }}</td>
                            <td style="vertical-align: middle;" class="text-center">{{ $prakerin->lama_prakerin }}</td>
                            <td>{{ $prakerin->keterangan_prakerin }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-center" colspan="5">&nbsp;</td>
                    </tr>
                @endif
            </tbody>
        </table>
        <br />
    @endif
    @if ($pd->kelas->semester->tahun_ajaran_id >= 2025)
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center">Kokurikuler</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        {{ $pd->kokurikuler?->uraian_deskripsi }}
                    </td>
                </tr>
            </tbody>
        </table>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="width: 5%;" style="vertical-align: middle;">No</th>
                <th style="width: 35%;" style="vertical-align: middle;">Ekstrakurikuler</th>
                <th style="width: 60%;" style="vertical-align: middle;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @if ($pd->ekskul->count())
                @foreach ($pd->ekskul as $ekskul)
                    <tr>
                        <td style="vertical-align: middle;">{{ $loop->iteration }}</td>
                        <td>{{ strtoupper($ekskul->rombongan_belajar?->nama) }}</td>
                        <td>{{ $ekskul->single_nilai_ekstrakurikuler?->deskripsi_ekskul }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td class="text-center" colspan="3">&nbsp;</td>
                </tr>
            @endif
        </tbody>
    </table>
    <br />
    <table style="width: 100%">
        <tr>
            <td style="width: 45%; vertical-align: top;">
                <table class="table table-bordered">
                    <tr>
                        <td>Sakit</td>
                        <td> : {{ $pd->kehadiran ? ($pd->kehadiran->sakit??0 == 0 ? '-' : $pd->kehadiran->sakit) : '-' }} hari</td>
                    </tr>
                    <tr>
                        <td>Izin</td>
                        <td> : {{ $pd->kehadiran ? ($pd->kehadiran->izin??0 == 0 ? '-' : $pd->kehadiran->izin) : '-' }} hari</td>
                    </tr>
                    <tr>
                        <td>Tanpa Keterangan</td>
                        <td> : {{ $pd->kehadiran ? ($pd->kehadiran->alpa??0 == 0 ? '-' : $pd->kehadiran->alpa) : '-' }} hari</td>
                    </tr>
                </table>
            </td>
            <td style="width: 10%">&nbsp;</td>
            <td style="width: 45%; vertical-align: top;">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center">Catatan Wali Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                {{ $pd->catatan_walas?->uraian_deskripsi }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    <br />
    <?php
    if ($pd->kelas->semester->semester == 2) {
        if ($opsi == 'lulus') {
            $text_status = 'Status Kelulusan';
            $not_yet = 'Belum dilakukan kelulusan';
        } else {
            $text_status = 'Kenaikan Kelas';
            $not_yet = 'Belum dilakukan kenaikan kelas';
        }
    } else {
        $text_status = '';
        $not_yet = '';
    }
    ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="text-center">Tanggapan Orang Tua/Wali Murid</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <br><br><br><br><br><br>
                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <table width="100%">
        <tr>
            <td style="width:30%">
                <p>Orang Tua/Wali</p><br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <p>..........................................</p>
            </td>
            <td style="width:5%"></td>
            <td style="width:55%; text-align: right;">
                <table width="auto">
                    <tr>
                        <td style="text-align: left;">
                            <p>{{ str_replace('Kab. ', '', $pd->sekolah->kabupaten) }},
                                {{ $tanggal_rapor }}<br>Wali Kelas</p><br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <p>
                                <strong><u>{{ $pd->kelas->wali_kelas->nama_lengkap }}</u></strong><br />
                                NIP. {{ $pd->kelas->wali_kelas->nip }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php
    $ks = get_setting('jabatan', $pd->sekolah_id, $pd->kelas->semester_id);
    $jabatan = str_replace('Plh. ', '', $ks);
    $jabatan = str_replace('Plt. ', '', $jabatan);
    $extend = str_replace('Kepala Sekolah', '', $ks);
    $extend = str_replace(' ', '', $extend);
    ?>
    <table width="100%" style="margin-top:10px;">
        <tr>
            <td style="width:40%;padding-right:0px;" class="text-right">
                <p>{{ $extend }}</p>
                <br>
                <br>
                <br>
                <br>
                <p>&nbsp;</p>
            </td>
            <td style="width:60%;">
                <p>Mengetahui,<br>{{ $jabatan }}</p>
                <br>
                @if (get_setting('ttd_kepsek', $pd->sekolah_id, $pd->kelas->semester_id))
                    <img src="{{ public_path('.' . get_setting('ttd_kepsek', $pd->sekolah_id, $pd->kelas->semester_id)) }}"
                        height="{{ get_setting('ttd_tinggi', $pd->sekolah_id, $pd->kelas->semester_id) . 'px' }}"
                        width="{{ get_setting('ttd_lebar', $pd->sekolah_id, $pd->kelas->semester_id) . 'px' }}"
                        class="ttd_kepsek">
                @else
                    <br>
                    <br>
                    <br>
                @endif
                <br>
                <p class="nama_ttd">
                    <strong><u>
                            @if ($pd->kelas->sekolah->kasek)
                                {{ $pd->kelas->sekolah->kasek->nama_lengkap }}
                            @elseif($pd->kelas->sekolah->kepala_sekolah)
                                {{ $pd->kelas->sekolah->kepala_sekolah?->nama_lengkap }}
                            @endif
                        </u></strong>
                </p>
            </td>
        </tr>
        <tr>
            <td style="width:40%;"></td>
            <td style="width:60%;" class="nip">
                NIP.
                @if ($pd->kelas->sekolah->kasek)
                    {{ $pd->kelas->sekolah->kasek->nip }}
                @elseif($pd->kelas->sekolah->kepala_sekolah)
                    {{ $pd->kelas->sekolah->kepala_sekolah?->nip }}
                @endif
            </td>
        </tr>
    </table>
@endsection
