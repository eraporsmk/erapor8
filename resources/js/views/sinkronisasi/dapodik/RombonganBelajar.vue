<script setup>
const props = defineProps({
  isTabActive: {
    type: String,
    required: true,
  },
  urlDapodik: {
    type: String,
    default: '',
  },
  tokenDapodik: {
    type: String,
    default: '',
  }
})
const headers = [
  {
    title: 'Kelas',
    key: 'nama',
    sortable: true,
  },
  {
    title: 'Wali Kelas',
    key: 'wali_kelas',
    sortable: false,
  },
  {
    title: 'Mata Evaluasi',
    key: 'matev',
    align: 'center',
    sortable: false,
  },
  {
    title: 'Mata Evaluasi Terkirim',
    key: 'matev_terkirim',
    align: 'center',
    sortable: false,
  },
  {
    title: 'Generate Matev',
    key: 'generate_matev',
    align: 'center',
    sortable: false,
  },
  {
    title: 'Kirim Nilai',
    key: 'nilai',
    align: 'center',
    sortable: false,
  },
]
const options = ref({
  page: 1,
  itemsPerPage: 10,
  searchQuery: '',
  selectedRole: null,
  sortby: 'nama',
  sortbydesc: 'ASC',
});
const loadingTable = ref(false)
const items = ref([])
const total = ref(0)
const url_dapodik = ref()
const token_dapodik = ref()
const updateSortBy = (val) => {
  options.value.sortby = val[0]?.key
  options.value.sortbydesc = val[0]?.order
}
const fetchData = async () => {
  loadingTable.value = true;
  try {
    const response = await useApi(createUrl('/sinkronisasi/rombongan-belajar', {
      query: {
        sekolah_id: $user.sekolah_id,
        semester_id: $semester.semester_id,
        q: options.value.searchQuery,
        page: options.value.page,
        per_page: options.value.itemsPerPage,
        sortby: options.value.sortby,
        sortbydesc: options.value.sortbydesc,
      },
    }));
    let getData = response.data.value
    items.value = getData.data.data
    total.value = getData.data.total
    url_dapodik.value = getData.url_dapodik
    token_dapodik.value = getData.token_dapodik
  } catch (error) {
    console.error(error);
  } finally {
    loadingTable.value = false;
  }
}
const isAlertDialogVisible = ref(false)
const isLoadingDialogVisible = ref(false)
const notif = ref({
  color: '',
  title: '',
  text: '',
})
const aksi = ref()
const handleMatev = (rombongan_belajar_id, nama_kelas) => {
  isLoadingDialogVisible.value = true
  matevRapor(rombongan_belajar_id, nama_kelas)
}
const handleKirim = (rombongan_belajar_id, nama_kelas) => {
  isLoadingDialogVisible.value = true
  kirimNilai(rombongan_belajar_id, nama_kelas)
}
const matevRapor = async (rombongan_belajar_id, nama_kelas) => {
  await $api('/sinkronisasi/matev-rapor', {
    method: 'POST',
    body: {
      rombongan_belajar_id: rombongan_belajar_id,
      nama_kelas: nama_kelas,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      notif.value = getData
      isLoadingDialogVisible.value = false
      isAlertDialogVisible.value = true
    }
  })
}
const kirimNilai = async (rombongan_belajar_id, nama_kelas) => {
  await $api('/sinkronisasi/kirim-nilai', {
    method: 'POST',
    body: {
      rombongan_belajar_id: rombongan_belajar_id,
      nama_kelas: nama_kelas,
      npsn: $sekolah.npsn,
      semester_id: $semester.semester_id,
      url_dapodik: props.urlDapodik,
      token_dapodik: props.tokenDapodik,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      notif.value = getData
      isLoadingDialogVisible.value = false
      isAlertDialogVisible.value = true
    }
  })
}
const confirmDialog = async () => {
  await fetchData()
}
onMounted(async () => {
  if (props.isTabActive == 'Rombongan Belajar') {
    await fetchData();
  }
});
watch(options, async () => {
  await fetchData();
}, { deep: true });
watch(
  () => options.value.searchQuery,
  () => {
    options.value.page = 1
  }
)
watch(props, async () => {
  if (props.isTabActive == 'Rombongan Belajar') {
    await fetchData()
  }
}, { deep: true })
</script>
<template>
  <div>
    <VCardText class="d-flex flex-wrap gap-4">
      <div class="d-flex gap-2 align-center">
        <AppSelect v-model="options.itemsPerPage" :items="[
          { value: 10, title: '10' },
          { value: 25, title: '25' },
          { value: 50, title: '50' },
          { value: 100, title: '100' },
        ]" style="inline-size: 15.5rem;" />
      </div>
      <VSpacer />
      <div class="d-flex align-center flex-wrap gap-4">
        <!-- 👉 Search  -->
        <AppTextField v-model="options.searchQuery" placeholder="Cari data" style="inline-size: 15.625rem;" />
      </div>
    </VCardText>
    <VDivider />
    <VDataTableServer v-model:page="options.page" :items-per-page="options.itemsPerPage" :items-per-page-options="[
      { value: 10, title: '10' },
      { value: 20, title: '20' },
      { value: 50, title: '50' },
      { value: 100, title: '100' },
    ]" :items="items" :server-items-length="total" :items-length="total" :headers="headers" items-per-page-text="Item"
      class="text-no-wrap" :options="options" :loading="loadingTable" loading-text="Loading..."
      @update:sortBy="updateSortBy">
      <template #item.wali_kelas="{ item }">
        {{ item.wali_kelas.nama_lengkap }}
      </template>
      <template #item.generate_matev="{ item }">
        <template v-if="item.pembelajaran_count">
          <VBtn color="warning" size="small" @click="handleMatev(item.rombongan_belajar_id, item.nama)">
            <VIcon start icon="tabler-bolt" />Generate
          </VBtn>
        </template>
        <template v-else>
          <VTooltip location="top">
            <template #activator="{ props }">
              <VIcon v-bind="props" size="30" icon="tabler-help" />
            </template>
            <span>Kelas {{ item.nama }} belum memiliki Pembelajaran termapping!</span>
          </VTooltip>
        </template>
      </template>
      <template #item.nilai="{ item }">
        <template v-if="item.matev">
          <VBtn color="primary" size="small" @click="handleKirim(item.rombongan_belajar_id, item.nama)">
            <VIcon start icon="tabler-database" />Kirim Data
          </VBtn>
        </template>
        <template v-else>
          <VTooltip location="top">
            <template #activator="{ props }">
              <VIcon v-bind="props" size="30" icon="tabler-help" />
            </template>
            <span>Kelas {{ item.nama }} belum memiliki Mata Evaluasi!</span>
          </VTooltip>
        </template>
      </template>
    </VDataTableServer>
    <VDialog v-model="isLoadingDialogVisible" width="300" persistent>
      <VCard color="primary" width="300">
        <VCardText class="pt-3">
          Mohon menunggu...
          <VProgressLinear indeterminate bg-color="rgba(var(--v-theme-surface), 0.1)" :height="8" class="mb-0 mt-4" />
        </VCardText>
      </VCard>
    </VDialog>
    <AlertDialog v-model:isDialogVisible="isAlertDialogVisible" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDialog" />
  </div>
</template>
