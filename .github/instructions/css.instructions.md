---
applyTo: "assets/css/**/*.css"
description: WordPress CSS Coding Standards for Silver Assist ACF Clone Fields plugin
---

# CSS Coding Standards - Silver Assist ACF Clone Fields

## Overview

This document outlines the CSS coding standards for the Silver Assist ACF Clone Fields plugin. All CSS files must follow the [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/) and best practices from the [WP Admin CSS Audit](https://wordpress.github.io/css-audit/public/wp-admin).

**Primary Reference**: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/

---

## 1. Structure & Formatting

### Indentation
- **CRITICAL**: Use **tabs** for indentation, NOT spaces
- Each property must be on its own line with one tab of indentation
- Closing brace should be flush left, matching the opening selector's indentation

```css
/* ✅ CORRECT */
.selector-1,
.selector-2 {
	background: #fff;
	color: #000;
}

/* ❌ INCORRECT - spaces instead of tabs */
.selector-1 {
    background: #fff;
}

/* ❌ INCORRECT - properties on same line */
.selector-1 { background: #fff; color: #000; }
```

### Spacing Between Sections
- **Two blank lines** between major sections
- **One blank line** between blocks within a section

```css
/* Section 1 */
.block-1 {
	margin: 0;
}

.block-2 {
	padding: 0;
}


/* Section 2 - note TWO blank lines above */
.block-3 {
	color: #000;
}
```

---

## 2. Selectors

### Naming Conventions
- Use **lowercase** with **hyphens** (kebab-case)
- NO camelCase, NO underscores (except for WordPress core classes)
- Use human-readable, descriptive names
- Plugin prefix: `silver-acf-clone-` or `acf-clone-`

```css
/* ✅ CORRECT */
.silver-acf-clone-modal {
	display: block;
}

.acf-clone-field-item {
	padding: 10px;
}

/* ❌ INCORRECT */
.silverAcfCloneModal { /* camelCase */
	display: block;
}

.acf_clone_field { /* underscores */
	padding: 10px;
}

#c1-xr { /* unclear name */
	margin: 0;
}
```

### Attribute Selectors
- **MUST** use double quotes around values

```css
/* ✅ CORRECT */
input[type="text"] {
	line-height: 1.1;
}

/* ❌ INCORRECT */
input[type=text] { /* missing quotes */
	line-height: 1.1;
}
```

### Avoid Over-Qualification
```css
/* ✅ CORRECT */
.container {
	margin: 1em 0;
}

/* ❌ INCORRECT */
div.container { /* over-qualified */
	margin: 1em 0;
}
```

---

## 3. Property Ordering (CRITICAL)

**Standard Order**: Display → Positioning → Box Model → Colors/Typography → Other

### Property Groups:

1. **Display**: `display`, `visibility`, `float`, `clear`, `flex-*`, `grid-*`
2. **Positioning**: `position`, `top`, `right`, `bottom`, `left`, `z-index`
3. **Box Model**: `width`, `height`, `margin`, `padding`, `border`, `overflow`
4. **Colors & Typography**: `background`, `color`, `font-*`, `text-*`, `line-height`
5. **Other**: `transform`, `transition`, `animation`, `cursor`, etc.

### Directional Order: TRBL (Top-Right-Bottom-Left)
- Applies to: `margin`, `padding`, `border`, positioning properties

```css
/* ✅ CORRECT - Logical property grouping */
.modal {
	/* Display */
	display: flex;
	flex-direction: column;
	
	/* Positioning */
	position: absolute;
	top: 50%;
	left: 50%;
	z-index: 1000;
	
	/* Box Model */
	width: 90%;
	max-width: 800px;
	margin: 0 auto;
	padding: 20px;
	border: 1px solid #e5e5e5;
	border-radius: 4px;
	overflow: hidden;
	
	/* Colors & Typography */
	background-color: #fff;
	color: #333;
	font-size: 14px;
	line-height: 1.5;
	
	/* Other */
	box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
	transform: translate(-50%, -50%);
}

/* ❌ INCORRECT - Random ordering */
.modal {
	background-color: #fff;
	position: absolute;
	width: 90%;
	transform: translate(-50%, -50%);
	display: flex;
	color: #333;
}
```

---

## 4. Property Values

### Colors
- Use **hex codes** (lowercase, shortened when possible)
- Use `rgba()` when opacity is needed
- Avoid RGB format and uppercase hex

```css
/* ✅ CORRECT */
.element {
	background: #fff;
	color: #0073aa;
	border-color: rgba(0, 0, 0, 0.1);
}

/* ❌ INCORRECT */
.element {
	background: #FFFFFF; /* uppercase, not shortened */
	color: rgb(0, 115, 170); /* use hex instead */
	border-color: rgba(0,0,0,.1); /* missing leading zero */
}
```

### Units
- **Zero values**: NO units (except `transition-duration`, `animation-duration`)
- **Line-height**: Unitless when possible
- Always include leading zero for decimals

```css
/* ✅ CORRECT */
.element {
	margin: 0;
	padding: 10px 0;
	line-height: 1.5;
	opacity: 0.5;
	transition-duration: 0.3s; /* exception - time needs unit */
}

/* ❌ INCORRECT */
.element {
	margin: 0px; /* unnecessary unit */
	line-height: 1.5em; /* should be unitless */
	opacity: .5; /* missing leading zero */
}
```

### Font Weights
- Use **numeric values** (400, 600, 700)
- Avoid named values (`normal`, `bold`)

```css
/* ✅ CORRECT */
.element {
	font-weight: 400; /* normal */
	font-weight: 600; /* semi-bold */
	font-weight: 700; /* bold */
}

/* ❌ INCORRECT */
.element {
	font-weight: normal;
	font-weight: bold;
}
```

### Quotes
- Use **double quotes** (not single quotes)
- Required for font names with spaces and `content` property

```css
/* ✅ CORRECT */
.element {
	font-family: "Helvetica Neue", sans-serif;
	content: "→";
}

/* ❌ INCORRECT */
.element {
	font-family: 'Helvetica Neue', sans-serif; /* single quotes */
	font-family: Helvetica Neue, sans-serif; /* missing quotes */
}
```

---

## 5. CSS Custom Properties (Variables)

### Plugin Variable Naming Convention
Use `--{plugin-prefix}-{category}-{property}` pattern:

```css
:root {
	/* Colors */
	--silver-acf-color-primary: #0073aa;
	--silver-acf-color-success: #28a745;
	--silver-acf-color-warning: #ffc107;
	--silver-acf-color-danger: #dc3545;
	
	/* Spacing */
	--silver-acf-spacing-xs: 4px;
	--silver-acf-spacing-sm: 8px;
	--silver-acf-spacing-md: 15px;
	--silver-acf-spacing-lg: 20px;
	
	/* Typography */
	--silver-acf-font-size-sm: 11px;
	--silver-acf-font-size-base: 13px;
	--silver-acf-font-size-md: 14px;
	--silver-acf-font-size-lg: 16px;
	
	/* Borders */
	--silver-acf-border-color: #e5e5e5;
	--silver-acf-border-radius: 3px;
	
	/* Z-index layers */
	--silver-acf-z-modal: 160000;
	--silver-acf-z-overlay: 159999;
}

/* Usage */
.modal {
	z-index: var(--silver-acf-z-modal);
	padding: var(--silver-acf-spacing-lg);
	background: var(--silver-acf-color-primary);
	border-radius: var(--silver-acf-border-radius);
}
```

**Benefits**:
- Centralized theme values
- Easy maintenance and updates
- Consistent design system
- Better dark mode support

---

## 6. Comments

### Section Headers
- Use decorative comment blocks for major sections
- Include section number for easy navigation

```css
/* ==========================================================================
   1.0 - Meta Box Styles
   ========================================================================== */
```

### Table of Contents
- Include at top of file for files > 300 lines

```css
/**
 * Table of Contents
 *
 * 1.0 - Meta Box Styles
 * 2.0 - Modal Styles
 * 3.0 - Field Selection
 */
```

### Inline Comments
- No empty newlines between comment and related code
- Explain WHY, not WHAT (code should be self-explanatory)

```css
/* This is a comment about this selector */
.selector {
	position: absolute;
	top: 0 !important; /* Override WordPress core - needed for modal positioning */
}
```

---

## 7. Media Queries

### Organization
- Group media queries at **bottom of stylesheet**
- Indent rule sets one level inside media query
- Use WordPress breakpoints when applicable

```css
@media screen and (max-width: 768px) {
	.element {
		width: 100%;
		padding: 10px;
	}
}

@media screen and (max-width: 480px) {
	.element {
		padding: 5px;
	}
}
```

---

## 8. Best Practices

### Magic Numbers
**Avoid** arbitrary values without explanation

```css
/* ❌ BAD - magic number */
.box {
	margin-top: 37px; /* Why 37? */
}

/* ✅ GOOD - semantic spacing */
.box {
	margin-top: var(--silver-acf-spacing-lg);
}
```

### DOM Targeting
- Target the element directly, not through parents
- Use specific classes instead of descendant selectors

```css
/* ✅ CORRECT */
.highlight {
	background: yellow;
}

/* ❌ INCORRECT - brittle, depends on DOM structure */
.container .sidebar .highlight a {
	background: yellow;
}
```

### Height Property
- Avoid fixed heights when possible
- Use `min-height` or `line-height` for flexibility

```css
/* ✅ CORRECT */
.element {
	min-height: 100px;
	line-height: 1.5;
}

/* ❌ INCORRECT - inflexible */
.element {
	height: 100px;
}
```

### Shorthand Properties
- Use shorthand for: `background`, `border`, `font`, `margin`, `padding`
- Avoid when overriding specific values

```css
/* ✅ CORRECT */
.element {
	margin: 0;
	margin-left: 20px; /* Specific override */
	background: #fff url(bg.png) no-repeat center;
}

/* ❌ INCORRECT */
.element {
	margin-top: 0;
	margin-right: 0;
	margin-bottom: 0;
	margin-left: 20px;
}
```

---

## 9. WP Admin CSS Audit Guidelines

Reference: https://wordpress.github.io/css-audit/public/wp-admin

### Key Metrics to Monitor:
1. **File size**: Keep CSS files under 50KB (unminified)
2. **Selector specificity**: Avoid overly specific selectors
3. **Redundancy**: Don't repeat the same properties
4. **Browser compatibility**: Support last 2 versions of major browsers
5. **Accessibility**: Ensure sufficient color contrast (WCAG AA)

### Common Issues to Avoid:
- ❌ Duplicate selectors
- ❌ Unused CSS rules
- ❌ Overly specific selectors (e.g., `div.class#id`)
- ❌ `!important` overuse (use sparingly, document why)
- ❌ Fixed pixel widths (use percentages or viewport units)

---

## 10. Plugin-Specific Guidelines

### File Naming
- Pattern: `{plugin-prefix}-{purpose}.css`
- Example: `silver-acf-clone-fields.css`
- NOT: `admin.css`, `style.css` (too generic)

### Class Prefix
- All classes: `acf-clone-` or `silver-acf-clone-`
- Prevents conflicts with other plugins/themes

### Browser Support
- Modern browsers (Chrome, Firefox, Safari, Edge - last 2 versions)
- NO IE11 support required
- Use modern CSS features: Flexbox, Grid, Custom Properties

### Performance
- Minimize use of expensive properties: `box-shadow`, `filter`, `transform`
- Use `will-change` for animations
- Avoid layout thrashing (batch DOM reads/writes)

---

## 11. Validation Checklist

Before committing CSS changes:

- [ ] All indentation uses tabs (not spaces)
- [ ] Properties ordered: Display → Positioning → Box → Colors → Other
- [ ] Two blank lines between major sections
- [ ] All hex colors lowercase and shortened (`#fff` not `#FFFFFF`)
- [ ] No units on zero values (except time)
- [ ] Font weights are numeric (400, 600, 700)
- [ ] Line-height is unitless when possible
- [ ] Attribute selectors use double quotes `[type="text"]`
- [ ] No camelCase or underscores in class names
- [ ] CSS variables used for repeated values
- [ ] Comments explain WHY, not WHAT
- [ ] Media queries grouped at bottom
- [ ] No `!important` without documentation
- [ ] File saved with UTF-8 encoding
- [ ] No trailing whitespace

---

## 12. Tools & Resources

### Validation
- [W3C CSS Validator](https://jigsaw.w3.org/css-validator/)
- [WordPress CSS Audit](https://wordpress.github.io/css-audit/public/wp-admin)
- Browser DevTools CSS validation

### References
- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [CSS Shorthand Reference](https://codex.wordpress.org/CSS_Shorthand)
- [Idiomatic CSS Principles](https://github.com/necolas/idiomatic-css)
- [Modern CSS Features](https://web.dev/learn/css/)

### Editor Setup (VS Code)
- Extension: "EditorConfig for VS Code"
- Extension: "stylelint"
- Settings: `"editor.insertSpaces": false` (use tabs)

---

## Examples from Plugin

See `assets/css/silver-acf-clone-fields.css` for a complete implementation following these standards.

**Last Updated**: November 2025  
**Plugin Version**: 1.1.0
