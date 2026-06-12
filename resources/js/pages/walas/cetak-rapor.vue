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
  is_new_ppa: true,
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
    defaultForm.value.is_new_ppa = getData.is_new_ppa
    arrayData.value.siswa = getData.data_siswa
  } catch (error) {
    console.error(error);
  } finally {
    loading.value.body = false;
  }
}

// =============================================
// Bulk Rapor
// =============================================
const bulkForm = ref({
  komponen: {
    cover: true,
    akademik: true,
    pts: false,
    p5: false,
    pelengkap: true,
  },
  format: 'zip',
})
const bulkStatus = ref({
  loading: false,
  job_id: null,
  progress: 0,
  total: 0,
  status: null,
  download_url: null,
  error_msg: null,
})
let pollingInterval = null

const unduhBulkRapor = async () => {
  if (!arrayData.value.siswa.length) return
  bulkStatus.value = { loading: true, job_id: null, progress: 0, total: 0, status: 'preparing', download_url: null, error_msg: null }

  const payload = {
    rombongan_belajar_ids: [defaultForm.value.rombongan_belajar_id],
    nama_rombel: defaultForm.value.nama_rombel ?? '',
    sekolah_id: defaultForm.value.sekolah_id,
    semester_id: defaultForm.value.semester_id,
    periode_aktif: defaultForm.value.periode_aktif,
    komponen: bulkForm.value.komponen,
    format: bulkForm.value.format,
  }

  try {
    const response = await $api('/cetak/bulk-rapor', {
      method: 'POST',
      body: payload,
    })
    if (response?.redirect_to_queue) {
      await startQueueJob(payload)
    } else {
      bulkStatus.value.loading = false
      bulkStatus.value.status = 'done'
    }
  } catch (e) {
    console.error(e)
    bulkStatus.value.loading = false
    bulkStatus.value.status = 'error'
    bulkStatus.value.error_msg = 'Terjadi kesalahan saat memproses rapor. Coba lagi.'
  }
}

const startQueueJob = async (payload) => {
  try {
    const response = await $api('/cetak/bulk-rapor/queue', {
      method: 'POST',
      body: payload,
    })
    bulkStatus.value.job_id = response.job_id
    bulkStatus.value.total  = response.total
    bulkStatus.value.status = 'queued'

    pollingInterval = setInterval(async () => {
      try {
        const statusResp = await $api(`/cetak/bulk-rapor/status/${bulkStatus.value.job_id}`)
        bulkStatus.value.progress = statusResp.progress ?? 0
        bulkStatus.value.status   = statusResp.status
        if (statusResp.status === 'done') {
          clearInterval(pollingInterval)
          bulkStatus.value.download_url = `/cetak/bulk-rapor/download/${bulkStatus.value.job_id}`
          bulkStatus.value.loading = false
        }
        if (statusResp.status === 'error') {
          clearInterval(pollingInterval)
          bulkStatus.value.loading   = false
          bulkStatus.value.error_msg = statusResp.message ?? 'Terjadi kesalahan saat memproses.'
        }
      } catch (pollErr) { console.error(pollErr) }
    }, 3000)
  } catch (e) {
    console.error(e)
    bulkStatus.value.loading = false
    bulkStatus.value.status  = 'error'
  }
}

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
})
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
            <th class="text-center" v-if="defaultForm.merdeka && !defaultForm.is_new_ppa">Rapor P5</th>
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
                :href="`/cetak/rapor-cover/${item.peserta_didik_id}/${defaultForm.sekolah_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-if="defaultForm.is_new_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-akademik/${item.peserta_didik_id}/${defaultForm.sekolah_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-else-if="defaultForm.merdeka || defaultForm.is_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-nilai-akhir/${item.anggota_rombel.anggota_rombel_id}/${defaultForm.sekolah_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-else>
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="warning" variant="text"
                :href="`/cetak/rapor-semester/${item.peserta_didik_id}/${defaultForm.sekolah_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-if="defaultForm.rapor_pts">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="primary" variant="text"
                :href="`/cetak/rapor-tengah-semester/${item.peserta_didik_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center" v-if="defaultForm.merdeka && !defaultForm.is_new_ppa">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="info" variant="text"
                :href="`/cetak/rapor-p5/${item.anggota_rombel.anggota_rombel_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
            <td class="text-center">
              <VBtn size="x-large" icon="tabler-file-type-pdf" color="error" variant="text"
                :href="`/cetak/rapor-pelengkap/${item.peserta_didik_id}/${defaultForm.sekolah_id}/${defaultForm.semester_id}`"
                target="_blank" />
            </td>
          </tr>
        </tbody>
      </VTable>
    </template>
  </VCard>

  <!-- Bulk Rapor Card -->
  <VCard class="mt-6" v-if="arrayData.siswa.length">
    <VCardItem class="pb-4">
      <VCardTitle>Unduh Rapor Semua Siswa</VCardTitle>
    </VCardItem>
    <VDivider />
    <VCardText>
      <VRow>
        <!-- Pilihan Komponen -->
        <VCol cols="12">
          <p class="text-body-2 font-weight-medium mb-2">Pilih Komponen Rapor:</p>
          <VRow>
            <VCol cols="6" md="4">
              <VCheckbox v-model="bulkForm.komponen.cover" label="Cover (Halaman Depan)" />
            </VCol>
            <VCol cols="6" md="4">
              <VCheckbox v-model="bulkForm.komponen.akademik" label="Rapor Akademik" />
            </VCol>
            <VCol cols="6" md="4" v-if="defaultForm.rapor_pts">
              <VCheckbox v-model="bulkForm.komponen.pts" label="Rapor Tengah Semester" />
            </VCol>
            <VCol cols="6" md="4" v-if="defaultForm.merdeka && !defaultForm.is_new_ppa">
              <VCheckbox v-model="bulkForm.komponen.p5" label="Rapor P5" />
            </VCol>
            <VCol cols="6" md="4">
              <VCheckbox v-model="bulkForm.komponen.pelengkap" label="Dokumen Pendukung" />
            </VCol>
          </VRow>
        </VCol>
        <!-- Pilihan Format -->
        <VCol cols="12">
          <p class="text-body-2 font-weight-medium mb-2">Format Output:</p>
          <VRadioGroup v-model="bulkForm.format" inline>
            <VRadio label="ZIP (PDF per-siswa)" value="zip" />
            <VRadio label="PDF Gabungan (1 file)" value="pdf" />
          </VRadioGroup>
        </VCol>
        <!-- Tombol Download -->
        <VCol cols="12">
          <VBtn
            prepend-icon="tabler-download"
            color="primary"
            :loading="bulkStatus.loading"
            :disabled="bulkStatus.loading"
            @click="unduhBulkRapor"
          >
            Unduh Rapor Semua Siswa
          </VBtn>
        </VCol>
        <!-- Progress Bar -->
        <VCol cols="12" v-if="bulkStatus.status === 'queued' || bulkStatus.status === 'processing'">
          <p class="text-body-2 mb-1">Memproses {{ bulkStatus.progress }}% dari {{ bulkStatus.total }} siswa...</p>
          <VProgressLinear :model-value="bulkStatus.progress" color="primary" height="8" rounded />
        </VCol>
        <!-- Tombol download setelah selesai -->
        <VCol cols="12" v-if="bulkStatus.status === 'done' && bulkStatus.download_url">
          <VAlert type="success" variant="tonal" class="d-flex align-center">
            File rapor siap!
            <VBtn :href="bulkStatus.download_url" color="success" class="ml-4" prepend-icon="tabler-download" size="small">
              Klik untuk Download
            </VBtn>
          </VAlert>
        </VCol>
        <!-- Error -->
        <VCol cols="12" v-if="bulkStatus.status === 'error'">
          <VAlert type="error" variant="tonal">{{ bulkStatus.error_msg ?? 'Terjadi kesalahan. Coba lagi.' }}</VAlert>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
