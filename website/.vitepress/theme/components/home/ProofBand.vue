<script setup lang="ts">
import { useData } from 'vitepress'

const { frontmatter } = useData()
</script>

<template>
  <section v-if="frontmatter.proof" class="maybe-band maybe-band--tight maybe-proof">
    <div class="maybe-band__inner">
      <dl class="proof-list">
        <div v-for="item in frontmatter.proof.items" :key="item.label" class="proof-item">
          <dt class="proof-value">{{ item.value }}</dt>
          <dd class="proof-label">{{ item.label }}</dd>
        </div>
      </dl>
    </div>
  </section>
</template>

<style scoped>
.maybe-proof {
  border-block: 1px solid var(--vp-c-divider);
  background: var(--maybe-surface-2);
}

.proof-list {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--maybe-space-6);
  margin: 0;
}

.proof-item {
  display: flex;
  flex-direction: column;
  gap: var(--maybe-space-1);
}

.proof-value {
  font-family: var(--maybe-display);
  font-size: var(--maybe-fs-h3);
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--maybe-brand-1);
}

.proof-label {
  margin: 0;
  font-size: 13px;
  line-height: 1.45;
  color: var(--vp-c-text-2);
}

@media (max-width: 959px) {
  .proof-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
