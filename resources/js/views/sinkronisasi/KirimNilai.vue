<script setup>
const props = defineProps({
  isTabActive: {
    type: Boolean,
    required: true,
  },
})
const searchQuery = ref('')
const per_page = ref(10)
const page = ref(1)
const sortby = ref('mata_pelajaran_id')
const sortbydesc = ref('ASC')
const updateOptions = options => {
  if (options.sortBy.length) {
    sortby.value = options.sortBy[0]?.key
    sortbydesc.value = options.sortBy[0]?.order
  }
}
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
const {
  data: getData,
  execute: fetchData,
} = await useApi(createUrl('/sinkronisasi/get-matev-rapor', {
  query: {
    sekolah_id: $user.sekolah_id,
    semester_id: $user.semester.semester_id,
    q: searchQuery,
    per_page,
    page,
    sortby,
    sortbydesc,
  },
}))
const items = computed(() => getData.value.data.data)
const total = computed(() => getData.value.data.total)
watch(props, () => {
  if (props.isTabActive) {
    fetchData()
  }
})
</script>
<template>
  <div>
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="d-flex gap-2 align-center">
          <AppSelect :model-value="per_page" :items="[
            { value: 10, title: '10' },
            { value: 25, title: '25' },
            { value: 50, title: '50' },
            { value: 100, title: '100' },
          ]" style="inline-size: 15.5rem;" @update:model-value="per_page = parseInt($event, 10)" />
        </div>
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <!-- 👉 Search  -->
          <AppTextField v-model="searchQuery" placeholder="Cari data" style="inline-size: 15.625rem;" />
        </div>
      </VCardText>
      <VDivider />
      <VDataTableServer v-model:items-per-page="per_page" v-model:page="page" :items-per-page-options="[
        { value: 10, title: '10' },
        { value: 20, title: '20' },
        { value: 50, title: '50' },
        { value: -1, title: '$vuetify.dataFooter.itemsPerPageAll' },
      ]" :items="items" :items-length="total" :headers="headers" items-per-page-text="Item" class="text-no-wrap" @update:options="updateOptions">
        <template #item.guru="{ item }">
          {{item.pembelajaran.guru.nama_lengkap}}
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
