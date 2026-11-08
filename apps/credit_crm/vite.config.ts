import react, { reactCompilerPreset } from '@vitejs/plugin-react'
import babel from '@rolldown/plugin-babel'
import { defineConfig } from 'vite'

// Port fixe : les 3 applications React tournent en même temps (credit_crm 5170, credit_client 5171, credit_tunnel 5172)
export default defineConfig({
  plugins: [
    react(),
    babel({ presets: [reactCompilerPreset()] })
  ],
  server: {
    port: 5170,
    strictPort: true,
  },
  preview: {
    port: 5170,
    strictPort: true,
  },
})
