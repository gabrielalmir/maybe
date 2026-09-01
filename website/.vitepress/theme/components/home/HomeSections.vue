<script setup lang="ts">
import CompositionSection from './CompositionSection.vue'
import LegacyBand from './LegacyBand.vue'
import CaseStudyStrip from './CaseStudyStrip.vue'
import AgentsSection from './AgentsSection.vue'
import HonestNote from './HonestNote.vue'
import FinalCta from './FinalCta.vue'
</script>

<template>
  <!-- Ordered wrapper for everything below the features grid. Each child guards
       itself on its own frontmatter key, so a locale that has not been
       translated yet renders nothing rather than an empty band. -->
  <CompositionSection />
  <LegacyBand />
  <CaseStudyStrip />
  <AgentsSection />
  <HonestNote />
  <FinalCta />
</template>
