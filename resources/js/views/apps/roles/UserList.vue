<script setup>
import AddNewUserDrawer from '@/views/apps/user/list/AddNewUserDrawer.vue'

// 👉 Store
const searchQuery = ref('')
const selectedRole = ref()
const emit = defineEmits(['roles'])
// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref('id')
const orderBy = ref('ASC')

const updateOptions = options => {
  if(options.sortBy.length){
    sortBy.value = options.sortBy[0]?.key
    orderBy.value = options.sortBy[0]?.order
  }
}

// Headers
const headers = [
  {
    title: 'User',
    key: 'user',
    sortable: false,
  },
  {
    title: 'Role',
    key: 'roles',
    sortable: false,
  },
  {
    title: 'Created By',
    key: 'created_by',
    sortable: false,
  },
  {
    title: 'Updated By',
    key: 'updated_by',
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
  data: usersData,
  execute: fetchUsers,
} = await useApi(createUrl('/settings/users-roles', {
  query: {
    q: searchQuery,
    role_id: selectedRole,
    itemsPerPage,
    page,
    sortBy,
    orderBy,
  },
}))
const users = computed(() => usersData.value.users.data)
const totalUsers = computed(() => usersData.value.users.total)
const roles = computed(() => usersData.value.roles)
emit('roles', roles.value)
// 👉 search filters

const resolveUserRoleVariant = role => {
  if(!role)
  return {
    color: 'primary',
    icon: 'tabler-user',
  }
  const roleLowerCase = role.toLowerCase()
  if (roleLowerCase === 'administrator')
    return {
      color: 'primary',
      icon: 'tabler-user',
    }
  if (roleLowerCase === 'operator')
    return {
      color: 'warning',
      icon: 'tabler-settings',
    }
  if (roleLowerCase === 'koperasi')
    return {
      color: 'success',
      icon: 'tabler-chart-donut',
    }
  if (roleLowerCase === 'editor')
    return {
      color: 'info',
      icon: 'tabler-pencil',
    }
  if (roleLowerCase === 'admin')
    return {
      color: 'error',
      icon: 'tabler-device-laptop',
    }
  
  return {
    color: 'primary',
    icon: 'tabler-user',
  }
}

const isAddNewUserDrawerVisible = ref(false)

const addNewUser = async userData => {
  await $api('/apps/users', {
    method: 'POST',
    body: userData,
  })

  // refetch User
  fetchUsers()
}

const deleteUser = async id => {
  await $api(`/apps/users/${ id }`, { method: 'DELETE' })

  // refetch User
  fetchUsers()
}
</script>

<template>
  <section>
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <div class="d-flex gap-2 align-center">
          <p class="text-body-1 mb-0">
            Show
          </p>
          <AppSelect
            :model-value="itemsPerPage"
            :items="[
              { value: 10, title: '10' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
            ]"
            style="inline-size: 5.5rem;"
            @update:model-value="itemsPerPage = parseInt($event, 10)"
          />
        </div>

        <VSpacer />

        <div class="d-flex align-center flex-wrap gap-4">
          <!-- 👉 Search  -->
          <AppTextField
            v-model="searchQuery"
            placeholder="Search User"
            style="inline-size: 15.625rem;"
          />

          <!-- 👉 Add user button -->
          <AppSelect
            v-model="selectedRole"
            placeholder="Select Role"
            :items="roles"
            item-title="display_name"
            item-value="id"
            clearable
            clear-icon="tabler-x"
            style="inline-size: 10rem;"
          />
        </div>
      </VCardText>

      <VDivider />
      <!-- SECTION datatable -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items-per-page-options="[
          { value: 10, title: '10' },
          { value: 20, title: '20' },
          { value: 50, title: '50' },
          { value: -1, title: '$vuetify.dataFooter.itemsPerPageAll' },
        ]"
        :items="users"
        :items-length="totalUsers"
        :headers="headers"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <!-- User -->
        <template #item.user="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" :variant="!item.avatar ? 'tonal' : undefined" :color="!item.avatar ? 'success' : undefined">
              <VImg v-if="item.avatar" :src="item.avatar" />
              <span v-else>{{ avatarText(item.name) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{item.name}}
              </h6>
              <div class="text-sm">
                {{ item.email }}
              </div>
            </div>
          </div>
        </template>

        <!-- 👉 Role -->
        <template #item.roles="{ item }">
          <div class="d-flex align-center gap-x-2">
            <template v-for="role in item.roles">
              <VIcon :size="22" :icon="resolveUserRoleVariant(role.name).icon" :color="resolveUserRoleVariant(role.name).color" />
              <div class="text-capitalize text-high-emphasis text-body-1">
                {{ role.display_name }}
              </div>
            </template>
          </div>
        </template>
        
        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn @click="deleteUser(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>

          <IconBtn>
            <VIcon icon="tabler-eye" />
          </IconBtn>

          <VBtn
            icon
            variant="text"
            color="medium-emphasis"
          >
            <VIcon icon="tabler-dots-vertical" />
            <VMenu activator="parent">
              <VList>
                <VListItem :to="{ name: 'apps-user-view-id', params: { id: item.id } }">
                  <template #prepend>
                    <VIcon icon="tabler-eye" />
                  </template>

                  <VListItemTitle>View</VListItemTitle>
                </VListItem>

                <VListItem link>
                  <template #prepend>
                    <VIcon icon="tabler-pencil" />
                  </template>
                  <VListItemTitle>Edit</VListItemTitle>
                </VListItem>

                <VListItem @click="deleteUser(item.id)">
                  <template #prepend>
                    <VIcon icon="tabler-trash" />
                  </template>
                  <VListItemTitle>Delete</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>
        </template>

        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalUsers"
          />
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>

    <!-- 👉 Add New User -->
    <AddNewUserDrawer
      v-model:isDrawerOpen="isAddNewUserDrawerVisible"
      @user-data="addNewUser"
    />
  </section>
</template>

<style lang="scss">
.text-capitalize {
  text-transform: capitalize;
}

.user-list-name:not(:hover) {
  color: rgba(var(--v-theme-on-background), var(--v-medium-emphasis-opacity));
}
</style>
