<script setup>
definePage({
  meta: {
    action: 'read',
    subject: 'Web',
    title: 'Daftar Perubahan Aplikasi',
  },
})
onMounted(async () => {
  await fetchData();
});
const currentTab = ref('changelog')
const loadingBody = ref(false)
const data = ref()
const githubData = ref([])
const options = ref({
  page: 1,
  perPage: 10,
})
const fetchData = async () => {
  loadingBody.value = true;
  try {
    const response = await useApi(createUrl(`/setting/${currentTab.value}`, {
      query: {
        page: options.value.page,
        per_page: options.value.perPage,
      }
    }));
    let getData = response.data.value
    if (currentTab.value == 'changelog') {
      data.value = getData.data
    } else {
      githubData.value = getData.data
      isPrev.value = !getData.headers.Link[0]?.includes('first')
      isNext.value = !getData.headers.Link[0]?.includes('last')
    }
  } catch (error) {
    console.error(error);
  } finally {
    loadingBody.value = false;
  }
}
const changeTab = async () => {
  await fetchData();
}
const tabs = [
  {
    name: 'changelog',
    icon: 'tabler-brand-google-play',
    title: 'Aplikasi',
  },
  {
    name: 'github',
    icon: 'tabler-brand-github',
    title: 'Github',
  }
]
const formattedText = (rawText) => {
  return rawText.replace(/\n/g, "<br />");
}
const navigasi = ref('next')
const isPrev = ref(true)
const isNext = ref(false)
const aksi = async (aksi) => {
  if (aksi == 'next') {
    options.value.page = options.value.page + 1
  } else {
    options.value.page = options.value.page - 1
  }
  await fetchData();
}
</script>
<template>
  <div>
    <VCard>
      <VCardItem class="pb-4">
        <VCardTitle>Daftar Perubahan Aplikasi</VCardTitle>
      </VCardItem>
      <VDivider />
      <VTabs v-model="currentTab" grow stacked @update:model-value="changeTab">
        <VTab v-for="item in tabs" :key="item.name" :value="item.name">
          <VIcon :icon="item.icon" class="mb-2" />
          <span>{{ item.title }}</span>
        </VTab>
      </VTabs>

      <VWindow v-model="currentTab">
        <VWindowItem value="changelog">
          <VCardText class="text-center" v-if="loadingBody">
            <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
          </VCardText>
          <VCardText v-else>
            <span v-html="data"></span>
          </VCardText>
        </VWindowItem>
        <VWindowItem value="github">
          <div class="text-center" v-if="loadingBody">
            <VProgressCircular :size="60" indeterminate color="error" class="my-10" />
          </div>
          <VTable v-else>
            <thead>
              <tr>
                <th class="text-center">No</th>
                <th>Date</th>
                <th>Commit</th>
                <th class="text-center">Author</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in githubData">
                <td class="text-center">{{ index + 1 + (10 * options.page) - 10 }}</td>
                <td>{{ new Date(item.commit.author.date).toLocaleString('id-ID', {
                  hour12: false,
                  day: '2-digit',
                  month: '2-digit',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                  second: '2-digit',
                }) }}</td>
                <td class="py-2">
                  <span v-html="formattedText(item.commit.message)"></span>
                </td>
                <td class="text-center">
                  {{ item.commit.author.name }}
                </td>
              </tr>
            </tbody>
          </VTable>
          <VDivider />
          <VCardText class="text-center">
            <v-btn-toggle v-model="navigasi" divided class="navigasi">
              <v-btn value="prev" :disabled="isPrev" @click="aksi('prev')">
                &laquo; prev
              </v-btn>
              <v-btn value="next" :disabled="isNext" @click="aksi('next')">
                next &raquo;
              </v-btn>
            </v-btn-toggle>
          </VCardText>
        </VWindowItem>
      </VWindow>
    </VCard>
  </div>
</template>
<style lang="scss">
.navigasi {
  padding: 0 !important;
  block-size: 40px !important;
}

h3 {
  margin-bottom: 10px !important;
}

ol {
  margin: 0 20px 20px;
}
</style>
