# Yomonsni Coaching Site — Restructure Plan (draft)

Status: consolidated from architecture discussion, 2026-09-05. This
supersedes most of `docs/improvement-plan-to-review.md` — that document's
generator bugs and dead-code items (the `.con`-detection regex, the
duplicate "Massage" nav label, the unused `subnav` heredocs, the stale
`page-variables` doc) are resolved by replacement here, not by patching the
old code. Once this plan is fully implemented, that document should be
marked superseded rather than treated as an active punch list.

**Implementation progress:**
- [x] §8 repo layout: `content`/`working` promoted to top level, old
      legacy `content/` deleted (branch `coaching-rebuild`). Two of that
      section's original items turned out to already be done before this
      plan was written — `fresh-content/skeleton/`, `upload-ready/
      yomonsni.com/`, and the `gen-site.php~`/`page-variables~` backup
      files no longer existed in the repo, so those bullets are removed
      below rather than checked off.
- [x] §4/§6 content model and engine: `+++`-fenced front matter + Markdown
      (Parsedown, vendored at `lib/Parsedown.php`), collection loading and
      domain filtering (`lib/entry.php`), the collection-loop templating
      mechanism (`lib/render.php` — plain PHP files as templates), all six
      page shapes (`templates/*.php`), and domain-scoped nav/footer
      partials with no lateral cross-domain links (`templates/partials/`).
      `gen-site.php` rewritten end to end on this model; the old
      `.con`/`.skel` engine is gone.
- [x] §7/§9 guardrails and validator: `lib/validate.php` implements
      cross-reference validation (bad domain slugs, orphaned coach
      references), the coach-photo-reuse-across-domains check, and the
      output-side scan (dead links, absolute-internal-link detection,
      cross-domain-leak detection, missing images, cached external-link
      liveness). Wired into `gen-site.php` as a hard gate (aborts before
      writing on a content error; reports a nonzero exit after generation
      on an output error) and into `push-site.php` (refuses to sync unless
      the last generation run recorded zero errors). All checks verified
      firing correctly against deliberately-broken test fixtures, then
      reverted — see commit for details.
- [x] Old content fully retired: `about/`, `development/`, `lifestyle/`,
      `massage/`, `reflections/`, `sanctuary/`, and the root `.con`/`.skel`
      files are deleted. Replaced with placeholder scaffolding for all six
      domains (intro copy, resources, one or two coaches, testimonials,
      whispers, and a sample workshop/retreat) — enough to exercise every
      page shape and guardrail, not real content. Real bios, testimonials,
      and copy still need to replace the placeholders before launch.
- [x] `sitemap.xml` decision (§7/§10): resolved as option 2 — generate one,
      but exclude individual coach-profile pages. Implemented in
      `gen-site.php` (`write_page()`'s `$in_sitemap` flag, defaulting true,
      passed `false` only for coach-profile pages) and enforced as a
      regression guard in `lib/validate.php`'s `validate_sitemap()`, which
      fails the build if a `coach-*.html` URL ever ends up in
      `sitemap.xml`. Verified both the generated sitemap (0 coach URLs,
      everything else present) and the guard (fires on a deliberately bad
      fixture) before committing.
- [x] `CLAUDE.md` architecture rewrite for the new engine (§8) — done
      (separate commit, ahead of the sitemap.xml work).
- [x] Content-authoring guide + `page-variables` rewrite (§8): merged into
      one document rather than kept separate — `CONTENT-GUIDE.md` at the
      repo root replaces `page-variables` (deleted) and covers both the
      field-by-field reference the old file was and the example-driven
      walkthrough coaches need, on the reasoning that the old file's
      original intent was already "document the engine's fields for
      whoever's writing content," which is one audience, not two. Explicit
      trade-off accepted: one document to keep in sync with the schema
      instead of two, at the cost of it being more work to maintain than a
      terse reference alone would be. `CLAUDE.md` points to it from the
      content-model section.
- [~] Visual design/tone pass (§1) — started and substantially advanced,
      not finished. **See §12 for the real checkpoint** — the hub page
      and the domain-overview page shape are both fully designed and
      correct on all six domains (verified with screenshots throughout);
      what's left is mainly content for five of the six domains, plus a
      few smaller loose ends §12 lists individually.

## 1. Mission and scope change

The site's mission changes from a personal wellness/massage practice to a
**coaching platform** with six independent focus areas ("domains"). All
current content and directories (`about`, `development`, `lifestyle`,
`massage`, `reflections`, `sanctuary`) are fully retired — nothing carries
over as-is.

The entire site should read as **seductive and inviting**, more so to women
than men, without ever stating that anywhere — carried by tone, imagery,
and pacing of copy rather than any explicit claim. Intensity of that tone
varies by domain (likely strongest in `intimate`/`creativity`/`discovery`,
more restrained in `leadership`/`performance`). This is a **design-system
concern for a later visual-design pass**, not something this structural
plan resolves — noted here so the template/CSS work that follows knows to
leave room for per-domain tonal variation on a shared structural skeleton.

## 2. The six domains

| Slug | Covers |
|---|---|
| `leadership` | Leadership, influence, business, career |
| `creativity` | Creativity, artistic expression, writing |
| `change` | Handling life changes and transitions |
| `performance` | Elite performance mindset |
| `intimate` | Sex, intimacy, relationship, BDSM, kink |
| `discovery` | Self-discovery and growth |

Each is a fully self-contained mini-site. **There is no lateral navigation
between domains.** A visitor who wants a different domain goes back to the
root landing page — no domain's nav, footer, or subnav links directly to
another domain.

## 3. The coach-privacy design

A coach may work across multiple domains, keeping the **same real name** in
each. What differs per domain, and what makes the overlap non-obvious to a
site visitor, is entirely architectural, not identity obfuscation:

- **A distinct photo per domain.**
- **A distinct, domain-scoped bio per domain** — only facts relevant to that
  domain, not a shared paragraph reused verbatim.
- **A distinct external booking link per domain** (booking/registration for
  coaching, workshops, and retreats is handled entirely off-site).
- **No page anywhere lists coaches across domains** ("meet the team," a
  sitewide staff directory) — this is the one rule that must never be
  broken, since it's the single easiest way to connect two profiles.
- **No coach's own page links to their profile in another domain.**

This protects against a visitor *browsing* the site connecting the dots. It
does not, and isn't meant to, protect against someone deliberately searching
for the coach's name elsewhere — that's out of scope, matching what was
asked for.

## 4. Content model: collections of cards, not one-file-per-page

The current `.con`/`.skel` model is one file mapping to one page. Most of
the new site is instead **collections of small records, filtered by domain
and joined into pages at generation time** — coach profiles, testimonials,
and whispers all work this way. Static one-off pages (a domain's overview
copy, its resources page, legal pages) keep the simpler one-file-to-one-page
shape.

### 4.1 File format for every human-authored record

Every content file — whether it becomes a whole page or a fragment slotted
into a list — has the same two-part shape:

```
+++
key: value
key2: value
+++

Markdown body text goes here. **Bold**, [links](url), lists,
headings — whatever Markdown supports.
```

- The `+++` fence (not `---`) is deliberate: body content is Markdown, and
  Markdown's own horizontal-rule/setext-heading syntax uses a bare `---`
  line. A `+++` fence never collides with real content.
- Front matter is a plain `key: value` list; the block's boundary (not
  pattern-matching against a list of "known" keys) is what tells the parser
  where metadata ends — unlike today's `.con` parsing, which scans every
  line for a match against reserved key names and can silently swallow
  ordinary prose that happens to collide with one.
- Body content is converted to HTML by a vendored, single-file,
  zero-dependency Markdown library (Parsedown), placed as its own thing —
  **not** inside `content/vendor/`, since that directory's contract is
  "copied verbatim, never executed," and this is the first vendored file
  that actually runs as part of the generator's own logic.
- File extension: proposing `.entry` as the default for every record type
  (domain overview copy, coach cards, testimonials, whispers, workshops,
  retreats, legal pages) — a naming detail, easy to bikeshed, not load-
  bearing to the rest of the plan.

### 4.2 Collections

Live as their own top-level directories under `content/`, independent of
the six domain directories (a coach card belongs to a domain via a field,
not by living inside that domain's folder — this is what lets the same
coach have one card per domain without any of them being nested inside one
"true" location):

```
content/coaches/jane--leadership.entry
content/coaches/jane--intimate.entry
content/testimonials/2024-03-leadership-jane-01.entry
content/whispers/what-a-stalled-team-is-really-asking.entry
content/workshops/2025-06-creativity-writing-retreat.entry
content/retreats/2025-09-discovery-weekend.entry
```

- **Coach card** (`content/coaches/`) — one file per (coach × domain):
  `name`, `coach_id` (stable across that coach's cards — the join key,
  never displayed), `domain`, `photo`, `booking_link`; body = domain-scoped
  bio.
- **Testimonial card** (`content/testimonials/`) — `client` (first name
  only), `date`, `domain`, `coach` (references a `coach_id`); body = the
  testimonial text. Always exactly one domain and one coach per card — a
  domain's testimonials page is "every card where `domain: X`," a coach's
  page shows the subset also matching that coach.
- **Whisper** (`content/whispers/`) — `title`, `coach`, `date`, `domains`
  (one or more — this is the one record type allowed to span domains);
  body = the full piece. Cross-tagging into `intimate` alongside other
  domains is allowed. **The domain tag list is generation-time-only
  metadata and is never rendered to a visitor** — it just decides which
  domain(s) the piece gets published under. A multi-tagged whisper is
  generated as an independent page instance per tagged domain (same
  pattern as coach cards: one underlying record, multiple domain-scoped
  page instances, none of them linking to the others).
- **Workshop / retreat** (`content/workshops/`, `content/retreats/`) —
  `title`, `domain` (single), `date`/`format`, `booking_link`; body =
  description. Structurally identical to each other (see §5). Purely
  data-driven: a domain "has workshops" simply because at least one
  workshop record names it — no separate on/off flag, satisfying "may or
  may not be offered, at any point, for any domain."

### 4.3 Per-domain static content

```
content/leadership/index.entry     # domain overview/outline copy
content/leadership/resources.entry # full resources page
```
(repeated per domain) plus sitewide:
```
content/legal/terms.entry
content/legal/privacy.entry
content/footer.entry               # generic ethical code + mission statement
```
Terms/privacy are dedicated top-level pages; a domain may carry its own
additional legal addendum alongside these rather than instead of them.

## 5. Page shapes (templates)

Six distinct layouts cover the whole site:

1. **Hub/landing** (root only, one-off). Introduces the practice, links to
   the six domains — likely pulling a short teaser line from each domain's
   own `index.entry` front matter rather than duplicating that copy here.
2. **Domain overview** (one per domain, same layout). Renders the domain's
   own intro copy, then *composes in* auto-generated teaser sections pulled
   from other collections filtered to that domain: a resources teaser, a
   coaches teaser, a whispers teaser, and a workshops/retreats teaser when
   any exist. This page is the first place the "loop over a filtered
   collection, render a small partial" mechanism (see §6) gets used, not
   just the listing pages.
3. **Coach profile** (one per coach card). Photo, domain-scoped bio, booking
   link, that coach's testimonials for this domain. Same layout regardless
   of domain.
4. **Simple content page.** Terms, privacy, domain legal addenda, and the
   full resources page — single-column prose, no collection data feeding
   it.
5. **Card-list page.** One reusable shape driving four different listings,
   each fed a different collection + card partial: domain testimonials,
   domain whisper teasers, domain workshops, domain retreats. Workshops and
   retreats are the same card shape (title, date/format, description,
   external link) reused for two collections, not two separate templates.
6. **Whisper article page.** One full page per whisper, replicated once per
   tagged domain per §4.2.

## 6. Engine changes required

This is the load-bearing infrastructure gap: today's `.skel` does flat
token substitution (`STUFFING` is one pre-written HTML blob per page) —
there is no concept of "repeat this partial once per matching record."
Nearly every page shape above except the hub and simple-content page needs
it. Concretely, the rewritten generator needs:

- The six page-shape templates above as real template files with named
  regions, replacing the current hardcoded PHP heredocs.
- Reusable nav/footer/subnav **partials**, parameterized per domain (a
  domain's subnav is "index / coaches / resources / testimonials /
  whispers / workshops-if-any / retreats-if-any / ← home," never a list of
  sibling domains) — replacing the six near-duplicate, mostly-dead
  `$dirs[$which]->subnav` heredocs.
- A collection-loop construct: given a collection directory and a domain
  filter, parse every matching `.entry` file's front matter + Markdown
  body, and render one partial per record into the page.
- `+++`-fence front-matter parsing (see §4.1) replacing the current
  scan-every-line-for-a-known-key approach.
- Parsedown (or equivalent) wired in for Markdown → HTML conversion.

## 7. Guardrails worth building in, not just relying on convention

The coach-privacy design (§3) currently lives entirely in content-authoring
discipline. These become concrete checks inside the site validator (§9),
rather than relying on nobody ever making a mistake:

- **Flag any image file referenced by more than one domain's coach cards.**
  Directly enforces "different photo per domain."
- **Flag any link from one domain's page into another domain's
  directory**, other than the single sanctioned "back to home" link.
  Catches an accidental cross-link before it ships, rather than relying on
  nobody ever pasting the wrong URL into a bio or whisper body.
- **Validate cross-references**: every testimonial's `coach` value resolves
  to a real coach card, every whisper's `domains` values are real domain
  slugs. Today a typo silently drops content with no error; this should
  fail loudly instead.
- **Resolved: generate `sitemap.xml`, excluding coach-profile pages.**
  Even with zero on-site links between a coach's domain profiles, a flat
  sitemap listing `leadership/coach-jane.html` and
  `intimate/coach-jane.html` side by side would hand over the same
  connection browsing wouldn't — no clicking required, just opening
  `/sitemap.xml`. That's a meaningfully lower bar than "someone Googling
  the coach's name" (which requires already knowing to look), so unlike
  that risk it doesn't get accepted as out-of-scope. Whisper pages are
  *not* excluded despite also being able to span domains — a whisper's
  identical content across its tagged domains is already a deliberately
  accepted exposure (§4.2 allows cross-tagging into `intimate`
  specifically), visible via ordinary browsing with or without a sitemap,
  so excluding it from the sitemap wouldn't close a gap that isn't already
  open. Coach profiles are different specifically *because* they're
  differentiated per domain (different photo, different bio) — the
  sitemap is the one thing that would make that pairing easy to spot,
  since normal browsing of two different-looking profiles doesn't.
  Enforced by `lib/validate.php`'s `validate_sitemap()`, which fails the
  build if a coach URL ever reappears in `sitemap.xml`.

## 8. Repository layout changes

Done, on branch `coaching-rebuild`:

- Promoted `fresh-content/content` → top-level `content/`, deleting the old
  top-level `content/` (pre-`fresh-content` legacy material, dead weight,
  untracked by git — turned out it had never actually been committed, so
  this was a plain delete, not a `git rm`).
- Promoted `fresh-content/working` → top-level `working/`; removed the
  now-empty `fresh-content/` wrapper entirely.
- Updated `$readprefix`/`$writeprefix` in `gen-site.php`, the commented
  example `aws s3 sync` line, `push-site.php`'s real sync path, and
  `.gitignore`'s working-directory entry to the new top-level paths.
  Verified `php gen-site.php` runs clean end-to-end from the new paths
  (output in `working/` checked against expected structure).
- Retired `startediting.sh` entirely — it encoded "the fixed handful of
  files one operator always edits," which stops making sense once content
  is spread across many small per-coach/per-testimonial/per-whisper files
  contributed by multiple people. A short content-authoring guide (what a
  coach card looks like, what fields are required) serves the actual need
  instead, once the new content model exists.
- Removed the commented-out dead `rsync --delete` loop from `gen-site.php`.
  **Not** the `/tmp` mirror step — that one is load-bearing (§9), not dead
  code, and stays untouched.
- Did a corrective pass on `CLAUDE.md` for everything the path changes made
  wrong, including fixing its mischaracterization of the `/tmp/foo` mirror
  as leftover debugging (§9's correction). Left the sections describing the
  *current* `.con`/`.skel` engine as-is, since that engine hasn't changed
  yet — only the parts invalidated by moving files.

Everything originally listed here as "still to do" is now done, in later
commits than this section's own text: the `templates/` directory exists
(§5–6 landed in the Phase 2 commit), `page-variables` was deleted and its
intent folded into `CONTENT-GUIDE.md` alongside the content-authoring guide
rather than into `CLAUDE.md`, and `CLAUDE.md` got its full architecture
rewrite in its own commit. See the progress checklist at the top of this
document for the up-to-date state — this subsection is left as a record of
the original plan, not a current task list.

## 9. Site validation and the local dry-run

Correcting an assumption from an earlier draft of this plan: `push-site.php`
doesn't need a dry-run flag of its own — **the existing workflow already is
one.** `gen-site.php` writes to `working/` and then mirrors the result to
`/tmp` (currently `/tmp/foo`) specifically so the whole generated site can
be browsed locally before `push-site.php` ever runs. That mirror step was
mischaracterized in the old `CLAUDE.md` as leftover debugging output with
"no place in a script meant to run on someone else's machine" — it's
actually the load-bearing manual-review mechanism, and it stays exactly as
it is (see the correction in §8).

What's still missing is automation on top of that manual review — nothing
today actually checks that a page's links work. A validator that walks
every generated page catches what a human skimming `/tmp` easily misses:

- Every internal link resolves to a real file in the generated output
  (dead-link detection).
- Every internal link is relative, not absolute — enforcing the
  requirement already in `docs/improvement-plan-to-review.md`.
- No internal link crosses from one domain's directory into another's,
  other than the single sanctioned "back to home" link (the §3/§7
  privacy guardrail, made concrete here).
- Every image reference (`BGIMAGE`, a coach's `photo`, any `<img src>`)
  resolves to a real file.
- Every external link (anything pointing to a different origin — booking
  links especially, since there's now one per coach per domain) is
  actually reachable.

The first four cost nothing — no network calls — and should run as a hard
gate every time the site is generated: fail loudly with a clear report
rather than silently shipping a broken or leaking page.

External link liveness is the one check with a real cost (an HTTP request
per URL), and shouldn't run at full cost on every generation during an
ordinary content-editing session. Cache each checked URL's result (status,
HTTP code, last-checked date) in a small file that lives outside
`working/` (which gets wiped every run) — e.g. a top-level, gitignored
`var/link-cache.json`. On each run, only actually re-fetch a URL if its
cache entry is missing or older than a week; otherwise reuse the cached
result. That keeps routine local generation fast — mostly cache hits —
while still catching a booking link that's gone dead within about a week
of it happening, without hammering Calendly/Eventbrite/whatever every time
a coach edits their bio.

A dead external link should be a loud warning, not a hard failure, by
default — a third-party site being briefly down shouldn't block generation
the way a genuinely broken internal link should — but it needs to be
impossible to miss in the report.

Worth wiring the validator into `push-site.php` itself as a final gate:
refuse to run the real S3 sync if the last validation run reported any
hard (internal) errors, so a moment of forgetting to check `/tmp` before
pushing doesn't ship something broken.

## 10. Open items not yet settled

1. `.entry` as the file extension for all content records — placeholder,
   easy to change.
2. ~~Whether to generate a `sitemap.xml`~~ — resolved, see §7.
3. Exact per-domain display names/branding shown to visitors (the slugs in
   §2 are technical/URL identifiers; the human-facing heading for, say,
   `change` could read as something else entirely) — this is a content-
   authoring detail that lives in each domain's own `index.entry` front
   matter and doesn't block this plan.
4. Visual design system / tone calibration per domain (§1) — deliberately
   deferred to a later pass.

## 11. What still stands from the old improvement plan

`docs/improvement-plan-to-review.md`'s requirement that **all internal
links be relative, never absolute** still applies unchanged under the new
architecture — if anything it matters more now, since local preview of a
domain-siloed site depends on it working correctly without a server.

## 12. Visual design pass — status as of 2026-09-06 (pick up here)

Started as "let's do the visual design pass, one page at a time." This
section is the real checkpoint for that work — the checklist entry near
the top just points here. Everything below was verified with
headless-Chrome screenshots during development, not just read back from
the code.

### Done

**Hub page (`index.html`)** — fully designed:
- One continuous background photo (the site's real pre-rebuild hero
  photo, `yo-IMG_56547-...jpg`, traced from the live production site) on
  `<body>`, no gradient/fade anywhere — now a standing site rule (see
  `CLAUDE.md`'s "Visual design" section), not just this page's choice.
- Nav has no domain list — brand is "Yomonsni Mission" (→ `mission.html`),
  one link "Our view on coaching" (→ `coaching.html`); both labels are
  sourced from those pages' own `title` field so the nav and the page
  heading can't drift apart.
- Domain cards: no title/label, just two "mom test" questions per domain
  (`hook`/`hook2`), translucent (opacity-only, never blurred), rounded,
  3-across, dark-navy "Explore" buttons, consistent button-to-bottom
  spacing via flex regardless of how much text is above it.
- Masthead compacted (root cause of the earlier excess space was a
  `height: 100vh` hidden inside a `@media (min-width: 992px)` override,
  not the base rule everyone had been editing), then ~1cm of top padding
  deliberately restored afterward.

**Domain-overview page shape** — now the default for all six domains
(confirmed by generating leadership, which has zero `$domain_design`
entry, and getting the full treatment anyway):
- Two-column layout: intro text on its `text_align` side (~2/3), a
  `Coaches → Resources → Whispers → Workshops → Retreats` stack on the
  other (~1/3), Coaches deliberately first so a visitor reaches a
  coach's booking link without scrolling past the whole intro.
- Coach card: photo floated left/right (alternates per coach by index —
  verified with creativity's 3 real coaches), name top-aligned with the
  photo, summary text wrapping around it, a chevron toggle (not a "Read
  more" link) revealing the full bio inline, two footer buttons — Book,
  or a non-interactive "Fully Booked" (diagonal strike-through) driven by
  either an empty `booking_link` or the new `fully_booked` field (lets a
  coach go unavailable without losing their real booking URL). Display
  order per domain is controllable via a new `order` field.
- Resources card: a couple of quick `pointers` (always visible,
  `|`-separated so a comma inside one doesn't break the list) plus a
  chevron toggle revealing `teaser` text, then a link through to the full
  resources page.
- All section headings centered; buttons rounded/pill-shaped site-wide;
  every domain has its own dark, muted accent button color
  (`$domain_design`'s `button_class` in `gen-site.php`), threaded through
  *every* page that domain has, not just the overview (an earlier gap
  that caused a real, visible inconsistency bug — fixed).
- All six domains now have a real background photo, user-selected
  directly rather than sampled unsupervised (`content/img/` mixes
  nature/abstract shots with artistic nude photography).
- The site's only JavaScript, `content/js/toggle-expand.js` — a generic
  `.toggle-more`/`data-target` show-hide-and-rotate-chevron, shared by
  both toggles above.

**Bugs found and fixed along the way** (surfaced by actually screenshotting
pages, not just reading the templates):
- `shell.php`'s body background silently 404'd on every page without an
  explicit `bgimage` (a `default-bg.jpg` fallback that was never
  created) — fixed, no fallback filename at all now.
- `resolve_coach_name()`'s documented cross-domain fallback could never
  fire in practice: every call site passed the domain-filtered coach list
  instead of the global one, so a whisper's byline showed the bare
  `coach_id` whenever its author had no card in a domain the whisper was
  also tagged into. Fixed at all three call sites.

### Not done / where to pick this up next

1. **Content for the other five domains** — the bigger piece. Only
   creativity has a real ~1500-word overview, real coaches (3, with
   real-shaped bios/summaries), and a real `pointers` list. Leadership and
   intimate each have one coach (Jane Doe, demonstrating the coach-privacy
   design and per-domain `fully_booked`); change/performance/discovery
   have none yet. Creativity is the pattern to extend, not a one-off —
   each domain will likely want its own iterative pass the way creativity
   got one, not a single mechanical copy.
2. **Root-level pages** (`mission.html`, `coaching.html`, `terms.html`,
   `privacy.html`) have no background photo yet — every other page does.
3. **Coach photos** are still placeholder color blocks (ImageMagick),
   even on domains that now have a real background photo.
4. **A domain's sub-pages** (`resources.html`, `testimonials.html`,
   `whispers.html`, `workshops.html`, individual coach-profile/whisper
   pages) inherit the right photo/button color already but haven't had
   their own layout pass — they're using the shared page-shape templates
   (`simple-content.php`, `card-list.php`, `coach-profile.php`) as-is.
5. No domain overview has been generated/screenshotted with **zero
   whispers** or **zero workshops/retreats** all at once (every domain so
   far has at least whispers) — the `if (!empty(...))` guards should
   handle it, but it's unverified.
