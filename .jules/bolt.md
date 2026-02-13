## 2025-02-13 - Manual Lazy Loading in Single-File PWA
**Learning:** In a single-file architecture (`index.html` only) without a bundler, significant performance gains can be achieved by manually lazy-loading external CDN libraries (like XLSX, jsPDF, Quagga). These libraries were blocking the main thread/network on initial load despite being used only in specific user flows.
**Action:** When working with "no-build" setups, always check for heavy CDN imports that can be deferred until user interaction. Implement a simple `loadScript` utility to handle dependency injection on demand.
