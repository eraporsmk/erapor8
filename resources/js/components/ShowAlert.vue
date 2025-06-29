<script setup>
const props = defineProps({
  icon: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  text: {
    type: String,
    required: true,
  },
  color: {
    type: String,
    required: true,
  },
  isSnackbarVisible: {
    type: Boolean,
    required: true,
  },
  isSnackbarClicked: {
    type: Boolean,
    required: true,
  },
  disableTimeOut: {
    type: Boolean,
    default: true,
  },
})
const emit = defineEmits([
  'update:isSnackbarVisible',
  'update:isSnackbarClicked',
])
const closeNotif = () => {
  emit('update:isSnackbarVisible', false)
  emit('update:isSnackbarClicked', true)
}
watch(props, () => {
  if(props.isSnackbarVisible){
    if(props.disableTimeOut){
      setTimeout(function(){ 
        emit('update:isSnackbarVisible', false)
      }, 4000);
    }
  }
  
})
</script>

<template>
  <VSnackbar location="center" :color="props.color" multi-line v-model="props.isSnackbarVisible">
    <div class="text-center">
      <VIcon size="50" :icon="props.icon" />
    </div>
    <h1 class="text-center text-white my-3">{{ props.title }}</h1>
    <p class="text-center" v-html="props.text"></p>
    <div class="text-center">
      <VBtn :color="props.color" @click="closeNotif" autofocus>OK</VBtn>
    </div>
  </VSnackbar>
</template>
