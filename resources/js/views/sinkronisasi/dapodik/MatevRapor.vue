<script setup>
const props = defineProps({
  isTabActive: {
    type: String,
    required: true,
  },
})
const headers = [
  {
    title: 'Mata Pelajaran',
    key: 'nm_mata_evaluasi',
    sortable: true,
  },
  {
    title: 'Guru Mapel',
    key: 'guru',
    sortable: false,
  },
  {
    title: 'Nomor Urut',
    key: 'no_urut',
    align: 'center',
    sortable: false,
  },
  {
    title: 'status',
    key: 'status',
    align: 'center',
    sortable: false,
  },
]
const options = ref({
  page: 1,
  itemsPerPage: 10,
  searchQuery: '',
  selectedRole: null,
  sortby: 'mata_pelajaran_id',
  sortbydesc: 'ASC',
});
const loadingTable = ref(false)
const items = ref([])
const total = ref(0)
const updateSortBy = (val) => {
  options.value.sortby = val[0]?.key
  options.value.sortbydesc = val[0]?.order
}
const fetchData = async () => {
  loadingTable.value = true;
  try {
    const response = await useApi(createUrl('/sinkronisasi/get-matev-rapor', {
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
  } catch (error) {
    console.error(error);
  } finally {
    loadingTable.value = false;
  }
};
onMounted(async () => {
  if (props.isTabActive == 'Mata Evaluasi Rapor') {
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
  if (props.isTabActive == 'Mata Evaluasi Rapor') {
    await fetchData()
  }
})
</script>
<template>
  <div>
    <VCard>
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
      ]" :items="items" :server-items-length="total" :items-length="total" :headers="headers"
        items-per-page-text="Item" class="text-no-wrap" :options="options" :loading="loadingTable"
        loading-text="Loading..." @update:sortBy="updateSortBy">
        <template #item.guru="{ item }">
          {{ item.pembelajaran.guru.nama_lengkap }}
        </template>
        <template #item.status="{ item }">
          <VChip size="small" color="success" v-if="item.status">
            Terkirim
          </VChip>
          <VChip size="small" color="error" v-else>
            Belum Terkirim
          </VChip>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
