<script setup lang="ts">
import { useData } from 'vitepress'

const { frontmatter } = useData()
</script>

<template>
  <section
    v-if="frontmatter.beforeAfter"
    class="maybe-band maybe-band--sunken"
    aria-labelledby="maybe-before-after-title"
  >
    <div class="maybe-band__inner">
      <p v-if="frontmatter.beforeAfter.eyebrow" class="maybe-eyebrow">{{ frontmatter.beforeAfter.eyebrow }}</p>
      <h2 id="maybe-before-after-title" class="maybe-band__title">
        {{ frontmatter.beforeAfter.title }}
      </h2>
      <p v-if="frontmatter.beforeAfter.lead" class="maybe-band__lead">
        {{ frontmatter.beforeAfter.lead }}
      </p>

      <div class="ba-grid">
        <figure class="ba-pane ba-pane--legacy">
          <figcaption class="ba-label">{{ frontmatter.beforeAfter.legacyLabel }}</figcaption>
          <!-- Hand-tokenised rather than a markdown fence: this section is full
               bleed, and the token colours carry the same Ok/Err meaning the
               rest of the page uses. PHP needs no translation. -->
          <pre class="ba-code"><code><span class="tok-cm">// Silently invisible:</span>
<span class="tok-fn">@mail</span>(<span class="tok-var">$to</span>, <span class="tok-var">$subject</span>, <span class="tok-var">$body</span>);

<span class="tok-cm">// "Handled", but the outcome is thrown away:</span>
<span class="tok-kw">try</span> {
    <span class="tok-var">$mailer</span>-&gt;send(<span class="tok-var">$to</span>, <span class="tok-var">$subject</span>, <span class="tok-var">$body</span>);
} <span class="tok-kw">catch</span> (<span class="tok-type">\Exception</span> <span class="tok-var">$e</span>) {
    <span class="tok-fn">error_log</span>(<span class="tok-var">$e</span>-&gt;getMessage());
}</code></pre>
          <p class="ba-note">{{ frontmatter.beforeAfter.legacyNote }}</p>
        </figure>

        <figure class="ba-pane ba-pane--maybe">
          <figcaption class="ba-label">{{ frontmatter.beforeAfter.maybeLabel }}</figcaption>
          <pre class="ba-code"><code><span class="tok-var">$emailResult</span> = <span class="tok-var">$emailSchema</span>-&gt;safeParse(<span class="tok-var">$message</span>)
    -&gt;andThen(<span class="tok-kw">static fn</span> (<span class="tok-type">array</span> <span class="tok-var">$valid</span>): <span class="tok-type">Result</span>
        =&gt; sendWithFallback(<span class="tok-var">$valid</span>));

<span class="tok-var">$emailResult</span>-&gt;match(
    <span class="tok-kw">static fn</span> (<span class="tok-type">string</span> <span class="tok-var">$ref</span>): <span class="tok-type">string</span> =&gt; <span class="tok-ok">"sent ({<span class="tok-var">$ref</span>})"</span>,
    <span class="tok-kw">static fn</span> (<span class="tok-type">array</span> <span class="tok-var">$error</span>): <span class="tok-type">string</span> =&gt; <span class="tok-var">$error</span>[<span class="tok-str">'retryable'</span>]
        ? <span class="tok-ok">"queued for retry"</span>
        : <span class="tok-err">"rejected: fix the input"</span>
);</code></pre>
          <p class="ba-note">{{ frontmatter.beforeAfter.note }}</p>
        </figure>
      </div>
    </div>
  </section>
</template>

<style scoped>
.ba-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--maybe-space-6);
  margin-top: var(--maybe-space-8);
}

.ba-pane {
  display: flex;
  flex-direction: column;
  margin: 0;
  padding: var(--maybe-space-5);
  border: 1px solid var(--vp-c-divider);
  border-radius: var(--maybe-radius-lg);
  background: var(--maybe-surface-1);
}

.ba-pane--maybe {
  border-color: var(--maybe-brand-3);
  box-shadow: var(--maybe-shadow-3);
}

.ba-label {
  margin-bottom: var(--maybe-space-4);
  font-family: var(--maybe-mono);
  font-size: var(--maybe-fs-eyebrow);
  letter-spacing: var(--maybe-track-eyebrow);
  text-transform: uppercase;
  color: var(--vp-c-text-3);
}

.ba-pane--maybe .ba-label {
  color: var(--maybe-brand-1);
}

.ba-code {
  flex: 1;
  margin: 0;
  overflow-x: auto;
  font-family: var(--maybe-mono);
  font-size: 12.5px;
  line-height: 1.7;
  color: var(--vp-c-text-1);
  text-align: left;
}

.ba-note {
  margin: var(--maybe-space-5) 0 0;
  font-size: 13.5px;
  line-height: 1.55;
  color: var(--vp-c-text-2);
}

.tok-kw { color: var(--maybe-code-keyword); }
.tok-var { color: var(--maybe-code-variable); }
.tok-type { color: var(--maybe-code-keyword); }
.tok-str { color: var(--maybe-code-ok); }
.tok-fn { color: var(--vp-c-text-1); }
.tok-cm { color: var(--vp-c-text-3); }
.tok-ok { color: var(--maybe-code-ok); }
.tok-err { color: var(--maybe-code-err); }

@media (max-width: 959px) {
  .ba-grid {
    grid-template-columns: 1fr;
  }
}
</style>
