<template>
  <figure class="code-panel" aria-labelledby="hero-code-caption">
    <div class="code-panel-bar">
      <span class="dot dot-err" />
      <span class="dot" style="background: #e6b800" />
      <span class="dot dot-ok" />
      <span class="filename">customer.php</span>
    </div>
    <figcaption id="hero-code-caption" class="visually-hidden">A PHP Result pipeline that makes success and failure explicit</figcaption>
    <pre class="code-panel-body"><code><span class="tok-kw">use</span> <span class="tok-ns">Maybe\Schema\Schema</span>;

<span class="tok-var">$result</span> = Schema::shape([
    <span class="tok-str">'email'</span> =&gt; Schema::string()-&gt;trimmed(),
])-&gt;safeParse(<span class="tok-var">$payload</span>)
    -&gt;andThen(<span class="tok-kw">fn</span> (<span class="tok-type">array</span> <span class="tok-var">$data</span>) =&gt; saveCustomer(<span class="tok-var">$data</span>));

<span class="tok-var">$result</span>-&gt;match(
    <span class="tok-kw">fn</span> (<span class="tok-type">Customer</span> <span class="tok-var">$customer</span>) =&gt; <span class="tok-ok">"<span class="tok-ok-tag">Ok</span>: saved"</span>,
    <span class="tok-kw">fn</span> (<span class="tok-type">mixed</span> <span class="tok-var">$error</span>) =&gt; <span class="tok-err">"<span class="tok-err-tag">Err</span>: fix input"</span>
);</code></pre>
  </figure>
</template>

<style scoped>
.code-panel {
  width: 100%;
  max-width: 520px;
  margin: 0 auto;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--vp-c-divider);
  background: var(--vp-c-bg-alt);
  box-shadow: 0 12px 32px -12px rgba(20, 18, 30, 0.25);
}

.code-panel-bar {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 14px;
  background: var(--vp-c-bg-soft);
  border-bottom: 1px solid var(--vp-c-divider);
}

.dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: var(--vp-c-divider);
}

.dot-err {
  background: var(--maybe-err);
  opacity: 0.75;
}

.dot-ok {
  background: var(--maybe-ok);
  opacity: 0.75;
}

.filename {
  margin-left: 8px;
  font-family: var(--maybe-mono);
  font-size: 12px;
  color: var(--vp-c-text-2);
}

.code-panel-body {
  margin: 0;
  padding: 18px 16px;
  font-family: var(--maybe-mono);
  font-size: 13px;
  line-height: 1.7;
  overflow-x: auto;
  color: var(--vp-c-text-1);
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

.tok-kw { color: var(--maybe-code-keyword); }
.tok-ns { color: var(--vp-c-text-2); }
.tok-var { color: var(--maybe-code-variable); }
.tok-num { color: var(--maybe-code-variable); }
.tok-str { color: var(--maybe-code-ok); }
.tok-type { color: var(--maybe-code-keyword); }
.tok-ok { color: var(--maybe-code-ok); }
.tok-err { color: var(--maybe-code-err); }
.tok-ok-tag { font-weight: 700; }
.tok-err-tag { font-weight: 700; }
</style>
