@php
    use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

    $semCount = count($semesters);
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
            <th rowspan="2">No</th>
            <th rowspan="2">NAMA PESERTA DIDIK</th>
            <th rowspan="2">NISN</th>
            <th rowspan="2">Jml Sem</th>
            @foreach ($mapel_list as $mapel)
                <th colspan="{{ $semCount + 1 }}">{{ $mapel['nama_mata_pelajaran'] }}</th>
            @endforeach
            <th rowspan="2">PKL</th>
            <th rowspan="2">UKK</th>
        </tr>
        <tr>
            @foreach ($mapel_list as $mapel)
                @foreach ($semesters as $sem)
                    <th>{{ $sem['label'] }}</th>
                @endforeach
                <th>Rata-rata</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($siswa_list as $siswa)
            @php
                $excelRow = 8 + $loop->index;
                $jmlSemText = $siswa['semester_count'] . '/' . $max_semester_count . ($siswa['is_incomplete'] ? ' (Belum Lengkap)' : '');
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $siswa['nama'] }}</td>
                <td>{{ $siswa['nisn'] }}</td>
                <td>{{ $jmlSemText }}</td>
                @foreach ($mapel_list as $mIdx => $mapel)
                    @php
                        $mapelId = $mapel['mata_pelajaran_id'];
                        $startColIdx = 5 + ($mIdx * ($semCount + 1));
                        $endColIdx = $startColIdx + $semCount - 1;
                        $startCol = Coordinate::stringFromColumnIndex($startColIdx);
                        $endCol = Coordinate::stringFromColumnIndex($endColIdx);
                    @endphp
                    @foreach ($semesters as $sem)
                        @php
                            $val = $siswa['nilai_per_mapel'][$mapelId]['per_semester'][$sem['semester_id']] ?? null;
                        @endphp
                        <td>{{ $val !== null ? $val : '' }}</td>
                    @endforeach
                    <td>
                        @if ($semCount > 0)
                            =IFERROR(ROUND(AVERAGE({{ $startCol }}{{ $excelRow }}:{{ $endCol }}{{ $excelRow }}), 2), "-")
                        @else
                            -
                        @endif
                    </td>
                @endforeach
                <td>{{ $siswa['nilai_pkl'] !== null ? $siswa['nilai_pkl'] : '-' }}</td>
                <td>{{ $siswa['nilai_ukk'] !== null ? $siswa['nilai_ukk'] : '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
