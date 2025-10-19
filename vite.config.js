import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
      '~': resolve(__dirname, 'resources'),
    },
  },
  build: {
    outDir: 'public/build',
    manifest: true,
    rollupOptions: {
      input: 'resources/js/app.js',
    },
  },
  server: {
    hmr: {
      host: 'localhost',
    },
  },
})