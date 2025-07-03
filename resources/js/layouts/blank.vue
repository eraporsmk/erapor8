<script setup>
const { injectSkinClasses } = useSkins()

// ℹ️ This will inject classes in body tag for accurate styling
injectSkinClasses()

// SECTION: Loading Indicator
const isFallbackStateActive = ref(false)
const refLoadingIndicator = ref(null)

watch([
  isFallbackStateActive,
  refLoadingIndicator,
], () => {
  if (isFallbackStateActive.value && refLoadingIndicator.value)
    refLoadingIndicator.value.fallbackHandle()
  if (!isFallbackStateActive.value && refLoadingIndicator.value)
    refLoadingIndicator.value.resolveHandle()
}, { immediate: true })
// !SECTION
</script>

<template>
  <AppLoadingIndicator ref="refLoadingIndicator" />

  <div class="layout-wrapper layout-blank" data-allow-mismatch>
    <RouterView #="{ Component }">
      <transition name="zoom-fade">
        <Suspense :timeout="0" @fallback="isFallbackStateActive = true" @resolve="isFallbackStateActive = false">
          <Component :is="Component" />
        </Suspense>
      </transition>
    </RouterView>
  </div>
</template>

<style>
.layout-wrapper.layout-blank {
  flex-direction: column;
}

.zoom-fade-enter-active,
.zoom-fade-leave-active {
  transition: transform 0.35s, opacity 0.28s ease-in-out;
}

.zoom-fade-enter {
  transform: scale(0.97);
  opacity: 0;
}

.zoom-fade-leave-to {
  transform: scale(1.03);
  opacity: 0;
}
</style>
