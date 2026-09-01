<script setup lang="ts">
import { ref } from 'vue'
import { useData } from 'vitepress'

const { lang } = useData()

const command = 'composer require gabrielalmir/maybe'

const labels = lang.value === 'pt-BR'
  ? { copy: 'Copiar', copied: 'Copiado', aria: 'Copiar o comando de instalação' }
  : { copy: 'Copy', copied: 'Copied', aria: 'Copy the install command' }

const copied = ref(false)

// navigator is only touched inside the handler, never during setup, so the
// static build never sees it.
async function copy() {
  try {
    await navigator.clipboard.writeText(command)
  } catch {
    return
  }
  copied.value = true
  setTimeout(() => (copied.value = false), 1800)
}
</script>

<template>
  <!-- A terminal affordance, not a third call to action: it copies rather than
       navigates, so it does not compete with the two buttons above it. -->
  <div class="hero-install">
    <code class="hero-install__cmd"><span class="hero-install__prompt" aria-hidden="true">$</span>{{ command }}</code>
    <button class="hero-install__copy" type="button" :aria-label="labels.aria" @click="copy">
      {{ copied ? labels.copied : labels.copy }}
    </button>
  </div>
</template>

<style scoped>
.hero-install {
  display: inline-flex;
  align-items: center;
  gap: var(--maybe-space-3);
  margin-top: var(--maybe-space-6);
  padding: 6px 6px 6px 14px;
  border: 1px solid var(--vp-c-divider);
  border-radius: var(--maybe-radius-sm);
  background: var(--maybe-surface-1);
  max-width: 100%;
}

.hero-install__cmd {
  overflow-x: auto;
  font-family: var(--maybe-mono);
  font-size: 13px;
  white-space: nowrap;
  color: var(--vp-c-text-1);
}

.hero-install__prompt {
  margin-right: 8px;
  color: var(--vp-c-text-3);
  user-select: none;
}

.hero-install__copy {
  flex-shrink: 0;
  padding: 5px 10px;
  border: 1px solid transparent;
  border-radius: 6px;
  background: var(--maybe-surface-2);
  font-family: var(--maybe-mono);
  font-size: 11.5px;
  font-weight: 600;
  color: var(--vp-c-text-2);
  cursor: pointer;
  transition: color 0.2s ease, border-color 0.2s ease;
}

.hero-install__copy:hover {
  border-color: var(--maybe-brand-3);
  color: var(--maybe-brand-1);
}

.hero-install__copy:active {
  transform: translateY(1px);
}
</style>
