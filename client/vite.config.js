import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  build: {
    outDir: '../public/dist',
    emptyOutDir: true,
    manifest: true,
    minify: 'esbuild', // Use esbuild instead of terser to avoid WASM memory issues
    rollupOptions: {
      input: '/src/Main.jsx',
    },
  },
})
