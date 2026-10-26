import '../css/before-tailwind.css'
import '../css/tailwind.css'
import '../css/after-tailwind.css'

import Popover from './components/popover'
import StickyNav from './components/sticky-nav'

document.addEventListener('DOMContentLoaded', function () {
  StickyNav('header')
})
