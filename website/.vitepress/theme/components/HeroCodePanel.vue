<template>
  <div class="code-panel" role="img" aria-label="PHP code example using Maybe's Result pipeline">
    <div class="code-panel-bar">
      <span class="dot dot-err" />
      <span class="dot" style="background: #e6b800" />
      <span class="dot dot-ok" />
      <span class="filename">invoice.php</span>
    </div>
    <pre class="code-panel-body"><code><span class="tok-kw">use</span> <span class="tok-ns">Maybe\Result\Result</span>;

<span class="tok-var">$invoice</span> = loadUser(<span class="tok-num">10</span>)
    -&gt;andThen(<span class="tok-kw">fn</span> (<span class="tok-type">array</span> <span class="tok-var">$user</span>) =&gt; charge(<span class="tok-var">$user</span>))
    -&gt;map(<span class="tok-kw">fn</span> (<span class="tok-type">array</span> <span class="tok-var">$paid</span>) =&gt; <span class="tok-var">$paid</span>[<span class="tok-str">'id'</span>]);

<span class="tok-var">$invoice</span>-&gt;match(
    <span class="tok-kw">fn</span> (<span class="tok-type">string</span> <span class="tok-var">$id</span>) =&gt; <span class="tok-ok">"<span class="tok-ok-tag">Ok</span>: invoice {<span class="tok-var">$id</span>} created"</span>,
    <span class="tok-kw">fn</span> (<span class="tok-type">string</span> <span class="tok-var">$e</span>) =&gt; <span class="tok-err">"<span class="tok-err-tag">Err</span>: {<span class="tok-var">$e</span>}"</span>
);</code></pre>
  </div>
</template>

<style scoped>
.code-panel {
  width: 100%;
  max-width: 480px;
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

.tok-kw { color: var(--maybe-brand-3); }
.tok-ns { color: var(--vp-c-text-2); }
.tok-var { color: #d97757; }
.tok-num { color: #d97757; }
.tok-str { color: var(--maybe-ok); }
.tok-type { color: var(--maybe-brand-1); }
.tok-ok { color: var(--maybe-ok); }
.tok-err { color: var(--maybe-err); }
.tok-ok-tag { font-weight: 700; }
.tok-err-tag { font-weight: 700; }
</style>
