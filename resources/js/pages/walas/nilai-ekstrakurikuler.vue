<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Wali',
    title: 'Nilai Ekstrakurikuler'
  },
})
onMounted(async () => {
  await fetchData();
});
const loading = ref({
  body: false,
  table: false,
})
const defaultForm = ref({
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  semester_id: $semester.semester_id,
  periode_aktif: $semester.nama,
  aksi: 'nilai-ekskul',
})
const arrayData = ref({
  siswa: [],
})
const confirmed = ref(false)
const isConfirmDialogVisible = ref(false)
const isNotifVisible = ref(false)
const notif = ref({
  color: null,
  title: null,
  text: null,
})
const errors = ref({
  dudi_id: null,
})
const fetchData = async () => {
  loading.value.body = true;
  try {
    const response = await useApi(createUrl('/walas', {
      query: {
        user_id: defaultForm.value.user_id,
        guru_id: defaultForm.value.guru_id,
        sekolah_id: defaultForm.value.sekolah_id,
        semester_id: defaultForm.value.semester_id,
        periode_aktif: defaultForm.value.nama,
        aksi: defaultForm.value.aksi,
      },
    }));
    let getData = response.data.value
    arrayData.value.siswa = getData.data_siswa
  } catch (error) {
    console.error(error);
  } finally {
    loading.value.body = false;
  }
}
const nilaiEkskul = (val) => {
  if (val) {
    var predikat = {
      1: 'Sangat Baik',
      2: 'Baik',
      3: 'Cukup',
      4: 'Kurang',
    }
    return predikat[val];
  }
  return '-';
}
</script>
<template>
  <VCard>
    <VCardItem class="pb-4">
      <VCardTitle>Nilai Ekstrakurikuler</VCardTitle>
    </VCardItem>
    <template v-if="loading.body">
      <VDivider />
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else>
      <VTable>
        <thead>
          <tr>
            <th class="text-center">Nama Peserta Didik</th>
            <th class="text-center">Nama Eskul</th>
            <th class="text-center">Pembina</th>
            <th class="text-center">Predikat</th>
            <th class="text-center">Deskripsi</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="item in arrayData.siswa">
            <tr>
              <td :rowspan="item.anggota_rombel.anggota_ekskul.length + 1" class="border-right">
                <ProfileSiswa :item="item" />
              </td>
            </tr>
            <template v-for="anggota_ekskul in item.anggota_rombel.anggota_ekskul">
              <tr>
                <td>{{ anggota_ekskul.rombongan_belajar.kelas_ekskul.nama_ekskul }}</td>
                <td>{{ anggota_ekskul.rombongan_belajar.kelas_ekskul.guru.nama_lengkap }}</td>
                <td>{{ nilaiEkskul(anggota_ekskul.single_nilai_ekstrakurikuler?.nilai) }}</td>
                <td>{{ anggota_ekskul.single_nilai_ekstrakurikuler?.deskripsi_ekskul }}</td>
              </tr>
            </template>
          </template>
        </tbody>
      </VTable>
    </template>
  </VCard>
</template>
