<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  isLoading: {
    type: Boolean,
    required: true,
  },
  dialogTitle: {
    type: String,
    required: true,
  },
  listData: {
    type: Array,
    required: true,
  },
  listGuru: {
    type: Array,
    required: true,
  },
  listKelompok: {
    type: Array,
    required: true,
  },
})
const updateModelValue = val => {
  emit('update:isDialogVisible', val)
}
const emit = defineEmits([
  'update:isDialogVisible',
  'save',
  'refresh'
])
const onSubmit = async () => {
  emit('save')
}
const loadings = ref([])
const hapus = async (pembelajaran_id) => {
  loadings.value[pembelajaran_id] = true
  await $api('/referensi/rombongan-belajar/hapus-pembelajaran', {
    method: 'POST',
    body: {
      pembelajaran_id: pembelajaran_id
    },
    onResponse() {
      loadings.value[pembelajaran_id] = false
      emit('refresh')
    }
  })
}
</script>

<template>
  <VDialog :model-value="props.isDialogVisible" @update:model-value="updateModelValue" fullscreen :scrim="false"
    transition="dialog-bottom-transition">
    <VCard>
      <VToolbar color="primary" class="sticky-header">
        <VBtn icon variant="plain" @click="updateModelValue(false)">
          <VIcon color="white" icon="tabler-x" />
        </VBtn>
        <VToolbarTitle>{{ dialogTitle }}</VToolbarTitle>
        <VSpacer />
        <VToolbarItems>
          <VBtn variant="text" @click="onSubmit">
            <VIcon icon="tabler-device-floppy" class="me-2"></VIcon> Simpan
          </VBtn>
        </VToolbarItems>
      </VToolbar>
      <VTable class="permission-table mb-6">
        <thead>
          <tr>
            <th class="text-center">No</th>
            <th class="text-center">Mata Pelajaran</th>
            <th class="text-center">ID Mapel</th>
            <th class="text-center">Guru Mapel (Dapodik)</th>
            <th class="text-center">Guru Pengajar</th>
            <th class="text-center">Kelompok</th>
            <th class="text-center">Nomor Urut</th>
            <th class="text-center">Reset</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="isLoading">
            <tr>
              <td class="text-center" colspan="8">
                <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
              </td>
            </tr>
          </template>
          <template v-else-if="listData.length">
            <tr v-for="(item, index) in listData">
              <td class="text-center">{{ index + 1 }}</td>
              <td>
                <AppTextField v-model="item.nama_mata_pelajaran" />
              </td>
              <td>
                <AppTextField v-model="item.mata_pelajaran_id" disabled />
              </td>
              <td>
                <AppSelect v-model="item.guru_id" :items="listGuru" item-title="nama_lengkap" item-value="guru_id"
                  disabled>
                  <template #selection="{ item }">
                    <VChip>
                      <template #prepend>
                        <VAvatar start :image="item.raw.photo" />
                      </template>

                      <span>{{ item.raw.nama_lengkap }}</span>
                    </VChip>
                  </template>
                </AppSelect>
              </td>
              <td>
                <AppAutocomplete :items="listGuru" placeholder="== Pilih Guru Pengajar ==" item-title="nama_lengkap"
                  item-value="guru_id" v-model="item.guru_pengajar_id" />
              </td>
              <td>
                <AppAutocomplete :items="listKelompok" placeholder="== Pilih Kelompok ==" item-title="nama_kelompok"
                  item-value="kelompok_id" v-model="item.kelompok_id" />
              </td>
              <td>
                <AppTextField v-model="item.no_urut" />
              </td>
              <td class="text-center">
                <VBtn :loading="loadings[item.pembelajaran_id]" :disabled="loadings[item.pembelajaran_id]" color="error"
                  icon="tabler-trash" @click="hapus(item.pembelajaran_id)" v-if="item.kelompok_id && item.no_urut" />
              </td>
            </tr>
          </template>
          <template v-else>
            <tr>
              <td class="text-center" colspan="8">Tidak ada untuk ditampilkan</td>
            </tr>
          </template>
        </tbody>
      </VTable>
    </VCard>
  </VDialog>
</template>
<style lang="scss">
.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;

    &:not(:first-child) {
      padding-inline: 0.5rem;
    }

    .v-label {
      white-space: nowrap;
    }
  }
}

.sticky-header {
  position: sticky !important;
  top: 0;
  z-index: 1;
}
</style>
