<script setup>
const props = defineProps({
    resetForm: {
        type: Boolean,
        required: true,
    },
    arrayData: {
        type: Object,
        required: true,
    },
    loading: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        required: true,
    },
    showMapel: {
        type: Boolean,
        default: true,
    },
    showPd: {
        type: Boolean,
        default: false,
    },
    isKunci: {
        type: Boolean,
        default: false,
    },
})
const emit = defineEmits([
    'update:form',
    'tingkat',
    'rombongan_belajar_id',
    'pembelajaran_id'
])
const changeFormTingkat = val => {
    emit('update:form', {
        tingkat: val,
        rombongan_belajar_id: props.form.rombongan_belajar_id,
        pembelajaran_id: props.form.pembelajaran_id,
        mata_pelajaran_id: props.form.mata_pelajaran_id,
        peserta_didik_id: props.form.peserta_didik_id,
        anggota_rombel_id: props.form.anggota_rombel_id,
    })
    emit('tingkat', val)
}
const changeFormRombel = val => {
    emit('update:form', {
        tingkat: props.form.tingkat,
        rombongan_belajar_id: val,
        pembelajaran_id: props.form.pembelajaran_id,
        mata_pelajaran_id: props.form.mata_pelajaran_id,
        peserta_didik_id: props.form.peserta_didik_id,
        anggota_rombel_id: props.form.anggota_rombel_id,
    })
    emit('rombongan_belajar_id', val)
}
const changeMapel = val => {
    emit('update:form', {
        tingkat: props.form.tingkat,
        rombongan_belajar_id: props.form.rombongan_belajar_id,
        pembelajaran_id: val?.pembelajaran_id,
        mata_pelajaran_id: val?.mata_pelajaran_id,
        peserta_didik_id: props.form.peserta_didik_id,
        anggota_rombel_id: props.form.anggota_rombel_id,
    })
    emit('pembelajaran_id', val)
}
const changePd = val => {
    emit('update:form', {
        tingkat: props.form.tingkat,
        rombongan_belajar_id: props.form.rombongan_belajar_id,
        pembelajaran_id: props.form.pembelajaran_id,
        mata_pelajaran_id: props.form.mata_pelajaran_id,
        peserta_didik_id: val.peserta_didik_id,
        anggota_rombel_id: val.anggota_rombel?.anggota_rombel_id,
    })
    emit('peserta_didik_id', val)
}
const defaultValue = ref({
    tingkat: null,
    rombongan_belajar_id: null,
    pembelajaran_id: null,
    mata_pelajaran_id: null,
    peserta_didik_id: null,
})
watch(() => props.resetForm, (newValue, oldValue) => {
    console.log(newValue, oldValue);
    if (newValue) {
        defaultValue.value = {
            tingkat: null,
            rombongan_belajar_id: null,
            pembelajaran_id: null,
            mata_pelajaran_id: null,
            peserta_didik_id: null,
        }
    }
})
watch(() => props.form.rombongan_belajar_id, (newValue, oldValue) => {
    if (!newValue && oldValue) {
        defaultValue.value.rombongan_belajar_id = null
    }
})
watch(() => props.form.pembelajaran_id, (newValue, oldValue) => {
    if (!newValue && oldValue) {
        defaultValue.value.pembelajaran_id = null
    }
})
</script>
<template>
    <VCol cols="12">
        <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="semester_id">Tahun Pelajaran</label>
            </VCol>
            <VCol cols="12" md="9">
                <AppTextField id="semester_id" :value="$semester.nama" disabled />
            </VCol>
        </VRow>
    </VCol>
    <VCol cols="12">
        <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="tingkat">Tingkat Kelas</label>
            </VCol>
            <VCol cols="12" md="9">
                <AppSelect v-model="defaultValue.tingkat" placeholder="== Pilih Tingkat kelas ==" :items="tingkatKelas"
                    clearable clear-icon="tabler-x" @update:model-value="changeFormTingkat"
                    :error-messages="props.errors.tingkat" />
            </VCol>
        </VRow>
    </VCol>
    <VCol cols="12">
        <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="rombonganBelajarId">Rombongan
                    Belajar</label>
            </VCol>
            <VCol cols="12" md="9">
                <AppSelect v-model="defaultValue.rombongan_belajar_id" placeholder="== Pilih Rombongan Belajar == "
                    :items="props.arrayData.rombel" clearable clear-icon="tabler-x"
                    @update:model-value="changeFormRombel" item-value="rombongan_belajar_id" item-title="nama"
                    :loading="props.loading.rombel" :disabled="props.loading.rombel"
                    :error-messages="props.errors.rombongan_belajar_id" />
            </VCol>
        </VRow>
    </VCol>
    <VCol cols="12" v-if="props.showMapel">
        <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="mata_pelajaran_id">Mata Pelajaran</label>
            </VCol>
            <VCol cols="12" md="9">
                <AppSelect v-model="defaultValue.pembelajaran_id" placeholder="== Pilih Mata Pelajaran =="
                    :items="props.arrayData.mapel" clearable clear-icon="tabler-x" @update:model-value="changeMapel"
                    item-value="pembelajaran_id" item-title="nama_mata_pelajaran" :loading="props.loading.mapel"
                    :disabled="props.loading.mapel || props.isKunci" :error-messages="props.errors.mata_pelajaran_id"
                    return-object />
            </VCol>
        </VRow>
    </VCol>
    <VCol cols="12" v-if="props.showPd">
        <VRow no-gutters>
            <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="peserta_didik_id">Peserta Didik</label>
            </VCol>
            <VCol cols="12" md="9">
                <AppAutocomplete v-model="defaultValue.peserta_didik_id" placeholder="== Pilih Peserta Didik =="
                    :items="props.arrayData.siswa" clearable clear-icon="tabler-x" @update:model-value="changePd"
                    item-value="peserta_didik_id" item-title="nama" :loading="props.loading.siswa"
                    :disabled="props.loading.siswa" :error-messages="props.errors.peserta_didik_id" return-object />
            </VCol>
        </VRow>
    </VCol>
</template>
