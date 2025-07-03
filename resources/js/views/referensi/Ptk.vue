<script setup>
const props = defineProps({
    data: {
        type: String,
        required: true,
    },
})
const headers = [
    {
        title: 'Nama',
        key: 'nama',
        sortable: true,
    },
    {
        title: 'NIK',
        key: 'nik',
        align: 'center',
        sortable: true,
    },
    {
        title: 'L/P',
        key: 'jenis_kelamin',
        align: 'center',
        sortable: true,
    },
    {
        title: 'Tempat, Tanggal Lahir',
        key: 'tempat_tanggal_lahir',
        sortable: false,
    },
    {
        title: 'detil',
        key: 'detil',
        align: 'center',
        sortable: false,
    },
]
const options = ref({
    page: 1,
    itemsPerPage: 10,
    searchQuery: '',
    sortby: 'nama',
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
        const response = await useApi(createUrl('/referensi/ptk', {
            query: {
                jenis_gtk: props.data,
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
const isDialogVisible = ref(false)
const isAlertDialogVisible = ref(false)
const notif = ref({
    color: null,
    title: null,
    text: null,
})
const loadings = ref([])
const cardTitle = ref('')
const detilData = ref({})
const form = ref({
    guru_id: null,
    gelar_depan: null,
    gelar_belakang: null,
    sekolah_id: $user.sekolah_id,
})
const detilUser = async (guru_id) => {
    form.value.guru_id = guru_id
    loadings.value[guru_id] = true
    try {
        const response = await useApi(createUrl(`/referensi/ptk/detil/${guru_id}`));
        let getData = response.data.value
        cardTitle.value = `Detil PTK (${getData.ptk.nama_lengkap})`
        detilData.value = getData.ptk
        form.value.gelar_depan = getData.ptk.gelar_depan
        form.value.gelar_belakang = getData.ptk.gelar_belakang
    } catch (error) {
        console.error(error);
    } finally {
        loadings.value[guru_id] = false
        isDialogVisible.value = true
    }
}
const onFormSubmit = async () => {
    await $api('/referensi/ptk/update', {
        method: 'POST',
        body: form.value,
        onResponse({ request, response, options }) {
            let getData = response._data
            isDialogVisible.value = false
            notif.value = getData
            isAlertDialogVisible.value = true
        }
    })
}
const confirmDialog = async () => {
    await fetchData()
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
            items-per-page-text="Item" class="text-no-wrap" :options="options" :loading="loadingTable"
            loading-text="Loading..." @update:sortBy="updateSortBy">
            <template #item.nama="{ item }">
                <div class="d-flex align-center gap-x-4">
                    <VAvatar size="34" :variant="!item.photo ? 'tonal' : undefined"
                        :color="!item.photo ? 'success' : undefined">
                        <VImg :src="item.photo" />
                    </VAvatar>
                    <div class="d-flex flex-column">
                        <h6 class="text-base">{{ item.nama_lengkap }}</h6>
                        <div class="text-sm">
                            {{ item.email }}
                        </div>
                    </div>
                </div>
            </template>
            <template #item.detil="{ item }">
                <VBtn :loading="loadings[item.guru_id]" :disabled="loadings[item.guru_id]" color="warning"
                    icon="tabler-eye" @click="detilUser(item.guru_id)" />
            </template>
            <!-- pagination -->
            <template #bottom>
                <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage"
                    :total-items="total" />
            </template>
        </VDataTableServer>
        <VDialog v-model="isDialogVisible" width="700" scrollable content-class="scrollable-dialog">
            <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />
            <VForm @submit.prevent="onFormSubmit">
                <VCard>
                    <VCardItem class="pb-5">
                        <VCardTitle>{{ cardTitle }}</VCardTitle>
                    </VCardItem>
                    <VDivider />
                    <VCardText>
                        <VRow>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nama">Nama
                                            Lengkap</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.nama_lengkap"
                                            placeholder="Nama Lengkap" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="gelar_depan">Gelar
                                            Depan</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="gelar_depan" v-model="form.gelar_depan"
                                            placeholder="Gelar Depan" persistent-placeholder />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="gelar_belakang">Gelar
                                            Belakang</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="gelar_belakang" v-model="form.gelar_belakang"
                                            placeholder="Gelar Belakang" persistent-placeholder />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nuptk">NUPTK</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.nuptk" placeholder="NUPTK"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nip">NIP</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.nip" placeholder="NIP"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nik">NIK</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.nik" placeholder="NIK"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="jenis_kelamin">Jenis
                                            Kelamin</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.jenis_kelamin_str"
                                            placeholder="Jenis Kelamin" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="tempat_lahir">Tempat
                                            Lahir</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.tempat_lahir"
                                            placeholder="Tempat Lahir" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis"
                                            for="tanggal_lahir_indo">Tanggal
                                            Lahir</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.tanggal_lahir_indo"
                                            placeholder="Tanggal Lahir" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis"
                                            for="agama_id">Agama</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.agama?.nama" placeholder="Agama"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis"
                                            for="alamat">Alamat</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.alamat" placeholder="Alamat"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="rt">RT</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.rt" placeholder="RT"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="rw">RW</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.rw" placeholder="RW"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis"
                                            for="desa_kelurahan">Desa/Kelurahan</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.desa_kelurahan"
                                            placeholder="Desa/Kelurahan" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis"
                                            for="kecamatan">Kecamatan</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.kecamatan" placeholder="Kecamatan"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis"
                                            for="kode_pos">Kodepos</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.kode_pos" placeholder="Kodepos"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis"
                                            for="no_hp">Telp/HP</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.no_hp" placeholder="Telp/HP"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="email">Email</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.email" placeholder="Email"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                        </VRow>
                    </VCardText>
                    <VDivider />
                    <VCardText class="d-flex justify-end flex-wrap gap-3 pt-5 overflow-visible">
                        <VBtn color="secondary" variant="tonal" @click="isDialogVisible = false">
                            Tutup
                        </VBtn>
                        <VBtn type="submit">
                            Perbaharui
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>
        <AlertDialog v-model:isDialogVisible="isAlertDialogVisible" :confirm-color="notif.color"
            :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDialog" />
    </VCard>
</template>
<style lang="scss">
.scrollable-dialog {
    overflow: visible !important;
}
</style>
