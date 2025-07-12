<script setup>
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw';
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw';
import { VNodeRenderer } from '@layouts/components/VNodeRenderer';
import { themeConfig } from '@themeConfig';
definePage({
    meta: {
        layout: 'blank',
        public: true,
    },
})
const route = useRoute()
const router = useRouter()
const form = ref({
    email: '',
    password: '',
    password_confirmation: '',
    token: '',
})
const errors = ref({
    email: undefined,
    password: undefined,
    password_confirmation: undefined,
    token: undefined,
})
const refVForm = ref()
const loadingButton = ref(false)
const resetPassword = () => {
    console.log('resetPassword');
    refVForm.value?.validate().then(({ valid: isValid }) => {
        if (isValid) postData();
    });
}
const isConfirmDialogVisible = ref(false)
const isNotifVisible = ref(false)
const notif = ref({
    color: null,
    title: null,
    text: null,
})
const validasiToken = ref(false)
const confirmClose = () => {
    console.log('confirmClose');
    if (!validasiToken.value) {
        console.log(validasiToken.value);
        router.replace("/login")
    }
}
const postData = async () => {
    errors.value = {
        email: undefined,
        password: undefined,
        password_confirmation: undefined,
        token: undefined,
    }
    loadingButton.value = true
    try {
        const res = await $api("/auth/reset-password", {
            method: "POST",
            body: {
                email: form.value.email,
                password: form.value.password,
                password_confirmation: form.value.password_confirmation,
                token: form.value.token,
            },
            onResponseError({ response }) {
                errors.value = response._data.errors;
                loadingButton.value = false
            },
            onResponse({ response }) {
                let getData = response._data
                if (!getData.errors) {
                    isNotifVisible.value = true
                    notif.value = getData
                    validasiToken.value = getData.error
                }
                /*
                await nextTick(() => {
                    console.log(res);

                router.replace("/login").then(() => {
                        notify()
                    });
                });
                */
            },
        });
    } catch (err) {
        loadingButton.value = false
        console.error(err);
    } finally {
        loadingButton.value = false
    }
}
const token = ref()
const isPasswordVisible = ref(false);
const isConfirmPasswordVisible = ref(false)
onMounted(async () => {
    await $api("/auth/get-email", {
        method: "POST",
        body: {
            token: route.params.token,
        },
        onResponse({ response }) {
            let getData = response._data
            token.value = getData.token
            form.value.email = getData.email
            form.value.token = getData.token
        }
    })
})
</script>

<template>
    <div class="auth-wrapper d-flex align-center justify-center pa-4">
        <div class="position-relative my-sm-16">
            <!-- 👉 Top shape -->
            <VNodeRenderer :nodes="h('div', { innerHTML: authV1TopShape })"
                class="text-primary auth-v1-top-shape d-none d-sm-block" />

            <!-- 👉 Bottom shape -->
            <VNodeRenderer :nodes="h('div', { innerHTML: authV1BottomShape })"
                class="text-primary auth-v1-bottom-shape d-none d-sm-block" />

            <!-- 👉 Auth card -->
            <VCard class="auth-card" max-width="460" :class="$vuetify.display.smAndUp ? 'pa-6' : 'pa-0'">
                <VCardItem class="justify-center">
                    <VCardTitle>
                        <RouterLink to="/">
                            <div class="app-logo">
                                <img :src="themeConfig.app.logo" height="28" />
                                <h1 class="app-logo-title">
                                    {{ themeConfig.app.title }}
                                </h1>
                            </div>
                        </RouterLink>
                    </VCardTitle>
                </VCardItem>

                <VCardText>
                    <h4 class="text-h4 mb-1">
                        Lupa Password? 🔒
                    </h4>
                    <p class="mb-0">
                        Masukkan email Anda dan kami akan mengirimkan instruksi untuk mengatur ulang password Anda
                    </p>
                </VCardText>

                <VCardText>
                    <VForm ref="refVForm" @submit.prevent="resetPassword">
                        <VRow>
                            <!-- email -->
                            <VCol cols="12">
                                <AppTextField v-model="form.email" autofocus label="Email" type="email"
                                    :rules="[requiredValidator, emailValidator]" :error-messages="errors.email"
                                    disabled />
                            </VCol>
                            <VCol cols="12">
                                <AppTextField v-model="form.password" :error-messages="errors.password" label="Password"
                                    placeholder="············" :type="isPasswordVisible ? 'text' : 'password'"
                                    autocomplete="password"
                                    :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                                    @click:append-inner="isPasswordVisible = !isPasswordVisible"
                                    :rules="[requiredValidator]" />
                            </VCol>
                            <VCol cols="12">
                                <AppTextField v-model="form.password_confirmation"
                                    :error-messages="errors.password_confirmation" label="Konfirmasi Password"
                                    placeholder="············" :type="isConfirmPasswordVisible ? 'text' : 'password'"
                                    autocomplete="password"
                                    :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                                    @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                                    :rules="[requiredValidator, confirmedValidator(form.password_confirmation, form.password)]" />
                            </VCol>
                            <!-- reset password -->
                            <VCol cols="12">
                                <VBtn block type="submit" :loading="loadingButton" :disabled="loadingButton">
                                    Simpan
                                </VBtn>
                            </VCol>

                            <!-- back to login -->
                            <VCol cols="12">
                                <RouterLink class="d-flex align-center justify-center" :to="{ name: 'login' }">
                                    <VIcon icon="tabler-chevron-left" size="20" class="me-1 flip-in-rtl" />
                                    <span>Kembali ke laman login</span>
                                </RouterLink>
                            </VCol>
                        </VRow>
                    </VForm>
                </VCardText>
            </VCard>
        </div>
        <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" v-model:isNotifVisible="isNotifVisible"
            confirmation-question="Apakah Anda yakin?" confirmation-text="Tindakan ini tidak dapat dikembalikan!"
            :confirm-color="notif.color" :confirm-title="notif.title" :confirm-msg="notif.text" @close="confirmClose" />
    </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth";
</style>
