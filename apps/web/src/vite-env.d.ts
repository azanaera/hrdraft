/// <reference types="vite/client" />

// Hando template vendor globals — loaded as plain <script> tags in index.html,
// not npm packages, so there's no @types package for them. See docs/HANDO_TEMPLATE.md.
interface Window {
  feather?: { replace: () => void };
}
