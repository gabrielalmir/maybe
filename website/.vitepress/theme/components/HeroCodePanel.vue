<script setup lang="ts">
import { useData } from 'vitepress'
import { data as heroCode } from '../data/heroCode.data'

const { lang } = useData()

// Only the rail is translated; the PHP snippet itself needs no translation.
const labels = lang.value === 'pt-BR'
  ? { input: 'entrada', result: 'Result', ok: 'Ok', err: 'Err' }
  : { input: 'input', result: 'Result', ok: 'Ok', err: 'Err' }
</script>

<template>
  <figure class="code-panel" aria-labelledby="hero-code-caption">
    <!-- A plain filename label, not a window title bar. The three traffic-light
         dots that used to sit here were fake macOS chrome, and they spent the
         reserved Ok/Err colours on decoration. -->
    <figcaption class="code-panel-file">customer.php</figcaption>

    <!-- Highlighted at build time by Shiki, the same highlighter and themes
         every other code block on this site uses. `vp-code` is what VitePress's
         two theme-swap rules key off, so it has to be on an ancestor of the
         spans. The HTML is build-time constant, never user input. -->
    <div class="code-panel-body vp-code" v-html="heroCode.html" />

    <div
      class="code-panel-outcome"
      role="img"
      :aria-label="`${labels.input} → ${labels.result} → ${labels.ok} / ${labels.err}`"
    >
      <span class="outcome-label">{{ labels.input }}</span>
      <span class="outcome-line" aria-hidden="true" />
      <span class="outcome-label">{{ labels.result }}</span>
      <span class="outcome-line" aria-hidden="true" />
      <span class="outcome-state">{{ labels.ok }}</span>
      <span class="outcome-state err">{{ labels.err }}</span>
    </div>

    <span id="hero-code-caption" class="visually-hidden">
      A PHP Result pipeline that makes success and failure explicit
    </span>
  </figure>
</template>

<style scoped>
.code-panel {
  width: 100%;
  max-width: 520px;
  margin: 0 auto;
  overflow: hidden;
  border: 1px solid var(--vp-c-divider);
  border-radius: var(--maybe-radius-lg);
  background: var(--maybe-surface-1);
  box-shadow: var(--maybe-shadow-3);
}

.code-panel-file {
  padding: 12px 16px;
  border-bottom: 1px solid var(--vp-c-divider);
  font-family: var(--maybe-mono);
  font-size: 12px;
  color: var(--vp-c-text-3);
  text-align: left;
}

.code-panel-body {
  padding: 16px;
  overflow-x: auto;
  /* Below 960px VPHero centres its container and a <pre> would inherit that,
     indenting every line of PHP off its own left margin. */
  text-align: left;
}

.code-panel-body :deep(pre) {
  margin: 0;
  background: transparent;
}

.code-panel-body :deep(code) {
  font-family: var(--maybe-mono);
  font-size: 12.5px;
  line-height: 1.7;
}

.code-panel-outcome {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-top: 1px solid var(--vp-c-divider);
  background: var(--maybe-surface-2);
  font-family: var(--maybe-mono);
  font-size: 11px;
}

.outcome-label {
  color: var(--vp-c-text-2);
}

.outcome-line {
  position: relative;
  flex: 1;
  height: 1px;
  background: var(--vp-c-divider);
}

.outcome-line::after {
  content: '›';
  position: absolute;
  right: -2px;
  top: -9px;
  color: var(--maybe-brand-1);
  font-size: 16px;
}

/* The only place green and red are allowed: the two values a Result holds. */
.outcome-state {
  padding: 2px 7px;
  border-radius: 5px;
  background: var(--maybe-ok-soft);
  color: var(--maybe-ok);
}

.outcome-state.err {
  background: var(--maybe-err-soft);
  color: var(--maybe-err);
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
