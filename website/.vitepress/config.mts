import { defineConfig } from 'vitepress'

const guideSidebarEn = [
  {
    text: 'Introduction',
    items: [
      { text: 'Getting Started', link: '/guide/getting-started' },
      { text: 'Why Maybe?', link: '/guide/why-maybe' },
      { text: 'Tutorial', link: '/guide/tutorial' }
    ]
  },
  {
    text: 'Guide',
    items: [
      { text: 'Option', link: '/guide/option' },
      { text: 'Result', link: '/guide/result' },
      { text: 'Schema', link: '/guide/schema' },
      { text: 'DTO', link: '/guide/dto' },
      { text: 'Async', link: '/guide/async' },
      { text: 'Recipes', link: '/guide/recipes' },
      { text: 'Case Studies', link: '/guide/case-studies' },
      { text: 'Object Calisthenics', link: '/guide/object-calisthenics' },
      { text: 'CodeIgniter 3', link: '/guide/codeigniter-3' },
      { text: 'Incremental Migration', link: '/guide/migration' }
    ]
  },
  {
    text: 'Reference',
    items: [
      { text: 'API Reference', link: '/guide/api-reference' }
    ]
  }
]

const guideSidebarPt = [
  {
    text: 'Introdução',
    items: [
      { text: 'Primeiros Passos', link: '/pt/guide/getting-started' },
      { text: 'Por que Maybe?', link: '/pt/guide/why-maybe' },
      { text: 'Tutorial', link: '/pt/guide/tutorial' }
    ]
  },
  {
    text: 'Guia',
    items: [
      { text: 'Option', link: '/pt/guide/option' },
      { text: 'Result', link: '/pt/guide/result' },
      { text: 'Schema', link: '/pt/guide/schema' },
      { text: 'DTO', link: '/pt/guide/dto' },
      { text: 'Async', link: '/pt/guide/async' },
      { text: 'Receitas', link: '/pt/guide/recipes' },
      { text: 'Estudos de Caso', link: '/pt/guide/case-studies' },
      { text: 'Object Calisthenics', link: '/pt/guide/object-calisthenics' },
      { text: 'CodeIgniter 3', link: '/pt/guide/codeigniter-3' },
      { text: 'Migração Incremental', link: '/pt/guide/migration' }
    ]
  },
  {
    text: 'Referência',
    items: [
      { text: 'Referência de API', link: '/pt/guide/api-reference' }
    ]
  }
]

export default defineConfig({
  title: 'Maybe for PHP',
  base: '/maybe/',
  lastUpdated: true,
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/maybe/favicon.svg' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Maybe — Explicit, predictable business logic for PHP' }],
    [
      'meta',
      {
        property: 'og:description',
        content: 'Option, Result, Schema, DTO and Async for PHP 7.4+ — typed success and error paths, no exceptions as control flow.'
      }
    ],
    ['meta', { property: 'og:url', content: 'https://gabrielalmir.github.io/maybe/' }],
    ['meta', { property: 'og:image', content: 'https://gabrielalmir.github.io/maybe/og-image.png' }],
    ['meta', { property: 'og:image:width', content: '1200' }],
    ['meta', { property: 'og:image:height', content: '630' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:title', content: 'Maybe — Explicit, predictable business logic for PHP' }],
    [
      'meta',
      {
        name: 'twitter:description',
        content: 'Option, Result, Schema, DTO and Async for PHP 7.4+ — typed success and error paths, no exceptions as control flow.'
      }
    ],
    ['meta', { name: 'twitter:image', content: 'https://gabrielalmir.github.io/maybe/og-image.png' }],
    ['meta', { name: 'keywords', content: 'PHP, Option, Result, Schema, DTO, Async, functional, monad, validation, PHP 7.4, CodeIgniter 3, error handling' }],
    ['meta', { name: 'author', content: 'Gabriel Almir' }],
    ['meta', { name: 'theme-color', content: '#5B47D6' }]
  ],
  themeConfig: {
    logo: '/logo.svg',
    search: { provider: 'local' },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/gabrielalmir/maybe' }
    ],
    editLink: {
      pattern: 'https://github.com/gabrielalmir/maybe/edit/main/website/:path'
    }
  },
  locales: {
    root: {
      label: 'English',
      lang: 'en-US',
      description:
        'Explicit, predictable business logic for PHP 7.4+ — Option, Result, Schema, DTO and Async, inspired by Rust.',
      themeConfig: {
        nav: [
          { text: 'Guide', link: '/guide/getting-started' },
          { text: 'Examples', link: '/guide/recipes' },
          { text: 'Why Maybe?', link: '/guide/why-maybe' },
          { text: 'API', link: '/guide/api-reference' },
          {
            text: 'v0.3.0',
            items: [
              { text: 'Changelog', link: 'https://github.com/gabrielalmir/maybe/blob/main/CHANGELOG.md' },
              { text: 'Packagist', link: 'https://packagist.org/packages/gabrielalmir/maybe' }
            ]
          }
        ],
        sidebar: guideSidebarEn
      }
    },
    pt: {
      label: 'Português',
      lang: 'pt-BR',
      link: '/pt/',
      description:
        'Lógica de negócio explícita e previsível para PHP 7.4+ — Option, Result, Schema, DTO e Async, inspirados em Rust.',
      themeConfig: {
        nav: [
          { text: 'Guia', link: '/pt/guide/getting-started' },
          { text: 'Exemplos', link: '/pt/guide/recipes' },
          { text: 'Por que Maybe?', link: '/pt/guide/why-maybe' },
          { text: 'API', link: '/pt/guide/api-reference' },
          {
            text: 'v0.3.0',
            items: [
              { text: 'Changelog', link: 'https://github.com/gabrielalmir/maybe/blob/main/CHANGELOG.md' },
              { text: 'Packagist', link: 'https://packagist.org/packages/gabrielalmir/maybe' }
            ]
          }
        ],
        sidebar: guideSidebarPt,
        outline: { label: 'Nesta página' },
        docFooter: { prev: 'Página anterior', next: 'Próxima página' },
        lastUpdated: { text: 'Atualizado em' },
        returnToTopLabel: 'Voltar ao topo',
        darkModeSwitchLabel: 'Tema',
      }
    }
  }
})
