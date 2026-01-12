<script setup>
const props = defineProps({
    isDialogVisible: {
        type: Boolean,
        required: true,
    },
})
</script>

<template>
    <VDialog v-model="props.isDialogVisible" width="300">
        <VCard color="primary" width="300">
            <VCardText class="pt-3">
                Mohon menunggu...
                <VProgressLinear indeterminate bg-color="rgba(var(--v-theme-surface), 0.1)" :height="8"
                    class="mb-0 mt-4" />
            </VCardText>
        </VCard>
    </VDialog>
</template>
