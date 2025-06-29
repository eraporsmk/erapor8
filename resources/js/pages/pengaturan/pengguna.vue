<script setup>
import { themeConfig } from '@themeConfig';
import { useHead } from '@unhead/vue';
import bcrypt from "bcryptjs";
useHead({
  title: `Data Pengguna | ${themeConfig.app.title}`
})
definePage({
  meta: {
    action: 'read',
    subject: 'Administrator',
  },
})

const options = ref({
  page: 1,
  itemsPerPage: 10,
  searchQuery: '',
  selectedRole: null,
  sortby: 'name',
  sortbydesc: 'ASC',
});
// Headers
const headers = [
  {
    title: 'Pengguna',
    key: 'name',
    nowrap: true,
  },
  {
    title: 'Jenis Pengguna',
    key: 'roles',
    sortable: false,
  },
  {
    title: 'Terakhir Login',
    key: 'login_terakhir',
    sortable: false,
  },
  {
    title: 'password',
    key: 'password',
    sortable: false,
  },
  {
    title: 'Aksi',
    key: 'actions',
    align: 'center',
    sortable: false,
    nowrap: true,
  },
]
const users = ref([])
const totalUsers = ref(0)
const roles = ref([])
const loadingTable = ref(false)
onMounted(async () => {
  await fetchData();
});
watch(options, async () => {
  await fetchData();
}, { deep: true });
const updateSortBy = (val) => {
  options.value.sortby = val[0]?.key
  options.value.sortbydesc = val[0]?.order
}
const fetchData = async () => {
  loadingTable.value = true;
  users.value = []
  totalUsers.value = []
  roles.value = []
  try {
    const response = await useApi(createUrl('/setting/users', {
      query: {
        q: options.value.searchQuery,
        page: options.value.page,
        per_page: options.value.itemsPerPage,
        periode_aktif: $user.semester.nama,
        sekolah_id: $user.sekolah_id,
        role_id: options.value.selectedRole,
        sortby: options.value.sortby,
        sortbydesc: options.value.sortbydesc,
      },
    }));
    let getData = response.data
    users.value = getData.value.data.data
    totalUsers.value = getData.value.data.total
    roles.value = getData.value.roles
  } catch (error) {
    console.error(error);
  } finally {
    loadingTable.value = false;
  }
};
const selectedRoles = ref([])
const allRoles = (roles) => {
  var names = roles.map((a) => a.display_name);
  return names.join(", ")
}
const cekPass = (pass, defaultPassword) => {
  if (defaultPassword) {
    return bcrypt.compareSync(defaultPassword, pass)
  }
  return false
}
const loading = ref(false)
const generateUserDialog = ref(false)
const generateUser = async () => {
  generateUserDialog.value = true
}
const confirmGenerate = async (val) => {
  loading.value = true
  await $api('/setting/generate-pengguna', {
    method: 'POST',
    body: {
      aksi: val,
      semester_id: $user.semester.semester_id,
      sekolah_id: $user.sekolah_id,
      periode_aktif: $user.semester.nama,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      loading.value = false
      generateUserDialog.value = false
      notif.value.color = getData.color
      notif.value.title = getData.title
      notif.value.text = getData.text
      isAlertDialogVisible.value = true
    }
  })
}
const loadings = ref([])
const isDialogVisible = ref(false)
const isConfirmDialogVisible = ref(false)
const dialogTitle = ref()
const dialogBody = ref({
  userId: null,
  nama: null,
  email: null,
  password: null,
  defaultPassword: null,
  lastLogin: null,
})
const permissions = ref([])
const availableRoles = ref([])
const detilUser = async (user_id) => {
  loadings.value[user_id] = true
  console.log(user_id);
  await $api('/setting/detil-user', {
    method: 'POST',
    body: {
      user_id: user_id,
      periode_aktif: $user.semester.nama,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      loadings.value[user_id] = false
      dialogTitle.value = `Detil ${getData.user.name}`
      dialogBody.value.userId = getData.user.user_id
      dialogBody.value.nama = getData.user.name
      dialogBody.value.email = getData.user.email
      dialogBody.value.password = getData.user.password
      dialogBody.value.defaultPassword = getData.user.default_password
      dialogBody.value.lastLogin = getData.user.login_terakhir
      availableRoles.value = getData.roles
      isDialogVisible.value = true
      permissions.value = getData.permission
    }
  })
}
const filterRole = (roles, role_id) => {
  let filteredRoles = roles.filter((role) => {
    return role.id === role_id
  });
  if (filteredRoles.length) {
    return filteredRoles[0].display_name
  } else {
    return '-'
  }
}
const getRoleName = (roles, role_id) => {
  let filteredRoles = roles.filter((role) => {
    return role.id === role_id
  });
  if (filteredRoles.length) {
    return filteredRoles[0].name
  } else {
    return '-'
  }
}
const loadHapus = ref([])
const idLoading = ref()
const aksesDelete = ref({
  user_id: null,
  role: null,
  periode_aktif: null,
})
const hapusAkses = (role, periode_aktif) => {
  idLoading.value = `${role}-${periode_aktif}`
  loadHapus.value[idLoading.value] = true
  isConfirmDialogVisible.value = true
  aksesDelete.value.role = role
  aksesDelete.value.periode_aktif = periode_aktif
}
const notif = ref({
  color: null,
  title: null,
  text: null,
})
const role_guru = [7, 8, 9];
const confirmDelete = async (val) => {
  console.log('confirmDelete', val);
  if (val) {
    await $api('/setting/hapus-akses', {
      method: 'POST',
      body: {
        user_id: dialogBody.value.userId,
        role: aksesDelete.value.role,
        periode_aktif: aksesDelete.value.periode_aktif,
      },
      onResponse({ request, response, options }) {
        let getData = response._data
        notif.value = getData
      }
    })
  }
  loadHapus.value[idLoading.value] = false
}
const isAlertDialogVisible = ref(false)
const onsubmit = async () => {
  await $api('/setting/update-akses', {
    method: 'POST',
    body: {
      user_id: dialogBody.value.userId,
      akses: selectedRoles.value,
      periode_aktif: $user.semester.nama,
    },
    onResponse({ request, response, options }) {
      let getData = response._data
      notif.value.color = getData.color
      notif.value.title = getData.title
      notif.value.text = getData.text
      isAlertDialogVisible.value = true
    }
  })
}
const confirmDialog = () => {
  isDialogVisible.value = false
  selectedRoles.value = []
  fetchData()
}
const confirmClose = (val) => {
  detilUser(dialogBody.value.userId)
}
</script>

<template>
  <section>
    <!-- 👉 Widgets -->
    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Akses Pengguna</VCardTitle>
      </VCardItem>
      <VDivider />

      <VCardText class="d-flex flex-wrap gap-4">
        <div class="me-3 d-flex gap-3">
          <AppSelect v-model="options.itemsPerPage" :items="[
            { value: 10, title: '10' },
            { value: 25, title: '25' },
            { value: 50, title: '50' },
            { value: 100, title: '100' },
          ]" style="inline-size: 6.25rem;" />
          <!--
          <AppSelect :model-value="per_page" :items="[
          <VSelect v-model="options.itemsPerPage" :items="[10, 20, 30, 40, 50]" label="Items per page" />
          -->
        </div>
        <VSpacer />

        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <!-- 👉 Search  -->
          <div style="inline-size: 15.625rem;">
            <AppTextField v-model="options.searchQuery" placeholder="Cari Data" />
          </div>

          <!-- 👉 Export button -->
          <AppSelect v-model="options.selectedRole" placeholder="Filter Hak Akses" :items="roles" clearable
            clear-icon="tabler-x" style="inline-size: 15rem;" item-title="display_name" item-value="name" />

          <!-- 👉 Add user button -->
          <VBtn prepend-icon="tabler-bolt" @click="generateUser">
            Atur Ulang
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- SECTION datatable -->
      <VDataTableServer :items="users" :server-items-length="totalUsers" :headers="headers" :options="options"
        :loading="loadingTable" loading-text="Loading..." @update:sortBy="updateSortBy">
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" :variant="!item.profile_photo_path ? 'tonal' : undefined"
              :color="!item.profile_photo_path ? 'success' : undefined">
              <VImg v-if="item.profile_photo_path" :src="item.profile_photo_path" />
              <span v-else>{{ avatarText(item.name) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">{{ item.name }}</h6>
              <div class="text-sm">
                {{ item.email }}
              </div>
            </div>
          </div>
        </template>
        <template #item.roles="{ item }">
          {{ allRoles(item.roles) }}
        </template>
        <template #item.password="{ item }">
          <template v-if="cekPass(item.password, item.default_password)">
            {{ item.default_password }}
          </template>
          <template v-else>
            Custom
          </template>
        </template>
        <!-- Actions -->
        <template #item.actions="{ item }">
          <VBtn :loading="loadings[item.user_id]" :disabled="loadings[item.user_id]" color="warning" icon="tabler-eye"
            @click="detilUser(item.user_id)" />
        </template>

        <!-- pagination -->
        <template #bottom>
          <TablePagination v-model:page="options.page" :items-per-page="options.itemsPerPage"
            :total-items="totalUsers" />
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>
    <VDialog v-model="isDialogVisible" width="800">
      <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />
      <VCard :title="dialogTitle" class="pb-8">
        <VTable class="text-no-wrap">
          <tbody>
            <tr>
              <td>Nama</td>
              <td>{{ dialogBody.nama }}</td>
            </tr>
            <tr>
              <td>Email</td>
              <td>{{ dialogBody.email }}</td>
            </tr>
            <tr>
              <td>Password</td>
              <td>
                <template v-if="cekPass(dialogBody.password, dialogBody.defaultPassword)">
                  {{ dialogBody.defaultPassword }}
                </template>
                <template v-else>
                  Custom | Reset Password
                  <!--b-badge variant="success">Custom</b-badge>
                  <b-button variant="danger" size="sm" @click="resetPassword()">Reset Password</b-button-->
                </template>
              </td>
            </tr>
            <tr>
              <td>Terakhir Login</td>
              <td>{{ dialogBody.lastLogin }}</td>
            </tr>
          </tbody>
        </VTable>
        <VCardText>
          <h4>Hak Akses yang Dimiliki</h4>
        </VCardText>
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th>Tahun Pelajaran</th>
              <th>Hak Akses</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(permission, index) in permissions" :key="index">
              <td>{{ permission.name }}</td>
              <td>{{ filterRole(roles, permission.pivot.role_id) }}</td>
              <td class="text-center">
                <VBtn :loading="loadHapus[`${filterRole(roles, permission.pivot.role_id)}-${permission.name}`]"
                  :disabled="loadHapus[`${filterRole(roles, permission.pivot.role_id)}-${permission.name}`]"
                  color="error" icon="tabler-trash"
                  @click="hapusAkses(getRoleName(roles, permission.pivot.role_id), permission.name)"
                  v-if="role_guru.includes(permission.pivot.role_id)" />
              </td>
            </tr>
          </tbody>
        </VTable>
        <template v-if="availableRoles.length">
          <VCardText>
            <h4>Tambah Hak Akses di Tahun Pelajaran {{ $user.semester.nama }}</h4>
            <VCheckbox v-for="role in availableRoles" :key="role" v-model="selectedRoles" :label="role.text"
              :value="role.value" />
          </VCardText>
        </template>
        <VCardText class="d-flex justify-end">
          <VBtn @click="onsubmit">
            Simpan
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
    <ConfirmDialog v-model:isDialogVisible="isConfirmDialogVisible" confirmation-question="Apakah Anda yakin?"
      confirmation-text="Tindakan ini tidak dapat dikembalikan!" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDelete" @close="confirmClose" />
    <AlertDialog v-model:isDialogVisible="isAlertDialogVisible" :confirm-color="notif.color"
      :confirm-title="notif.title" :confirm-msg="notif.text" @confirm="confirmDialog" />
    <VDialog max-width="500" v-model="generateUserDialog">
      <VCard class="text-center px-10 py-6">
        <VCardText>
          <VBtn icon variant="outlined" color="warning" class="my-4"
            style=" block-size: 88px;inline-size: 88px; pointer-events: none;">
            <span class="text-5xl">!</span>
          </VBtn>

          <h6 class="text-lg font-weight-medium">
            Generate Pengguna
          </h6>
          <p>Silahkan Pilih Jenis Akun</p>
        </VCardText>

        <VCardText class="d-flex align-center justify-center gap-2">
          <VBtn variant="elevated" color="primary" @click="confirmGenerate('ptk')" :loading="loading"
            :disabled="loading">
            Akun PTK
          </VBtn>
          <VBtn variant="elevated" color="error" @click="confirmGenerate('pd')" :loading="loading" :disabled="loading">
            Akun PD
          </VBtn>
          <VBtn color="secondary" variant="tonal" @click="generateUserDialog = false" :loading="loading"
            :disabled="loading">
            Batal
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>
