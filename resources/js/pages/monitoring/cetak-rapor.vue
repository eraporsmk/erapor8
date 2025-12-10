<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Waka',
    title: 'Monitoring \u00bb Cetak Rapor',
  },
})
const form = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  merdeka: false,
  rapor_pts: false,
  is_ppa: false,
  is_new_ppa: true,
})
const arrayData = ref({
  rombel: [],
  siswa: [],
})
const loading = ref({
  rombel: false,
  body: false,
})
const getData = async (postData) => {
  const mergedForm = { ...postData, ...form.value };
  await $api("/monitoring/get-data", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data;
      if (postData.data == "rombel") {
        arrayData.value.rombel = getData;
      }
      if (postData.data == "siswa") {
        arrayData.value.siswa = getData.data_siswa
        form.value.merdeka = getData.merdeka
        form.value.rapor_pts = getData.rapor_pts
        form.value.is_ppa = getData.is_ppa
        form.value.is_new_ppa = getData.is_new_ppa
      }
    },
  });
}
const changeTingkat = async (val) => {
  form.value.rombongan_belajar_id = null;
  arrayData.value.rombel = []
  arrayData.value.siswa = []
  if (val) {
    loading.value.rombel = true;
    await getData({
      data: "rombel",
    }).then(() => {
      loading.value.rombel = false;
    });
  }
}
const changeRombel = async (val) => {
  arrayData.value.siswa = []
  if (val) {
    loading.value.body = true;
    await getData({
      data: "siswa",
    }).then(() => {
      loading.value.body = false;
    });
  }
}
</script>
<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Monitoring &raquo; Cetak Rapor</VCardTitle>
    </VCardItem>
    <VDivider />
    <VCardText>
      <VRow>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="semester_id">Tahun Pelajaran</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppTextField id="semester_id" :value="$semester.nama" disabled />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="tingkat">Tingkat Kelas</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppSelect v-model="form.tingkat" placeholder="== Pilih Tingkat kelas ==" :items="tingkatKelas" clearable
                clear-icon="tabler-x" @update:model-value="changeTingkat" />
            </VCol>
          </VRow>
        </VCol>
        <VCol cols="12">
          <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
              <label class="v-label text-body-2 text-high-emphasis" for="rombonganBelajarId">Rombongan
                Belajar</label>
            </VCol>
            <VCol cols="12" md="9">
              <AppSelect v-model="form.rombongan_belajar_id" placeholder="== Pilih Rombongan Belajar == "
                :items="arrayData.rombel" clearable clear-icon="tabler-x" @update:model-value="changeRombel"
                item-value="rombongan_belajar_id" item-title="nama" :loading="loading.rombel"
                :disabled="loading.rombel" />
            </VCol>
          </VRow>
        </VCol>
      </VRow>
    </VCardText>
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
            <th class="text-center" v-if="form.rapor_pts">Rapor Tengah Semester</th>
            <th class="text-center" v-if="form.merdeka && !form.is_new_ppa">Rapor P5</th>
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
                :href="`/cetak/rapor-cover/${item.peserta_didik_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-if="form.is_new_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-akademik/${item.peserta_didik_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-else-if="form.merdeka || form.is_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-nilai-akhir/${item.anggota_rombel.anggota_rombel_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-else>
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-semester/${item.peserta_didik_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-if="form.rapor_pts">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="primary" variant="text"
                :href="`/cetak/rapor-tengah-semester/${item.peserta_didik_id}/${form.semester_id}`" target="_blank" />
            </td>
            <td class="text-center" v-if="form.merdeka && !form.is_new_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="info" variant="text"
                :href="`/cetak/rapor-p5/${item.anggota_rombel.anggota_rombel_id}/${form.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="error" variant="text"
                :href="`/cetak/rapor-pelengkap/${item.peserta_didik_id}/${form.sekolah_id}/${form.semester_id}`"
                target="_blank" />
            </td>
          </tr>
        </tbody>
      </VTable>
    </template>
  </VCard>
</template>
