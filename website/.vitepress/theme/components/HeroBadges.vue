<script setup lang="ts">
import { useData } from 'vitepress'

const { lang } = useData()

const copy = {
  'en-US': { license: 'MIT license', php: 'PHP ≥ 7.4', tests: 'CI passing', packagist: 'Packagist', input: 'input', result: 'Result', ok: 'Ok', err: 'Err', install: 'Install' },
  'pt-BR': { license: 'Licença MIT', php: 'PHP ≥ 7.4', tests: 'CI passando', packagist: 'Packagist', input: 'entrada', result: 'Result', ok: 'Ok', err: 'Err', install: 'Instalar' }
} as const

const labels = copy[lang.value as keyof typeof copy] ?? copy['en-US']
const base = lang.value === 'pt-BR' ? '/maybe/pt' : '/maybe'
</script>

<template>
  <div class="badges">
    <a class="badge" href="https://github.com/gabrielalmir/maybe/blob/main/LICENSE">{{ labels.license }}</a>
    <span class="badge">{{ labels.php }}</span>
    <a class="badge" href="https://github.com/gabrielalmir/maybe/actions">{{ labels.tests }}</a>
    <a class="badge" href="https://packagist.org/packages/gabrielalmir/maybe">{{ labels.packagist }}</a>
  </div>
  <div class="maybe-outcome-rail" role="img" :aria-label="`${labels.input} → ${labels.result} → ${labels.ok} / ${labels.err}`">
    <span class="rail-label">{{ labels.input }}</span>
    <span class="rail-line" aria-hidden="true" />
    <span class="rail-label">{{ labels.result }}</span>
    <span class="rail-line" aria-hidden="true" />
    <span class="rail-state">{{ labels.ok }}</span>
    <span class="rail-state err">{{ labels.err }}</span>
  </div>
  <div class="maybe-hero-install">
    <code>composer require gabrielalmir/maybe</code>
    <a :href="`${base}/guide/getting-started.html`">{{ labels.install }} →</a>
  </div>
</template>

<style scoped>
.badges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 20px;
}

.rail-label {
  color: var(--vp-c-text-2);
}

.maybe-hero-install {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px 16px;
  margin-top: 20px;
  color: var(--vp-c-text-2);
}

.maybe-hero-install code {
  padding: 7px 9px;
  border: 1px solid var(--vp-c-divider);
  border-radius: 6px;
  background: var(--vp-c-bg-alt);
  color: var(--vp-c-text-1);
  font-size: 12px;
}

.maybe-hero-install a {
  color: var(--maybe-brand-1);
  font-family: var(--maybe-mono);
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
}

.maybe-hero-install a:hover {
  color: var(--maybe-brand-2);
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
