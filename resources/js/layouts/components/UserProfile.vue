<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';

const router = useRouter()
const ability = useAbility()

// TODO: Get type from backend
const userData = useCookie('userData')
const bus = useEventBus('erapor');
const profilePhotoPath = ref($profilePhotoPath)
const listener = (event) => {
  profilePhotoPath.value = event
}
bus.on(listener)
const logout = async () => {
  await $api('/auth/logout', {
    method: 'GET',
    async onResponse() {
      userData.value = null
      useCookie("sekolah").value = null;
      useCookie("semester").value = null;
      useCookie("roles").value = null;
      useCookie('accessToken').value = null
      useCookie('languages').value = null
      useCookie('userData').value = null
      useCookie('userAbilityRules').value = null
      useCookie('profilePhotoPath').value = null
      ability.update([])
      window.location.replace('/login')
    },
    onResponseError({ response }) {
      console.log(response._data.errors)
    },
  })
}

const userProfileList = [
  { type: 'divider' },
  {
    type: 'navItem',
    icon: 'tabler-user',
    title: 'Profile',
    to: {
      name: 'profile',
    },
  },
  { type: 'divider' },
]
</script>

<template>
  <VBadge v-if="userData" dot bordered location="bottom right" offset-x="1" offset-y="2" color="success">
    <VAvatar class="cursor-pointer" color="primary" variant="tonal">
      <VImg :src="profilePhotoPath" />

      <!-- SECTION Menu -->
      <VMenu activator="parent" width="240" location="bottom end" offset="12px">
        <VList>
          <VListItem>
            <div class="d-flex gap-2 align-center">
              <VListItemAction>
                <VBadge dot location="bottom right" offset-x="3" offset-y="3" color="success" bordered>
                  <VAvatar color="primary" variant="tonal">
                    <VImg :src="profilePhotoPath" />
                  </VAvatar>
                </VBadge>
              </VListItemAction>

              <div>
                <h6 class="text-h6 font-weight-medium">
                  {{ userData.name }}
                </h6>
                <VListItemSubtitle class="text-disabled">
                  {{ userData.email }}
                </VListItemSubtitle>
              </div>
            </div>
          </VListItem>
          <VListItem>
            <VListItemSubtitle v-for="role in $roles" :key="role">{{ role }}</VListItemSubtitle>
          </VListItem>
          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template v-for="item in userProfileList" :key="item.title">
              <VListItem v-if="item.type === 'navItem'" :to="item.to">
                <template #prepend>
                  <VIcon :icon="item.icon" size="22" />
                </template>

                <VListItemTitle>{{ item.title }}</VListItemTitle>

                <template v-if="item.badgeProps" #append>
                  <VBadge rounded="sm" class="me-3" v-bind="item.badgeProps" />
                </template>
              </VListItem>

              <VDivider v-else class="my-2" />
            </template>

            <div class="px-4 py-2">
              <VBtn block size="small" color="error" append-icon="tabler-logout" @click="logout">
                Logout
              </VBtn>
            </div>
          </PerfectScrollbar>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
</template>
