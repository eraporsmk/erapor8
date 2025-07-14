<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Wali',
    title: 'Catatan Sikap'
  },
})
onMounted(async () => {
  await fetchData();
});
const loadingBody = ref(false)
const defaultForm = ref({
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  semester_id: $semester.semester_id,
  periode_aktif: $semester.nama,
  aksi: 'catatan-sikap',
})
const form = ref({
  merdeka: false,
  uraian_sikap: {},
})
const arrayData = ref({
  siswa: [],
  budaya: [],
})
const confirmed = ref(false)
const isConfirmDialogVisible = ref(false)
const isNotifVisible = ref(false)
const notif = ref({
  color: null,
  title: null,
  text: null,
})
const fetchData = async () => {
  loadingBody.value = true;
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
    form.value.merdeka = getData.merdeka
    arrayData.value.siswa = getData.data_siswa
    arrayData.value.budaya = getData.budaya_kerja
    getData.data_siswa.forEach((siswa) => {
      siswa.anggota_rombel.all_catatan_budaya_kerja.forEach((catatan) => {
        form.value.uraian_sikap[siswa.anggota_rombel.anggota_rombel_id + '#' + catatan.budaya_kerja_id] = catatan.catatan
      })
    })
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
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
      <VCardTitle>Catatan Sikap</VCardTitle>
    </VCardItem>
    <VDivider />
    <template v-if="loadingBody">
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else>
      <VCardText v-if="form.merdeka">
        <VAlert color="error" class="text-center my-4" variant="tonal">
          <h2 class="mt-4 mb-4">Fitur ditutup!</h2>
          <p>Fitur Catatan Sikap hanya untuk <strong>Kurikulum 2013</strong></p>
        </VAlert>
      </VCardText>
      <template v-else>
        <VForm @submit.prevent="onSubmit">
          <VTable>
            <thead>
              <tr>
                <th>Nama Peserta Didik</th>
                <th>Catatan Sikap dari Guru</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="siswa in arrayData.siswa">
                <tr>
                  <td rowspan="2">
                    <ProfileSiswa :item="siswa" />
                  </td>
                  <td class="px-0 py-0" style="padding-inline-end:0px !important;"
                    v-if="siswa.anggota_rombel.nilai_budaya_kerja_guru.length">
                    <TableCatatanGuru :siswa="siswa" />
                  </td>
                  <td v-else>Tidak ada catatan sikap dari guru</td>
                </tr>
                <tr>
                  <td class="px-0 py-0" style="padding-inline-end:0px !important; padding-inline-start: 0px !important">
                    <h2 class="text-center">Catatan Sikap Wali Kelas</h2>
                    <TableCatatanWalas :arrayData="arrayData" :form="form" :siswa="siswa" />
                  </td>
                </tr>
              </template>
            </tbody>
          </VTable>
          <VCardText class="d-flex justify-end flex-wrap gap-3 pt-5 overflow-visible">
            <VBtn variant="elevated" type="submit" :loading="confirmed" :disabled="confirmed">
              Simpan
            </VBtn>
          </VCardText>
        </VForm>
      </template>
    </template>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" confirmation-text="Tindakan ini tidak dapat dikembalikan!"
      :confirm-color="notif.color" :confirm-title="notif.title" :confirm-msg="notif.text" />
  </VCard>
</template>
