@php
    use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

    $semCount = count($semesters);
    $mapelCount = count($mapel_list);
    $firstMapelCol = $mapelCount > 0 ? Coordinate::stringFromColumnIndex(5) : null;
    $lastMapelCol = $mapelCount > 0 ? Coordinate::stringFromColumnIndex(4 + $mapelCount) : null;
@endphp
<table class="table" border="1">
    <tr>
        <td colspan="2">Sekolah</td>
        <td colspan="2">{{ $rombel->sekolah->nama ?? '-' }}</td>
    </tr>
    <tr>
        <td colspan="2">NPSN</td>
        <td colspan="2">{{ $rombel->sekolah->npsn ?? '-' }}</td>
    </tr>
    <tr>
        <td colspan="2">Kelas</td>
        <td colspan="2">{{ $rombel->nama }}</td>
    </tr>
    <tr>
        <td colspan="2">Tahun Pelajaran</td>
        <td colspan="2">{{ $tahun_ajaran }}</td>
    </tr>
</table>
<table class="table" border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>NAMA PESERTA DIDIK</th>
            <th>NISN</th>
            <th>Jml Sem</th>
            @foreach ($mapel_list as $mapel)
                <th>{{ $mapel['nama_mata_pelajaran'] }}</th>
            @endforeach
            <th>PKL</th>
            <th>UKK</th>
            <th>Rata-rata Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($siswa_list as $siswa)
            @php
                $excelRow = 7 + $loop->index;
                $rincianRow = 8 + $loop->index;
                $jmlSemText = $siswa['semester_count'] . '/' . $max_semester_count . ($siswa['is_incomplete'] ? ' (Belum Lengkap)' : '');
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $siswa['nama'] }}</td>
                <td>{{ $siswa['nisn'] }}</td>
                <td>{{ $jmlSemText }}</td>
                @foreach ($mapel_list as $mIdx => $mapel)
                    @if ($link_to_rincian && $semCount > 0)
                        @php
                            $rincianAvgColIdx = 5 + ($mIdx * ($semCount + 1)) + $semCount;
                            $rincianAvgCol = Coordinate::stringFromColumnIndex($rincianAvgColIdx);
                        @endphp
                        <td>='Rincian Per Semester'!{{ $rincianAvgCol }}{{ $rincianRow }}</td>
                    @else
                        @php
                            $val = $siswa['nilai_per_mapel'][$mapel['mata_pelajaran_id']]['rata_rata'] ?? null;
                        @endphp
                        <td>{{ $val !== null ? $val : '-' }}</td>
                    @endif
                @endforeach
                <td>{{ $siswa['nilai_pkl'] !== null ? $siswa['nilai_pkl'] : '-' }}</td>
                <td>{{ $siswa['nilai_ukk'] !== null ? $siswa['nilai_ukk'] : '-' }}</td>
                <td>
                    @if ($firstMapelCol && $lastMapelCol)
                        =IFERROR(ROUND(AVERAGE({{ $firstMapelCol }}{{ $excelRow }}:{{ $lastMapelCol }}{{ $excelRow }}), 2), "-")
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
