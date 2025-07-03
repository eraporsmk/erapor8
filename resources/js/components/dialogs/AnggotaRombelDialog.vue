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
})
const updateModelValue = val => {
  emit('update:isDialogVisible', val)
  emit('close')
}
const emit = defineEmits([
  'update:isDialogVisible',
  'refresh',
  'close'
])
const isConfirmDialogVisible = ref(false)
const isNotifVisible = ref(false)
const notif = ref({
  color: '',
  title: '',
  text: '',
})
const loadings = ref([])
const anggotaRombelId = ref()
const deleteData = ref()
const hapus = async (anggota_rombel_id, data) => {
  anggotaRombelId.value = anggota_rombel_id
  deleteData.value = data
  isConfirmDialogVisible.value = true
}
const confirmDialog = async () => {
  loadings.value[anggotaRombelId.value] = true
  await $api('/referensi/rombongan-belajar/hapus-anggota-rombel', {
    method: 'POST',
    body: {
      anggota_rombel_id: anggotaRombelId.value,
      data: deleteData.value,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      loadings.value[anggotaRombelId.value] = false
      notif.value = getData
      isNotifVisible.value = true
    }
  })
}
const confirmClose = async () => {
  isNotifVisible.value = false
  setTimeout(() => {
    notif.value = {
      color: '',
      title: '',
      text: '',
    }
  }, 300)
  emit('refresh')
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
          <VBtn variant="text" @click="updateModelValue(false)">
            <VIcon icon="tabler-x" class="me-2"></VIcon> Tutup
          </VBtn>
        </VToolbarItems>
      </VToolbar>
      <VTable class="permission-table mb-6">
        <thead>
          <tr>
            <th class="text-center">No</th>
            <th>Nama</th>
            <th class="text-center">NISN</th>
            <th class="text-center">L/P</th>
            <th>Tempat, Tanggal Lahir</th>
            <th>Agama</th>
            <th class="text-center">Keluarkan</th>
          </tr>
        </thead>
        <tbody>
          <template v-if="isLoading">
            <tr>
              <td class="text-center" colspan="7">
                <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
              </td>
            </tr>
          </template>
          <template v-else-if="listData.length">
            <tr v-for="(item, index) in listData">
              <td class="text-center">{{ index + 1 }}</td>
              <td>{{ item.nama }}</td>
              <td class="text-center">{{ item.nisn }}</td>
              <td class="text-center">{{ item.jenis_kelamin }}</td>
              <td>{{ item.tempat_tanggal_lahir }}</td>
              <td>{{ item.agama?.nama }}</td>
              <td class="text-center">
                <template v-if="item.anggota_akt_pd">
                  <VBtn :loading="loadings[item.anggota_akt_pd.anggota_akt_pd_id]"
                    :disabled="loadings[item.anggota_akt_pd.anggota_akt_pd_id]" color="error" icon="tabler-trash"
                    @click="hapus(item.anggota_akt_pd.anggota_akt_pd_id, 'prakerin')" />
                </template>
                <template v-else>
                  <VBtn :loading="loadings[item.anggota_rombel.anggota_rombel_id]"
                    :disabled="loadings[item.anggota_rombel.anggota_rombel_id]" color="error" icon="tabler-trash"
                    @click="hapus(item.anggota_rombel.anggota_rombel_id, 'rombel')" />
                </template>
              </td>
            </tr>
          </template>
          <template v-else>
            <tr>
              <td class="text-center" colspan="7">Tidak ada untuk ditampilkan</td>
            </tr>
          </template>
        </tbody>
      </VTable>
    </VCard>
  </VDialog>
  <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
    confirmation-question="Apakah Anda yakin?" confirmation-text="Tindakan ini tidak dapat dikembalikan!"
    :confirm-color="notif.color" :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDialog"
    @close="confirmClose" />
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
