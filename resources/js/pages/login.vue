<script setup>
import { useGenerateImageVariant } from "@core/composable/useGenerateImageVariant";
import authV2LoginIllustrationBorderedDark from "@images/pages/auth-v2-login-illustration-bordered-dark.png";
import authV2LoginIllustrationBorderedLight from "@images/pages/auth-v2-login-illustration-bordered-light.png";
//import authV2LoginIllustrationDark from "@images/pages/auth-v2-login-illustration-dark.png";
//import authV2LoginIllustrationLight from "@images/pages/auth-v2-login-illustration-light.png";
import { default as authV2LoginIllustrationDark, default as authV2LoginIllustrationLight } from "@images/pages/bg_login.png";
import authV2MaskDark from "@images/pages/misc-mask-dark.png";
import authV2MaskLight from "@images/pages/misc-mask-light.png";
import { themeConfig } from "@themeConfig";
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
const authThemeImg = useGenerateImageVariant(
  authV2LoginIllustrationLight,
  authV2LoginIllustrationDark,
  authV2LoginIllustrationBorderedLight,
  authV2LoginIllustrationBorderedDark,
  true
);
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark);
definePage({
  meta: {
    layout: "blank",
    unauthenticatedOnly: true,
    title: 'Login',
  },
});
const refVForm = ref();
const form = ref({
  email: "",
  password: "",
  semester_id: "",
  remember: false,
});
const route = useRoute();
const router = useRouter();
const ability = useAbility();

const errors = ref({
  email: undefined,
  password: undefined,
});
const isPasswordVisible = ref(false);
const role = ref([])
const namaUser = ref('')
const notify = () => {
  toast(
    `<strong>Selamat Datang ${namaUser.value}</strong>
    <br>Anda telah berhasil masuk sebagai <strong>${role.value}</strong>. Sekarang Anda dapat mulai berselancar di Aplikasi e-Rapor SMK!`, {
    closeOnClick: false,
    autoClose: 5000,
    position: toast.POSITION.TOP_RIGHT,
    dangerouslyHTMLString: true,
  });
}
const loadingButton = ref(false)
const login = async () => {
  loadingButton.value = true
  try {
    const res = await $api("/auth/login", {
      method: "POST",
      body: {
        email: form.value.email,
        password: form.value.password,
        semester_id: form.value.semester_id,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors;
        loadingButton.value = false
      },
    });

    const { accessToken, userData, sekolah, semester, userAbility, roles } = res;
    namaUser.value = userData.name
    useCookie("userAbilityRules").value = userAbility;
    ability.update(userAbility);
    useCookie("userData").value = userData;
    useCookie("accessToken").value = accessToken;
    useCookie("sekolah").value = sekolah;
    useCookie("semester").value = semester;
    useCookie("roles").value = roles;
    useCookie("profilePhotoPath").value = userData.profile_photo_path;
    role.value = roles.join(', ')
    loadingButton.value = false
    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : "/").then(() => {
        notify()
      });
    });
  } catch (err) {
    console.error(err);
  }
};

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) login();
  });
};
const allowRegister = ref(false)
const data_semester = ref([])
const bgLogin = ref()
onMounted(async () => {
  await fetchData();
});
const fetchData = async () => {
  try {
    const response = await useApi(createUrl('/auth/semester'))
    let getData = response.data.value
    data_semester.value = getData.semester
    form.value.semester_id = getData.semester_id
    allowRegister.value = getData.allowRegister
    bgLogin.value = getData.bg_login
  } catch (error) {
    console.error(error);
  }
}
</script>

<template>
  <a href="javascript:void(0)">
    <div class="auth-logo d-flex align-center gap-x-3">
      <img :src="themeConfig.app.logo" height="24" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </a>

  <VRow no-gutters class="auth-wrapper bg-surface">
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 me-0">
        <div class="d-flex align-center justify-center w-100 h-100" style="padding-inline: 6.25rem">
          <VImg max-width="613" :src="bgLogin" class="auth-illustration mt-16 mb-2" v-if="bgLogin" />
          <VImg max-width="613" :src="authThemeImg" class="auth-illustration mt-16 mb-2" v-else />
        </div>

        <img class="auth-footer-mask flip-in-rtl" :src="authThemeMask" alt="auth-footer-mask" height="280"
          width="100" />
      </div>
    </VCol>

    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center">
      <VCard flat :max-width="500" class="mt-sm-0 pa-6">
        <VCardText class="text-center">
          <h1 class="text-h2 mb-1">
            <img :src="themeConfig.app.logo" height="28" /> {{ themeConfig.app.title }}
          </h1>
          <p>Versi {{ themeConfig.app.version }}</p>
          <p class="mb-0">Silahkan login untuk dapat mengakses Aplikasi</p>
        </VCardText>
        <VCardText>
          <VForm ref="refVForm" @submit.prevent="onSubmit">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField v-model="form.email" :error-messages="errors.email" autofocus label="Email/NUPTK/NISN"
                  placeholder="Email/NUPTK/NISN" :rules="[requiredValidator]" />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField v-model="form.password" :error-messages="errors.password" label="Password"
                  placeholder="············" :type="isPasswordVisible ? 'text' : 'password'" autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible" :rules="[requiredValidator]" />
              </VCol>
              <VCol cols="12">
                <AppSelect :items="data_semester" item-title="nama" item-value="semester_id" label="Tahun Pelajaran"
                  v-model="form.semester_id" placeholder="Select Item" />
                <div class="d-flex align-center flex-wrap justify-space-between my-6">
                  <VCheckbox v-model="form.remember" label="Simpan Login" />
                  <RouterLink class="text-primary" :to="{ name: 'lupa-password' }">
                    Lupa Password?
                  </RouterLink>
                </div>

                <VBtn block type="submit" :loading="loadingButton" :disabled="loadingButton"> Login </VBtn>
              </VCol>
              <VCol cols="12" class="text-center" v-if="allowRegister">
                <span>Pengguna Baru?</span>
                <RouterLink class="text-primary ms-1" :to="{ name: 'register' }">
                  Register Disini
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardText></VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
