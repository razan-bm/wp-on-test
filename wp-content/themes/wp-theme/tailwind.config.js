const colors = require('tailwindcss/colors')
const defaultTheme = require('tailwindcss/defaultTheme')

module.exports = {
  content: [
    './404.php',
    './footer.php',
    './header.php',
    './index.php',
    './page.php',
    './search.php',
    './single.php',
    './lib/**/*.php',
    './components/**/*.php',
    './components/**/*.js',
    './src/js/**/*.js',
  ],
  theme: {
    colors: {
      transparent: 'transparent',
      current: 'currentColor',
      black: colors.black,
      gray: colors.gray,
      white: colors.white,
      red: {
        ...colors.red,
        DEFAULT: '#F15A22',
      },
      green: {
        ...colors.green,
        DEFAULT: '#72BF44',
      },
    },
    fontFamily: {
      body: ['Roboto', ...defaultTheme.fontFamily.sans],
      heading: ['SOME SERIF FONT', ...defaultTheme.fontFamily.serif],
    },
    extend: {
      typography: theme => ({
        DEFAULT: {
          css: {
            '--tw-prose-body': colors.black,
            '--tw-prose-bold': colors.black,
            '--tw-prose-headings': colors.black,
            '--tw-prose-bullets': colors.black,
            '--tw-prose-quotes': colors.black,
          },
        },
      }),
      screens: {
        '4k': '2560px',
      },
    },
  },
  corePlugins: {
    container: false,
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    require('@tailwindcss/aspect-ratio'),
    require('@tailwindcss/line-clamp'),
  ],
}
