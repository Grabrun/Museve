/**
 * Tailwind 构建配置
 *
 * 原来走 cdn.tailwindcss.com 的 Play CDN：官方明确说明"不要用于生产"
 * （每页浏览器端 JIT 编译、体积大、把 DOM 内容外发给第三方）。
 * 现在改为构建期预编译，产物是 resources/css/tailwind.css。
 *
 * 改了模板里的 class 之后需要重新执行：npm run build:css
 */
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './*.php',
    './404.html',
    './includes/**/*.php',
    './sections/**/*.php',
    './admin/**/*.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        'museve-bg': '#F9F7F4',
        'museve-rose': '#DDB8B8',
        'museve-rose-deep': '#B28B8B',
        'museve-haze': '#F5F2F0',
        'museve-blue': '#A8C5DA',
        'museve-night': '#3E3640',
        'museve-gray': '#8E827F',
        'museve-green': '#87A878',
        'museve-orange': '#E0A96D',
        'museve-red': '#D18B8B',
        'museve-ash': '#9BADB7',
      },
      fontFamily: {
        serif: ['Noto Serif SC', 'Source Han Serif SC', 'serif'],
        sans: ['Inter', 'system-ui', 'sans-serif'],
        hand: ['ZCOOL XiaoWei', 'Caveat', 'cursive'],
      },
    },
  },
  plugins: [],
};
