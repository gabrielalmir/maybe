import { defineLoader } from 'vitepress'
import { codeToHtml } from 'shiki'

// The one boundary the whole library is about: parse untrusted input, chain a
// fallible call, and force both outcomes to be handled at the edge.
const source = `$result = Schema::shape([
    'email' => Schema::string()->trimmed(),
])->safeParse($payload)
    ->andThen(fn (array $data) => saveCustomer($data));

$result->match(
    fn (Customer $customer) => "Ok: saved",
    fn (mixed $error) => "Err: fix input"
);`

declare const data: { html: string }
export { data }

// Runs at build time only: the loader body is replaced wholesale with a JSON
// string, so Shiki never reaches the client bundle. Themes and defaultColor
// mirror what VitePress applies to every other code block on this site, which
// is what finally makes the hero panel match the guides instead of using its
// own hand-tokenised palette.
export default defineLoader({
  async load() {
    return {
      html: await codeToHtml(source, {
        lang: 'php',
        themes: { light: 'github-light', dark: 'github-dark' },
        defaultColor: false
      })
    }
  }
})
