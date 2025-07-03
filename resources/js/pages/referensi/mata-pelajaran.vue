<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
    title: 'Data Mata Pelajaran',
  },
})

const options = ref({
  page: 1,
  itemsPerPage: 10,
  searchQuery: '',
  selectedRole: null,
  sortby: 'mata_pelajaran_id',
  sortbydesc: 'ASC',
});
// Headers
const headers = [
  {
    title: 'ID Mapel',
    key: 'mata_pelajaran_id',
  },
  {
    title: 'Nama Mata Pelajaran',
    key: 'nama',
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
    const response = await useApi(createUrl('/referensi/mata-pelajaran', {
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
</script>

<template>
  <section>
    <!-- 👉 Widgets -->
    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Data Mata Pelajaran</VCardTitle>
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
        <!-- pagination -->
        <template #bottom>
          <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage" :total-items="total" />
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>
  </section>
</template>
