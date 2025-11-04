# CSS Organization Guide

## Overview
The project's CSS has been organized into external files for better maintainability.

## CSS Files Structure

### 1. `global.css`
**Purpose**: Common styles used across the entire application
**Contains**:
- CSS variables (colors, spacing)
- Base reset and typography
- Container styles
- Button styles (.btn, .btn.primary)
- Card styles (.card, .card-head, .card-body)
- Form elements (input, select, textarea)

### 2. `client.css`
**Purpose**: Client-facing pages (shop, homepage, about, contact)
**Contains**:
- Navbar styles (.navbar, .nav-row, .brand, .links)
- Footer styles (.footer-row, .social-icons, .copyright)
- Client-specific responsive styles

### 3. `admin.css`
**Purpose**: Admin dashboard and management pages
**Contains**:
- Admin header and navigation (.admin-header, .admin-nav)
- Quick actions (.quick-actions)
- Stats grid (.stats, .stat)
- Tables (table, th, td)
- Row actions (.row-actions)
- Badges (.badge, .b-admin, .b-customer, etc.)
- Modal styles (.modal)
- Tab content (.tab-content)
- Flash messages (.flash-msg)

## How to Link CSS Files

### For Client Pages (in `proj/client/` folder):
The `header.php` already includes the CSS files:
```html
<link rel="stylesheet" href="../assets/css/global.css">
<link rel="stylesheet" href="../assets/css/client.css">
```

### For Admin Pages (in `proj/admin/` folder):
Replace the `<style>` blocks in the `<head>` section with:
```html
<link rel="stylesheet" href="../assets/css/global.css">
<link rel="stylesheet" href="../assets/css/admin.css">
```

## Files Already Updated
✅ `proj/includes/header.php` - Uses global.css + client.css
✅ `proj/includes/footer.php` - Removed inline styles (uses client.css)
✅ `proj/admin/dashboard.php` - Uses global.css + admin.css

## Files That Need Updating

### Admin Files (in `proj/admin/`):
- [ ] `employees.php`
- [ ] `users.php`
- [ ] `products.php`
- [ ] `orders.php`
- [ ] `reports.php`
- [ ] `settings.php`
- [ ] `detailsa.php`

### Steps to Update Each Admin File:
1. Open the file
2. Find the `<head>` section
3. Replace all `<style>` blocks with:
   ```html
   <link rel="stylesheet" href="../assets/css/global.css">
   <link rel="stylesheet" href="../assets/css/admin.css">
   ```
4. Keep only page-specific styles in a `<style>` tag if needed
5. Test the page to ensure styles still work

### Client Files (if they have inline styles):
Most client files use `header.php` which already includes the CSS files.
Check these files for any additional inline `<style>` blocks:
- [ ] `shop.php`
- [ ] `details.php`
- [ ] `contact.php`
- [ ] `about.php`
- [ ] `cart.php`
- [ ] `checkout.php`
- [ ] `profile.php`

## Benefits
✅ **Maintainability**: Change styles in one place, affects all pages
✅ **Performance**: Browser caches CSS files
✅ **Organization**: Clear separation of concerns
✅ **Consistency**: Ensures uniform styling across pages
✅ **Scalability**: Easy to add new styles or pages

## Notes
- Page-specific styles can still be added in `<style>` tags after the CSS links
- The CSS files use the same CSS variables, so colors and spacing remain consistent
- All responsive breakpoints are maintained
