<script setup lang="ts">
import { useData, withBase } from 'vitepress'

const { frontmatter } = useData()
</script>

<template>
  <section v-if="frontmatter.caseStudies" class="maybe-band" aria-labelledby="maybe-cases-title">
    <div class="maybe-band__inner">
      <p v-if="frontmatter.caseStudies.eyebrow" class="maybe-eyebrow">{{ frontmatter.caseStudies.eyebrow }}</p>
      <h2 id="maybe-cases-title" class="maybe-band__title">{{ frontmatter.caseStudies.title }}</h2>

      <ul class="case-list">
        <li v-for="item in frontmatter.caseStudies.items" :key="item.title" class="case-card">
          <h3 class="case-title">{{ item.title }}</h3>
          <p class="case-risk">{{ item.risk }}</p>
          <p class="case-links">
            <a class="case-link" :href="withBase(item.link)">{{ item.linkText }} →</a>
            <a class="case-example" :href="item.example">{{ item.exampleText }}</a>
          </p>
        </li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
.case-list {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--maybe-space-6);
  margin: var(--maybe-space-8) 0 0;
  padding: 0;
  list-style: none;
}

.case-card {
  display: flex;
  flex-direction: column;
  padding: var(--maybe-space-6);
  border: 1px solid var(--vp-c-divider);
  border-radius: var(--maybe-radius-lg);
  background: var(--maybe-surface-1);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.case-card:hover {
  border-color: var(--maybe-brand-3);
  box-shadow: var(--maybe-shadow-3);
}

.case-title {
  margin: 0 0 var(--maybe-space-3);
  font-family: var(--maybe-display);
  font-size: var(--maybe-fs-h3);
  font-weight: 650;
  letter-spacing: -0.015em;
  line-height: 1.25;
  color: var(--vp-c-text-1);
}

.case-risk {
  flex: 1;
  margin: 0 0 var(--maybe-space-5);
  font-size: 14px;
  line-height: 1.6;
  color: var(--vp-c-text-2);
}

.case-links {
  display: flex;
  flex-wrap: wrap;
  gap: var(--maybe-space-2) var(--maybe-space-5);
  margin: 0;
  font-size: 13px;
}

.case-link {
  font-weight: 600;
  color: var(--maybe-brand-1);
  text-decoration: none;
}

.case-example {
  font-family: var(--maybe-mono);
  color: var(--vp-c-text-3);
  text-decoration: none;
}

.case-link:hover,
.case-example:hover {
  color: var(--maybe-brand-2);
}

@media (max-width: 959px) {
  .case-list {
    grid-template-columns: 1fr;
  }
}
</style>
