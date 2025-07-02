<script setup>
import authV2RegisterIllustrationBorderedDark from '@images/pages/auth-v2-register-illustration-bordered-dark.png';
import authV2RegisterIllustrationBorderedLight from '@images/pages/auth-v2-register-illustration-bordered-light.png';
//import authV2RegisterIllustrationDark from '@images/pages/auth-v2-register-illustration-dark.png'
//import authV2RegisterIllustrationLight from '@images/pages/auth-v2-register-illustration-light.png'
import { default as authV2RegisterIllustrationDark, default as authV2RegisterIllustrationLight } from '@images/pages/bg_login.png';
import authV2MaskDark from '@images/pages/misc-mask-dark.png';
import authV2MaskLight from '@images/pages/misc-mask-light.png';
import { themeConfig } from '@themeConfig';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { VForm } from 'vuetify/components/VForm';
const imageVariant = useGenerateImageVariant(authV2RegisterIllustrationLight, authV2RegisterIllustrationDark, authV2RegisterIllustrationBorderedLight, authV2RegisterIllustrationBorderedDark, true)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)
const router = useRouter();
definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
    title: 'Register',
  },
})
onBeforeMount(async () => {
  await fetchData();
})
const loadingBody = ref(true)
const fetchData = async () => {
  try {
    const response = await useApi(createUrl('/auth/allow-register'));
    let getData = response.data.value
    if (getData.sekolah) {
      if (!getData.allowRegister) {
        await nextTick(() => {
          //router.replace('/:404(.*)')
        });
      } else {
        loadingBody.value = false;
      }
    } else {
      loadingBody.value = false;
    }
  } catch (error) {
    console.error(error);
  }
}
const refVForm = ref();
const form = ref({
  npsn: '',
  email: '',
  password: '',
})
const failed = ref({
  npsn: undefined,
  email: undefined,
  password: undefined,
});
const isPasswordVisible = ref(false)
const notify = () => {
  toast(
    `<strong>Registrasi Berhasil</strong>
    <br>Anda telah berhasil register. Sekarang Anda dapat mulai berselancar di Aplikasi e-Rapor SMK!`, {
    closeOnClick: false,
    autoClose: 5000,
    position: toast.POSITION.TOP_RIGHT,
    dangerouslyHTMLString: true,
  });
}
const register = async () => {
  try {
    const res = await $api("/auth/register", {
      method: "POST",
      body: {
        npsn: form.value.npsn,
        email: form.value.email,
        password: form.value.password,
      },
      onResponseError({ response }) {
        failed.value = response._data.errors;
      },
    });
    const { error, errors, message } = res;
    if (error) {
      failed.value.npsn = errors.npsn?.join(', ')
      failed.value.email = errors.email?.join(', ')
      failed.value.password = errors.password?.join(', ')
    } else {
      await nextTick(() => {
        router.replace('/login').then(() => {
          notify()
        });
      });
    }
  } catch (err) {
    console.error(err);
  }
};
const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) register();
  });
}
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <img :src="themeConfig.app.logo" height="24" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink>
  <VRow no-gutters class="auth-wrapper bg-surface text-center" v-if="loadingBody">
    <VCol cols="12">
      <VProgressCircular :size="60" indeterminate color="error" class="mt-16" />
    </VCol>
  </VRow>
  <VRow no-gutters class="auth-wrapper bg-surface" v-else>
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 me-0">
        <div class="d-flex align-center justify-center w-100 h-100" style="padding-inline: 100px;">
          <VImg max-width="500" :src="imageVariant" class="auth-illustration mt-16 mb-2" />
        </div>

        <img class="auth-footer-mask" :src="authThemeMask" alt="auth-footer-mask" height="280" width="100">
      </div>
    </VCol>

    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center"
      style="background-color: rgb(var(--v-theme-surface));">
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-4">
        <VCardText class="text-center">
          <h1 class="text-h2 mb-1">
            <img :src="themeConfig.app.logo" height="28" /> {{ themeConfig.app.title }}
          </h1>
          <p>Versi {{ themeConfig.app.version }}</p>
          <p class="mb-0">Silahkan register untuk dapat mengakses Aplikasi</p>
        </VCardText>

        <VCardText>
          <VForm ref="refVForm" @submit.prevent="onSubmit">
            <VRow>
              <!-- Username -->
              <VCol cols="12">
                <AppTextField v-model="form.npsn" :rules="[requiredValidator]" autofocus label="NPSN" placeholder="NPSN"
                  :error-messages="failed.npsn" required />
              </VCol>

              <!-- email -->
              <VCol cols="12">
                <AppTextField v-model="form.email" :rules="[requiredValidator, emailValidator]" label="Email"
                  type="email" placeholder="Email Admin Dapodik" :error-messages="failed.email" required />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField v-model="form.password" :rules="[requiredValidator]" label="Password"
                  placeholder="············" :type="isPasswordVisible ? 'text' : 'password'" autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible" :error-messages="failed.password"
                  required />


                <VBtn block type="submit" class="mt-6">
                  Register
                </VBtn>
              </VCol>

              <!-- create account -->
              <VCol cols="12" class="text-center text-base">
                <span class="d-inline-block">Telah terdaftar?</span>
                <RouterLink class="text-primary ms-1 d-inline-block" :to="{ name: 'login' }">
                  Login Disini
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
