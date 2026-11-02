// Table of contents: highlights the link for the heading currently in view
// Usage: <nav x-data="Components.toc()"> with links to #heading-id
window.Components.toc = () => ({
  active: null,
  init() {
    const headings = [...this.$el.querySelectorAll('a[href^="#"]')]
      .map(link => document.getElementById(decodeURIComponent(link.hash.slice(1))))
      .filter(Boolean)
    if (!headings.length) return

    const visible = new Set()
    const observer = new IntersectionObserver(
      entries => {
        entries.forEach(entry => (entry.isIntersecting ? visible.add(entry.target) : visible.delete(entry.target)))
        // The first heading on screen wins, otherwise keep the last one scrolled past
        const first = headings.find(heading => visible.has(heading))
        if (first) this.active = first.id
      },
      { rootMargin: '0px 0px -60% 0px' }
    )
    headings.forEach(heading => observer.observe(heading))
  },
})
