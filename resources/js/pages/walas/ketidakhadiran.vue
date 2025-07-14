<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Wali',
    title: 'Ketidakhadiran'
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
  aksi: 'ketidakhadiran',
})
const form = ref({
  sakit: {},
  izin: {},
  alpa: {},
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
    getData.data_siswa.forEach((siswa) => {
      form.value.sakit[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.absensi?.sakit ?? 0
      form.value.izin[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.absensi?.izin ?? 0
      form.value.alpa[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.absensi?.alpa ?? 0
    })
  } catch (error) {
    console.error(error);
  } finally {
    loading.value.body = false;
  }
}
const onSubmit = async () => {
  confirmed.value = true
  const mergedForm = { ...defaultForm.value, ...form.value };
  await $api("/walas/save", {
    method: "POST",
    body: mergedForm,
    onResponseError({ response }) {
      console.log('ERROR:', response._data.errors);
      confirmed.value = false
    },
    onResponse({ response }) {
      confirmed.value = false
      let getData = response._data
      if (!getData.errors) {
        isNotifVisible.value = true
        notif.value = getData
      }
    }
  })
}
</script>
<template>
  <VCard>
    <VCardItem class="pb-4">
      <VCardTitle>Ketidakhadiran</VCardTitle>
    </VCardItem>
    <template v-if="loading.body">
      <VDivider />
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else>
      <VForm @submit.prevent="onSubmit">
        <VTable v-if="arrayData.siswa.length">
          <thead>
            <tr>
              <th class="text-center">Nama Peserta Didik</th>
              <th class="text-center">Sakit</th>
              <th class="text-center">Izin</th>
              <th class="text-center">Tanpa Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in arrayData.siswa">
              <td>
                <ProfileSiswa :item="item" />
              </td>
              <td>
                <AppTextField v-model="form.sakit[item.anggota_rombel.anggota_rombel_id]" />
              </td>
              <td>
                <AppTextField v-model="form.izin[item.anggota_rombel.anggota_rombel_id]" />
              </td>
              <td>
                <AppTextField v-model="form.alpa[item.anggota_rombel.anggota_rombel_id]" />
              </td>
            </tr>
          </tbody>
        </VTable>
        <VCardText class="d-flex justify-end flex-wrap gap-3 pt-5 overflow-visible" v-if="arrayData.siswa.length">
          <VBtn variant="elevated" type="submit" :loading="confirmed" :disabled="confirmed">
            Simpan
          </VBtn>
        </VCardText>
      </VForm>
    </template>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" confirmation-text="Tindakan ini tidak dapat dikembalikan!"
      :confirm-color="notif.color" :confirm-title="notif.title" :confirm-msg="notif.text" />
  </VCard>
</template>
