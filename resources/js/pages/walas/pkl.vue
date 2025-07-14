<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Wali',
    title: 'Praktik Kerja Lapangan'
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
  aksi: 'praktik-kerja-lapangan',
})
const form = ref({
  merdeka: false,
  allowed: false,
  informasi: null,
  dudi_id: null,
  prakerin_id: {},
  lokasi_prakerin: {},
  skala: {},
  lama_prakerin: {},
  keterangan_prakerin: {},
})
const arrayData = ref({
  dudi: [],
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
    form.value.merdeka = getData.merdeka
    form.value.informasi = getData.notif
    form.value.allowed = getData.allowed
    arrayData.value.dudi = getData.data_dudi
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
const getData = async (postData) => {
  const mergedForm = { ...postData, ...defaultForm.value, ...form.value }
  await $api("/walas/get-data", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data
      arrayData.value.siswa = getData.data_siswa
      getData.data_siswa.forEach((siswa) => {
        form.value.prakerin_id[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_prakerin?.prakerin_id
        form.value.lokasi_prakerin[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_prakerin?.lokasi_prakerin ?? getData.dudi.alamat_jalan
        form.value.skala[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_prakerin?.skala
        form.value.lama_prakerin[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_prakerin?.lama_prakerin
        form.value.keterangan_prakerin[siswa.anggota_rombel.anggota_rombel_id] = siswa.anggota_rombel.single_prakerin?.keterangan_prakerin
      })
      loading.value.table = false
    },
  })
}
const changeDudi = async (val) => {
  if (val) {
    loading.value.table = true
    await getData({ data: 'anggota-pkl' })
  }
}
</script>
<template>
  <VCard>
    <VCardItem class="pb-4">
      <VCardTitle>Praktik Kerja Lapangan</VCardTitle>
    </VCardItem>
    <VDivider />
    <template v-if="loading.body">
      <VCardText class="text-center">
        <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
      </VCardText>
    </template>
    <template v-else>
      <template v-if="form.allowed">
        <VForm @submit.prevent="onSubmit">
          <VCardText>
            <VRow>
              <VCol cols="12">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">
                    <label class="v-label text-body-2 text-high-emphasis" for="dudi_id">DUDI</label>
                  </VCol>
                  <VCol cols="12" md="9">
                    <AppAutocomplete v-model="form.dudi_id" placeholder="== Pilih DUDI ==" :items="arrayData.dudi"
                      clearable clear-icon="tabler-x" @update:model-value="changeDudi" :error-messages="errors.dudi_id"
                      item-value="dudi_id" item-title="nama" />
                  </VCol>
                </VRow>
              </VCol>
            </VRow>
            <div class="text-center" v-if="loading.table">
              <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
            </div>
          </VCardText>
          <VTable v-if="arrayData.siswa.length">
            <thead>
              <tr>
                <th class="text-center">Nama Peserta Didik</th>
                <th class="text-center">Alamat DUDI</th>
                <th class="text-center">Skala Kesesuaian dengan Kompetensi Keahlian <br>(1-10)</th>
                <th class="text-center">Lamanya (bulan)</th>
                <th class="text-center">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in arrayData.siswa">
                <td>
                  <ProfileSiswa :item="item" />
                </td>
                <td>
                  <AppTextField v-model="form.lokasi_prakerin[item.anggota_rombel.anggota_rombel_id]" />
                </td>
                <td>
                  <AppSelect v-model="form.skala[item.anggota_rombel.anggota_rombel_id]" placeholder="== Pilih Skala =="
                    :items="[1, 2, 3, 4, 5, 6, 7, 8, 9, 10]" clearable clear-icon="tabler-x">
                  </AppSelect>
                </td>
                <td>
                  <AppTextField v-model="form.lama_prakerin[item.anggota_rombel.anggota_rombel_id]" />
                </td>
                <td>
                  <AppTextField v-model="form.keterangan_prakerin[item.anggota_rombel.anggota_rombel_id]" />
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
      <template v-else>
        <VCardText>
          <VAlert color="error" class="my-4" variant="tonal">
            <h2 class="mt-4 mb-4 text-center">Akses ditutup!</h2>
            <p v-html="form.informasi"></p>
          </VAlert>
        </VCardText>
      </template>
    </template>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" confirmation-text="Tindakan ini tidak dapat dikembalikan!"
      :confirm-color="notif.color" :confirm-title="notif.title" :confirm-msg="notif.text" />
  </VCard>
</template>
