<script setup lang="ts">
import { useData } from 'vitepress'

const { frontmatter } = useData()
</script>

<template>
  <section
    v-if="frontmatter.composition"
    class="maybe-band"
    aria-labelledby="maybe-composition-title"
  >
    <div class="maybe-band__inner">
      <p v-if="frontmatter.composition.eyebrow" class="maybe-eyebrow">{{ frontmatter.composition.eyebrow }}</p>
      <h2 id="maybe-composition-title" class="maybe-band__title">
        {{ frontmatter.composition.title }}
      </h2>
      <p class="maybe-band__lead">{{ frontmatter.composition.lead }}</p>

      <ul class="comp-list">
        <li v-for="edge in frontmatter.composition.edges" :key="edge.from" class="comp-edge">
          <!-- The arrow is decorative; the accessible name spells the relation
               out, the same way the hero code panel labels its outcome strip. -->
          <p class="comp-rel" :aria-label="`${edge.from} returns ${edge.to}`">
            <span class="comp-node">{{ edge.from }}</span>
            <span class="comp-arrow" aria-hidden="true">→</span>
            <span class="comp-node comp-node--to">{{ edge.to }}</span>
          </p>
          <p class="comp-detail">{{ edge.detail }}</p>
        </li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
.comp-list {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--maybe-space-6);
  margin: var(--maybe-space-8) 0 0;
  padding: 0;
  list-style: none;
}

.comp-edge {
  padding: var(--maybe-space-5);
  border: 1px solid var(--vp-c-divider);
  border-radius: var(--maybe-radius-md);
  background: var(--maybe-surface-1);
}

.comp-rel {
  display: flex;
  align-items: center;
  gap: var(--maybe-space-2);
  margin: 0 0 var(--maybe-space-3);
  font-family: var(--maybe-mono);
  font-size: 14px;
  font-weight: 700;
}

.comp-node {
  color: var(--vp-c-text-1);
}

.comp-node--to {
  color: var(--maybe-brand-1);
}

.comp-arrow {
  color: var(--vp-c-text-3);
}

.comp-detail {
  margin: 0;
  font-size: 14px;
  line-height: 1.55;
  color: var(--vp-c-text-2);
}

@media (max-width: 959px) {
  .comp-list {
    grid-template-columns: 1fr;
  }
}
</style>
