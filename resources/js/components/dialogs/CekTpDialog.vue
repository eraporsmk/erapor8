<script setup>
const props = defineProps({
    isDialogVisible: {
        type: Boolean,
        required: true,
    },
    nilaiTp: {
        type: Number,
        required: true,
        default: 0,
    },
    tpNilai: {
        type: Number,
        required: true,
        default: 0,
    },
    tpMapel: {
        type: Number,
        required: true,
        default: 0,
    },
    tpPkl: {
        type: Number,
        required: true,
        default: 0,
    },
})
const emit = defineEmits([
  'update:isDialogVisible',
  'delete',
  'close',
])
const updateModelValue = val => {
  emit('update:isDialogVisible', val)
  emit('close')
}
const deleteData = () => {
    emit('update:isDialogVisible', false)
    emit('delete')
}
const ada = ref(true)
watch(props, () => {
  if (props.isDialogVisible) {
    const jumlah = props.tpNilai + props.nilaiTp + props.tpMapel + props.tpPkl
    ada.value = jumlah > 0 ? true : false
  }
})
</script>

<template>
    <VDialog v-model="props.isDialogVisible" scrollable max-width="350" content-class="scrollable-dialog">
        <!-- Dialog close btn -->
        <DialogCloseBtn @click="updateModelValue(false)" />

        <!-- Dialog Content -->
        <VCard>
            <VCardItem class="pb-5">
                <VCardTitle>Data Turunan Tujuan Pembelajaran</VCardTitle>
            </VCardItem>

            <VTable>
                <thead>
                    <tr>
                        <th>Nama Table</th>
                        <th class="text-center">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nilai</td>
                        <td class="text-center">{{ props.nilaiTp }}</td>
                    </tr>
                    <tr>
                        <td>Capaian Kompentensi</td>
                        <td class="text-center">{{ props.tpNilai }}</td>
                    </tr>
                    <tr>
                        <td>Mapping Rombel</td>
                        <td class="text-center">{{ props.tpMapel }}</td>
                    </tr>
                    <tr>
                        <td>Rencana Penilaian PKL</td>
                        <td class="text-center">{{ props.tpPkl }}</td>
                    </tr>
                </tbody>
            </VTable>

            <VCardText class="d-flex justify-end flex-wrap gap-3 pt-5 overflow-visible">
                <VBtn color="secondary" variant="tonal" @click="updateModelValue(false)">
                    Batal
                </VBtn>
                <VBtn @click="deleteData" :disabled="ada">
                    Hapus
                </VBtn>
            </VCardText>
        </VCard>
    </VDialog>
</template>

<style lang="scss">
.scrollable-dialog {
    overflow: visible !important;
}
</style>
