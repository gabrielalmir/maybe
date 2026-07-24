import { h } from 'vue'
import DefaultTheme from 'vitepress/theme'
import type { Theme } from 'vitepress'
import HeroCodePanel from './components/HeroCodePanel.vue'
import HeroBadges from './components/HeroBadges.vue'
import SiteFooter from './components/SiteFooter.vue'
import './custom.css'

export default {
  extends: DefaultTheme,
  Layout: () => {
    return h(DefaultTheme.Layout, null, {
      'home-hero-image': () => h(HeroCodePanel),
      'home-hero-actions-after': () => h(HeroBadges),
      'layout-bottom': () => h(SiteFooter)
    })
  }
} satisfies Theme
