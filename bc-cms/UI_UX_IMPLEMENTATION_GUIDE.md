# Tsoka Portal - UI/UX Implementation Guide

Quick actionable steps to modernize the Tsoka Portal UI/UX.

---

## 🚀 Quick Start: Option 1 (2 Weeks)

### Step 1: Create Design Tokens CSS
**File**: `themes/gotrip/css/_tokens.css`

```css
:root {
  /* Colors - Tsoka Branding */
  --color-primary: #005a9c;        /* Primary blue */
  --color-secondary: #eea61a;      /* Bronze accent */
  --color-success: #22c55e;        /* Green */
  --color-warning: #f59e0b;        /* Amber */
  --color-danger: #ef4444;         /* Red */
  --color-info: #0284c7;           /* Light blue */

  /* Neutral colors */
  --color-bg-light: #ffffff;
  --color-bg-default: #f9fafb;
  --color-bg-dark: #1a0900;        /* Ink color */
  --color-text-primary: #1a0900;
  --color-text-secondary: #6b7280;
  --color-text-light: #9ca3af;
  --color-border: #e5e7eb;

  /* Spacing */
  --spacing-xs: 0.25rem;  /* 4px */
  --spacing-sm: 0.5rem;   /* 8px */
  --spacing-md: 1rem;     /* 16px */
  --spacing-lg: 1.5rem;   /* 24px */
  --spacing-xl: 2rem;     /* 32px */
  --spacing-2xl: 3rem;    /* 48px */

  /* Typography */
  --font-family-body: 'Instrument Sans', system-ui, sans-serif;
  --font-family-heading: 'DM Sans', system-ui, sans-serif;
  --font-size-sm: 0.875rem;     /* 14px */
  --font-size-base: 1rem;       /* 16px */
  --font-size-lg: 1.125rem;     /* 18px */
  --font-size-xl: 1.25rem;      /* 20px */
  --font-size-2xl: 1.5rem;      /* 24px */
  --font-size-3xl: 1.875rem;    /* 30px */

  /* Border radius */
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-full: 999px;

  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);

  /* Transitions */
  --transition-fast: 150ms ease-in-out;
  --transition-base: 250ms ease-in-out;
  --transition-slow: 350ms ease-in-out;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  :root {
    --color-bg-light: #1f2937;
    --color-bg-default: #111827;
    --color-text-primary: #f3f4f6;
    --color-text-secondary: #d1d5db;
    --color-border: #374151;
  }
}
```

### Step 2: Update Main CSS to Use Tokens
**File**: `themes/gotrip/css/main.css`

```css
/* Apply tokens globally */
body {
  font-family: var(--font-family-body);
  font-size: var(--font-size-base);
  line-height: 1.5;
  color: var(--color-text-primary);
  background-color: var(--color-bg-light);
  transition: background-color var(--transition-base),
              color var(--transition-base);
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-family-heading);
  font-weight: 600;
  margin-bottom: var(--spacing-lg);
}

h1 { font-size: var(--font-size-3xl); }
h2 { font-size: var(--font-size-2xl); }
h3 { font-size: var(--font-size-xl); }

/* Button standardization */
.btn, button {
  padding: var(--spacing-md) var(--spacing-lg);
  border-radius: var(--radius-md);
  border: none;
  cursor: pointer;
  font-weight: 600;
  transition: all var(--transition-fast);
  min-height: 48px;  /* Touch target */
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
}

.btn-primary:hover {
  background-color: #004578;
  box-shadow: var(--shadow-lg);
}

/* Card styling */
.card {
  background: var(--color-bg-light);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-base);
}

.card:hover {
  box-shadow: var(--shadow-md);
}
```

### Step 3: Replace Font Awesome with Bootstrap Icons

```bash
# Add Bootstrap Icons CDN to layout
```

In `themes/GoTrip/Layout/app.blade.php`:
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
```

Then replace in templates:
```blade
{{-- Before --}}
<i class="fa fa-star"></i>

{{-- After --}}
<i class="bi bi-star-fill"></i>
```

### Step 4: Create Reusable Components

**File**: `resources/views/components/price-badge.blade.php`

```blade
@props([
  'original' => 0,
  'sale' => 0,
  'currency' => '$',
  'fromLabel' => false
])

<div class="price-badge">
  @if($fromLabel)
    <span class="price-label">{{ __('from') }}</span>
  @endif
  <div class="price-display">
    @if($sale && $sale < $original)
      <span class="price-original">{{ $currency }}{{ $original }}</span>
      <span class="price-sale">{{ $currency }}{{ $sale }}</span>
    @else
      <span class="price-current">{{ $currency }}{{ $original }}</span>
    @endif
  </div>
</div>

<style scoped>
.price-badge {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.price-label {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
}

.price-display {
  display: flex;
  gap: var(--spacing-sm);
  align-items: baseline;
}

.price-original {
  text-decoration: line-through;
  color: var(--color-text-secondary);
  font-size: var(--font-size-sm);
}

.price-sale {
  font-size: var(--font-size-2xl);
  font-weight: 700;
  color: var(--color-danger);
}

.price-current {
  font-size: var(--font-size-2xl);
  font-weight: 700;
  color: var(--color-primary);
}
</style>
```

Usage:
```blade
<x-price-badge :original="150" :sale="100" />
```

---

## 🎯 Immediate Improvements

### Fix #1: Improve Rating Display
**Before**:
```blade
<div class="list-star">
  <ul class="booking-item-rating-stars">
    <li><i class="fa fa-star-o"></i></li>
    <!-- 5 times -->
  </ul>
  <div class="booking-item-rating-stars-active" style="width: {{ $score_total * 2 * 10 ?? 0 }}%">
```

**After**:
```blade
<div class="rating-display" role="img" :aria-label="`{{ $score }} out of 5 stars`">
  @for($i = 1; $i <= 5; $i++)
    <i class="bi bi-star{{ $i <= $score ? '-fill' : '' }}" 
       style="color: {{ $i <= $score ? 'var(--color-warning)' : 'var(--color-border)' }}">
    </i>
  @endfor
</div>
```

### Fix #2: Mobile Buttons
**Before**:
```blade
<div class="bc-more-book-mobile">
  <button class="rounded-4 bg-blue-1 text-white">Book Now</button>
</div>
```

**After**:
```blade
<button class="btn btn-primary w-full md:w-auto sticky bottom-0 left-0 right-0 md:relative">
  Book Now
</button>
```

### Fix #3: Loading States
```blade
{{-- Add skeleton loader component --}}
<div class="skeleton-loader" :class="{ 'is-loading': loading }">
  <div class="skeleton-item h-12 mb-4"></div>
  <div class="skeleton-item h-8 mb-2"></div>
  <div class="skeleton-item h-8"></div>
</div>

<style>
.skeleton-loader.is-loading {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 2s infinite;
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
```

---

## 📱 Mobile-First Responsive Pattern

### Standard breakpoints (add to CSS)
```css
/* Mobile first (320px+) */
.card { padding: var(--spacing-md); }

/* Tablet (768px+) */
@media (min-width: 768px) {
  .card { padding: var(--spacing-lg); }
}

/* Desktop (1024px+) */
@media (min-width: 1024px) {
  .card { 
    padding: var(--spacing-xl);
    display: grid;
    grid-template-columns: repeat(2, 1fr);
  }
}
```

---

## ✨ Dark Mode Implementation

Add to `themes/GoTrip/Layout/app.blade.php`:

```blade
<script>
  // Check user preference
  if (localStorage.theme === 'dark' || 
      (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  }

  // Toggle handler
  function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
  }
</script>

{{-- Add toggle button in header --}}
<button class="btn-icon" onclick="toggleDarkMode()" aria-label="Toggle dark mode">
  <i class="bi bi-moon"></i>
</button>
```

---

## 🎨 Tanova-Specific Improvements

### Better Package Card
```blade
<div class="package-card" :class="{ 'is-featured': package.popular }">
  @if(package.popular)
    <div class="badge-popular">
      <i class="bi bi-star-fill"></i>
      {{ __('Most Popular') }}
    </div>
  @endif

  <div class="package-header">
    <h3>{{ package.name }}</h3>
    <p class="package-subtitle">{{ package.duration }} days • {{ package.cities_count }} cities</p>
  </div>

  <div class="pricing-section">
    <div class="price-per-person">
      <span class="label">{{ __('Per Person') }}</span>
      <div class="price">
        @if(package.sale_price)
          <span class="original">${{ package.original_price }}</span>
        @endif
        <span class="amount">${{ package.sale_price ?? package.original_price }}</span>
      </div>
    </div>
  </div>

  <ul class="highlights">
    <li><i class="bi bi-check2"></i> All zone 1 activities included</li>
    <li><i class="bi bi-check2"></i> 4-star accommodations</li>
    <li><i class="bi bi-check2"></i> Daily breakfast included</li>
  </ul>

  <button class="btn btn-primary w-full">
    {{ __('View Full Itinerary') }}
  </button>
</div>

<style scoped>
.package-card {
  border: 2px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  transition: all var(--transition-base);
  position: relative;
}

.package-card.is-featured {
  border-color: var(--color-warning);
  box-shadow: var(--shadow-lg);
  transform: scale(1.02);
}

.badge-popular {
  position: absolute;
  top: -12px;
  left: 20px;
  background: var(--color-warning);
  color: white;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-full);
  font-size: var(--font-size-sm);
  font-weight: 600;
}

.package-header h3 {
  margin: 0;
  color: var(--color-primary);
}

.highlights {
  list-style: none;
  padding: 0;
  margin: var(--spacing-lg) 0;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.highlights li {
  display: flex;
  gap: var(--spacing-sm);
  align-items: center;
  color: var(--color-text-secondary);
}

.highlights i {
  color: var(--color-success);
  flex-shrink: 0;
}
</style>
```

---

## 📊 Implementation Timeline

**Week 1**:
- [ ] Create `_tokens.css`
- [ ] Update `main.css` to use tokens
- [ ] Add Bootstrap Icons
- [ ] Create 3 reusable components

**Week 2**:
- [ ] Implement dark mode toggle
- [ ] Fix rating displays
- [ ] Improve mobile buttons
- [ ] Add loading states

**Ongoing**:
- [ ] Update each page template (one per day)
- [ ] Test on mobile devices
- [ ] Gather user feedback

---

## ✅ Quality Checklist

- [ ] All buttons are at least 48px height (touch target)
- [ ] Color contrast meets WCAG AA
- [ ] All interactive elements have visible focus state
- [ ] Images have descriptive alt text
- [ ] Forms are labeled properly
- [ ] Loading states shown for async operations
- [ ] Mobile viewport is responsive
- [ ] Dark mode works properly
- [ ] Animations don't cause seizures (< 3 flashes/sec)

---

This covers **Option 1: Quick Wins** (2 weeks). Once complete, we can plan **Option 2: Full Tailwind Migration** for future phases.

