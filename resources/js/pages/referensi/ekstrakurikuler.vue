<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
    title: 'Data Ekstrakurikuler',
  },
})

const options = ref({
  page: 1,
  itemsPerPage: 10,
  searchQuery: '',
  selectedRole: null,
  sortby: 'nama_ekskul',
  sortbydesc: 'ASC',
});
// Headers
const headers = [
  {
    title: 'Nama Ekstrakurikuler',
    key: 'nama_ekskul',
  },
  {
    title: 'Nama Pembina',
    key: 'guru',
    sortable: false,
  },
  {
    title: 'Prasarana',
    key: 'alamat_ekskul',
  },
  {
    title: 'Anggota Ekskul',
    key: 'anggota',
    align: 'center',
    sortable: false,
  },
  {
    title: 'Aksi',
    key: 'sinkron',
    align: 'center',
    sortable: false,
  },
]
const items = ref([])
const total = ref(0)
const loadingTable = ref(false)
onMounted(async () => {
  await fetchData();
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
const updateSortBy = (val) => {
  options.value.sortby = val[0]?.key
  options.value.sortbydesc = val[0]?.order
}
const fetchData = async () => {
  loadingTable.value = true;
  try {
    const response = await useApi(createUrl('/referensi/ekstrakurikuler', {
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
    let getData = response.data
    items.value = getData.value.data.data
    total.value = getData.value.data.total
  } catch (error) {
    console.error(error);
  } finally {
    loadingTable.value = false;
  }
}
const showAnggota = ref(false)
const isLoading = ref(false)
const dialogTitle = ref('')
const anggotaRombel = ref([])
const rombonganBelajarId = ref()
const loadingAnggota = ref([])
const anggota = async (rombongan_belajar_id) => {
  rombonganBelajarId.value = rombongan_belajar_id
  loadingAnggota.value[rombongan_belajar_id] = true
  isLoading.value = true
  rombonganBelajarId.value = rombongan_belajar_id
  showAnggota.value = true
  await $api('/referensi/rombongan-belajar/anggota-rombel', {
    method: 'POST',
    body: {
      rombongan_belajar_id: rombonganBelajarId.value,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      dialogTitle.value = `Anggota Ekstrakurikuler Kelas ${getData.rombel.nama}`
      anggotaRombel.value = getData.data
      isLoading.value = false
      loadingAnggota.value[rombongan_belajar_id] = false
    }
  })
}
const loadingSinkron = ref([])
const sinkron = async (ekstrakurikuler_id) => {
  loadingSinkron.value[ekstrakurikuler_id] = true
  console.log(ekstrakurikuler_id);
}
const reFecthAnggota = async () => {
  getAnggota(rombonganBelajarId.value)
}
</script>
<template>
  <section>
    <!-- 👉 Widgets -->
    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Data Ekstrakurikuler</VCardTitle>
      </VCardItem>
      <VDivider />
      <VCardText>
        <VRow>
          <VCol cols="12" md="4" class="d-flex align-items-center">
            <AppSelect v-model="options.itemsPerPage" :items="[
              { value: 10, title: '10' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
            ]" />
          </VCol>
          <VCol cols="12" md="4" offset-md="4" class="d-flex gap-4">
            <AppTextField v-model="options.searchQuery" placeholder="Cari Data" />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <!-- SECTION datatable -->
      <VDataTableServer class="text-no-wrap" :items="items" :server-items-length="total" :headers="headers"
        :options="options" :loading="loadingTable" loading-text="Loading..." @update:sortBy="updateSortBy">
        <template #item.guru="{ item }">
          {{ item.guru.nama_lengkap }}
        </template>
        <template #item.anggota="{ item }">
          <VBadge :content="item.rombongan_belajar.anggota_rombel_count" color="success">
            <VBtn :loading="loadingAnggota[item.rombongan_belajar_id]"
              :disabled="loadingAnggota[item.rombongan_belajar_id]" @click="anggota(item.rombongan_belajar_id)"
              size="x-small">
              Detil
            </VBtn>
          </VBadge>
        </template>
        <template #item.sinkron="{ item }">
          <VBtn color="error" @click="sinkron(item.ekstrakurikuler_id)" size="x-small"
            :loading="loadingSinkron[item.ekstrakurikuler_id]" :disabled="loadingSinkron[item.ekstrakurikuler_id]">
            <VIcon start icon="tabler-refresh" />Sinkron Anggta
          </VBtn>
        </template>
        <!-- pagination -->
        <template #bottom>
          <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage" :total-items="total" />
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>
    <AnggotaRombelDialog v-model:isDialogVisible="showAnggota" v-model:isLoading="isLoading" :dialog-title="dialogTitle"
      v-model:listData="anggotaRombel" @refresh="reFecthAnggota" />
  </section>
</template>
