---
name: OSIS Digital · OSIM MTsN 2 Kota Malang
description: Kanal kabar dan bilik suara OSIM, dibangun dari kartu peserta madrasah.
colors:
  hijau-50: "#f2f8f3"
  hijau-100: "#e3f1e6"
  hijau-200: "#c9e8d2"
  hijau-300: "#9fd3ae"
  hijau-500: "#13894a"
  hijau-600: "#0f7a3d"
  hijau-700: "#0c6633"
  hijau-800: "#0a5a2d"
  hijau-900: "#063d22"
  hijau-950: "#04291a"
  emas-200: "#fff0a8"
  emas-300: "#ffe066"
  emas-400: "#f2c200"
  emas-500: "#d9a800"
  cap: "#d7261e"
  tinta: "#14261b"
  tinta-2: "#3d5c48"
  garis: "#d3e4d7"
  grafik: "#13894a"
  grafik-grid: "#dbe9de"
  white: "#ffffff"
typography:
  display:
    fontFamily: "'Archivo Variable', ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(1.85rem, 8.4vw, 3.75rem)"
    fontWeight: 900
    lineHeight: 0.95
    letterSpacing: "-0.02em"
    fontVariation: "'wdth' 112"
  headline:
    fontFamily: "'Archivo Variable', ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.875rem"
    fontWeight: 900
    lineHeight: 1.25
    letterSpacing: "-0.02em"
    fontVariation: "'wdth' 112"
  title:
    fontFamily: "'Archivo Variable', ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 800
    lineHeight: 1.25
    letterSpacing: "-0.02em"
    fontVariation: "'wdth' 112"
  numeral:
    fontFamily: "'Archivo Variable', ui-sans-serif, system-ui, sans-serif"
    fontSize: "4.5rem"
    fontWeight: 900
    lineHeight: 1
    letterSpacing: "-0.02em"
    fontFeature: "'tnum'"
    fontVariation: "'wdth' 112"
  body:
    fontFamily: "'Figtree Variable', ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
  body-sm:
    fontFamily: "'Figtree Variable', ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.43
  label:
    fontFamily: "'Archivo Variable', ui-sans-serif, system-ui, sans-serif"
    fontSize: "11px"
    fontWeight: 700
    lineHeight: 1.4
    letterSpacing: "0.14em"
    fontVariation: "'wdth' 75"
  arab:
    fontFamily: "'Reem Kufi', 'Archivo Variable', sans-serif"
    fontWeight: 500
    lineHeight: 1
rounded:
  chip: "6px"
  tab: "8px"
  control: "12px"
  kartu: "14px"
  pill: "9999px"
spacing:
  "1": "4px"
  "2": "8px"
  "3": "12px"
  "4": "16px"
  "5": "20px"
  "6": "24px"
  "8": "32px"
  "10": "40px"
components:
  button-primary:
    backgroundColor: "{colors.emas-400}"
    textColor: "{colors.hijau-950}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "12px 20px"
  button-primary-hover:
    backgroundColor: "{colors.emas-300}"
    textColor: "{colors.hijau-950}"
  button-hijau:
    backgroundColor: "{colors.hijau-600}"
    textColor: "{colors.white}"
    rounded: "{rounded.control}"
    padding: "12px 20px"
  button-hijau-hover:
    backgroundColor: "{colors.hijau-700}"
  button-kertas:
    backgroundColor: "{colors.white}"
    textColor: "{colors.hijau-800}"
    rounded: "{rounded.control}"
    padding: "12px 20px"
  button-kertas-hover:
    backgroundColor: "{colors.hijau-50}"
  button-terkunci:
    backgroundColor: "{colors.hijau-100}"
    textColor: "{colors.hijau-800}"
    rounded: "{rounded.control}"
    padding: "12px 20px"
  kartu:
    backgroundColor: "{colors.white}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.kartu}"
    padding: "24px"
  kartu-kepala:
    backgroundColor: "{colors.hijau-100}"
    textColor: "{colors.hijau-800}"
    typography: "{typography.label}"
    padding: "10px 24px"
  pita:
    backgroundColor: "{colors.hijau-600}"
    textColor: "{colors.white}"
  tab-mading:
    backgroundColor: "{colors.emas-400}"
    textColor: "{colors.hijau-950}"
    rounded: "{rounded.tab}"
    padding: "8px 14px 6px"
  tab-mading-hijau:
    backgroundColor: "{colors.hijau-600}"
    textColor: "{colors.white}"
    rounded: "{rounded.tab}"
    padding: "8px 14px 6px"
  chip-kategori:
    backgroundColor: "{colors.hijau-100}"
    textColor: "{colors.hijau-800}"
    typography: "{typography.label}"
    rounded: "{rounded.chip}"
    padding: "2px 8px"
  chip-sorot:
    backgroundColor: "{colors.emas-400}"
    textColor: "{colors.hijau-950}"
    typography: "{typography.label}"
    rounded: "{rounded.chip}"
    padding: "2px 8px"
  nav-aktif:
    backgroundColor: "{colors.emas-400}"
    textColor: "{colors.hijau-950}"
    rounded: "{rounded.pill}"
    padding: "8px 16px"
  input-field:
    backgroundColor: "{colors.hijau-50}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.control}"
    height: "48px"
    padding: "0 16px"
---

# Design System: OSIS Digital · OSIM MTsN 2 Kota Malang

## Overview

**Creative North Star: "Kartu Peserta Madrasah"**

Every screen is a card the student holds: a white card stamped out of a deep madrasah-green band, with a printed header strip, small spaced capitals naming each field the way a student ID does ("NO. URUT", "DIBUKA", "KODE BUKTI"), a giant tabular number, a dashed tear line with half-moon notches, and a red round stamp once the vote is cast. The system answers to the madrasah's own logo: green shield, yellow wings, red-and-yellow frame, the *Iqra'* calligraphy.

Pages follow one structure. A full-width green ribbon (the header continues into a page band with a faint guilloche wave print) carries the page's display headline, and the first card pushes up into it from below with a negative overlap. Under it sits the "mading" (wall board): dense white modules hung from small coloured tabs. The layout stays light and phone-first: no photo banners, no hero imagery, no sidebars. Density comes from ruled lists with date blocks, not from cards nested in cards.

The build rejects the stock school portal (photo banner, grey-white cards, sidebar) and the stock SaaS look of the starter kit; neutral zinc/grey is not part of the student-facing world.

**Key Characteristics:**
- A green ribbon owns the head of every page, and a card overlaps it from below.
- White cards with a 14px radius, a 1px green-grey hairline, and a deep green-tinted drop.
- Field labels in narrow, spaced capitals; values set bold in ink green.
- Giant tabular numerals for ballot numbers, dates and percentages.
- Yellow is for the one primary action, the active nav state, and the main mading tab.
- Red appears only as the stamp and as error text.

## Colors

The palette is madrasah green in a full ramp, wing gold for action, frame red for the stamp, and a green-tinted ink and hairline in place of neutral grey.

### Primary
- **Madrasah Green** (hijau-600): the ribbon, header, green mading tab, secondary buttons, selected ballot card ring. Also the `theme-color`.
- **Deep Shield Green** (hijau-700 / hijau-800): giant numerals, label text on pale strips, hover state of green buttons, link hover.
- **Night Green** (hijau-900 / hijau-950): text on gold, the header strip of the official winner card, numeral tiles in the results chart, shadow tint (rgb 4 41 26).
- **Pale Leaf** (hijau-100 / hijau-200 / hijau-300): card header strips, category chips, the locked "already voted" button, muted text on the ribbon (hijau-100/200), empty-state icons (hijau-300).
- **Card Ground** (hijau-50): page background behind the cards, the fill of the perforation notches, input fill.

### Secondary
- **Wing Gold** (emas-400): the primary action button, active nav pill, main mading tab, "BARU"/"Terpilih" chips, focus ring, skip link.
- **Bright Gold** (emas-300): hover state of gold buttons; the highlighted half of display headlines on the ribbon ("Budi!", "satu suara."); Arabic salutation; text selection.
- **Pale Gold** (emas-200): slogan quote panels (at 60%), today's agenda row.
- **Deep Gold** (emas-500): the tinted ledge shadow under gold buttons; trophy icons.

### Tertiary
- **Frame Red** (cap): the round stamp (stempel), plus error messages, invalid field borders and destructive text buttons.

### Neutral
- **Ink** (tinta): all body and heading text on white.
- **Ink Soft** (tinta-2): secondary text, field labels, meta lines. Never pure grey.
- **Hairline** (garis): card ring, list dividers, perforation dash, input border.
- **Paper** (white): card and module surfaces.
- **Chart** (grafik / grafik-grid): the single vote-share series and its track.

### Named Rules
**The Stamp Red Rule.** Frame red is the stamp's ink. Outside the stamp it only marks an error or a destructive action. It never decorates, never fills a button, never appears on a chip.

**The One Gold Action Rule.** A view has at most one gold button. Other actions are green, white-with-hairline, or the dashed locked state.

**The Green-Tinted Neutral Rule.** Text, lines and shadows use the green-tinted ink, hairline and rgb(4 41 26) shadow tint. Zinc, grey and black do not appear in student-facing screens.

## Typography

**Display Font:** Archivo Variable, width axis widened to 112 (with ui-sans-serif, system-ui)
**Body Font:** Figtree Variable (with ui-sans-serif, system-ui)
**Label Font:** Archivo Variable, width axis narrowed to 75
**Arabic:** Reem Kufi 500/700, used for السَّلَامُ عَلَيْكُمْ on the ribbon and اِقْرَأْ in the footer

**Character:** a wide, very heavy grotesque does the shouting (greetings, titles, ballot numbers), and its own condensed cut prints the small field labels, so headline and label come from one typeface like a printed card. Figtree keeps reading text friendly and clear for 12-15-year-olds on cheap phones.

### Hierarchy
- **Display** (900, clamp 1.85rem→3.75rem, lh 0.95): the ribbon greeting on Beranda. Split across two lines, with the second line in Bright Gold.
- **Headline** (900, 1.875rem, rising to 3rem on ribbon page titles, lh 1.25): page h1s on the ribbon, card h1s (Masuk, error titles), section heads on the page ground ("Sorotan Program Kerja").
- **Title** (800-900, 1.25-1.5rem): card titles, program names, the election title inside the voting card.
- **Numeral** (900, 4.5-6rem in cards; 7-10rem for the winner and error codes; lh 0.8-1, tabular): ballot numbers, winner number, HTTP code. Smaller numerals (1.5rem, 900) for list date blocks and percentages.
- **Body** (Figtree 400, 1rem; 1.125rem with relaxed leading in article bodies, max 70ch): reading text. **Body-sm** (0.875rem) for summaries and meta.
- **Label** (Archivo narrow 700, 11px, uppercase, tracking 0.14em; 10px inside the number box): card field labels, card header strip names, chips, month under a date.

### Named Rules
**The Field Label Rule.** Spaced narrow capitals name a field and sit directly above its value (a `dt`), or they name the card in its header strip. They never float above a section heading as an eyebrow.

**The Tabular Number Rule.** Every number a student compares (ballot number, time, date, vote count, percentage) is tabular.

## Layout

There is one centred column, `max-width 72rem` (narrower at 48rem/36rem for reading and receipts), with 16px side padding (24px from 640px). The header is 64px tall. The ribbon has 24-40px of top padding and 64-112px of bottom padding, and the first card is pulled up into it by -64px to -80px. Spacing runs on a 4px base. Structure uses 16/24/40px steps: 40px between page sections, 24px gaps in grids, 20-24px card padding (32px on wide card bodies). Half-steps (6, 10, 14px) are used inside small controls.

The mading on Beranda is a 3-column grid at 1024px+ (announcements span 2, agenda 1) and stacks below that. On phones a fixed bottom tab bar (4 items, icon in a 28×48 pill) replaces the header nav; main gets 112px of bottom padding to clear it. The auth layout splits 1.1fr/1fr at 1024px, with the ribbon on the left.

## Elevation & Depth

Depth is physical. Cards are paper lifted off the green-tinted ground with one long, soft, green-tinted drop. Gold buttons sit on a short tinted ledge, like a raised key. Lists and modules inside the page are flat, with only a hairline ring. There are no hard offset shadows and no grey shadows.

### Shadow Vocabulary
- **Card Lift** (`0 18px 40px -18px rgb(4 41 26 / 0.5)`): every primary card (voting card, receipt, login, article, error, winner).
- **Ballot Card Rest** (`0 2px 10px -3px var(--color-garis), 0 10px 24px -14px rgb(4 41 26 / 0.35)`): unselected candidate cards.
- **Ballot Card Selected** (`0 14px 30px -12px rgb(15 122 61 / 0.55)` + 3px hijau-600 ring): the chosen candidate.
- **Gold Ledge** (`0 4px 10px -3px var(--color-emas-500)`): gold buttons. Mulai Memilih adds `0 8px 18px -6px rgb(4 41 26 / 0.45)`.
- **Green Ledge** (`0 4px 10px -3px var(--color-hijau-900)`): green action buttons.
- **On-Ribbon Lift** (`0 3px 10px -3px rgb(0 0 0 / 0.15)`): the active nav pill and the Masuk pill on the green header.

### Named Rules
**The Paper Over Green Rule.** Only the primary card and buttons cast shadows. Modules in the mading stay flat with a hairline ring.

## Shapes

- **Kartu** (14px): all cards and modules. A module hung from a tab drops its top-left radius to 0 so the tab joins it.
- **Control** (12px): buttons, inputs, the number box, info chips on the ribbon.
- **Tab** (8px top corners only): mading tabs; sosmed tabs use 12px top corners.
- **Chip** (6px): category and status chips, passport photo.
- **Pill** (full): nav items, avatar, status pills for work programs, chart bars.

Signature forms: the **perforation** (a 2px dashed hairline with 22px half-moon notches cut in the page-ground colour at both ends) separates the stub of a card from its body. The **passport photo** slot is 3:4 with four triangular photo corners. The **stamp** is a round double ring with arched text and a red band, rotated -12°.

## Components

### Buttons
- **Shape:** 12px corners, 12px×20px padding, bold body type, icon + label, `inline-flex` with an 8px gap.
- **Primary (gold):** Wing Gold on Night Green text with the Gold Ledge. Hover moves to Bright Gold and lifts 2px; active sinks 2px and the ledge shortens. The arrow nudges right on hover.
- **Green:** Madrasah Green, white text, Green Ledge; hover Deep Shield Green.
- **Paper:** white, hijau-800 text, hairline ring; hover hijau-50.
- **Locked:** after voting, the action becomes a pale-green button with a 2px dashed hijau-300 border reading "Anda sudah memilih".
- **Focus:** a 3px Wing Gold outline with a 2px offset, applied globally to everything under `.osim`.

### Chips
- **Category:** hijau-100 / hijau-800, narrow label type, 6px corners.
- **Highlight:** Wing Gold / Night Green ("BARU", "Terpilih", "HARI INI").
- **Status (program):** full pill, same label type.

### Cards / Containers
- **Corner Style:** 14px.
- **Background:** white on the hijau-50 ground.
- **Shadow Strategy:** Card Lift for primary cards; hairline only for mading modules.
- **Border:** a 1px garis ring.
- **Header strip:** every primary card opens with a hijau-100 band (hijau-900 for the official winner) that holds the card's name in label type and the madrasah logo or a status on the right.
- **Internal Padding:** 20px, or 24-32px from 640px up.

### Inputs / Fields
- **Style:** 48px tall, 12px corners, 2px hairline border, hijau-50 fill turning white on focus, ink text, placeholder in tinta-2 at 60%.
- **Focus:** the border changes to Madrasah Green.
- **Error:** a Frame Red border, with a bold red message and an exclamation icon below.
- **Search on ribbon:** a white 12px field with a green magnifier icon and a soft lift.

### Navigation
- **Desktop:** pills on the green header. Inactive items are hijau-50 text with a white/10 hover; the active item is a Wing Gold pill with Night Green text. Each has a micro icon and semibold 14px text.
- **Mobile:** a fixed white/95 bottom bar with blur and a hairline top border, 4 equal items, 11px semibold labels. The active icon is solid inside a gold pill.
- **Footer (desktop):** white with a hairline top border; the madrasah slogan in italics and اِقْرَأْ in green Kufi.

### Mading Tab Module (signature)
A small heading tab (gold for the primary module, green for the secondary one) with a micro icon, sitting directly on a white 14px module with a square top-left corner. Inside are ruled rows: a tabular date block (2xl black numeral above a narrow month label), chips, a bold title, and a 2-line summary.

### Ballot Card (signature)
The candidate card is a pale header strip "KARTU PASANGAN CALON", a passport photo beside a hijau-50 number box with a giant hijau-700 numeral, a 2-column definition list of names, an optional pale-gold slogan, then the perforation and the action stub. When selected, the strip turns Madrasah Green with a "Pilihanmu" mark, and the card takes the 3px green ring.

### Stamp (signature)
The red round stamp is applied to the receipt and to the official winner card. It enters with `osim-cap` (520ms, cubic-bezier(0.16, 1, 0.3, 1), scaling down from 1.6 and settling at -12°) only when reduced motion is not requested. It uses multiply blending at 90% opacity.

## Do's and Don'ts

### Do:
- **Do** open every student-facing page with the hijau-600 guilloche ribbon and overlap the first card into it (-64 to -80px).
- **Do** give primary cards a hijau-100 header strip naming the card in label type, with the madrasah logo on the right.
- **Do** set field labels in narrow 11px capitals (tracking 0.14em) directly above a bold ink value.
- **Do** separate a card's action stub with the perforation, and set its notch colour to the ground behind the card.
- **Do** use tabular Archivo 900 for every compared number.
- **Do** keep text, lines and shadows in the green-tinted ink, hairline and rgb(4 41 26) tint.
- **Do** keep the 3px gold focus outline on every interactive element.

### Don't:
- **Don't** use Frame Red for anything except the stamp, errors and destructive actions.
- **Don't** place more than one gold button in a view.
- **Don't** float a spaced-capital label above a section heading as an eyebrow; labels name fields or card strips only.
- **Don't** introduce zinc, grey or black neutrals, or hard offset shadows, into the student world.
- **Don't** add photo banners, hero imagery or sidebars; the ribbon and the card carry the page.
- **Don't** recreate the OSIM logo; use only the official madrasah logo file.
