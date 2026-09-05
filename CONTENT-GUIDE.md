# Writing content for yomonsni.com

This is for anyone adding or editing content on the site — a coach writing
their own bio, submitting a testimonial, or publishing a whisper — with no
HTML or PHP knowledge required. It replaces the old `page-variables` file,
which documented the previous engine's tokens; this covers the same ground
(what fields exist and what they do) plus how to actually write a file,
since that was always the intent behind that old cheat-sheet too.

## The one rule nothing automated checks for you

**Never write a bio, testimonial, or whisper that reveals a coach works in
more than one domain.** The site's whole design (see `CLAUDE.md`'s "The
coach-privacy design") depends on this, and the automated checks
(`lib/validate.php`) only catch *structural* mistakes — a reused photo, a
stray link, a leaked sitemap entry. They cannot read your prose. A
testimonial that says "Jane helped me both level up at work and reconnect
with my partner" gives away exactly what the whole architecture exists to
prevent, and no script will ever catch it. You're the only check that
exists for this — read back what you wrote with that in mind before
publishing.

## The file shape

Every content file is a plain text file with two parts:

```
+++
key: value
key2: value
+++

Your writing goes here, in Markdown.
```

- The fence is `+++`, not `---` — a bare `---` line in Markdown means
  "horizontal rule," so `+++` is used instead to avoid the file
  accidentally closing itself early if you ever want a section break.
- Everything between the two `+++` lines is metadata (see field reference
  below, by content type). Everything after the second `+++` is what
  actually gets published.
- Markdown, briefly: `**bold**`, `_italic_`, `[link text](https://...)`,
  a blank line between paragraphs, `- item` for a bullet list, `#
  Heading` for a heading. That's almost everything you'll need.

## Previewing before it goes live

Run `php gen-site.php` from the repo root. Read what it prints:

- A line starting `ERROR:` means something is wrong enough that the site
  won't generate — usually a typo in a domain name or a reference to a
  coach who doesn't have a matching card. Fix it and run again.
- A line starting `WARNING:` won't block generation but is worth reading
  (e.g. a booking link that appears to be dead).
- No errors or warnings, or only warnings you expect? Open the generated
  copy (mirrored to `/tmp/foo`, or look under `working/`) in a browser and
  check your page before it's pushed live.

## Field reference, by content type

### Coach card — `content/coaches/<your-name>--<domain>.entry`

One file **per domain you work in** — if you work in two domains, you
write two separate cards, with a different photo and a bio written just
for that domain (nothing in one bio should reference the other).

```
+++
name: Jane Doe
coach_id: jane
domain: leadership
photo: jane-leadership.jpg
booking_link: https://your-booking-tool.example/jane-leadership
+++

Your bio for this domain, in Markdown. Only what's relevant here —
credentials, approach, experience specific to this domain.
```

- `name` — your display name. The same across all your cards; it's not a
  secret, only the cross-domain *connection* is meant to stay non-obvious.
- `coach_id` — a short, stable, lowercase identifier (e.g. `jane`). Must
  be the exact same value on every one of your cards, in every domain —
  it's how testimonials and whispers get attributed to you, and it's
  never shown to a visitor.
- `domain` — exactly one of `leadership`, `creativity`, `change`,
  `performance`, `intimate`, `discovery`.
- `photo` — a filename under `content/img/`. Must be a **different file**
  from any photo used on your other domain cards — this is enforced
  automatically (`gen-site.php` will refuse to generate if it isn't).
- `booking_link` — your external booking page for this domain (Calendly
  or similar). Also domain-specific if you use different calendars.

### Testimonial — `content/testimonials/<anything-unique>.entry`

```
+++
client: Alex
date: 2025-01-14
domain: leadership
coach: jane
+++

The testimonial text itself, in the client's own words where possible.
```

- `client` — **first name only.**
- `date` — `YYYY-MM-DD`.
- `domain` — which domain this testimonial belongs to.
- `coach` — the `coach_id` of the coach it's about. Must match a real
  coach card in that same `domain`, or generation will fail with an error
  telling you so.

### Whisper — `content/whispers/<url-slug>.entry`

The filename (minus `.entry`) becomes part of the page's URL, so keep it
short and URL-friendly (lowercase, hyphens, no spaces).

```
+++
title: What a stalled team is really asking
coach: jane
date: 2025-03-01
domains: leadership, change
teaser: An optional one-or-two-sentence hook shown on the listing page.
+++

The full piece, in Markdown.
```

- `domains` — one or more, comma-separated. This is the one content type
  allowed to appear under more than one domain. **This field is never
  shown to a reader** — it only decides which domain page(s) the piece
  gets published to. Writing something for more than one domain is fine;
  see the rule at the top of this guide for what "fine" doesn't cover.
- `teaser` — optional. If you skip it, the listing page uses the first
  paragraph of your piece instead.

### Workshop / retreat — `content/workshops/<anything>.entry` or `content/retreats/<anything>.entry`

Same shape for both — a domain "has workshops" (or retreats) simply
because at least one file like this exists for it; there's nothing else
to turn on.

```
+++
title: One-Day Writing Intensive
domain: creativity
date: November 15, 2025
format: Half-day, in-person
booking_link: https://your-registration-page.example
+++

Description of the workshop or retreat.
```

### A domain's own overview — `content/<domain>/index.entry`

Usually only edited by whoever maintains the site structure, not by
individual coaches, but documented here for completeness:

```
+++
title: Leadership
hook: A "mom test" style question shown on the homepage's domain card.
hook2: A second, deeper follow-up question shown just below it.
+++

The domain's own intro/outline copy.
```

`hook`/`hook2` are deliberately questions, not descriptions — something
simple and personal enough that a visitor recognizes themselves in them
(think "does this sound like you?", not "we offer X and Y"). `hook` is the
first thing a visitor reads; `hook2` follows it in smaller, quieter text as
a second layer — meant to suggest there's more depth here than the first
question alone lets on, not just repeat it. The homepage card shows only
these two questions, with no title or domain name on it at all.

### A domain's resources page — `content/<domain>/resources.entry`

```
+++
title: Leadership resources
teaser: A short plain-text hook shown on the domain's overview page.
+++

The full resources content.
```

Note: `teaser` here is **plain text**, not Markdown — it's rendered as-is
without formatting. Everything else in this guide's `teaser`/body fields
is Markdown; this one field is the exception.
