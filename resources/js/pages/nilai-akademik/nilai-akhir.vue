<script setup>
definePage({
  meta: {
    action: "read",
    subject: "Guru",
    title: "Input Nilai Akhir",
  },
});
const linkTemplateTp = ref('')
const form = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  pembelajaran_id: null,
  mata_pelajaran_id: null,
  bentuk_penilaian: null,
  template_excel: null,
});
const nilai = ref({
  akhir: {},
  kompeten: {},
});
const errors = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  pembelajaran_id: null,
  bentuk_penilaian: null,
  template_excel: null,
});
const arrayData = ref({
  rombel: [],
  mapel: [],
  teknik: [],
  siswa: [],
  tp: [],
});
const loading = ref({
  rombel: false,
  mapel: false,
  bentuk_penilaian: false,
  body: false,
});
const showBtn = ref(false);
const confirmed = ref(false);
const isConfirmDialogVisible = ref(false);
const isNotifVisible = ref(false);
const confirmationText = ref("");
const notif = ref({
  color: "",
  title: "",
  text: "",
});
const onSubmit = async () => {
  confirmed.value = true;
  const mergedForm = { ...defaultForm, ...form.value, ...nilai.value };
  await $api("/penilaian/simpan", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data;
      confirmed.value = false;
      if (getData.errors) {
        errors.value = getData.errors;
      } else {
        isNotifVisible.value = true;
        notif.value = getData;
      }
    },
  });
};
const defaultForm = {
  opsi: "nilai-akhir",
  semester_id: $semester.semester_id,
  user_id: $user.user_id,
  guru_id: $user.guru_id,
  sekolah_id: $user.sekolah_id,
  merdeka: false,
};
const resetForm = ref(false)
const formReset = () => {
  resetForm.value = true
  form.value = {
    tingkat: null,
    rombongan_belajar_id: null,
    pembelajaran_id: null,
    mata_pelajaran_id: null,
    bentuk_penilaian: null,
    template_excel: null,
  }
  nilai.value = {
    akhir: {},
    kompeten: {}
  }
  errors.value = {
    tingkat: null,
    rombongan_belajar_id: null,
    pembelajaran_id: null,
    bentuk_penilaian: null,
    template_excel: null,
  }
  arrayData.value = {
    rombel: [],
    mapel: [],
    teknik: [],
    siswa: [],
    tp: [],
  }
  loading.value = {
    rombel: false,
    mapel: false,
    bentuk_penilaian: false,
    body: false,
  }
  showBtn.value = false;
  confirmed.value = false;
  isConfirmDialogVisible.value = false;
  isNotifVisible.value = false;
  confirmationText.value = ""
  notif.value = {
    color: "",
    title: "",
    text: "",
  }
};
const getData = async (postData) => {
  const mergedForm = { ...postData, ...defaultForm };
  await $api("/referensi/get-data", {
    method: "POST",
    body: mergedForm,
    onResponse({ response }) {
      let getData = response._data;
      if (postData.data == "rombel") {
        arrayData.value.rombel = getData;
      }
      if (postData.data == "mapel") {
        arrayData.value.mapel = getData.mapel;
        defaultForm.merdeka = getData.merdeka
      }
      if (postData.data == "teknik") {
        arrayData.value.teknik = getData;
      }
    },
  });
};
const changeTingkat = async (val) => {
  form.value.rombongan_belajar_id = null;
  form.value.pembelajaran_id = null;
  form.value.mata_pelajaran_id = null
  if (val) {
    loading.value.rombel = true;
    await getData({
      data: "rombel",
      nilai: 1,
      tingkat: val,
    }).then(() => {
      loading.value.rombel = false;
    });
  }
};
const changeRombel = async (val) => {
  form.value.pembelajaran_id = null;
  form.value.mata_pelajaran_id = null
  if (val) {
    loading.value.mapel = true;
    await getData({
      data: "mapel",
      nilai: 1,
      rombongan_belajar_id: val,
    }).then(() => {
      loading.value.mapel = false;
    });
  }
};
const changeMapel = async (val) => {
  form.value.bentuk_penilaian = null;
  if (val) {
    loading.value.bentuk_penilaian = true;
    await getData({
      data: "teknik",
    }).then(() => {
      loading.value.bentuk_penilaian = false;
    });
  }
};
const isDisabled = ref(false)
const changeTeknik = async (val) => {
  arrayData.value.siswa = []
  arrayData.value.tp = []
  if (val) {
    const mergedForm = { ...form.value, ...defaultForm };
    loading.value.body = true;
    showBtn.value = false;
    await $api("/penilaian/get-nilai-akhir", {
      method: "POST",
      body: mergedForm,
      onResponse({ response }) {
        let getData = response._data;
        arrayData.value.siswa = getData.data_siswa;
        arrayData.value.tp = getData.data_tp;
        linkTemplateTp.value = ''
        if (form.value.bentuk_penilaian == 'asesmen') {
          isDisabled.value = true
        } else {
          isDisabled.value = false
          linkTemplateTp.value = `/downloads/template-nilai-akhir/${form.value.pembelajaran_id}`
        }
        getData.data_siswa.forEach((siswa) => {
          if (form.value.bentuk_penilaian == 'asesmen') {
            nilai.value.akhir[siswa.anggota_rombel_id] = siswa.nilai_asesmen
          } else {
            nilai.value.akhir[siswa.anggota_rombel_id] = siswa.nilai_akhir
          }
          getData.data_tp.forEach((tp) => {
            const capaian = siswa.capaian_kompeten.find(item => item.tp_id === tp.tp_id)
            nilai.value.kompeten[siswa.anggota_rombel_id + '#' + tp.tp_id] = capaian?.kompeten
          })
        })
        showBtn.value = true;
        loading.value.body = false;
      },
    });
  }
};
const confirmClose = () => {
  formReset();
}
const uploading = ref(false)
const onFileChange = async (val) => {
  uploading.value = true
  const data = new FormData();
  data.append('template_excel', val);
  data.append('rombongan_belajar_id', form.value.rombongan_belajar_id)
  data.append('pembelajaran_id', form.value.pembelajaran_id)
  data.append('sekolah_id', defaultForm.sekolah_id)
  data.append('merdeka', defaultForm.merdeka)
  await $api("/penilaian/upload-nilai", {
    method: "POST",
    body: data,
    onResponse({ response }) {
      let getData = response._data;
      isNotifVisible.value = true;
      notif.value = getData;
      uploading.value = false
    }
  })
}
</script>
<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Input Nilai Akhir</VCardTitle>
    </VCardItem>
    <VDivider />
    <VForm @submit.prevent="onSubmit">
      <VCardText>
        <VRow>
          <DefaultForm v-model:form="form" v-model:errors="errors" v-model:arrayData="arrayData"
            v-model:loading="loading" v-model:resetForm="resetForm" @tingkat="changeTingkat"
            @rombongan_belajar_id="changeRombel" @pembelajaran_id="changeMapel"></DefaultForm>
          <VCol cols="12">
            <VRow no-gutters>
              <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="bentuk_penilaian">Bentuk Penilaian</label>
              </VCol>
              <VCol cols="12" md="9">
                <AppSelect v-model="form.bentuk_penilaian" placeholder="== Pilih Bentuk Penilaian =="
                  :items="bentukPenilaian" clearable clear-icon="tabler-x" @update:model-value="changeTeknik"
                  :error-messages="errors.bentuk_penilaian" :loading="loading.bentuk_penilaian"
                  :disabled="loading.bentuk_penilaian" />
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="12" v-if="arrayData.siswa.length && !isDisabled">
            <VRow no-gutters>
              <VCol cols="12" md="3" class="d-flex align-items-center">
                <label class="v-label text-body-2 text-high-emphasis" for="template_excel">Template Excel</label>
              </VCol>
              <VCol cols="12" md="9">
                <VRow no-gutters>
                  <VCol cols="6">
                    <VFileInput id="template_excel" v-model="form.template_excel"
                      :error-messages="errors.template_excel" @update:model-value="onFileChange"
                      accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                      label="Unggah Template Excel" />
                  </VCol>
                  <VCol cols="6">
                    <VBtn color="primary" class="ms-3" :href="linkTemplateTp" target="_blank">
                      Unduh Template Nilai Akhir
                    </VBtn>
                  </VCol>
                </VRow>
              </VCol>
            </VRow>
          </VCol>
        </VRow>
        <div class="text-center" v-if="loading.body">
          <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
        </div>
      </VCardText>
      <VDivider />
      <template v-if="arrayData.siswa.length && !uploading">
        <VTable>
          <thead>
            <tr>
              <th class="text-center">Nama Peserta Didik</th>
              <th class="text-center">Nilai Akhir</th>
              <th class="text-center">Ketercapaian Kompetensi</th>
              <th>Deskripsi Tujuan Pembelajaran</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="siswa in arrayData.siswa">
              <tr>
                <td :rowspan="arrayData.tp.length + 1" class="text-no-wrap pt-2" style="vertical-align: top;">
                  <ProfileSiswa :item="siswa" />
                </td>
                <td :rowspan="arrayData.tp.length + 1" style="vertical-align: top;" class="pt-2">
                  <div style="inline-size: 6rem;">
                    <AppTextField type="number" v-model="nilai.akhir[siswa.anggota_rombel_id]" :disabled="isDisabled" />
                  </div>
                </td>
              </tr>
              <template v-for="(tp, i) in arrayData.tp">
                <tr>
                  <td style="vertical-align: top;" class="pt-2">
                    <AppSelect v-model="nilai.kompeten[siswa.anggota_rombel_id + '#' + tp.tp_id]"
                      placeholder="== Pilih Capaian ==" :items="dataCapaian" clearable clear-icon="tabler-x"
                      style="inline-size: 12rem;" />
                  </td>
                  <td>{{ tp.deskripsi }}</td>
                </tr>
              </template>
            </template>
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
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" :confirmation-text="confirmationText" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @close="confirmClose" />
    <VOverlay v-model="uploading" contained persistent scroll-strategy="none" class="align-center justify-center">
      <VProgressCircular indeterminate />
    </VOverlay>
  </VCard>
</template>
