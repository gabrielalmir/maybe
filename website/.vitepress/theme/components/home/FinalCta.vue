<script setup lang="ts">
import { useData, withBase } from 'vitepress'

const { frontmatter } = useData()
</script>

<template>
  <section v-if="frontmatter.cta" class="maybe-band maybe-cta" aria-labelledby="maybe-cta-title">
    <div class="maybe-band__inner cta-inner">
      <h2 id="maybe-cta-title" class="cta-title">{{ frontmatter.cta.title }}</h2>
      <p class="cta-lead">{{ frontmatter.cta.lead }}</p>
      <code class="cta-install">{{ frontmatter.cta.install }}</code>
      <p class="cta-actions">
        <a class="cta-button cta-button--brand" :href="withBase(frontmatter.cta.primary.link)">
          {{ frontmatter.cta.primary.text }}
        </a>
        <a class="cta-button" :href="withBase(frontmatter.cta.secondary.link)">
          {{ frontmatter.cta.secondary.text }}
        </a>
      </p>
      <p class="cta-meta">{{ frontmatter.cta.meta }}</p>
    </div>
  </section>
</template>

<style scoped>
.maybe-cta {
  border-top: 1px solid var(--vp-c-divider);
  background: var(--maybe-surface-2);
}

.cta-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.cta-title {
  margin: 0;
  max-width: 20ch;
  font-family: var(--maybe-display);
  font-size: var(--maybe-fs-h2);
  font-weight: 700;
  letter-spacing: var(--maybe-track-h2);
  line-height: var(--maybe-lead-h2);
  text-wrap: balance;
  color: var(--vp-c-text-1);
}

.cta-lead {
  margin: var(--maybe-space-4) 0 0;
  max-width: 56ch;
  font-size: var(--maybe-fs-lead);
  line-height: var(--maybe-lead-lead);
  color: var(--vp-c-text-2);
}

.cta-install {
  margin-top: var(--maybe-space-7);
  padding: var(--maybe-space-3) var(--maybe-space-5);
  border: 1px solid var(--vp-c-divider);
  border-radius: var(--maybe-radius-sm);
  background: var(--maybe-surface-1);
  font-family: var(--maybe-mono);
  font-size: 13.5px;
  color: var(--vp-c-text-1);
}

.cta-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: var(--maybe-space-3);
  margin: var(--maybe-space-6) 0 0;
}

.cta-button {
  display: inline-flex;
  align-items: center;
  padding: 0 var(--maybe-space-6);
  height: 44px;
  border: 1px solid var(--vp-c-divider);
  border-radius: 22px;
  background: var(--maybe-surface-1);
  font-size: 14px;
  font-weight: 600;
  color: var(--vp-c-text-1);
  text-decoration: none;
  transition: border-color 0.2s ease, background 0.2s ease;
}

.cta-button:hover {
  border-color: var(--maybe-brand-3);
}

.cta-button--brand {
  border-color: transparent;
  background: var(--vp-button-brand-bg);
  color: #fff;
}

.cta-button--brand:hover {
  background: var(--vp-button-brand-hover-bg);
  border-color: transparent;
}

.cta-meta {
  margin: var(--maybe-space-6) 0 0;
  font-family: var(--maybe-mono);
  font-size: 12px;
  color: var(--vp-c-text-3);
}
</style>
