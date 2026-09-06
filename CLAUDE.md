# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

The source for **yomonsni.com**, a coaching platform built by a hand-rolled
PHP site generator (`gen-site.php`) rather than any standard static-site
generator (no Jekyll/Hugo/11ty/etc). There is no build tooling, package
manager, linter, or test suite in this repo — there is nothing to `npm
install`/`build`/`test` at the top level. The only "commands" are the PHP
scripts described below.

The site offers coaching across six independent focus areas ("domains"):
**leadership**, **creativity**, **change** (life transitions),
**performance**, **intimate** (sex/intimacy/relationships/kink), and
**discovery** (self-discovery and growth). This is a rebuild — see "History
and current status" below before assuming any given page's content is real
rather than placeholder.

## Commands

```bash
# Generate the site: reads content/*, writes working/*
php gen-site.php

# Push the generated site to production (S3) — refuses to run unless the
# last gen-site.php run recorded a clean validation pass
php push-site.php
```

There is no watch mode or dev server. The dry-run *is* running
`gen-site.php` locally: after writing `working/`, it mirrors that output to
`/tmp/foo` specifically so the whole generated site can be browsed in a
browser before ever running `push-site.php`. This is intentional, not
leftover debugging — don't remove it.

`gen-site.php` also validates its own output (see "Guardrails and the
validator" below) and writes the result to `var/last-generation.json`.
`push-site.php` reads that file and refuses to sync if the last run had any
validation errors, so forgetting to check `/tmp/foo` before pushing doesn't
ship something broken.

## Content model: `+++`-fenced front matter, one shape for everything

For the friendly, example-driven version of everything in this section and
the next (aimed at someone writing content, not maintaining the engine),
see `CONTENT-GUIDE.md` — it replaced the old `page-variables` file and is
the place field names get documented for real; keep it in sync with any
change here.

Every human-authored content file under `content/` (a `.entry` file) has the
same two-part shape, parsed by `lib/entry.php`:

```
+++
key: value
key2: value
+++

Markdown body text — **bold**, [links](url), lists, headings, whatever
Parsedown supports.
```

- The fence is `+++`, deliberately not `---` — body content is Markdown,
  and Markdown's own horizontal-rule/setext-heading syntax uses a bare
  `---` line, so `+++` avoids colliding with real content.
- Front matter is unambiguous by *position* (everything between the two
  fences), not by matching known key names — a body paragraph can safely
  contain a line that looks like `key: value` without being misparsed.
  Front matter may legitimately be empty (see `content/footer.entry`).
- The body is converted to HTML by a vendored, single-file, zero-dependency
  Markdown library, `lib/Parsedown.php` (MIT license, license file
  alongside it) — the first vendored file in this repo that actually
  executes as part of the generator, as opposed to the front-end assets
  under `content/vendor/`, which are copied verbatim and never run.
- `entry_html($entry)` renders an entry's body; `field($entry, 'key',
  $default)` reads a front-matter field; `field_bool($entry, 'key')` reads
  a yes/no field (`yes`/`true`/`1`, any case, are true).

## Collections vs. one-off pages

Most content is **collections of records, filtered by domain and joined
into pages at generation time** — not the old one-file-to-one-page model.
`lib/entry.php`'s `load_collection($dir)` loads every `*.entry` file
directly inside a directory (collections are flat, never nested); domains
are self-contained via a `domain` field.

- `content/coaches/` — one file per (coach × domain), e.g.
  `jane--leadership.entry` and `jane--intimate.entry` are the *same real
  person*, two independent cards. Fields: `name`, `coach_id` (the stable
  join key — never rendered), `domain`, `photo`, `booking_link`,
  `fully_booked` (yes/no — shows a "Fully Booked" notice without clearing
  `booking_link`, unlike leaving that field blank), `order` (optional
  number controlling display order on that domain's page, lowest first —
  see `sort_by_order()`), `summary` (a sentence or two for the domain
  page's card; falls back to the bio's first sentence if omitted),
  `location` (optional — set only by coaches who take in-person clients
  in that domain; shown next to their name on the full profile page and
  gates whether an in-person booking button can appear at all),
  `in_person_booking_link`/`in_person_fully_booked` (same shape as
  `booking_link`/`fully_booked`, but for the in-person offering — the two
  tracks are independent, e.g. full online but still open in-person),
  `photo_side` (`left`/`right`, default `left` — which side the photo
  lands on, on both the teaser card and the full profile page; a
  deliberate manual per-card choice rather than an automatic alternation,
  so it can be A/B tested for booking rate rather than left to file
  order); body = that domain's full bio. **See "The coach-privacy
  design" below before adding or editing one of these.**
- `content/testimonials/` — `client` (first name only), `date`, `domain`,
  `coach` (a `coach_id`); body = the testimonial. Always exactly one domain
  and one coach — `filter_by_domain($testimonials, $slug, 'domain')` gets a
  domain's full list, `entries_for_coach(...)` narrows to one coach.
  Rendered as "Client of ..." under the quote, `coach` resolved and
  linked to that coach's profile in this domain via the same
  `coach_links_html()` helper an event's (possibly several) coaches use
  — passed a one-element list here, since a testimonial only ever has
  the one.
- `content/whispers/` — `title`, `coach`, `date`, `domains` (comma-
  separated — the one record type allowed to span multiple domains).
  `filter_by_domain($whispers, $slug, 'domains')` handles the list form.
  **The `domains` field is generation-time-only and must never be rendered
  to a visitor** — it only decides which domain page(s) the piece gets
  published under. A multi-tagged whisper becomes an independent generated
  page per tagged domain; none of those pages link to each other. `coach`
  is rendered "By ..." and linked via `coach_links_html()` the same way an
  event's coaches are (plain text when the coach has no card in whichever
  domain the whisper is currently rendering on). Like an event's body, a
  whisper's body can embed a photo via a raw `<img class="content-photo
  content-photo-left|right">` tag — no engine support needed, see the
  events entry below. A `teaser` that relies on the "first paragraph"
  fallback (`whisper-teaser-card.php`) breaks if the body opens with an
  image tag — write an explicit `teaser` whenever a whisper embeds one.
- `content/events/` — workshops and retreats together in one flat folder
  (originally two separate collections/nav items; merged since a visitor
  browsing "what's coming up" doesn't care which bucket a given date came
  from). Fields: `title`, `domain` (single), `coaches` (optional, comma-
  separated `coach_id`s — an event, unlike a testimonial, can have more
  than one; each is validated against that domain's coach cards the same
  way a testimonial's single `coach` is, and rendered as "With ..." with
  each name linking to that coach's profile page *within this domain*
  when they have one there, via `coach_links_html()`/`coach_has_profile()`
  in `lib/entry.php` — plain text, no link, when they don't), `type`
  (`workshop` or `retreat` — a display label only, doesn't affect where
  it's listed), `date` (plain text, not machine-parsed — events list in
  file-sort order, so filenames are chosen to sort the way they should
  read), `format` (free text — this is where "online" vs. "in-person"
  actually lives, not a separate field), `location` (optional — city/
  state or city/country, set only for an in-person event), `booking_link`
  (external registration link; independent of whether the event has a
  full page — see below); body = description. Purely data-driven: a
  domain "has events" simply because at least one record names it —
  `gen-site.php` only writes `events.html` for a domain when
  `filter_by_domain(...)` for this collection is non-empty. No separate
  on/off flag anywhere. Each event also gets its own full page
  (`event-<slug>.html`, `templates/event-article.php`) — unlike a
  whisper, an event lives in exactly one domain, so this is always a
  single generated page, never one per tagged domain. The Events-page
  teaser card (`templates/partials/event-card.php`) shows only an
  optional `summary` field (falling back to the body's first sentence,
  same convention as a coach's `summary`) — the full body is reserved for
  the event's own page, specifically so a body that embeds photos (see
  next) never gets dumped whole onto a teaser card. `booking_link` drives
  a Register button on both the teaser and the full page independently;
  "Learn More" (teaser only) always links through to the full page
  regardless of whether `booking_link` is set.
  **Embedding photos in an event's body:** there's no front-matter field
  or engine support for this — an entry's body is Markdown, and
  Parsedown is used unconfigured (`entry_html()` in `lib/entry.php`),
  which passes raw HTML in the body through untouched. So an in-person
  event showing off its venue just writes a plain `<img>` tag by hand in
  its body (`src="../img/..."`, same asset folder as everything else)
  with class `content-photo` plus `content-photo-left` or
  `content-photo-right` (myfunk.css) — floats the photo with body text
  wrapping around it, magazine-style, the same idea as
  `.coach-photo-left`/`-right` but sized for body content rather than a
  fixed headshot. `event-article.php` puts a `<div class="clearfix">`
  after the rendered body so a tall photo can't bleed into the Register
  button below it. See `content/events/2025-11-creativity-writing-
  intensive.entry` for a worked example with two images.

- `content/resource-items/` — a book, video, academic paper, article, or
  podcast a coach recommends, shown as a large card on that domain's
  resources page — this page is deliberately for anyone curious about
  the subject, not just people ready to book a session, so these are
  meant to be real recommendations, not a bibliography assembled to fill
  the page. Fields: `title`, `domain` (single), `type` (`book`, `video`,
  `paper`, `article`, `podcast` — picks the card's button label, e.g.
  "Watch Video" for `video`; anything else falls back to a generic "View
  Resource"), `coach` (optional — a `coach_id`, validated the same way a
  testimonial's `coach` is; rendered "Recommended by {first name}" via
  the new `first_name()` helper in `lib/entry.php`, linking to that
  coach's profile in this domain the same way an event's/testimonial's/
  whisper's coach does), `image` (optional cover/thumbnail filename under
  `content/img/`), `url` (external link; blank means no button at all);
  body = a short description, rendered via `entry_html()` like everything
  else.

Per-domain static content (not a collection): `content/<domain>/index.entry`
(the domain's own overview/outline copy, plus `hook`/`hook2` — two "mom
test" style questions shown on that domain's card on the hub page, the
second smaller/quieter as a deeper follow-up, no title/label on the card at
all) and `content/<domain>/resources.entry` (this domain's resources page —
its body is only the page's *intro copy*, not the resources themselves,
which are `content/resource-items/` entries filtered to this domain;
its `pointers` field is a `|`-separated list of short always-visible
bullets on the domain overview page's resources card, and its `teaser`
field — *plain text*, not Markdown, the one exception — is revealed by
that card's toggle rather than being "the one thing shown" the way it
used to work). Sitewide:
`content/index.entry` (hub copy), `content/mission.entry`/`coaching.entry`
(the hub nav's two destinations — their own `title` field is reused as
both the nav label and the page's own heading, so the two can't drift
apart), `content/footer.entry` (the generic ethical code/mission statement
shown in the footer on every page), `content/legal/terms.entry`,
`content/legal/privacy.entry`.

## The coach-privacy design (read this before touching coach content)

A coach may work across multiple domains, keeping the **same real name** in
each — nothing about their identity is meant to be hidden. What must never
happen is a site visitor *browsing the site* connecting a coach's presence
across domains. This is enforced architecturally, not just by convention:

- Every coach card is domain-scoped: a distinct **photo** and a distinct,
  domain-relevant **bio** per domain, never a shared paragraph reused
  verbatim. Availability is domain-scoped the same way — a coach can be
  `fully_booked` in one domain and completely bookable in another, since
  each domain's card is its own independent file.
- **No page anywhere lists coaches across domains** — no "meet the team,"
  no sitewide staff directory. This is the one rule that must never be
  broken.
- **No domain's nav links to another domain.** `templates/partials/
  domain-nav.php` only links within its own domain plus a single "← Home"
  link back to the root hub — never to a sibling domain. `templates/
  partials/hub-nav.php` (root only) is the one place all six domains are
  listed together (as a card grid on the hub page's body — the hub *nav*
  itself doesn't list them at all, just a Mission/Coaching link pair; see
  "Visual design" below).
- `lib/validate.php` enforces three pieces of this automatically:
  `validate_content_references()` flags a coach's photo being reused
  across more than one of their domain cards, `validate_output()` scans
  every generated page for a link crossing from one domain's directory
  into another's (other than the sanctioned home link), and
  `validate_sitemap()` fails the build if a coach-profile URL ever ends up
  in `sitemap.xml` — see "Guardrails and the validator" for why that file
  is a separate case from on-site navigation.
- **`sitemap.xml` is generated, but deliberately excludes coach-profile
  pages.** `gen-site.php`'s `write_page()` registers every page it writes
  into the sitemap except ones passed `$in_sitemap = false` (only the
  coach-profile loop does this). The reason this needs separate handling
  from the nav rule above: a sitemap is a flat manifest, not something a
  visitor navigates — even with zero on-site links between
  `leadership/coach-jane.html` and `intimate/coach-jane.html`, having both
  listed a few lines apart in one file hands over the connection to
  anyone who opens `/sitemap.xml`, no browsing required. Whisper pages are
  *not* excluded despite also spanning domains — a whisper's identical
  content across its tagged domains is already a deliberately accepted
  exposure (§4.2 of the restructure plan allows cross-tagging into
  `intimate`), not a new one a sitemap would introduce.

## Visual design (read before touching CSS/templates)

The visual language lives almost entirely in `content/css/myfunk.css`
(one file, no preprocessor) plus a handful of PHP templates. A few rules
apply everywhere, not just wherever they were first introduced:

- **Never blur a background image, anywhere on the site — no
  `backdrop-filter`, no `filter: blur()`, no blurred duplicate/thumbnail
  standing in for the real photo.** The specific photo chosen for a given
  page is a deliberate part of that page's aesthetic, not incidental
  texture; it must always render sharp, including where it shows through
  a translucent element (a card, a panel, an overlay). If something needs
  to look translucent, the only lever is opacity/color on the element
  itself — never touch the sharpness of what's behind it.
- **One continuous background photo per page, on `<body>`, nothing else.**
  `templates/partials/shell.php` sets it (fixed attachment, `cover` sized)
  when a page has a `bgimage`; the masthead/mastblank header on that same
  page carries `style="background: none;"` so its own CSS background
  (a leftover gradient+image rule from the pre-rebuild theme) doesn't
  paint a second, differently-scaled copy over part of the page. Don't
  reintroduce a masthead-level background image or gradient — both were
  tried and explicitly removed for exactly this reason (see git history
  around the hub page's masthead).
- **`#mainNav` is `fixed-top`, so any page shape needs *something*
  reserving room under it.** `hub.php` and `domain-overview.php` get this
  for free from their masthead/mastblank hero's own padding. Every other
  page shape (`coach-profile.php`, `card-list.php`, `simple-content.php`,
  `whisper-article.php`, `event-article.php`) has no hero and instead puts
  `.nav-clearance`
  (myfunk.css) on its outer `.container-fluid` — margin, not padding, so
  it isn't clobbered by Bootstrap's `!important` spacing utilities like
  `.p-3` on that same element. If you add a new page shape with no
  masthead/mastblank of its own, it needs this class too, or its content
  renders hidden underneath the navbar instead of below it.
- **Buttons are rounded/pill-shaped everywhere** (`border-radius: 2rem` on
  the shared `.btn` rule, not a one-off per button style) and use
  `.card-glass` for translucency (opacity only, per the no-blur rule
  above) — this is the site's default look, not something to redo per
  page.
- **Per-domain visual tuning is deliberately narrow.** `gen-site.php`'s
  `$domain_design` array is the only place a domain's look is allowed to
  diverge, and only along four axes: `bg` (background photo filename,
  defaults to a placeholder `<slug>-bg.jpg`), `text_align` (`'left'`
  default, `'right'` also supported — which side the intro column lands
  on), `card_class` (extra class on that domain's cards, e.g.
  `card-glass-transparent`), `button_class` (extra class on that domain's
  buttons, e.g. `btn-creativity`). The domain-overview *layout itself*
  (intro beside a Coaches/Resources/Whispers/Workshops stack, centered
  section headings, the coach card design) is **not** part of this
  config — it's the same for every domain by default; every domain now
  has its own `bg`/`button_class` (see "History and current status"), and
  only creativity currently also overrides `card_class` (fully
  transparent cards, vs. everyone else's standard translucent). Every
  domain gets the two-column layout whether or not it has an entry in
  `$domain_design` at all (confirmed by generating leadership before it
  had one). `domain_design($domain_design, $slug, $key, $default)` is the
  lookup helper; `$button_class`/`$card_class` get threaded through
  *every* page a domain has (overview, resources, testimonials, coach
  profiles, whispers, events) via `write_page()` calls and the
  card partials' own optional `$card_class`/`$button_class` params — if
  you add a new per-domain-styled element, thread it through all of these
  call sites, not just the overview page's; that gap (styling only
  reaching the overview page) has already caused a visible inconsistency
  bug once.
- **The site's only JavaScript** is `content/js/toggle-expand.js` — a
  small, dependency-free expand/collapse: any element with class
  `toggle-more` and a `data-target` pointing at another element's `id`
  shows/hides that element and rotates a chevron icon inside the button.
  Used by a coach card's "read more" (reveals the full bio) and the
  resources card's toggle (reveals its `teaser`). No jQuery, no Bootstrap
  JS bundle — deliberately, since one interaction didn't justify pulling
  either in.

## The eight page shapes (`templates/`)

Templates are plain PHP files, included with a set of variables in scope
(`lib/render.php`'s `render_template()`) — this is the entire templating
mechanism, not a placeholder-substitution language. `templates/partials/
shell.php` wraps every page (doctype/head/nav/footer, plus the
`toggle-expand.js` include); each shape below supplies just its own inner
content.

1. **`templates/hub.php`** — root landing page. One-off; links to all six
   domains via a card carrying no label/title, just `hook`/`hook2` fields
   pulled from each domain's own `index.entry` — two "mom test" style
   questions (simple, personal, no jargon), the second smaller and quieter
   as a deeper follow-up, meant to pull a visitor in by resonance rather
   than by describing the service. `templates/partials/hub-nav.php` (used
   here and on the root-level mission/coaching/terms/privacy pages) does
   *not* list the domains — just a Mission/Coaching link pair, sourced
   from those two pages' own `title` fields so the nav label and the
   page's own heading can't drift apart, and each omitted on that page's
   own nav (rendered per page via `gen-site.php`'s `hub_nav_for()`, not
   one nav shared verbatim across all five root pages, precisely so each
   can drop its own item — no point linking somewhere you already are,
   same reasoning `domain-nav.php` never links a domain to itself). The
   brand slot links home (`index.html`), showing the hub's own `title`
   field — same convention `domain-nav.php` uses for its own brand —
   rather than linking to `mission.html` the way it originally did (that
   made the mission page's own top-left link to itself, with no way back
   to the hub from the nav at all).
2. **`templates/domain-overview.php`** — a domain's front door. Composite,
   two-column by default: intro copy on its `text_align` side (~2/3
   width), a `Coaches → Resources → Whispers → Events` stack
   on the other (~1/3) — Coaches deliberately first, so a visitor reaches
   a coach's profile/booking link without scrolling past the whole intro.
   `order-md-first`/`order-md-last` keeps the intro first in the actual
   markup regardless of which side it lands on visually. Resources renders
   via `templates/partials/resources-card.php` (pointers + toggle + link
   through to the full resources page), matching the look of the
   coach/whisper/event cards next to it.
3. **`templates/coach-profile.php`** — one per coach card. Same layout for
   every domain; only the entry's own data and that domain's testimonials
   for that coach differ. Name, location (if set), bio and booking
   buttons sit in one column beside the photo in another — top-aligned
   with each other, side controlled by the coach's own `photo_side`
   field (see above) via `order-md-first`/`order-md-last`, the same
   convention `domain-overview.php` uses for its intro/aside columns.
   The outer container carries a `.nav-clearance` class (see
   `myfunk.css`) purely to reserve room under the fixed `#mainNav` — this
   page shape has no masthead/mastblank hero the way
   `domain-overview.php`'s title header does, so without it the
   name/photo would render hidden underneath the navbar instead of below
   it. An online booking button (a booking link renders a Book button
   unless `fully_booked` is set, in which case, or with no
   `booking_link` at all, a non-interactive `.btn-unavailable` shows
   instead) sits alongside a second, independent in-person booking button
   that only appears at all when the coach has set `location` — not every
   coach takes in-person clients. The teaser card
   (`templates/partials/coach-card.php`) still shows only the single
   online Book-or-Fully-Booked button — it hasn't been extended to the
   in-person fields, since a domain-overview card's summary/space budget
   doesn't have room for a second button; the full profile page is the
   one source of truth for a coach's actual availability.
   `coach-card.php`'s layout floats the photo to one side (per the same
   `photo_side` field, so a coach's side choice is consistent between
   their teaser and their full page) so the name and summary text wrap
   around it, magazine-style, rather than sitting in a centered row above
   the text.
   Below the name/photo row, this coach's testimonials ("What clients
   have said about ...") and their whispers tagged into this domain
   ("What ... has written") each render inside their own outer
   `.card-glass` panel wrapping a bounded-height, scrollable stack of
   nested cards (`.card-scroll-box`/`.card-glass-nested`, `myfunk.css`) —
   "a card within a card" — rather than a grid that grows the page as
   more of either pile up. `testimonial-card.php`/`whisper-teaser-card.php`
   both take an optional `$nested` param that swaps their normal
   `.col-sm` grid wrapper (used by `card-list.php`'s own testimonials/
   whispers pages) for this plain full-width nested-card form; `$card_class`
   is ignored when nested, since the point of `.card-glass-nested` is
   contrast against the panel holding it, not per-domain theming.
4. **`templates/simple-content.php`** — plain prose, no collection data:
   terms, privacy, mission, coaching.
5. **`templates/card-list.php`** — one reusable "list of short cards"
   shape, reused for a domain's testimonials, whisper teasers, and events
   (workshops and retreats together — one collection, one nav item, one
   listing page; see the Content model section above). The caller
   pre-renders each item with the matching card
   partial (`templates/partials/{coach,testimonial,whisper-teaser,event}-
   card.php`, all using `.card-glass` plus each accepting the optional
   `$card_class`/`$button_class` per-domain overrides) and hands this
   template the finished HTML fragments — it doesn't know which
   collection it's listing. **Every card partial's card-body is a flex
   column (`d-flex flex-column`) with its button(s) pinned to the bottom
   via `mt-auto`** — a standing rule, not just the event card's own
   Register/Learn More buttons — so buttons line up across a row of cards
   regardless of how much text precedes them (same trick `hub.php`'s
   domain cards use for their Explore button). When the button is a
   direct flex child rather than sitting in its own wrapping row div
   (`whisper-teaser-card.php`, `resources-card.php`), it also needs
   `align-self-start` or flex's default cross-axis stretch spreads it to
   the card's full width. A wrapping div (`coach-card.php`'s Book/Full
   profile row, the event card's Register/Learn More row) can add `pt-3`
   alongside `mt-auto` for a guaranteed minimum gap above the buttons —
   but that only belongs on the wrapping div; putting `pt-3` directly on
   a bare `<a class="btn">` pads the button's own interior instead of the
   space above it. Any new card partial needs this same treatment.
6. **`templates/whisper-article.php`** — one whisper's full page, generated
   once per domain it's tagged into.
7. **`templates/event-article.php`** — one event's full page, linked from
   its teaser's "Learn More" button. Unlike a whisper, an event lives in
   exactly one domain, so this is always a single generated page
   (`event-<slug>.html`), never one per tagged domain. Shows the same
   type/date/format/location meta line and linked coach attribution as
   the teaser, plus the full description and (if `booking_link` is set)
   a Register button — the teaser's own Register button and this page's
   are independent, both driven by the same `booking_link`.
8. **`templates/resources-page.php`** — a domain's full resources page.
   Composite like `domain-overview.php`: `resources.entry`'s own body as
   framing/intro copy, then a grid of large resource cards
   (`templates/partials/resource-card.php` — singular, one per resource
   item; not to be confused with `resources-card.php` — plural, the
   compact Resources teaser on the domain overview page, which is
   unrelated and unchanged) pulled from `content/resource-items/`,
   filtered to this domain. Two per row (`col-md-6`, deliberately
   larger/fewer-per-row than the three-across testimonial/event grids),
   each with an optional cover image on top (`.resource-cover`,
   `object-fit: cover`, clipped to the card's rounded corners via
   `.card-glass`'s own `overflow: hidden`), a type/coach-attribution
   subtitle ("Book · Recommended by Sam", the coach linked the same way
   as elsewhere), the description, and a type-specific button label
   ("Watch Video", "Read Paper", etc., falling back to "View Resource").
   This page is explicitly meant for anyone curious about the subject,
   not just people ready to book — see its own intro copy.

A domain with zero coaches (currently `change`, `performance`,
`discovery`) skips the Coaches heading/stack entirely (`if (!empty
($coaches))` in `domain-overview.php`) — this path runs cleanly through
generation and validation for all three, but hasn't been individually
screenshotted the way leadership/creativity/intimate have.

## Guardrails and the validator (`lib/validate.php`)

Two passes, both wired into `gen-site.php`:

- **`validate_content_references()`** runs on the loaded collections
  *before* any HTML is written: a testimonial's `coach` must resolve to a
  real coach card in that domain, a whisper's `domains` must all be real
  domain slugs, and no coach photo may be reused across domains. Any error
  here aborts generation — nothing gets written.
- **`validate_output()`** runs after generation, by walking every
  generated `.html` file: every internal link must resolve to a real file,
  every internal link must be relative (a root-relative `/foo.html` or an
  absolute `https://yomonsni.com/...` link is an error — internal links
  must always be relative), no link may cross from one domain's directory
  into another's, every image must resolve, and every external link (a
  coach's booking link, say) is checked for liveness via `curl`, cached in
  `var/link-cache.json` for a week so routine local generation doesn't
  hammer third-party sites on every run. A dead external link is a
  warning, not an error; everything else here is a hard error.

Both passes were verified against deliberately-broken fixtures during
development — they're not just written, they fire. Note: neither checks
inline CSS `background-image: url(...)` references (only `<img>`/`href`),
which is exactly how a previous `shell.php` bug — a body background
falling back to a `default-bg.jpg` that didn't exist — went undetected
until traced by hand.

## Repository layout

- `CONTENT-GUIDE.md` — the content-authoring reference (field-by-field, with
  copy-pasteable templates), for anyone writing a coach card, testimonial,
  whisper, or event listing. Replaced `page-variables`, which documented
  the old engine's tokens and no longer applied to anything. Keep it in
  sync with the Content model section above — it's the friendlier version
  of the same fields.
- `content/` — the live source of truth: the six domains' own directories,
  the five collection directories, `legal/`, the sitewide
  `index.entry`/`mission.entry`/`coaching.entry`/`footer.entry`, and the
  non-templated asset directories (`css/`, `js/` — just `toggle-expand.js`
  —, `scss/`, `vendor/`, `img/`, `doc/`) copied verbatim by the generator.
- `lib/` — the engine's PHP: `entry.php` (parsing/collections/sorting),
  `render.php` (templating), `validate.php` (guardrails), and the vendored
  `Parsedown.php`.
- `templates/` — the eight page shapes and their partials. Deliberately kept
  out of `content/` so the human-authored/engine-owned split is a real
  directory boundary, not just a convention.
- `working/` — generated output (gitignored, wiped and rewritten on every
  `gen-site.php` run).
- `var/` — runtime state (gitignored): `link-cache.json` (external-link
  liveness cache) and `last-generation.json` (the status `push-site.php`
  gates on).
- `toolset/` — vendored, unmodified upstream sources for Bootstrap 4.3.1
  and two Start Bootstrap themes (`business-casual`, `grayscale`). Nothing
  in this repo builds from `toolset/`; the actual assets in use live under
  `content/{css,js,scss,vendor}`. Reference material only.
- `Testimonials - Originals/` — source `.odt` documents, not consumed by
  the generator.
- `docs/coaching-site-restructure-plan.md` — the design doc the engine was
  originally built from; check its progress checklist for what's done vs.
  still open before assuming the current state matches every detail there
  — a lot of the subsequent visual-design work (this file's "Visual
  design" section) happened after that doc's own checklist was last
  updated and isn't reflected in it.
- `docs/improvement-plan-to-review.md` — superseded by the restructure
  plan for everything about the old `.con`/`.skel` engine; its still-live
  requirement (all internal links must be relative) is now enforced by the
  validator rather than being an aspiration.

## History and current status

This is a rebuild from an earlier personal-wellness site (see git log
before the `coaching-rebuild` branch for that version's `.con`/`.skel`
engine, now fully replaced). Current state:

- The engine and the current visual design (this file's descriptions
  above) are real and working, verified with headless-Chrome screenshots
  throughout development, not just by reading the CSS/templates.
- **Content is a mix of real and placeholder, domain by domain.**
  Creativity has a real ~1500-word overview piece, three real (if
  fictional) coaches with real-shaped bios/summaries, a real
  resources-card pointer list, and three real (if fictional)
  `content/resource-items/` entries (a book, a video, a paper) with a
  placeholder cover image on one of them — it's the domain the visual
  design was built and iterated against. Leadership and intimate each have one
  coach (the same person, Jane Doe, with independent domain-scoped cards
  — used to demonstrate the coach-privacy design and per-domain
  `fully_booked`). Change, performance, and discovery have no coaches at
  all yet and only their original short placeholder intro copy. Don't
  treat any of it as launch-ready; treat creativity as the pattern the
  other five still need to follow, not as finished.
- Every domain and the hub now use a real photo as their background (all
  six domains' choices in `gen-site.php`'s `$domain_design`, picked by the
  user directly rather than sampled unsupervised — see "Visual design"
  above for why; the hub's was traced from the live pre-rebuild site's
  actual behavior, see git history). **Coach photos are still placeholder
  color blocks** (generated with ImageMagick) — that hasn't changed.
- Booking links point at example.com-style placeholder URLs, which the
  validator correctly flags as dead — expected until real links exist.
  (One coach card, Jane's intimate one, is deliberately `fully_booked`
  with a real-shaped placeholder link still attached, to demonstrate that
  field.)
- Of the root-level pages, only `mission.html` has its own background
  photo so far (`yo-IMG_57108-5DII-raw32-rawtherapee-shaped.jpg`, set via
  `gen-site.php`'s `$root_page_bgimages` — the same per-page-lookup
  pattern as `$domain_design`, just keyed by page slug instead of
  domain). `coaching.html`/`terms.html`/`privacy.html` still don't have
  one, picked up one page at a time rather than all at once.

## Known gaps / open decisions

- Content for five of the six domains — see "History and current status"
  above for exactly what each domain has vs. still needs. Creativity is
  the reference pattern to extend, not a one-off.
- Root-level pages: `mission.html` now has a background photo (see
  "History and current status" above); `coaching.html`/`terms.html`/
  `privacy.html` still don't, unlike every other page on the site.
- `push-site.php` depends entirely on the operator's local `aws` CLI
  credentials — none are configured in this repo, which is correct.

See `docs/coaching-site-restructure-plan.md` for the original design
reasoning this was built from, and git log for the visual-design
iteration since (a long sequence of small, verified changes — the commit
messages are the more detailed record of *why* any given CSS/template
choice looks the way it does).
