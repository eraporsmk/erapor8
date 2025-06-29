<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  togglerIcon: {
    type: String,
    required: false,
    default: 'tabler-layout-grid-add',
  },
  shortcuts: {
    type: Array,
    required: true,
  },
})

const router = useRouter()
</script>

<template>
  <IconBtn>
    <VIcon :icon="props.togglerIcon" />

    <VMenu
      activator="parent"
      offset="12px"
      location="bottom end"
    >
      <VCard
        :width="$vuetify.display.smAndDown ? 330 : 380"
        max-height="560"
        class="d-flex flex-column"
      >
        <VCardItem class="py-3">
          <h6 class="text-base font-weight-medium">
            Shortcuts
          </h6>

          <template #append>
            <IconBtn
              size="small"
              color="high-emphasis"
            >
              <VIcon
                size="20"
                icon="tabler-plus"
              />
            </IconBtn>
          </template>
        </VCardItem>

        <VDivider />

        <PerfectScrollbar :options="{ wheelPropagation: false }">
          <VRow class="ma-0 mt-n1">
            <VCol
              v-for="(shortcut, index) in props.shortcuts"
              :key="shortcut.title"
              cols="6"
              class="text-center border-t cursor-pointer pa-6 shortcut-icon"
              :class="(index + 1) % 2 ? 'border-e' : ''"
              @click="router.push(shortcut.to)"
            >
              <VAvatar
                variant="tonal"
                size="50"
              >
                <VIcon
                  size="26"
                  color="high-emphasis"
                  :icon="shortcut.icon"
                />
              </VAvatar>

              <h6 class="text-base font-weight-medium mt-3 mb-0">
                {{ shortcut.title }}
              </h6>
              <p class="text-sm mb-0">
                {{ shortcut.subtitle }}
              </p>
            </VCol>
          </VRow>
        </PerfectScrollbar>
      </VCard>
    </VMenu>
  </IconBtn>
</template>

<style lang="scss">
.shortcut-icon:hover {
  background-color: rgba(var(--v-theme-on-surface), var(--v-hover-opacity));
}
</style>
