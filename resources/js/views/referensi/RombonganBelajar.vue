<script setup>
const props = defineProps({
    jenisRombel: {
        type: Number,
        required: true,
    },
})
const headers = [
    {
        title: 'Nama',
        key: 'nama',
        sortable: true,
        nowrap: true,
    },
    {
        title: 'Wali Kelas',
        key: 'wali_kelas',
        sortable: false,
    },
    {
        title: 'Tingkat',
        key: 'tingkat',
        align: 'center',
        sortable: true,
    },
    {
        title: 'Program/Kompetensi Keahlian',
        key: 'jurusan_sp',
        sortable: false,
    },
    {
        title: 'Kurikulum',
        key: 'kurikulum',
        sortable: false,
    },
    {
        title: 'aksi',
        key: 'aksi',
        align: 'center',
        sortable: false,
    },
]
const options = ref({
    page: 1,
    itemsPerPage: 10,
    searchQuery: '',
    selectedRole: null,
    sortby: 'tingkat',
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
        const response = await useApi(createUrl('/referensi/rombongan-belajar', {
            query: {
                jenis_rombel: props.jenisRombel,
                sekolah_id: $user.sekolah_id,
                semester_id: $semester.semester_id,
                periode_aktif: $semester.nama,
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
const rombonganBelajarId = ref()
const showPembelajaran = ref(false)
const showAnggota = ref(false)
const dialogTitle = ref('')
const pembelajaran = ref([])
const anggotaRombel = ref([])
const guru = ref([])
const kelompok = ref([])
const isLoading = ref(false)
const snackBarText = ref('')
const snackBarLocaltion = ref('')
const getAnggota = async (rombongan_belajar_id) => {
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
            dialogTitle.value = `Anggota Rombel Kelas ${getData.rombel.nama}`
            anggotaRombel.value = getData.data
            isLoading.value = false
        }
    })
}
const getPembelajaran = async (rombongan_belajar_id) => {
    isLoading.value = true
    rombonganBelajarId.value = rombongan_belajar_id
    showPembelajaran.value = true
    await $api('/referensi/rombongan-belajar/pembelajaran', {
        method: 'POST',
        body: {
            rombongan_belajar_id: rombonganBelajarId.value,
            sekolah_id: $sekolah.sekolah_id,
            semester_id: $semester.semester_id,
        },
        onResponse({ request, response, options }) {
            let getData = response._data
            dialogTitle.value = `Pembelajaran Kelas ${getData.rombel.nama}`
            pembelajaran.value = getData.data
            guru.value = getData.guru
            kelompok.value = getData.kelompok
            isLoading.value = false
        }
    })
}
const reFecthData = async () => {
    snackBarText.value = 'Pembelajaran berhasil dihapus'
    snackBarLocaltion.value = 'bottom'
    isSnackbarVisible.value = true
    getPembelajaran(rombonganBelajarId.value)
}
const reFecthAnggota = async () => {
    getAnggota(rombonganBelajarId.value)
}
const isSnackbarVisible = ref(false)
const savePembelajaran = async () => {
    await $api('/referensi/rombongan-belajar/simpan-pembelajaran', {
        method: 'POST',
        body: pembelajaran.value,
        onResponse() {
            snackBarText.value = 'Pembelajaran berhasil disimpan'
            snackBarLocaltion.value = 'top'
            isSnackbarVisible.value = true
            getPembelajaran(rombonganBelajarId.value)
        }
    })
}
</script>
<template>
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
        ]" :items="items" :server-items-length="total" :items-length="total" :headers="headers"
            items-per-page-text="Item" :options="options" :loading="loadingTable" loading-text="Loading..."
            @update:sortBy="updateSortBy" :header-props="{ class: 'text-no-wrap' }">
            <template #item.wali_kelas="{ item }">
                <div class="d-flex align-center gap-x-4">
                    <VAvatar size="34" :variant="!item.wali_kelas.photo ? 'tonal' : undefined"
                        :color="!item.wali_kelas.photo ? 'success' : undefined">
                        <VImg :src="item.wali_kelas.photo" />
                    </VAvatar>
                    <div class="d-flex flex-column">
                        <h6 class="text-base">{{ item.wali_kelas.nama_lengkap }}</h6>
                        <div class="text-sm">
                            {{ item.wali_kelas.email }}
                        </div>
                    </div>
                </div>
            </template>
            <template #item.jurusan_sp="{ item }">
                {{ item.jurusan_sp?.nama_jurusan_sp }}
            </template>
            <template #item.kurikulum="{ item }">
                {{ item.kurikulum?.nama_kurikulum }}
            </template>
            <template #item.aksi="{ item }">
                <VBtn icon variant="text" color="medium-emphasis">
                    <VIcon icon="tabler-dots-vertical" />
                    <VMenu activator="parent">
                        <VList>
                            <VListItem @click="getAnggota(item.rombongan_belajar_id)">
                                <template #prepend>
                                    <VIcon icon="tabler-users-group" />
                                </template>
                                <VListItemTitle>Anggota Rombel</VListItemTitle>
                            </VListItem>
                            <VListItem @click="getPembelajaran(item.rombongan_belajar_id)">
                                <template #prepend>
                                    <VIcon icon="tabler-list" />
                                </template>
                                <VListItemTitle>Pembelajaran</VListItemTitle>
                            </VListItem>
                        </VList>
                    </VMenu>
                </VBtn>
            </template>
            <!-- pagination -->
            <template #bottom>
                <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage"
                    :total-items="total" />
            </template>
        </VDataTableServer>
        <PembelajaranDialog v-model:isDialogVisible="showPembelajaran" v-model:isLoading="isLoading"
            :dialog-title="dialogTitle" v-model:listData="pembelajaran" :list-guru="guru" :list-kelompok="kelompok"
            @save="savePembelajaran" @refresh="reFecthData" />
        <AnggotaRombelDialog v-model:isDialogVisible="showAnggota" v-model:isLoading="isLoading"
            :dialog-title="dialogTitle" v-model:listData="anggotaRombel" @refresh="reFecthAnggota" />
        <VSnackbar v-model="isSnackbarVisible" color="success" :location="snackBarLocaltion">
            {{ snackBarText }}
        </VSnackbar>
    </VCard>
</template>
<style lang="scss">
.scrollable-dialog {
    overflow: visible !important;
}
</style>
