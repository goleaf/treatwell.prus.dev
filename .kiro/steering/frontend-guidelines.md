---
inclusion: fileMatch
fileMatchPattern: '*.blade.php,*.vue,*.jsx,*.tsx,*.css'
---

# Frontend Development Guidelines

## Tailwind CSS v4 Usage
Always use Tailwind v4 syntax and import method:

```css
/* Use this in CSS files */
@import "tailwindcss";

/* NOT the old v3 directives */
/* @tailwind base; */
/* @tailwind components; */
/* @tailwind utilities; */
```

## Updated Utilities
Use the new Tailwind v4 utilities instead of deprecated ones:

| Old (v3) | New (v4) |
|----------|----------|
| `bg-opacity-50` | `bg-black/50` |
| `text-opacity-75` | `text-black/75` |
| `flex-shrink-0` | `shrink-0` |
| `flex-grow` | `grow` |
| `overflow-ellipsis` | `text-ellipsis` |

## Spacing and Layout
- Use `gap` utilities for spacing between items, not margins
- Group related elements logically
- Remove redundant classes

```html
<!-- Good: Use gap for spacing -->
<div class="flex gap-4">
    <div>Item 1</div>
    <div>Item 2</div>
</div>

<!-- Avoid: Using margins -->
<div class="flex">
    <div class="mr-4">Item 1</div>
    <div>Item 2</div>
</div>
```

## Dark Mode Support
If existing components support dark mode, new components must too:
```html
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
    Content
</div>
```

## Build Process
If frontend changes aren't reflected:
- Run `npm run build` for production
- Run `npm run dev` or `composer run dev` for development
- Check for Vite manifest errors