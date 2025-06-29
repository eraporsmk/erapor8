<script setup>
const { t } = useI18n() 
const props = defineProps({
  page: {
    type: Number,
    required: true,
  },
  per_page: {
    type: Number,
    required: true,
  },
  headers: {
    type: Array,
    required: true,
  },
  items: {
    type: Array,
    required: true,
  },
  totalItems: {
    type: Number,
    required: true,
  },
  searchQuery: {
    type: String,
    required: true,
  },
  btnAdd: {
    type: Object,
    required: true,
  }
})
const emit = defineEmits([
  'update:searchQuery',
  'update:per_page',
  'update:page',
  'aksi',
  'btnClick',
  'option',
])
const aksi = (aksi, item) => {
  emit('aksi', {
    aksi: aksi,
    item: item,
  })
}
const updatePerPage = val => {
  emit('update:per_page', val)
}
const setPage = val => {
  emit('update:page', val)
}
const updatesearchQuery = val => {
  emit('update:searchQuery', val)
}
const btnClick = val => {
  emit('btnClick', val)
}
const updateOptions = val => {
  emit('option', val)
}
</script>

<template>
  <div>
    <div class="d-flex flex-wrap gap-4 ma-6">
      <div class="d-flex gap-4 flex-wrap align-center">
        <AppSelect :model-value="props.per_page" :items="[5, 10, 20, 25, 50]" @update:modelValue="updatePerPage" />
      </div>
      <VSpacer />
      <div class="d-flex align-center">
        <!-- 👉 Search  -->
        <AppTextField :model-value="searchQuery" @update:modelValue="updatesearchQuery" placeholder="Search..." style="inline-size: 200px;" />
        <template v-if="props.btnAdd && Object.keys(props.btnAdd).length">
          <VBtn class="ms-3" :color="btnAdd.color" :prepend-icon="btnAdd.icon" @click="btnClick(btnAdd.action)">
            {{t(btnAdd.text)}}
          </VBtn>
        </template>
      </div>
    </div>
    <VDivider class="mt-4" />
      <VDataTableServer :model-value="props.page" :headers="headers" :items="items" :items-length="totalItems" class="text-no-wrap" @update:options="updateOptions">
      <!--v-model:items-per-page="per_page"-->
      <!--v-model:page="page"-->
      <template v-slot:header="{ props: { headers } }">
        <thead>
          <tr>
            <th v-for="h in headers" :class="h.class">
              <span>{{t(h.text)}}</span>
            </th>
          </tr>
        </thead>
      </template>
      <!-- Actions -->
      <template #item.actions="{ item }">
        <IconBtn title="Edit" @click="aksi('edit', item)">
          <VIcon icon="tabler-pencil" />
        </IconBtn>
        <IconBtn title="Delete" @click="aksi('delete', item)">
          <VIcon icon="tabler-trash" />
        </IconBtn>
      </template>
      
      <!-- pagination -->
      <template #bottom>
        <!--TablePagination :model-value="props.page" :items-per-page="props.per_page" :total-items="props.totalItems" /-->
        {{ counter }}
        <TablePagination :page="page" @changePage="setPage" :items-per-page="per_page" :total-items="totalItems" />
      </template>
    </VDataTableServer>
  </div>
</template>
