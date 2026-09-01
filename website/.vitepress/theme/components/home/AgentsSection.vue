<script setup lang="ts">
import { useData } from 'vitepress'

const { frontmatter } = useData()
</script>

<template>
  <section
    v-if="frontmatter.agents"
    class="maybe-band maybe-band--sunken"
    aria-labelledby="maybe-agents-title"
  >
    <div class="maybe-band__inner agents-inner">
      <div class="agents-copy">
        <p v-if="frontmatter.agents.eyebrow" class="maybe-eyebrow">{{ frontmatter.agents.eyebrow }}</p>
        <h2 id="maybe-agents-title" class="maybe-band__title">{{ frontmatter.agents.title }}</h2>
        <p class="maybe-band__lead">{{ frontmatter.agents.lead }}</p>
        <p class="agents-note">{{ frontmatter.agents.note }}</p>
      </div>
      <!-- A plain link, not a clipboard button: a copy button would need
           navigator.clipboard, and anything touching window in setup() breaks
           the static build, not just dev. -->
      <a class="agents-url" :href="frontmatter.agents.url">{{ frontmatter.agents.url }}</a>
    </div>
  </section>
</template>

<style scoped>
.agents-inner {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 0.9fr);
  align-items: center;
  gap: var(--maybe-space-9);
}

.agents-note {
  margin: var(--maybe-space-4) 0 0;
  font-size: 13.5px;
  line-height: 1.55;
  color: var(--vp-c-text-3);
}

.agents-url {
  display: block;
  padding: var(--maybe-space-5);
  border: 1px solid var(--vp-c-divider);
  border-radius: var(--maybe-radius-md);
  background: var(--maybe-surface-1);
  font-family: var(--maybe-mono);
  font-size: 13px;
  line-height: 1.5;
  word-break: break-all;
  color: var(--maybe-brand-1);
  text-decoration: none;
  transition: border-color 0.2s ease;
}

.agents-url:hover {
  border-color: var(--maybe-brand-3);
}

@media (max-width: 959px) {
  .agents-inner {
    grid-template-columns: 1fr;
    gap: var(--maybe-space-6);
  }
}
</style>
