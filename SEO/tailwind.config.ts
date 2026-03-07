import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./app/**/*.{ts,tsx}",
    "./components/**/*.{ts,tsx}",
    "./lib/**/*.{ts,tsx}"
  ],
  theme: {
    extend: {
      colors: {
        background: "#020617",
        foreground: "#f9fafb",
        muted: "#0b1220",
        primary: {
          DEFAULT: "#4f46e5",
          foreground: "#e5e7ff"
        },
        secondary: {
          DEFAULT: "#0f172a",
          foreground: "#e5e7eb"
        },
        border: "#1f2937",
        accent: "#06b6d4"
      },
      boxShadow: {
        card: "0 18px 45px rgba(15,23,42,0.55)"
      },
      borderRadius: {
        xl: "1.25rem"
      }
    }
  },
  plugins: []
};

export default config;

