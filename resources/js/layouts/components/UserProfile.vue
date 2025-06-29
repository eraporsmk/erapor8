<script setup>
import avatar1 from '@images/avatars/avatar-1.png'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
const router = useRouter()
const ability = useAbility()

// TODO: Get type from backend
const userData = useCookie('userData')

const logout = async () => {
  await $api('/auth/logout', {
    method: 'GET',
    async onResponse({ request, response, options }) {
      let getData = response._data
      console.log(getData)
      useCookie('accessToken').value = null
      useCookie('languages').value = null
      useCookie('userData').value = null
      //await router.push('/login')
      useCookie('userAbilityRules').value = null
      ability.update([])
      //await router.push('/login')
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
      name: 'settings-profile',
    },
  },
  { type: 'divider' },
]
</script>

<template>
  <VBadge dot location="bottom right" offset-x="3" offset-y="3" bordered color="success" v-if="userData">
    <VAvatar class="cursor-pointer" color="primary" variant="tonal" >
      <VImg :src="avatar1" />

      <!-- SECTION Menu -->
      <VMenu activator="parent" width="230" location="bottom end" offset="14px">
        <VList>
          <!-- 👉 User Avatar & Name -->
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge dot location="bottom right" offset-x="3" offset-y="3" color="success">
                  <VAvatar color="primary" variant="tonal">
                    <VImg :src="avatar1" />
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-semibold">
              {{ userData.name }}
              
            </VListItemTitle>
            <VListItemSubtitle>Admin</VListItemSubtitle>
          </VListItem>
          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template
              v-for="item in userProfileList"
              :key="item.title"
            >
              <VListItem
                v-if="item.type === 'navItem'"
                :to="item.to"
              >
                <template #prepend>
                  <VIcon
                    :icon="item.icon"
                    size="22"
                  />
                </template>

                <VListItemTitle>{{ item.title }}</VListItemTitle>

                <template
                  v-if="item.badgeProps"
                  #append
                >
                  <VBadge
                    rounded="sm"
                    class="me-3"
                    v-bind="item.badgeProps"
                  />
                </template>
              </VListItem>

              <VDivider
                v-else
                class="my-2"
              />
            </template>

            <div class="px-4 py-2">
              <VBtn
                block
                size="small"
                color="error"
                append-icon="tabler-logout"
                @click="logout"
              >
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
