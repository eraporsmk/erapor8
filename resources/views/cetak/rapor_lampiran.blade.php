@extends('layouts.cetak')
@section('content')
    <table border="0" width="100%" style="margin-bottom: 0px;">
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
        <table class="table table-bordered" style="margin-bottom: 10px;">
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
        
    @endif
    @if ($pd->kelas->semester->tahun_ajaran_id >= 2025)
        <table class="table table-bordered" style="margin-bottom: 10px;">
            <thead>
                <tr>
                    <th class="text-center">Kokurikuler</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: justify;">
                        {{ $pd->kokurikuler?->uraian_deskripsi }}
                    </td>
                </tr>
            </tbody>
        </table>
    @endif
    <table class="table table-bordered" style="">
        <thead>
            <tr>
                <th style="width: 5%;" style="vertical-align: middle; text-align: center;">No</th>
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
                        <td style="text-align: justify;">{{ $ekskul->single_nilai_ekstrakurikuler?->deskripsi_ekskul }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td class="text-center" colspan="3">&nbsp;</td>
                </tr>
            @endif
        </tbody>
    </table>
    <table style="width: 100%; margin-left:-2px; margin-right:-2px;margin-bottom: 8px;">
        <tr>
            <td style="width: 47%; vertical-align: top; padding:0px;">
                <table class="table table-bordered" style="margin-bottom: 0px;">
                    <thead>
                        <tr>
                            <th class="text-center" style="vertical-align: middle;" colspan="2">Ketidakhadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sakit</td>
                            <td> : {{ $pd->kehadiran ? $pd->kehadiran->sakit ?? 0 : 0 }} hari</td>
                        </tr>
                        <tr>
                            <td>Izin</td>
                            <td> : {{ $pd->kehadiran ? $pd->kehadiran->izin ?? 0 : 0 }} hari</td>
                        </tr>
                        <tr>
                            <td>Tanpa Keterangan</td>
                            <td> : {{ $pd->kehadiran ? $pd->kehadiran->alpa ?? 0 : 0 }} hari</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 4%">&nbsp;</td>
            <td style="width: 47%; vertical-align: top; padding:0px;">
                <table class="table table-bordered" style="margin-bottom: 0px;">
                    <thead>
                        <tr>
                            <th class="text-center">Catatan Wali Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: justify;">
                                {{ $pd->catatan_walas?->uraian_deskripsi }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    
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
    @if ($pd->kelas->semester->semester == 2)
        <table width="100%" class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-center">{{ $text_status }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:10px;">
                        @if ($pd->kenaikan)
                            @if ($pd->kenaikan->status == 3)
                                LULUS
                            @else
                                {{ status_kenaikan($pd->kenaikan->status) }} {{ $pd->kenaikan->nama_kelas }}
                            @endif
                        @else
                            {{ $not_yet }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    @endif
    <table class="table table-bordered" style="margin-bottom: 0px;">
        <thead>
            <tr>
                <th class="text-center">Tanggapan Orang Tua/Wali Murid</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <br><br><br><br><br>
                </td>
            </tr>
        </tbody>
    </table>
    <table width="100%" style="margin-bottom: 0px;">
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
    <table width="100%" style="margin-bottom:0px;">
        <tr>
            <td style="width:40%;padding-right:0px;" class="text-right">
                <p>{{ $extend }}</p>
                <br>
                <br>
                <br>
                <p>&nbsp;</p>
            </td>
            <td>
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
                @endif
                <br>
                <p class="nama_ttd">
                    <strong><u>
                            @if ($pd->kelas->sekolah->kasek)
                                {{ $pd->kelas->sekolah->kasek->nama_lengkap }}
                            @elseif($pd->kelas->sekolah->kepala_sekolah)
                                {{ $pd->kelas->sekolah->kepala_sekolah?->nama_lengkap }}
                            @endif
                        </u></strong><br/>
                        NIP.
                        @if ($pd->kelas->sekolah->kasek)
                            {{ $pd->kelas->sekolah->kasek->nip }}
                        @elseif($pd->kelas->sekolah->kepala_sekolah)
                            {{ $pd->kelas->sekolah->kepala_sekolah?->nip }}
                        @endif
                </p>
            </td>
        </tr>
    </table>
@endsection
