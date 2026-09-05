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
  $default)` reads a front-matter field.

## Collections vs. one-off pages

Most content is **collections of records, filtered by domain and joined
into pages at generation time** — not the old one-file-to-one-page model.
`lib/entry.php`'s `load_collection($dir)` loads every `*.entry` file
directly inside a directory (collections are flat, never nested); domains
are self-contained via a `domain` field.

- `content/coaches/` — one file per (coach × domain), e.g.
  `jane--leadership.entry` and `jane--intimate.entry` are the *same real
  person*, two independent cards. Fields: `name`, `coach_id` (the stable
  join key — never rendered), `domain`, `photo`, `booking_link`; body =
  that domain's bio. **See "The coach-privacy design" below before adding
  or editing one of these.**
- `content/testimonials/` — `client` (first name only), `date`, `domain`,
  `coach` (a `coach_id`); body = the testimonial. Always exactly one domain
  and one coach — `filter_by_domain($testimonials, $slug, 'domain')` gets a
  domain's full list, `entries_for_coach(...)` narrows to one coach.
- `content/whispers/` — `title`, `coach`, `date`, `domains` (comma-
  separated — the one record type allowed to span multiple domains).
  `filter_by_domain($whispers, $slug, 'domains')` handles the list form.
  **The `domains` field is generation-time-only and must never be rendered
  to a visitor** — it only decides which domain page(s) the piece gets
  published under. A multi-tagged whisper becomes an independent generated
  page per tagged domain; none of those pages link to each other.
- `content/workshops/`, `content/retreats/` — `title`, `domain` (single),
  `date`, `format`, `booking_link`; body = description. Purely data-driven:
  a domain "has workshops" simply because at least one record names it —
  `gen-site.php` only writes `workshops.html`/`retreats.html` for a domain
  when `filter_by_domain(...)` for that collection is non-empty. No
  separate on/off flag anywhere.

Per-domain static content (not a collection): `content/<domain>/index.entry`
(the domain's own overview/outline copy) and `content/<domain>/resources.entry`
(full resources page; its `teaser` field is a *plain-text* short hook shown
on the domain overview page — not Markdown, unlike everything else here,
which is a known inconsistency worth resolving). Sitewide:
`content/index.entry` (hub copy), `content/footer.entry` (the generic
ethical code/mission statement shown in the footer on every page),
`content/legal/terms.entry`, `content/legal/privacy.entry`.

## The coach-privacy design (read this before touching coach content)

A coach may work across multiple domains, keeping the **same real name** in
each — nothing about their identity is meant to be hidden. What must never
happen is a site visitor *browsing the site* connecting a coach's presence
across domains. This is enforced architecturally, not just by convention:

- Every coach card is domain-scoped: a distinct **photo** and a distinct,
  domain-relevant **bio** per domain, never a shared paragraph reused
  verbatim.
- **No page anywhere lists coaches across domains** — no "meet the team,"
  no sitewide staff directory. This is the one rule that must never be
  broken.
- **No domain's nav links to another domain.** `templates/partials/
  domain-nav.php` only links within its own domain plus a single "← Home"
  link back to the root hub — never to a sibling domain. `templates/
  partials/hub-nav.php` (root only) is the one place all six domains are
  listed together.
- `lib/validate.php` enforces three pieces of this automatically:
  `validate_content_references()` flags a coach's photo being reused
  across more than one of their domain cards, `validate_output()` scans
  every generated page for a link crossing from one domain's directory
  into another's (other than the sanctioned home link), and
  `validate_sitemap()` fails the build if a coach-profile URL ever ends up
  in `sitemap.xml` — see the next section for why that file is a separate
  case from on-site navigation.
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
  exposure (see "Collections vs. one-off pages" above), not a new one a
  sitemap would introduce.

## The six page shapes (`templates/`)

Templates are plain PHP files, included with a set of variables in scope
(`lib/render.php`'s `render_template()`) — this is the entire templating
mechanism, not a placeholder-substitution language. `templates/partials/
shell.php` wraps every page (doctype/head/nav/footer); each shape below
supplies just its own inner content.

1. **`templates/hub.php`** — root landing page. One-off; links to all six
   domains with a short blurb pulled from each domain's own `index.entry`.
2. **`templates/domain-overview.php`** — a domain's front door. Composite:
   renders its own intro copy, then teaser sections pulled live from the
   coaches/resources/whispers/workshops/retreats collections filtered to
   that domain.
3. **`templates/coach-profile.php`** — one per coach card. Same layout for
   every domain; only the entry's own data and that domain's testimonials
   for that coach differ.
4. **`templates/simple-content.php`** — plain prose, no collection data:
   terms, privacy, a domain's full resources page.
5. **`templates/card-list.php`** — one reusable "list of short cards"
   shape, reused for a domain's testimonials, whisper teasers, workshops,
   and retreats. The caller pre-renders each item with the matching card
   partial (`templates/partials/{coach,testimonial,whisper-teaser,event}-
   card.php`) and hands this template the finished HTML fragments — it
   doesn't know which collection it's listing.
6. **`templates/whisper-article.php`** — one whisper's full page, generated
   once per domain it's tagged into.

No template yet exists/has been exercised for a domain with **zero**
coaches — all six sample domains currently have at least one.

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
development (see the Phase 2 commit) — they're not just written, they fire.

## Repository layout

- `content/` — the live source of truth: the six domains' own directories,
  the five collection directories, `legal/`, and the sitewide
  `index.entry`/`footer.entry`, plus the non-templated asset directories
  (`css/`, `js/`, `scss/`, `vendor/`, `img/`, `doc/`) copied verbatim by
  the generator.
- `lib/` — the engine's PHP: `entry.php` (parsing/collections),
  `render.php` (templating), `validate.php` (guardrails), and the vendored
  `Parsedown.php`.
- `templates/` — the six page shapes and their partials. Deliberately kept
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
- `docs/coaching-site-restructure-plan.md` — the design doc this whole
  engine was built from; check its progress checklist for what's done vs.
  still open before assuming the current state matches every detail there.
- `docs/improvement-plan-to-review.md` — superseded by the restructure
  plan for everything about the old `.con`/`.skel` engine; its still-live
  requirement (all internal links must be relative) is now enforced by the
  validator rather than being an aspiration.

## History and current status

This is a rebuild from an earlier personal-wellness site (see git log
before the `coaching-rebuild` branch for that version's `.con`/`.skel`
engine, now fully replaced). Current state:

- The engine (this file's description above) is real and working.
- **All six domains' content is placeholder/sample**, written to exercise
  every page shape and guardrail — not real bios, testimonials, or copy.
  Don't treat anything under `content/{leadership,creativity,change,
  performance,intimate,discovery}/`, `content/coaches/`,
  `content/testimonials/`, or `content/whispers/` as launch-ready.
- Coach/background images are placeholder color blocks (generated with
  ImageMagick), not real photos.
- Booking links point at example.com-style placeholder URLs, which the
  validator correctly flags as dead — expected until real links exist.

## Known gaps / open decisions

- `page-variables` still documents the old, now-nonexistent `.con`/`.skel`
  token model and hasn't been rewritten or removed yet.
- No content-authoring guide exists yet for coaches contributing their own
  bios/whispers (Markdown + front-matter fields, no HTML knowledge
  needed) — `startediting.sh`, the old single-operator emacs-launch
  script, was retired for exactly this reason but nothing has replaced it.
- Visual design/tone (the site is meant to read as "seductive and
  inviting," more so to women than men, without ever stating that) is
  deliberately deferred — current templates reuse the old theme's
  Bootstrap/`myfunk.css` classes with no new design system yet.
- `push-site.php` depends entirely on the operator's local `aws` CLI
  credentials — none are configured in this repo, which is correct.

See `docs/coaching-site-restructure-plan.md` for the full design reasoning
behind all of the above.
