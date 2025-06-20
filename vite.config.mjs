import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  base: '/h-rims/public/',
  plugins: [
    laravel([
      'resources/sass/app.scss', // ✅ เพิ่มไฟล์ SCSS
      'resources/js/app.js',
    ]),
  ],
})