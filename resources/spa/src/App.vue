<template>

  <layout-default router="default">
    <template slot="sidebar">
      <sv-sidebar>
        <template slot="logo">
          <svg class="fill-current w-24" viewBox="0 0 226 62" xmlns="http://www.w3.org/2000/svg">
            <g fill="none" fill-rule="evenodd" font-weight="500" stroke="none" stroke-width="1">
              <g fill="#999999" transform="translate(-1.000000, -25.000000)">
                <text font-family="AvenirNext-DemiBold, Avenir Next" font-size="64">
                  <tspan x="0" y="71">super</tspan>
                </text>
                <text fill="#67c23a" font-family=""
                      font-size="70" font-style="italic">
                  <tspan x="156" y="72">V</tspan>
                </text>
              </g>
            </g>
          </svg>
        </template>
      </sv-sidebar>
    </template>
  </layout-default>

</template>

<script>
import { LayoutDefault, SvSidebar } from 'superv-js'

export default {
  name: 'App',
  components: { SvSidebar, LayoutDefault },
  data() {
    return {}
  },
}
</script>
