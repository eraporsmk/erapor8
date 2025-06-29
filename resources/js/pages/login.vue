<script setup>
import { useGenerateImageVariant } from "@core/composable/useGenerateImageVariant";
import authV2LoginIllustrationBorderedDark from "@images/pages/auth-v2-login-illustration-bordered-dark.png";
import authV2LoginIllustrationBorderedLight from "@images/pages/auth-v2-login-illustration-bordered-light.png";
import authV2LoginIllustrationDark from "@images/pages/auth-v2-login-illustration-dark.png";
import authV2LoginIllustrationLight from "@images/pages/auth-v2-login-illustration-light.png";
import authV2MaskDark from "@images/pages/misc-mask-dark.png";
import authV2MaskLight from "@images/pages/misc-mask-light.png";
import { themeConfig } from "@themeConfig";
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
const rememberMe = ref(false);
const isPasswordVisible = ref(false);
const login = async () => {
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
      },
    });

    const { accessToken, userData, userAbilityRules } = res;

    useCookie("userAbilityRules").value = userData.ability;
    ability.update(userData.ability);
    useCookie("userData").value = userData;
    useCookie("accessToken").value = accessToken;
    await nextTick(() => {
      //window.location.replace('/')
      router.replace(route.query.to ? String(route.query.to) : "/");
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
const { data: getData, execute: fetchData } = await useApi(createUrl("/auth/semester"));
const data_semester = computed(() => getData.value.semester);
//form.value.semester_id = computed(() => getData.value.semester_id)
const semesterId = ref(getData.value.semester_id);
form.value.semester_id = semesterId.value;
</script>

<template>
  <a href="javascript:void(0)">
    <div class="auth-logo d-flex align-center gap-x-3">
      <!--VNodeRenderer :nodes="themeConfig.app.logo" /-->
      <img :src="themeConfig.app.logo" height="24" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </a>

  <VRow no-gutters class="auth-wrapper bg-surface">
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem"
        >
          <VImg
            max-width="613"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask flip-in-rtl"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        />
      </div>
    </VCol>

    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center">
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-6">
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
                <AppTextField
                  v-model="form.email"
                  :error-messages="errors.email"
                  autofocus
                  label="Email"
                  type="email"
                  placeholder="Email/NUPTK/NISN"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.password"
                  :error-messages="errors.password"
                  label="Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>
              <VCol cols="12">
                <AppSelect
                  :items="data_semester"
                  item-title="nama"
                  item-value="semester_id"
                  label="Tahun Pelajaran"
                  v-model="form.semester_id"
                  placeholder="Select Item"
                />
                <div class="d-flex align-center flex-wrap justify-space-between my-6">
                  <VCheckbox v-model="form.remember" label="Simpan Login" />
                  <a class="text-primary" href="javascript:void(0)"> Lupa Password? </a>
                </div>

                <VBtn block type="submit"> Login </VBtn>
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
