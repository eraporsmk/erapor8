<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Web',
  },
})
import DataTables from '@/components/DataTables.vue';
import ShowAlert from '@/components/ShowAlert.vue';
const { t } = useI18n() 
const searchQuery = ref('')
const per_page = ref(10)
const page = ref(1)
const sortBy = ref('lang_id')
const orderBy = ref('ASC')

const updateOptions = options => {
  if(options.sortBy.length){
    sortBy.value = options.sortBy[0]?.key
    orderBy.value = options.sortBy[0]?.order
  }
}
const headers = [
  {
    title: 'ID',
    key: 'lang_id',
    //sortable: false,
  },
  {
    title: 'Portuguese',
    key: 'pt',
    //sortable: false,
  },
  {
    title: 'English',
    key: 'en',
    sortable: false,
  },
  {
    title: 'Tetun',
    key: 'te',
    sortable: false,
  },
  {
    title: 'Indonesia',
    key: 'id',
    sortable: false,
  },
  {
    title: 'Actions',
    key: 'actions',
    align: 'center',
    sortable: false,
  },
]
const {
  data: getData,
  execute: fetchData,
} = await useApi(createUrl('/settings/languages', {
  query: {
    q: searchQuery,
    page,
    per_page,
    sortBy,
    orderBy,
  },
}))
const items  = computed(() => getData.value.data.data)
const totalItems = computed(() => getData.value.data.total)
const isDialogVisible = ref(false)
const isSnackbarVisible = ref(false)
const isSnackbarClicked = ref(false)
const form = ref({
  lang_id: null,
  pt: null,
  en: null,
  te: null,
  id: null,
})
const notif = ref({
  icon: undefined,
  title: undefined,
  text: undefined,
  color: undefined,
})
const error = ref({
  pt: undefined,
  en: undefined,
  te: undefined,
  id: undefined,
})
const resetModal =  () => {
  form.value = {
    lang_id: null,
    pt: null,
    en: null,
    te: null,
    id: null,
  }
  notif.value = {
    icon: null,
    title: null,
    text: null,
    color: null,
  }
}
const saveData  = async () => {
  await $api('/settings/languages', {
    method: 'POST',
    body: form.value,
    onResponse({ request, response, options }) {
      let getData = response._data
      console.log(response);
      
      if(getData.errors){
        error.value = getData.errors
      } else {
        notif.value = getData.data
        isDialogVisible.value = false
        isSnackbarVisible.value = true
      }
    },
  })
}
const isConfirmDialogVisible = ref(false)
const deletedId = ref()
const confirmDelete = async (val) => {
  if(val){
    await $api(`/settings/destroy/languages/${ deletedId.value }`, { 
    method: 'DELETE',
    onResponse({ request, response, options }) {
      let getData = response._data
      notif.value = getData
      fetchData()
    }
  })
  }
}
const deleteItem = async id => {
  deletedId.value = id
  isConfirmDialogVisible.value = true
}
const handleAksi = async(val) => {
  console.log(val);
  if(val.aksi == 'edit'){
    form.value = {
      lang_id: val.item.lang_id,
      pt: val.item.pt,
      en: val.item.en,
      te: val.item.te,
      id: val.item.id,
    }
    isDialogVisible.value = true
  }
  if(val.aksi == 'delete'){
    deleteItem(val.item.lang_id)
  }
}
const handleBtn = async(val) => {
  console.log(val);
  isDialogVisible.value = true
}
watch([
  isDialogVisible,
  isSnackbarVisible,
  isSnackbarClicked,
  form.value,
],  () => {
  if(!isSnackbarVisible.value && isDialogVisible.value && !form.value.lang_id){
    resetModal()
  }
  if(isSnackbarClicked.value){
    fetchData()
  }
})
</script>
<template>
  <VCard :title="t('Languages')" class="mb-6">
      <DataTables 
        :per_page="per_page" 
        :headers="headers" 
        :items="items" 
        :totalItems="totalItems" 
        :page="page" 
        :btnAdd="{
          text: 'Add New',
          icon: 'tabler-plus',
          color: 'primary',
          action: 'add-lang',
        }"
        @aksi="handleAksi" 
        @btnClick="handleBtn"
        v-model:per_page="per_page" 
        v-model:page="page" 
        v-model:searchQuery="searchQuery"
        @option="updateOptions"
        >
      </DataTables>
      <VDialog v-model="isDialogVisible" max-width="800">
        <VCard :title="(form.lang_id) ? t(`Edit Language`) : t('Add New Language')">
          <VCardText>
            <VRow>
              <VCol cols="12">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">Portuguese</VCol>
                  <VCol cols="12" md="9">
                    <AppTextField v-model="form.pt" :error-messages="error.pt" placeholder="Portuguese (default language)" />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">English</VCol>
                  <VCol cols="12" md="9">
                    <AppTextField v-model="form.en" :error-messages="error.en" placeholder="English" />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">Tetun</VCol>
                  <VCol cols="12" md="9">
                    <AppTextField v-model="form.te" :error-messages="error.te" placeholder="Tetun" />
                  </VCol>
                </VRow>
              </VCol>
              <VCol cols="12">
                <VRow no-gutters>
                  <VCol cols="12" md="3" class="d-flex align-items-center">Indonesia</VCol>
                  <VCol cols="12" md="9">
                    <AppTextField v-model="form.id" :error-messages="error.id" placeholder="Indonesia" />
                  </VCol>
                </VRow>
              </VCol>
            </VRow>
          </VCardText>
          <VCardText class="d-flex justify-end flex-wrap gap-3">
            <VBtn variant="tonal" color="secondary" @click="isDialogVisible = false">Batal</VBtn>
            <VBtn @click="saveData()">Simpan</VBtn>
          </VCardText>
        </VCard>
      </VDialog>
      <ShowAlert :color="notif.color" :icon="notif.icon" :title="notif.title" :text="notif.text" :disable-time-out="false" v-model:isSnackbarVisible="isSnackbarVisible" v-model:isSnackbarClicked="isSnackbarClicked"></ShowAlert>
      <ConfirmDialog
        v-model:isDialogVisible="isConfirmDialogVisible"
        :confirmation-question="t(`Are you sure?`)"
        :cancel-msg="t('Cancel Delete')"
        :cancel-title="t('Cancel')"
        :confirm-msg="notif.text ? t(notif.text) : ''"
        :confirm-title="notif.title ? t(notif.title) : ''"
        :confirm-icon="notif.icon ?? ''"
        :confirm-color="notif.color ?? ''"
        @confirm="confirmDelete"
      />
      <!--
      :confirm-msg="t(notif.text)"
      :confirm-title="t(notif.title)"
      :confirm-icon="notif.icon"
      :confirm-color="notif.color"
      -->
    </VCard>
</template>
