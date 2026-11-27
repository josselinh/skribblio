<script setup lang="ts">
import { RouterView } from 'vue-router';
import NavigationProgress from '@/components/NavigationProgress.vue';
import ToastStack from '@/components/ToastStack.vue';
</script>

<template>
    <NavigationProgress />
    <RouterView />
    <ToastStack />
</template>
