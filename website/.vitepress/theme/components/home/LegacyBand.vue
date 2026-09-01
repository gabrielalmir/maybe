<script setup lang="ts">
import { useData } from 'vitepress'

const { frontmatter } = useData()
</script>

<template>
  <section v-if="frontmatter.legacy" class="maybe-band maybe-legacy" aria-labelledby="maybe-legacy-title">
    <div class="maybe-band__inner">
      <p v-if="frontmatter.legacy.eyebrow" class="maybe-eyebrow legacy-eyebrow">{{ frontmatter.legacy.eyebrow }}</p>
      <h2 id="maybe-legacy-title" class="maybe-band__title legacy-title">
        {{ frontmatter.legacy.title }}
      </h2>
      <p class="maybe-band__lead legacy-lead">{{ frontmatter.legacy.lead }}</p>

      <ul class="legacy-points">
        <li v-for="point in frontmatter.legacy.points" :key="point.title" class="legacy-point">
          <h3 class="legacy-point__title">{{ point.title }}</h3>
          <p class="legacy-point__detail">{{ point.detail }}</p>
        </li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
/* The one full-colour band on the page. A solid background-color sits under
   everything so forced-colors mode, which drops background images, still has a
   ground for this text. */
.maybe-legacy {
  background-color: var(--maybe-surface-invert);
  color: var(--maybe-surface-invert-ink);
}

/* Violet, not --maybe-ok: green and red stay reserved for the two values a
   Result can actually have, and an eyebrow is decoration. */
.legacy-eyebrow {
  color: var(--maybe-brand-on-invert);
}

.legacy-title,
.legacy-point__title {
  color: var(--maybe-surface-invert-ink);
}

.legacy-lead,
.legacy-point__detail {
  color: color-mix(in srgb, var(--maybe-surface-invert-ink) 78%, transparent);
}

.legacy-points {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--maybe-space-6);
  margin: var(--maybe-space-9) 0 0;
  padding: 0;
  list-style: none;
}

.legacy-point {
  padding-top: var(--maybe-space-4);
  border-top: 1px solid var(--maybe-surface-invert-line);
}

.legacy-point__title {
  margin: 0 0 var(--maybe-space-2);
  font-family: var(--maybe-display);
  font-size: 15px;
  font-weight: 650;
  letter-spacing: -0.01em;
}

.legacy-point__detail {
  margin: 0;
  font-size: 14px;
  line-height: 1.55;
}

@media (max-width: 959px) {
  .legacy-points {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 639px) {
  .legacy-points {
    grid-template-columns: 1fr;
  }
}
</style>
