document.addEventListener('alpine:init', () => {
  Alpine.data('Acceptance', (checked = false) => ({
    checked,
    init() {
      const checkbox = this.$el.querySelector('input[type="checkbox"]')
      checkbox.addEventListener('change', () => {
        this.checked = checkbox.checked
      })
    },
  }))
})
