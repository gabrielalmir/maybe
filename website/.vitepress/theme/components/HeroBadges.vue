<script setup lang="ts">
import { useData } from 'vitepress'

const { lang } = useData()

const copy = {
  'en-US': { license: 'MIT license', php: 'PHP ≥ 7.4', tests: 'CI passing', packagist: 'Packagist' },
  'pt-BR': { license: 'Licença MIT', php: 'PHP ≥ 7.4', tests: 'CI passando', packagist: 'Packagist' }
} as const

const labels = copy[lang.value as keyof typeof copy] ?? copy['en-US']
</script>

<template>
  <div class="badges">
    <a class="badge" href="https://github.com/gabrielalmir/maybe/blob/main/LICENSE">{{ labels.license }}</a>
    <span class="badge">{{ labels.php }}</span>
    <a class="badge" href="https://github.com/gabrielalmir/maybe/actions">{{ labels.tests }}</a>
    <a class="badge" href="https://packagist.org/packages/gabrielalmir/maybe">{{ labels.packagist }}</a>
  </div>
</template>

<style scoped>
.badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 20px;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid var(--vp-c-divider);
  background: var(--vp-c-bg-soft);
  font-family: var(--maybe-mono);
  font-size: 12px;
  color: var(--vp-c-text-2);
  text-decoration: none;
  transition: border-color 0.2s ease, color 0.2s ease;
}

a.badge:hover {
  border-color: var(--maybe-brand-2);
  color: var(--maybe-brand-2);
}
</style>
