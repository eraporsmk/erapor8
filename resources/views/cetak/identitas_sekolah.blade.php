@extends('layouts.cetak')
@section('content')
    <div class="text-center">
        <h3>RAPOR PESERTA DIDIK<br>SEKOLAH MENENGAH KEJURUAN<br>(SMK)</h3><br>
    </div>
    <table width="100%">
        <tr>
            <td style="width: 30%;padding:20px;">Nama Sekolah</td>
            <td style="width: 5%">:</td>
            <td style="width: 65%">{{ $pd->sekolah->nama }}</td>
        </tr>
        <tr>
            <td style="width: 30%;padding:20px;">NPSN / NSS</td>
            <td style="width: 5%">:</td>
            <td style="width: 65%">{{ $pd->sekolah->npsn }} / {{ $pd->sekolah->nss }}</td>
        </tr>
        <tr>
        <tr>
            <td style="width: 30%;padding:20px;">Alamat</td>
            <td style="width: 5%">:</td>
            <td style="width: 65%">{{ $pd->sekolah->alamat }}</td>
        </tr>
        <tr>
            <td style="width: 30%; padding:20px;">&nbsp;</td>
            <td style="width: 5%">&nbsp;</td>
            <td style="width: 65%">Kode Pos {{ $pd->sekolah->kode_pos }} Telp. {{ $pd->sekolah->no_telp }}</td>
        </tr>
        <tr>
            <td style="width: 30%;padding:20px;">Kelurahan</td>
            <td style="width: 5%">:</td>
            <td style="width: 65%">{{ $pd->sekolah->desa_kelurahan }}</td>
        </tr>
        <tr>
            <td style="width: 30%;padding:20px;">Kecamatan</td>
            <td style="width: 5%">:</td>
            <td style="width: 65%">{{ str_replace('Kec. ', '', $pd->sekolah->kecamatan) }}</td>
        </tr>
        <tr>
            <td style="width: 30%;padding:20px;">Kabupaten/Kota</td>
            <td style="width: 5%">:</td>
            <td style="width: 65%">{{ str_replace('Kab. ', '', $pd->sekolah->kabupaten) }}</td>
        </tr>
        <tr>
            <td style="width: 30%;padding:20px;">Provinsi</td>
            <td style="width: 5%">:</td>
            <td style="width: 65%">{{ str_replace('Prov. ', '', $pd->sekolah->provinsi) }}</td>
        </tr>
        <tr>
            <td style="width: 30%;padding:20px;">Website</td>
            <td style="width: 5%">:</td>
            <td style="width: 65%">{{ $pd->sekolah->website }}</td>
        </tr>
        <tr>
            <td style="width: 30%;padding:20px;">Email</td>
            <td style="width: 5%">:</td>
            <td style="width: 65%">{{ $pd->sekolah->email }}</td>
        </tr>
    </table>
@endsection
