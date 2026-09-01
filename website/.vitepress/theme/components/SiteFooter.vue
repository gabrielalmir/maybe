<script setup lang="ts">
import { useData, withBase } from 'vitepress'

const { lang } = useData()

const copy = {
  'en-US': {
    tagline: 'Explicit, predictable business logic for legacy-friendly PHP.',
    docs: 'Documentation',
    changelog: 'Changelog',
    license: 'MIT License',
    made: 'Built for PHP 7.4+ teams who want typed success and error paths.'
  },
  'pt-BR': {
    tagline: 'Lógica de negócio explícita e previsível para PHP com foco em legado.',
    docs: 'Documentação',
    changelog: 'Changelog',
    license: 'Licença MIT',
    made: 'Construído para times PHP 7.4+ que querem caminhos de sucesso e erro tipados.'
  }
} as const

// Keys must match the `lang` each locale declares in config.mts.
const t = copy[lang.value as keyof typeof copy] ?? copy['en-US']

// Locale prefix only; withBase() supplies `/maybe/`, so nothing here breaks if
// the site's base ever changes.
const prefix = lang.value === 'pt-BR' ? '/pt' : ''
const docsHref = withBase(`${prefix}/guide/getting-started.html`)
</script>

<template>
  <footer class="site-footer">
    <div class="site-footer-inner">
      <div class="site-footer-brand">
        <span class="site-footer-name">Maybe</span>
        <p class="site-footer-tagline">{{ t.tagline }}</p>
      </div>
      <nav class="site-footer-links" aria-label="Footer">
        <a :href="docsHref">{{ t.docs }}</a>
        <a href="https://github.com/gabrielalmir/maybe">GitHub</a>
        <a href="https://packagist.org/packages/gabrielalmir/maybe">Packagist</a>
        <a href="https://github.com/gabrielalmir/maybe/blob/main/CHANGELOG.md">{{ t.changelog }}</a>
        <a href="https://github.com/gabrielalmir/maybe/blob/main/LICENSE">{{ t.license }}</a>
      </nav>
    </div>
    <p class="site-footer-note">{{ t.made }}</p>
  </footer>
</template>

<style scoped>
.site-footer {
  border-top: 1px solid var(--vp-c-divider);
  padding: 32px 24px 40px;
  margin-top: 8px;
}

.site-footer-inner {
  max-width: 1152px;
  margin: 0 auto;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: baseline;
  gap: 16px;
}

.site-footer-name {
  font-family: var(--maybe-mono);
  font-weight: 700;
  color: var(--vp-c-text-1);
}

.site-footer-tagline {
  margin: 4px 0 0;
  font-size: 13px;
  color: var(--vp-c-text-2);
  max-width: 40ch;
}

.site-footer-links {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 18px;
}

.site-footer-links a {
  font-size: 13px;
  color: var(--vp-c-text-2);
  text-decoration: none;
}

.site-footer-links a:hover {
  color: var(--maybe-brand-2);
}

.site-footer-note {
  max-width: 1152px;
  margin: 20px auto 0;
  font-size: 12px;
  color: var(--vp-c-text-3, var(--vp-c-text-2));
  opacity: 0.7;
}
</style>
