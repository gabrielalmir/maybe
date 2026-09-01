<script setup lang="ts">
import { useData, withBase } from 'vitepress'

const { frontmatter } = useData()
</script>

<template>
  <section v-if="frontmatter.honest" class="maybe-band maybe-band--tight" aria-labelledby="maybe-honest-title">
    <div class="maybe-band__inner">
      <!-- Deliberately the quietest block on the page: bordered, uncoloured,
           and late. Placed early it would read as hedging; placed here it reads
           as candour. -->
      <div class="honest">
        <h2 id="maybe-honest-title" class="honest-title">{{ frontmatter.honest.title }}</h2>
        <p class="honest-lead">{{ frontmatter.honest.lead }}</p>
        <ul class="honest-list">
          <li v-for="item in frontmatter.honest.items" :key="item">{{ item }}</li>
        </ul>
        <a class="honest-link" :href="withBase(frontmatter.honest.link)">
          {{ frontmatter.honest.linkText }} →
        </a>
      </div>
    </div>
  </section>
</template>

<style scoped>
.honest {
  max-width: var(--maybe-measure-prose);
  padding: var(--maybe-space-7);
  border: 1px solid var(--vp-c-divider);
  border-radius: var(--maybe-radius-lg);
}

.honest-title {
  margin: 0 0 var(--maybe-space-3);
  font-family: var(--maybe-display);
  font-size: var(--maybe-fs-h3);
  font-weight: 650;
  letter-spacing: -0.015em;
  color: var(--vp-c-text-1);
}

.honest-lead {
  margin: 0 0 var(--maybe-space-4);
  font-size: 14.5px;
  line-height: 1.6;
  color: var(--vp-c-text-2);
}

.honest-list {
  margin: 0 0 var(--maybe-space-5);
  padding-left: 1.15em;
  /* VitePress's base stylesheet zeroes list markers globally. */
  list-style: disc;
  color: var(--vp-c-text-2);
}

.honest-list li {
  margin-bottom: var(--maybe-space-2);
  font-size: 14.5px;
  line-height: 1.6;
}

.honest-link {
  font-size: 14px;
  font-weight: 600;
  color: var(--maybe-brand-1);
  text-decoration: none;
}

.honest-link:hover {
  color: var(--maybe-brand-2);
}
</style>
