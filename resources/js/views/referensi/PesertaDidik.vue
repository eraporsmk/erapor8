<script setup>
const props = defineProps({
    titleCard: {
        type: String,
        required: true,
    },
    status: {
        type: String,
        required: true,
    },
})
const headers = ref([])
if (props.status == 'password') {
    headers.value = [
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
            title: 'email',
            key: 'email',
            sortable: false,
        },
        {
            title: 'password',
            key: 'password',
            align: 'center',
            sortable: false,
        },
    ]
} else {
    headers.value = [
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
            title: 'Agama',
            key: 'agama',
            sortable: false,
        },
        {
            title: 'Kelas',
            key: 'kelas',
            align: 'center',
            sortable: false,
        },
        {
            title: 'detil',
            key: 'detil',
            align: 'center',
            sortable: false,
        },
    ]
}
const options = ref({
    tingkat: null,
    jurusanSpId: null,
    rombonganBelajarId: null,
    page: 1,
    itemsPerPage: 10,
    searchQuery: '',
    sortby: 'nama',
    sortbydesc: 'ASC',
});
const loadingTable = ref(false)
const items = ref([])
const total = ref(0)
const dataJurusan = ref([])
const dataRombel = ref([])
const updateSortBy = (val) => {
    options.value.sortby = val[0]?.key
    options.value.sortbydesc = val[0]?.order
}
const fetchData = async () => {
    loadingTable.value = true;
    try {
        const response = await useApi(createUrl('/referensi/pd', {
            query: {
                status: props.status,
                sekolah_id: $user.sekolah_id,
                semester_id: $semester.semester_id,
                periode_aktif: $semester.nama,
                tingkat: options.value.tingkat,
                jurusan_sp_id: options.value.jurusanSpId,
                rombongan_belajar_id: options.value.rombonganBelajarId,
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
        dataJurusan.value = getData.jurusan_sp
        dataRombel.value = getData.rombel
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
    peserta_didik_id: null,
    status: null,
    anak_ke: null,
    diterima_kelas: null,
    email: null,
    nama_wali: null,
    alamat_wali: null,
    telp_wali: null,
    kerja_wali: null,
    photo: null,
})
const errors = ref({
    email: undefined,
    photo: undefined,
})
const pekerjaan = ref([])
const detilUser = async (peserta_didik_id) => {
    form.value.peserta_didik_id = peserta_didik_id
    loadings.value[peserta_didik_id] = true
    try {
        const response = await useApi(createUrl(`/referensi/pd/detil/${peserta_didik_id}`));
        let getData = response.data.value
        cardTitle.value = `Detil Peserta Didik (${getData.pd.nama})`
        detilData.value = getData.pd
        pekerjaan.value = getData.pekerjaan
        form.value.status = getData.pd.status
        form.value.anak_ke = getData.pd.anak_ke
        form.value.diterima_kelas = getData.pd.diterima_kelas
        form.value.email = getData.pd.email
        form.value.nama_wali = getData.pd.nama_wali
        form.value.alamat_wali = getData.pd.alamat_wali
        form.value.telp_wali = getData.pd.telp_wali
        form.value.kerja_wali = getData.pd.kerja_wali
    } catch (error) {
        console.error(error);
    } finally {
        loadings.value[peserta_didik_id] = false
        isDialogVisible.value = true
    }
}
const onFormSubmit = async () => {
    const dataForm = new FormData();
    dataForm.append('peserta_didik_id', form.value.peserta_didik_id)
    dataForm.append('status', (form.value.status) ? form.value.status : '')
    dataForm.append('anak_ke', (form.value.anak_ke) ? form.value.anak_ke : '')
    dataForm.append('diterima_kelas', (form.value.diterima_kelas) ? form.value.diterima_kelas : '')
    dataForm.append('email', (form.value.email) ? form.value.email : '')
    dataForm.append('nama_wali', (form.value.nama_wali) ? form.value.nama_wali : '')
    dataForm.append('alamat_wali', (form.value.alamat_wali) ? form.value.alamat_wali : '')
    dataForm.append('telp_wali', (form.value.telp_wali) ? form.value.telp_wali : '')
    dataForm.append('kerja_wali', (form.value.kerja_wali) ? form.value.kerja_wali : '')
    dataForm.append('photo', (form.value.photo) ? form.value.photo : '')
    await $api('/referensi/pd/update', {
        method: 'POST',
        body: dataForm,
        onResponse({ request, response, options }) {
            let getData = response._data
            if (getData.errors) {
                errors.value = getData.errors
            } else {
                errors.value = {
                    email: undefined,
                    photo: undefined,
                }
                form.value.photo = null
                isDialogVisible.value = false
                notif.value = getData
                isAlertDialogVisible.value = true
            }
        }
    })
}
const confirmDialog = async () => {
    await fetchData()
}
const dataStatus = ['Anak Kandung', 'Anak Tiri', 'Anak Angkat'];
import bcrypt from "bcryptjs";
const cekPass = (pass, defaultPassword) => {
    if (defaultPassword) {
        return bcrypt.compareSync(defaultPassword, pass)
    }
    return false
}
</script>
<template>
    <VCard class="mb-6">
        <VCardItem class="pb-4">
            <VCardTitle>{{ titleCard }}</VCardTitle>
        </VCardItem>
        <VDivider />
        <VCardText>
            <VRow>
                <VCol cols="12" sm="4">
                    <AppSelect v-model="options.tingkat" placeholder="== Filter Tingkat Kelas" :items="tingkatKelas"
                        clearable clear-icon="tabler-x" />
                </VCol>
                <VCol cols="12" sm="4">
                    <AppSelect v-model="options.jurusanSpId" placeholder="== Filter Jurusan ==" :items="dataJurusan"
                        item-title="nama_jurusan_sp" item-value="jurusan_sp_id" clearable clear-icon="tabler-x" />
                </VCol>
                <VCol cols="12" sm="4">
                    <AppSelect v-model="options.rombonganBelajarId" placeholder="== Filter Rombel" :items="dataRombel"
                        item-title="nama" item-value="rombongan_belajar_id" clearable clear-icon="tabler-x" />
                </VCol>
            </VRow>
        </VCardText>
        <VDivider />
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
                        <h6 class="text-base">{{ item.nama }}</h6>
                        <div class="text-sm">
                            {{ item.nisn }}
                        </div>
                    </div>
                </div>
            </template>
            <template #item.agama="{ item }">
                {{ item.agama?.nama }}
            </template>
            <template #item.kelas="{ item }">
                {{ item.anggota_rombel?.rombongan_belajar?.nama }}
            </template>
            <template #item.email="{ item }">
                {{ item.user?.email }}
            </template>
            <template #item.password="{ item }">
                <template v-if="item.user">
                    <template v-if="cekPass(item.user.password, item.user.default_password)">
                        {{ item.user.default_password }}
                    </template>
                    <template v-else>
                        <VChip size="x-small" color="error">
                            Custom
                        </VChip>
                    </template>
                </template>
            </template>
            <template #item.detil="{ item }">
                <VBtn :loading="loadings[item.peserta_didik_id]" :disabled="loadings[item.peserta_didik_id]"
                    color="warning" icon="tabler-eye" @click="detilUser(item.peserta_didik_id)" />
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
                            <VCol cols="12" class="text-center">
                                <VAvatar rounded :size="150" color="primary" variant="tonal">
                                    <VImg :src="detilData?.photo" />
                                </VAvatar>
                            </VCol>
                        </VRow>
                        <VRow>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nama">Nama
                                            Lengkap</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.nama" placeholder="Nama Lengkap"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nis">NIS</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nis" :value="detilData?.nis" placeholder="NIS"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nisn">NISN</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nisn" :value="detilData?.nisn" placeholder="NISN"
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
                                        <label class="v-label text-body-2 text-high-emphasis" for="status">Status dalam
                                            Keluarga</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppSelect v-model="form.status" placeholder="== Pilih Status dalam
                                            Keluarga ==" :items="dataStatus" clearable clear-icon="tabler-x" />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="anak_ke">Anak ke
                                            berapa?</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="anak_ke" v-model="form.anak_ke" placeholder="Anak ke berapa?"
                                            persistent-placeholder />
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
                                            for="no_telp">Telp/HP</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" :value="detilData?.no_telp" placeholder="Telp/HP"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="sekolah_asal">Asal
                                            Sekolah</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="sekolah_asal" :value="detilData?.sekolah_asal"
                                            placeholder="Asal Sekolah" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis"
                                            for="diterima_kelas">Diterima
                                            dikelas</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="diterima_kelas" v-model="form.diterima_kelas"
                                            placeholder="Diterima dikelas" persistent-placeholder />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="diterima">Diterima
                                            pada
                                            tanggal</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="diterima" :value="detilData?.diterima"
                                            placeholder="Diterima pada tanggal" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="email">Email</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama" v-model="form.email" placeholder="Email"
                                            persistent-placeholder :error-messages="errors.email" />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nama_ayah">Nama
                                            Ayah</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama_ayah" :value="detilData?.nama_ayah"
                                            placeholder="Nama Ayah" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="kerja_ayah">Pekerjaan
                                            Ayah</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="kerja_ayah" :value="detilData?.pekerjaan_ayah?.nama"
                                            placeholder="Pekerjaan Ayah" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nama_ibu">Nama
                                            Ibu</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama_ibu" :value="detilData?.nama_ibu" placeholder="Nama Ibu"
                                            persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="kerja_ibu">Pekerjaan
                                            Ibu</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="kerja_ibu" :value="detilData?.pekerjaan_ibu?.nama"
                                            placeholder="Pekerjaan Ibu" persistent-placeholder disabled />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="nama_wali">Nama
                                            Wali</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="nama_wali" v-model="form.nama_wali" placeholder="Nama Wali"
                                            persistent-placeholder />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="alamat_wali">Alamat
                                            Wali</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="alamat_wali" v-model="form.alamat_wali"
                                            placeholder="Alamat Wali" persistent-placeholder />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="telp_wali">Telp/HP
                                            Wali</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppTextField id="telp_wali" v-model="form.telp_wali" placeholder="Telp/HP Wali"
                                            persistent-placeholder />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="kerja_wali">Pekerjaan
                                            Wali</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <AppSelect v-model="form.kerja_wali" placeholder="== Pilih Pekerjaan Wali =="
                                            :items="pekerjaan" item-title="nama" item-value="pekerjaan_id" clearable
                                            clear-icon="tabler-x" />
                                    </VCol>
                                </VRow>
                            </VCol>
                            <VCol cols="12">
                                <VRow no-gutters>
                                    <VCol cols="12" md="3" class="d-flex align-items-center">
                                        <label class="v-label text-body-2 text-high-emphasis" for="photo">Unggah
                                            Foto</label>
                                    </VCol>
                                    <VCol cols="12" md="9">
                                        <VFileInput v-model="form.photo" :error-messages="errors.photo" accept="image/*"
                                            label="Unggah Foto Peserta Didik" />
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
