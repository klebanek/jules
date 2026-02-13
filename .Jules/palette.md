# Palette's Journal

## 2026-02-13 - Accessible Status Updates
**Learning:** Purely visual status indicators (border color) and static text updates are insufficient for screen reader users and those with color blindness.
**Action:** When validating inputs dynamically:
1. Use `aria-live="polite"` on the status container to announce updates.
2. Link the input to the status message with `aria-describedby`.
3. Use `aria-invalid` to programmatically indicate error states on the input itself.
4. Add high-contrast icons (✅, ⚠️) to text messages for quicker visual scanning.
