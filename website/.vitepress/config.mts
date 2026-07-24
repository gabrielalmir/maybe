import { defineConfig } from 'vitepress'

const guideSidebarEn = [
  {
    text: 'Guide',
    items: [
      { text: 'Getting Started', link: '/guide/getting-started' },
      { text: 'Option', link: '/guide/option' },
      { text: 'Result', link: '/guide/result' },
      { text: 'Schema', link: '/guide/schema' },
      { text: 'DTO', link: '/guide/dto' },
      { text: 'Async', link: '/guide/async' },
      { text: 'CodeIgniter 3', link: '/guide/codeigniter-3' },
      { text: 'Incremental Migration', link: '/guide/migration' }
    ]
  }
]

const guideSidebarPt = [
  {
    text: 'Guia',
    items: [
      { text: 'Primeiros Passos', link: '/pt/guide/getting-started' },
      { text: 'Option', link: '/pt/guide/option' },
      { text: 'Result', link: '/pt/guide/result' },
      { text: 'Schema', link: '/pt/guide/schema' },
      { text: 'DTO', link: '/pt/guide/dto' },
      { text: 'Async', link: '/pt/guide/async' },
      { text: 'CodeIgniter 3', link: '/pt/guide/codeigniter-3' },
      { text: 'Migração Incremental', link: '/pt/guide/migration' }
    ]
  }
]

export default defineConfig({
  title: 'Maybe',
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
    ['meta', { name: 'theme-color', content: '#5B47D6' }]
  ],
  themeConfig: {
    logo: '/logo.svg',
    search: { provider: 'local' },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/gabrielalmir/maybe' }
    ],
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © Gabriel Almir'
    },
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
          {
            text: 'v0.2.2',
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
          {
            text: 'v0.2.2',
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
        footer: {
          message: 'Publicado sob a licença MIT.',
          copyright: 'Copyright © Gabriel Almir'
        }
      }
    }
  }
})
