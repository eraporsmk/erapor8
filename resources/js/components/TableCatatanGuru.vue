<script setup>
const props = defineProps({
  siswa: {
    type: Object,
    required: true,
  },
})
</script>
<template>
  <VTable>
    <thead>
      <tr>
        <th>Nama Guru</th>
        <th>Dimensi Sikap</th>
        <th>Elemen Sikap</th>
        <th>Catatan Sikap</th>
        <th>Opsi Sikap</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="nilai_budaya_kerja_guru in siswa.anggota_rombel.nilai_budaya_kerja_guru">
        <td>{{ nilai_budaya_kerja_guru.guru.nama_lengkap }}</td>
        <td>{{ nilai_budaya_kerja_guru.budaya_kerja.aspek }}</td>
        <td>{{ nilai_budaya_kerja_guru.elemen_budaya_kerja.elemen }}</td>
        <td>{{ nilai_budaya_kerja_guru.deskripsi }}</td>
        <td>{{ (nilai_budaya_kerja_guru.opsi_id === 1) ? 'Positif' : 'Negatif' }}</td>
      </tr>
    </tbody>
  </VTable>
</template>
