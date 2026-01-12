<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Ekskul',
    title: 'Nilai Ekstrakurikuler'
  },
})
const removeEmptyValues = (obj) => {
  Object.keys(obj).forEach(key => {
    if (obj[key] && typeof obj[key] === 'object') {
      removeEmptyValues(obj[key]);
      if (Object.keys(obj[key]).length === 0) {
        delete obj[key];
      }
    } else if (obj[key] === '' || obj[key] === null || obj[key] === undefined) {
      delete obj[key];
    }
  });
  return obj;
}
onMounted(async () => {
  await fetchData();
});
const loading = ref({
  ekskul: false,
  rombel: false,
  body: false
})
const fetchData = async () => {
  loading.value.ekskul = true;
  await getData({
    data: "ekstrakurikuler",
  }).then(() => {
    loading.value.ekskul = false;
  });
}
const getData = async (postData) => {
  const mergedForm = { ...postData, ...form.value };
  await $api("/referensi/get-data", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data;
      if (postData.data == "ekstrakurikuler") {
        arrayData.value.ekstrakurikuler = getData.data_ekskul
        arrayData.value.data_nilai = getData.data_nilai
      }
      if (postData.data == "reguler") {
        arrayData.value.rombel = getData.reguler
        namaEkskul.value = getData.ekstrakurikuler?.nama_ekskul
        form.value.ekstrakurikuler_id = getData.ekstrakurikuler?.ekstrakurikuler_id
      }
      if (postData.data == "kelas") {
        var nilai_ekskul = {}
        var deskripsi_ekskul = {}
        getData.siswa.forEach((siswa) => {
          nilai_ekskul[siswa.anggota_ekskul.anggota_rombel_id] = siswa.anggota_ekskul.single_nilai_ekstrakurikuler?.nilai
          deskripsi_ekskul[siswa.anggota_ekskul.anggota_rombel_id] = siswa.anggota_ekskul.single_nilai_ekstrakurikuler?.deskripsi_ekskul
        })
        form.value.nilai_ekskul = nilai_ekskul
        form.value.deskripsi_ekskul = deskripsi_ekskul
        namaRombel.value = getData.rombel
        isReset.value = Object.keys(removeEmptyValues(nilai_ekskul)).length
        arrayData.value.siswa = getData.siswa
        showBtn.value = true
      }
    },
  });
}
const form = ref({
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  rombongan_belajar_id: null,
  rombel_id_reguler: null,
  ekstrakurikuler_id: null,
  nilai_ekskul: {},
  deskripsi_ekskul: {},
})
const errors = ref({
  rombongan_belajar_id: null,
})
const arrayData = ref({
  ekstrakurikuler: [],
  rombel: [],
  siswa: [],
  data_nilai: [],
})
const showBtn = ref(false)
const confirmed = ref(false)
const isConfirmDialogVisible = ref(false);
const isNotifVisible = ref(false);
const confirmationText = ref("");
const notif = ref({
  color: "",
  title: "",
  text: "",
});
const namaEkskul = ref()
const namaRombel = ref()
const isReset = ref(false)
const onSubmit = async () => {
  confirmed.value = true;
  const newForm = {
    opsi: 'nilai-ekskul'
  }
  const mergedForm = { ...newForm, ...form.value };
  await $api("/penilaian/simpan", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data;
      confirmed.value = false;
      arrayData.value = {
        ekstrakurikuler: [],
        rombel: [],
        siswa: [],
        data_nilai: [],
      }
      if (getData.errors) {
        errors.value = getData.errors;
      } else {
        isNotifVisible.value = true;
        notif.value = getData;
      }
    },
  });
}
const changeEkskul = async (val) => {
  if (val) {
    loading.value.rombel = true;
    await getData({
      data: "reguler",
    }).then(() => {
      loading.value.rombel = false;
    });
  }
}
const changeRombel = async (val) => {
  if (val) {
    isReset.value = false
    loading.value.body = true;
    await getData({
      data: "kelas",
    }).then(() => {
      loading.value.body = false;
    });
  }
}
const changeNilai = (val, anggota_rombel_id) => {
  var template_desk = {
    1: 'Sangat Aktif',
    2: 'Aktif',
    3: 'Cukup aktif',
    4: 'Tidak aktif',
  }
  form.value.deskripsi_ekskul[anggota_rombel_id] = (val) ? `${template_desk[val]} dalam kegiatan ${namaEkskul.value}` : ''
}
const confirmClose = () => {
  loading.value = {
    ekskul: false,
    rombel: false,
    body: false
  }
  form.value = {
    semester_id: $semester.semester_id,
    user_id: $user.user_id,
    guru_id: $user.guru_id,
    sekolah_id: $user.sekolah_id,
    rombongan_belajar_id: null,
    rombel_id_reguler: null,
    ekstrakurikuler_id: null,
    nilai_ekskul: {},
    deskripsi_ekskul: {},
  }
  errors.value = {
    rombongan_belajar_id: null,
  }
  arrayData.value = {
    ekstrakurikuler: [],
    rombel: [],
    siswa: [],
    data_nilai: [],
  }
  showBtn.value = false
  confirmed.value = false
  isConfirmDialogVisible.value = false
  isNotifVisible.value = false
  confirmationText.value = ''
  notif.value = {
    color: "",
    title: "",
    text: "",
  }
  namaEkskul.value = undefined
  namaRombel.value = undefined
  isReset.value = false
  fetchData()
}
const resetNilai = async () => {
  isConfirmDialogVisible.value = true
  confirmationText.value = `Seluruh Nilai ${namaEkskul.value} di Kelas ${namaRombel.value} akan dihapus!`
  console.log('resetNilai');
}
const confirmDialog = async () => {
  const newForm = {
    data: 'nilai-ekskul'
  }
  const mergedForm = { ...newForm, ...form.value };
  await $api("/penilaian/destroy", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data;
      isNotifVisible.value = true
      notif.value = getData
    },
  });
}
</script>
<template>
  <div>
    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Nilai Ekstrakurikuler</VCardTitle>
      </VCardItem>
      <VDivider />
      <VForm @submit.prevent="onSubmit">
        <VCardText>
          <VRow>
            <VCol cols="12">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis" for="semester_id">Tahun
                    Pelajaran</label>
                </VCol>
                <VCol cols="12" md="9">
                  <AppTextField id="semester_id" :value="$semester.nama" disabled />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis"
                    for="rombongan_belajar_id">Ekstrakurikuler</label>
                </VCol>
                <VCol cols="12" md="9">
                  <AppAutocomplete id="rombongan_belajar_id" v-model="form.rombongan_belajar_id"
                    placeholder="== Pilih Ekstrakurikuler ==" :items="arrayData.ekstrakurikuler" clearable
                    clear-icon="tabler-x" @update:model-value="changeEkskul" item-value="rombongan_belajar_id"
                    item-title="nama_ekskul" :error-messages="errors.rombongan_belajar_id" :loading="loading.ekskul"
                    :disabled="loading.ekskul" />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis" for="rombel_id_reguler">Rombongan
                    Belajar</label>
                </VCol>
                <VCol cols="12" md="9">
                  <AppAutocomplete id="rombel_id_reguler" v-model="form.rombel_id_reguler"
                    placeholder="== Pilih Rombongan Belajar ==" :items="arrayData.rombel" clearable
                    clear-icon="tabler-x" @update:model-value="changeRombel" item-value="rombongan_belajar_id"
                    item-title="nama" :error-messages="errors.rombel_id_reguler" :loading="loading.rombel"
                    :disabled="loading.rombel" />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" v-if="isReset">
              <VRow no-gutters>
                <VCol cols="12" md="3" class="d-flex align-items-center">
                  <label class="v-label text-body-2 text-high-emphasis">Reset Nilai Ekstrakurikuler</label>
                </VCol>
                <VCol cols="12" md="9">
                  <VBtn @click="resetNilai" color="error" variant="tonal">
                    {{ `Reset Nilai ${namaEkskul} kelas ${namaRombel}` }}
                  </VBtn>
                </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VCardText>
        <template v-if="loading.body">
          <VCardText class="text-center">
            <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
          </VCardText>
        </template>
        <template v-if="arrayData.siswa.length">
          <VDivider />
          <VTable class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-center" width="20%">Nama Peserta Didik</th>
                <th class="text-center" width="10%">Kelas</th>
                <th class="text-center" width="20%">Predikat</th>
                <th class="text-center" width="50%">Deskripsi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="siswa in arrayData.siswa">
                <td>
                  <ProfileSiswa :item="siswa" />
                </td>
                <td class="text-center">{{ siswa.kelas?.nama }}</td>
                <td>
                  <AppSelect v-model="form.nilai_ekskul[siswa.anggota_ekskul.anggota_rombel_id]"
                    placeholder="== Pilih Predikat ==" :items="arrayData.data_nilai" clearable clear-icon="tabler-x"
                    @update:model-value="changeNilai(form.nilai_ekskul[siswa.anggota_ekskul.anggota_rombel_id], siswa.anggota_ekskul.anggota_rombel_id)" />
                </td>
                <td>
                  <AppTextField v-model="form.deskripsi_ekskul[siswa.anggota_ekskul.anggota_rombel_id]" />
                </td>
              </tr>
            </tbody>
          </VTable>
        </template>
        <VDivider />
        <VCardText class="d-flex justify-end flex-wrap gap-3 pt-5 overflow-visible" v-if="showBtn">
          <VBtn variant="elevated" type="submit" :loading="confirmed" :disabled="confirmed">
            Simpan
          </VBtn>
        </VCardText>
      </VForm>
    </VCard>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" :confirmation-text="confirmationText" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDialog" @close="confirmClose" />
  </div>
</template>
