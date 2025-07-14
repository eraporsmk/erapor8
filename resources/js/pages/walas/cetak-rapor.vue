<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Wali',
    title: 'Cetak Rapor'
  },
})
onMounted(async () => {
  await fetchData();
});
const loading = ref({
  body: false,
})
const defaultForm = ref({
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  semester_id: $semester.semester_id,
  periode_aktif: $semester.nama,
  aksi: 'cetak-rapor',
  rapor_pts: false,
  merdeka: false,
  is_ppa: false,
})
const arrayData = ref({
  siswa: [],
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
    defaultForm.value.rapor_pts = getData.rapor_pts
    defaultForm.value.merdeka = getData.merdeka
    defaultForm.value.is_ppa = getData.is_ppa
    arrayData.value.siswa = getData.data_siswa
  } catch (error) {
    console.error(error);
  } finally {
    loading.value.body = false;
  }
}
</script>
<template>
  <VCard>
    <VCardItem class="pb-4">
      <VCardTitle>Cetak Rapor</VCardTitle>
    </VCardItem>
    <template v-if="loading.body">
      <VDivider />
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else>
      <VTable class="text-no-wrap" v-if="arrayData.siswa.length">
        <thead>
          <tr>
            <th class="text-center">Peserta Didik</th>
            <th class="text-center">Halaman Depan</th>
            <th class="text-center">Rapor Akademik</th>
            <th class="text-center" v-if="defaultForm.rapor_pts">Rapor Tengah Semester</th>
            <th class="text-center" v-if="defaultForm.merdeka">Rapor P5</th>
            <th class="text-center">Dokumen Pendukung</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in arrayData.siswa">
            <td>
              <ProfileSiswa :item="item" />
            </td>
            <td class="text-center">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="success" variant="text"
                :href="`/cetak/rapor-cover/${item.anggota_rombel.anggota_rombel_id}`" target="_blank" />
            </td>
            <td class="text-center" v-if="defaultForm.merdeka || defaultForm.is_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-nilai-akhir/${item.anggota_rombel.anggota_rombel_id}/${defaultForm.sekolah_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-else>
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-semester/${item.anggota_rombel.anggota_rombel_id}/${defaultForm.sekolah_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-if="defaultForm.rapor_pts">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="primary" variant="text"
                :href="`/cetak/rapor-tengah-semester/${item.anggota_rombel.anggota_rombel_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-if="defaultForm.merdeka">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="info" variant="text"
                :href="`/cetak/rapor-p5/${item.anggota_rombel.anggota_rombel_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="error" variant="text"
                :href="`/cetak/rapor-pelengkap/${item.anggota_rombel.anggota_rombel_id}/${item.anggota_rombel.rombongan_belajar_id}`"
                target="_blank" />
            </td>
          </tr>
        </tbody>
      </VTable>
    </template>
  </VCard>
</template>
