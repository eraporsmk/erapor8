<script setup>
definePage({
  meta: {
    action: "read",
    subject: "Guru",
    title: "Input Asesmen",
  },
});
const namaTemplate = ref('')
const linkTemplateTp = ref('')
const form = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  pembelajaran_id: null,
  mata_pelajaran_id: null,
  teknik_penilaian_id: null,
  template_excel: null,
});
const nilai = ref({
  tp: {},
  sumatif: {},
});
const errors = ref({
  tingkat: null,
  rombongan_belajar_id: null,
  pembelajaran_id: null,
  teknik_penilaian_id: null,
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
  teknik_penilaian_id: false,
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
  opsi: "",
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
    teknik_penilaian_id: null,
    template_excel: null,
  };
  nilai.value = {
    tp: {},
    sumatif: {},
  };
  errors.value = {
    tingkat: null,
    rombongan_belajar_id: null,
    pembelajaran_id: null,
    teknik_penilaian_id: null,
    template_excel: null,
  };
  arrayData.value = {
    rombel: [],
    mapel: [],
    teknik: [],
    siswa: [],
    tp: [],
  };
  loading.value = {
    rombel: false,
    mapel: false,
    teknik_penilaian_id: false,
    body: false,
  };
  showBtn.value = false;
  confirmed.value = false;
  isConfirmDialogVisible.value = false;
  isNotifVisible.value = false;
  confirmationText.value = "";
  notif.value = {
    color: "",
    title: "",
    text: "",
  };
  defaultForm.opsi = "";
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
  form.value.teknik_penilaian_id = null;
  if (val) {
    loading.value.teknik_penilaian_id = true;
    await getData({
      data: "teknik",
    }).then(() => {
      loading.value.teknik_penilaian_id = false;
    });
  }
};
const noTp = ref(false);
const showCp = ref(false);
const changeTeknik = async (val) => {
  arrayData.value.siswa = []
  arrayData.value.tp = []
  if (val) {
    loading.value.body = true;
    await $api("/penilaian/get-cp", {
      method: "POST",
      body: {
        rombongan_belajar_id: form.value.rombongan_belajar_id,
        pembelajaran_id: form.value.pembelajaran_id,
        mata_pelajaran_id: form.value.mata_pelajaran_id,
        teknik_penilaian_id: form.value.teknik_penilaian_id,
      },
      onResponse({ response }) {
        let getData = response._data;
        showCp.value = getData.show_cp;
        arrayData.value.siswa = getData.data_siswa;
        arrayData.value.tp = getData.data_tp;
        noTp.value = false;
        showBtn.value = false;
        if (getData.show_cp && !getData.data_tp.length) {
          noTp.value = true;
        }
        if (arrayData.value.siswa.length) {
          showBtn.value = true;
        }
        arrayData.value.siswa.forEach((el) => {
          if (showCp.value) {
            namaTemplate.value = 'Sumatif Lingkup Materi'
            defaultForm.opsi = "sumatif-lingkup-materi";
            if (el.anggota_rombel.nilai_tp.length) {
              el.anggota_rombel.nilai_tp.forEach((tp) => {
                nilai.value.tp[el.anggota_rombel.anggota_rombel_id + "#" + tp.tp_id] =
                  tp.nilai;
              });
            }
          } else {
            namaTemplate.value = 'Sumatif Akhir Semester'
            defaultForm.opsi = "sumatif-akhir-semester";
            if (el.anggota_rombel.nilai_sumatif.length) {
              el.anggota_rombel.nilai_sumatif.forEach((sumatif) => {
                nilai.value.sumatif[
                  el.anggota_rombel.anggota_rombel_id + "#" + sumatif.jenis
                ] = sumatif.nilai;
              });
            }
          }
        });
        linkTemplateTp.value = `/downloads/template-${defaultForm.opsi}/${form.value.pembelajaran_id}`
        loading.value.body = false;
      },
    });
  }
};
const confirmClose = () => {
  formReset();
}
const calculateAverage = (array) => {
  var total = 0;
  var count = 0;
  array.forEach(function (item, index) {
    total += item;
    count++;
  });
  var angka = total / count;
  return angka.toFixed(0);
}
const setRerata = (anggota_rombel_id, jenis) => {
  var getRerata = 0;
  var nilai_nontes = nilai.value.sumatif[`${anggota_rombel_id}#non-tes`]
  var nilai_tes = nilai.value.sumatif[`${anggota_rombel_id}#tes`]
  if (nilai_nontes && nilai_tes) {
    getRerata = calculateAverage([parseInt(nilai_nontes), parseInt(nilai_tes)])
  } else if (nilai_nontes && !nilai_tes) {
    getRerata = nilai_nontes
  } else if (!nilai_nontes && nilai_tes) {
    getRerata = nilai_tes
  }
  nilai.value.sumatif[`${anggota_rombel_id}#na`] = getRerata
}
const uploading = ref(false)
const onFileChange = async (val) => {
  uploading.value = true
  const data = new FormData();
  data.append('template_excel', val);
  data.append('rombongan_belajar_id', form.value.rombongan_belajar_id)
  data.append('pembelajaran_id', form.value.pembelajaran_id)
  data.append('sekolah_id', form.value.sekolah_id)
  data.append('merdeka', defaultForm.merdeka)
  data.append('opsi', defaultForm.opsi)
  await $api("/penilaian/upload-nilai", {
    method: "POST",
    body: data,
    onResponse({ response }) {
      let getData = response._data;
      uploading.value = false
      form.value.template_excel = null
      getData.data_nilai.forEach((item) => {
        if (defaultForm.opsi == 'sumatif-lingkup-materi') {
          item.nilai.forEach((a) => {
            nilai.value.tp[item.anggota_rombel_id + '#' + a.tp] = a.angka
          })
        } else {
          nilai.value.sumatif[item.anggota_rombel_id + '#non-tes'] = item.nilai_non_tes
          nilai.value.sumatif[item.anggota_rombel_id + '#tes'] = item.nilai_tes
          var getRerata = 0;
          if (item.nilai_non_tes && item.nilai_tes) {
            getRerata = calculateAverage([item.nilai_non_tes, item.nilai_tes])
          } else if (item.nilai_non_tes && !item.nilai_tes) {
            getRerata = item.nilai_non_tes
          } else if (!item.nilai_non_tes && item.nilai_tes) {
            getRerata = item.nilai_tes
          }
          nilai.value.sumatif[item.anggota_rombel_id + '#na'] = getRerata
        }
      })
    }
  })
}
</script>
<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Input Asesmen</VCardTitle>
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
                <label class="v-label text-body-2 text-high-emphasis" for="teknik_penilaian_id">Jenis Asesmen</label>
              </VCol>
              <VCol cols="12" md="9">
                <AppSelect v-model="form.teknik_penilaian_id" placeholder="== Pilih Jenis Asesmen =="
                  :items="arrayData.teknik" clearable clear-icon="tabler-x" @update:model-value="changeTeknik"
                  item-value="teknik_penilaian_id" item-title="nama" :error-messages="errors.teknik_penilaian_id"
                  :loading="loading.teknik_penilaian_id" :disabled="loading.teknik_penilaian_id" />
              </VCol>
            </VRow>
          </VCol>
          <VCol cols="12" v-if="arrayData.siswa.length">
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
                      Unduh Template {{ namaTemplate }}
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
        <VAlert color="error" class="text-center my-4" variant="tonal" v-if="noTp">
          <h2 class="mt-4 mb-4">Tidak ditemukan data Tujuan Pembelajaran</h2>
          <p>
            Silahkan tambah data Tujuan Pembelajaran terlebih dahulu
            <router-link :to="{ name: 'referensi-tujuan-pembelajaran' }">disini</router-link>
          </p>
        </VAlert>
      </VCardText>
      <VDivider />
      <template v-if="arrayData.siswa.length && !uploading">
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th class="text-center">Nama Peserta Didik</th>
              <template v-if="showCp">
                <template v-for="(tp, i) in arrayData.tp">
                  <th class="text-center">
                    {{ `TP ${i + 1}` }}
                    <VTooltip location="top" activator="parent" transition="scale-transition">
                      {{ tp.deskripsi }}
                    </VTooltip>
                  </th>
                </template>
              </template>
              <template v-else>
                <th class="text-center">Non Tes</th>
                <th class="text-center">Tes</th>
                <th class="text-center">NA Sumatif Akhir Semester</th>
              </template>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(siswa, index) in arrayData.siswa">
              <td>
                <ProfileSiswa :item="siswa" />
              </td>
              <template v-if="showCp">
                <template v-for="(tp, i) in arrayData.tp">
                  <td>
                    <div style="inline-size: 4rem">
                      <AppTextField type="number" v-model="nilai.tp[
                        siswa.anggota_rombel.anggota_rombel_id + '#' + tp.tp_id
                      ]
                        " />
                    </div>
                  </td>
                </template>
              </template>
              <template v-else>
                <td>
                  <AppTextField type="number" v-model="nilai.sumatif[siswa.anggota_rombel.anggota_rombel_id + '#non-tes']
                    " @update:model-value="
                      setRerata(siswa.anggota_rombel.anggota_rombel_id, '#non-tes')
                      " />
                </td>
                <td>
                  <AppTextField type="number" v-model="nilai.sumatif[siswa.anggota_rombel.anggota_rombel_id + '#tes']
                    " @update:model-value="
                      setRerata(siswa.anggota_rombel.anggota_rombel_id, '#tes')
                      " />
                </td>
                <td>
                  <AppTextField type="number" v-model="nilai.sumatif[siswa.anggota_rombel.anggota_rombel_id + '#na']
                    " disabled />
                </td>
              </template>
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
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
      confirmation-question="Apakah Anda yakin?" :confirmation-text="confirmationText" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @close="confirmClose" />
    <VOverlay v-model="uploading" contained persistent scroll-strategy="none" class="align-center justify-center">
      <VProgressCircular indeterminate />
    </VOverlay>
  </VCard>
</template>
