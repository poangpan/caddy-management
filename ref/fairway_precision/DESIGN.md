---
name: Fairway Precision
colors:
  surface: '#f9f9ff'
  surface-dim: '#cfdaf2'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f0f3ff'
  surface-container: '#e7eeff'
  surface-container-high: '#dee8ff'
  surface-container-highest: '#d8e3fb'
  on-surface: '#111c2d'
  on-surface-variant: '#414944'
  inverse-surface: '#263143'
  inverse-on-surface: '#ecf1ff'
  outline: '#717974'
  outline-variant: '#c0c8c3'
  surface-tint: '#3b6756'
  primary: '#00261a'
  on-primary: '#ffffff'
  primary-container: '#0f3d2e'
  on-primary-container: '#7ba894'
  inverse-primary: '#a2d1bb'
  secondary: '#5f5e59'
  on-secondary: '#ffffff'
  secondary-container: '#e5e2db'
  on-secondary-container: '#65645f'
  tertiary: '#132232'
  on-tertiary: '#ffffff'
  tertiary-container: '#283748'
  on-tertiary-container: '#91a0b5'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#beedd7'
  primary-fixed-dim: '#a2d1bb'
  on-primary-fixed: '#002116'
  on-primary-fixed-variant: '#234f3f'
  secondary-fixed: '#e5e2db'
  secondary-fixed-dim: '#c9c6c0'
  on-secondary-fixed: '#1c1c18'
  on-secondary-fixed-variant: '#474742'
  tertiary-fixed: '#d4e4fa'
  tertiary-fixed-dim: '#b9c8de'
  on-tertiary-fixed: '#0d1c2d'
  on-tertiary-fixed-variant: '#39485a'
  background: '#f9f9ff'
  on-background: '#111c2d'
  surface-variant: '#d8e3fb'
  status-ready: '#10B981'
  status-busy: '#F59E0B'
  status-leave: '#64748B'
  status-waiting: '#3B82F6'
  scb-purple: '#4E2E7F'
typography:
  headline-lg:
    fontFamily: Hanken Grotesk
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Hanken Grotesk
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-sm:
    fontFamily: Hanken Grotesk
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Hanken Grotesk
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Hanken Grotesk
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Hanken Grotesk
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: JetBrains Mono
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: JetBrains Mono
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.05em
  headline-lg-mobile:
    fontFamily: Hanken Grotesk
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 8px
  container-margin: 24px
  gutter-desktop: 20px
  gutter-mobile: 12px
  sidebar-width: 260px
---

## Brand & Style

The design system is engineered for the high-stakes operational environment of a luxury golf resort. It bridges the gap between elite hospitality and rigorous administrative utility. The brand personality is **utilitarian, reliable, and fair**, focusing on the "FIFO" (First-In, First-Out) logic to ensure transparency and trust among caddy staff and management.

The visual direction follows a **Corporate / Modern** aesthetic with a **specialized internal tool** feel. It prioritizes information density and operational speed over decorative elements. The style utilizes structured card layouts and a systematic color language to provide "status-at-a-glance" capabilities, essential for both desktop accounting and mobile on-the-field management.

## Colors

The palette is anchored by **Golf Green** (#0F3D2E), a deep, authoritative shade that evokes the premium nature of the resort. **Sand White** (#F4F1EA) serves as the primary surface color, providing a softer, more sophisticated alternative to pure white that reduces glare during outdoor use. **Fairway Silver** (#94A3B8) is used for secondary UI elements and borders.

Functional color is critical for this system. A dedicated semantic set handles caddy availability:
- **Ready:** Emerald green for active availability.
- **Busy:** Amber for caddies currently on a round.
- **Leave:** Slate for off-duty or personal leave.
- **Waiting:** Blue for pending confirmations.
- **Financials:** A specific purple is reserved for SCB banking integrations to provide instant recognition for payroll tasks.

## Typography

This design system uses **Hanken Grotesk** as the primary typeface. It is a sharp, contemporary sans-serif that maintains exceptional legibility in data-dense environments. For technical data, caddy IDs, and currency values, **JetBrains Mono** is employed to ensure character distinction and a clean, tabular appearance.

In mobile views, headlines are scaled down to ensure the FIFO queue and payroll tables remain the focal point without excessive scrolling. Large display sizes (Headline LG) are reserved for key dashboard metrics like "Current Rounds" or "Daily Total Wages."

## Layout & Spacing

The system utilizes a **12-column fluid grid** for desktop administrative views and a **single-column layout** for mobile queue management. 

- **Desktop:** Features a fixed left-hand sidebar (260px) for navigation, with a fluid content area for expansive data tables.
- **Information Density:** A strict 8px baseline grid is used. For the FIFO queue, vertical spacing is condensed to maximize the number of caddies visible on one screen.
- **Mobile/Tablet:** Gutters are reduced to 12px to maximize screen real estate for "on-the-green" interactions. Touch targets for status buttons are explicitly kept at a minimum of 44px.

## Elevation & Depth

To maintain the professional "tool" feel, the system uses **Tonal Layers** and **Low-Contrast Outlines** rather than heavy shadows. 

- **Level 0 (Background):** Sand White (#F4F1EA).
- **Level 1 (Cards/Content):** Pure White (#FFFFFF) with a thin 1px border in Fairway Silver (#94A3B8) at 30% opacity.
- **Level 2 (Active/Hover):** A subtle 4px ambient shadow with a tint of Golf Green to indicate interactivity.
- **Modals:** Use a heavy backdrop blur (12px) to focus attention on critical notifications or requested caddy unavailability alerts, ensuring the user is not distracted by the underlying queue data.

## Shapes

The system uses a **Soft (1)** roundedness profile. This level (4px - 12px) maintains a professional and organized architectural feel while avoiding the harshness of sharp corners. 

- **Buttons & Small Inputs:** 4px (rounded-sm)
- **Cards & Modals:** 8px (rounded-lg)
- **Status Badges:** Pill-shaped (fully rounded) to differentiate "Status" from "Data."

## Components

### Buttons
- **Primary:** Solid Golf Green with white text. High contrast for "Confirm Round" or "Start Session."
- **Secondary:** Outlined Fairway Silver for "Edit" or "Cancel" actions.
- **Action Icons:** Ghost-style buttons for table-row actions to reduce visual noise.

### Cards
- Caddy cards in the FIFO queue feature a left-aligned status bar (color-coded).
- Card headers use Hanken Grotesk 600 weight for the Caddy Name and ID.
- Secondary info (Rounds Today, Waiting Time) uses JetBrains Mono for rapid scanning.

### Status Indicators
- **Badges:** Small, pill-shaped containers with a background opacity of 15% of the status color and 100% opacity text for maximum readability.
- **Advance Booking:** A distinct "Premium" badge style for requested caddies, using a subtle gold border to signify special handling.

### Input Fields
- Underlined or softly bordered fields with clear labels. 
- Focus state uses a 2px Golf Green border.
- **Numeric Inputs:** Large, clear font for "Holes Played" (9/18) to prevent entry errors in the field.

### Payroll Tables
- Zebra-striping using a very faint Sand White tint. 
- **Locked State:** When a payroll period is closed, the entire table container gains a subtle gray overlay and a "Locked" icon in the header, disabling all inline editing.