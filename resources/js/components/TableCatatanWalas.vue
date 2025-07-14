<script setup>
const props = defineProps({
  arrayData: {
    type: Object,
    required: true,
  },
  form: {
    type: Object,
    required: true,
  },
  siswa: {
    type: Object,
    required: true,
  },
})
</script>
<template>
  <VTable>
    <thead>
      <tr>
        <th class="text-center" width="30%">Dimensi Sikap</th>
        <th class="text-center" width="70%">Catatan Sikap</th>
      </tr>
    </thead>
    <tbody>
      <template v-for="(budaya, key) in props.arrayData.budaya">
        <tr>
          <td style="border-bottom: none !important;">{{ budaya.aspek }}</td>
          <td class="py-2" style="border-bottom: none !important;">
            <VTextarea label="Ketik catatan disini..."
              v-model="props.form.uraian_sikap[props.siswa.anggota_rombel.anggota_rombel_id + '#' + budaya.budaya_kerja_id]">
            </VTextarea>
          </td>
        </tr>
      </template>
    </tbody>
  </VTable>
</template>
