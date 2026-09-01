import { h } from 'vue'
// theme-without-fonts is the same default theme minus its bundled Inter, which
// this site never renders: --vp-font-family-base is remapped to --maybe-body in
// tokens.css. Keeping it would preload ~30KB of unused webfont against the hero.
import DefaultTheme from 'vitepress/theme-without-fonts'
import type { Theme } from 'vitepress'
import HeroCodePanel from './components/HeroCodePanel.vue'
import HeroInstall from './components/HeroInstall.vue'
import SiteFooter from './components/SiteFooter.vue'
import ProofBand from './components/home/ProofBand.vue'
import BeforeAfterSection from './components/home/BeforeAfterSection.vue'
import HomeSections from './components/home/HomeSections.vue'

// Cascade order is load-bearing and lives here rather than in CSS @import so it
// stays reviewable in one place: font faces load first, tokens define the
// vocabulary, base sets document primitives, overrides reshape VitePress's own
// components, and home styles the pieces this landing page composes on top.
import './styles/fonts.css'
import './styles/tokens.css'
import './styles/base.css'
import './styles/vitepress-overrides.css'
import './styles/home.css'

export default {
  extends: DefaultTheme,
  Layout: () => {
    return h(DefaultTheme.Layout, null, {
      'home-hero-image': () => h(HeroCodePanel),
      'home-hero-actions-after': () => h(HeroInstall),
      'home-hero-after': () => h(ProofBand),
      'home-features-before': () => h(BeforeAfterSection),
      'home-features-after': () => h(HomeSections),
      'layout-bottom': () => h(SiteFooter)
    })
  }
} satisfies Theme
