import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  base: '/h-rims/',  // ถ้าต้องการตั้ง base path สำหรับโปรเจกต์ (แก้ตามจริง)
  plugins: [
    laravel({
      input: [
        'resources/sass/app.scss',
        'resources/js/app.js',
      ],
      refresh: true,  // เปิด live reload เวลาแก้ไฟล์ Laravel blade / PHP
    }),
  ],
});
