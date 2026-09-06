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
order: 1
photo: jane-leadership.jpg
booking_link: https://your-booking-tool.example/jane-leadership
summary: A one-or-two-sentence hook shown on the domain page's coach card.
+++

Your full bio for this domain, in Markdown. Only what's relevant here —
credentials, approach, experience specific to this domain. This is also
what shows up when a visitor expands the card's "Read more" toggle, and
on your full profile page.
```

- `name` — your display name. The same across all your cards; it's not a
  secret, only the cross-domain *connection* is meant to stay non-obvious.
- `coach_id` — a short, stable, lowercase identifier (e.g. `jane`). Must
  be the exact same value on every one of your cards, in every domain —
  it's how testimonials and whispers get attributed to you, and it's
  never shown to a visitor.
- `domain` — exactly one of `leadership`, `creativity`, `change`,
  `performance`, `intimate`, `discovery`.
- `order` — optional. A plain number controlling where this coach appears
  in the list on that domain's page, lowest first. Without it, coaches
  just appear in whatever order the files happen to sort on disk (which
  today means alphabetically by filename) — fine by accident, not
  something to rely on. You don't need to number every card consecutively
  or worry about gaps; `1`, `2`, `5` works the same as `1`, `2`, `3`. A
  coach with no `order` at all appears after every coach who has one.
  Since this lives on the card, it's domain-specific too — the same
  coach could be first in one domain and third in another.
- `photo` — a filename under `content/img/`. Must be a **different file**
  from any photo used on your other domain cards — this is enforced
  automatically (`gen-site.php` will refuse to generate if it isn't).
  Shown at 120x120 on the domain page's card, cropped to fit.
- `booking_link` — your external booking page for this domain (Calendly
  or similar). Also domain-specific if you use different calendars.
  Leave this blank while you're not taking new clients here — the card
  shows a plain "Fully Booked" notice instead of a Book button when it's
  empty.
- `fully_booked` — optional, `yes` or `no` (default `no`). Set this to
  `yes` to show the same "Fully Booked" notice *without* clearing out
  `booking_link` — useful if you're only temporarily full and don't want
  to retype your booking URL when you reopen. Since this lives on the
  card, it's domain-specific like everything else here: you can be fully
  booked in one domain and still taking clients in another.
- `summary` — optional. If you skip it, the card falls back to the first
  sentence of your bio.
- `location` — optional. Set this only if you offer **in-person** sessions
  in this domain (not every coach does) — it's the city/region shown next
  to your name on your full profile page, e.g. `Portland, OR`. Leaving it
  blank means the page treats you as online-only: no location is shown,
  and no in-person booking button appears at all.
- `in_person_booking_link` — your external booking page for in-person
  sessions, only meaningful if `location` is set. Leave it blank while
  you're not taking new in-person clients — the profile page shows an
  "In Person Coaching Fully Booked" notice instead of a button.
- `in_person_fully_booked` — optional, `yes` or `no` (default `no`). Same
  idea as `fully_booked`, but for the in-person booking button
  specifically — set it to keep `in_person_booking_link` on file without
  showing a working button. `fully_booked` and `in_person_fully_booked`
  are independent: you can be full online but still taking in-person
  clients, or vice versa.
- `photo_side` — optional, `left` (default) or `right`. Which side your
  photo appears on, both on your teaser card on the domain page and on
  your full profile page. This is deliberately a manual per-card choice
  rather than something the site picks for you — e.g. so it can be
  changed and compared for which side books better, for a given coach in
  a given domain.

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
  telling you so. Rendered as "Client of ..." under the testimonial,
  linking to that coach's profile page in this domain.

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
- `coach` — the `coach_id` who wrote it. Shown as "By ...", linking to
  that coach's profile page in whichever domain the whisper is currently
  showing on, when they have a card there (plain text if they don't —
  e.g. a whisper tagged into a domain its author doesn't formally coach
  in).
- `teaser` — optional. If you skip it, the listing page uses the first
  paragraph of your piece instead — but if your piece opens with an
  embedded photo (see below), always write an explicit teaser instead,
  since the fallback would grab the raw image tag as your "first
  paragraph" and show nothing else.

**Embedding photos:** same technique as an event's description (see
below) — a plain `<img src="../img/..." class="content-photo
content-photo-left">` tag written directly in the piece, Markdown or not.

### Event (workshop or retreat) — `content/events/<anything>.entry`

One flat folder for both workshops and retreats — a domain "has events"
simply because at least one file like this exists for it; there's
nothing else to turn on. They show up together, oldest-editing-effort
aside, on that domain's single Events page and nav link, and each also
gets its own full page (linked from that teaser's "Learn More" button).

```
+++
title: One-Day Writing Intensive
domain: creativity
coaches: sam
type: workshop
date: November 15, 2025
format: Half-day, in-person
location: Portland, OR
booking_link: https://your-registration-page.example
summary: A one- or two-sentence hook shown on the Events page's card.
+++

The full description, shown only on the event's own page — as long and
as detailed as you want, it never appears anywhere else.
```

- `domain` — exactly one of the six domain slugs.
- `coaches` — optional. A comma-separated list of `coach_id`s running it
  (`sam` or `sam, priya`) — not every event is tied to a named coach at
  all (some are run by the practice generally), but an event can have
  more than one, unlike a testimonial's single `coach`. Each one must
  match a real coach card in that domain. Shown as "With ...", each name
  linking to that coach's own profile page in this domain when they have
  one there (plain text if they don't).
- `type` — `workshop` or `retreat`. Shown on the card as a small label;
  doesn't change where the event appears (both types are listed
  together) — it's just so a visitor scanning the list can tell them
  apart at a glance.
- `date` — plain text, not a machine-parsed date — write it however
  reads best (`November 15, 2025`, `Three Tuesdays, starting October 7`,
  a date range for a multi-day retreat, etc). Events are listed in
  whatever order their files happen to sort on disk, so pick filenames
  (like the existing ones) that sort roughly the way you want them to
  read, e.g. `2025-11-...` .
- `format` — free text, e.g. `Half-day, in-person` or `Evenings, online
  via Zoom` — this is where "online" vs. "in-person" actually lives, not
  a separate field.
- `location` — optional. Set this for an in-person event (city/state or
  city/country) — an online-only event should leave it blank. Shown
  alongside the date/format on both the teaser and the event's own page.
- `booking_link` — external registration page. Leave it blank and no
  Register button shows (the teaser's "Learn More" button through to the
  event's own page shows either way).
- `summary` — optional. If you skip it, the Events page's card falls
  back to the first sentence of your description — but if your
  description embeds an image (see below), always write an explicit
  summary instead, since the fallback can't tell an image tag isn't part
  of the first sentence.

**Embedding photos in the description:** an in-person event's page is a
good place to show off the venue — the room, the grounds, whatever helps
someone picture showing up. The description field is Markdown, and
Markdown's own image syntax doesn't support floating a photo with text
wrapping around it, so do it with a plain HTML `<img>` tag written
directly in the description instead — Parsedown (the Markdown renderer)
passes raw HTML straight through:

```
<img src="../img/your-photo.jpg" alt="Describe what's in the photo" class="content-photo content-photo-left">

The rest of your description flows around the photo — write it exactly
like the surrounding paragraphs.
```

- `src` — same `content/img/` folder every other photo on the site uses;
  from an event's own page the path back to it is always `../img/...`.
- `alt` — a real description of the photo, not decorative — this is what
  a screen reader announces.
- `class` — `content-photo` plus either `content-photo-left` or
  `content-photo-right`, picking which side the photo sits on while text
  wraps around it. Use more than one image (alternating sides) for a
  longer description — see the One-Day Writing Intensive event for a
  worked example with two.
- This only appears on the event's own full page, never on its Events-page
  teaser card (which shows only `summary`), so there's no length limit to
  worry about.

### Resource — `content/resource-items/<anything-unique>.entry`

A book, video, academic paper, article, or podcast a coach actually
recommends — shown as a large card on that domain's resources page
(`content/<domain>/resources.entry` below is that page's own intro copy,
not these). This is for anyone curious about the subject, not just
people ready to book a session, so write these as real recommendations
worth a stranger's time, not a bibliography assembled to fill the page.

```
+++
title: The Practice of Practice
domain: creativity
type: book
coach: sam
image: cover-photo.jpg
url: https://example.com/the-practice-of-practice
+++

A sentence or two on what it is and why you recommend it.
```

- `domain` — exactly one of the six domain slugs.
- `type` — `book`, `video`, `paper`, `article`, or `podcast`. Picks the
  button's label (e.g. `video` shows "Watch Video") — anything else
  still works, it just gets a generic "View Resource" button.
- `coach` — optional. The `coach_id` who's recommending it — must match
  a real coach card in that domain, same as a testimonial's `coach`.
  Shown as "Recommended by {first name}", linking to that coach's
  profile page in this domain when they have one there. Leaving it blank
  is fine for a resource the practice recommends generally rather than
  one specific coach.
- `image` — optional, a filename under `content/img/` — a cover image or
  thumbnail shown at the top of the card. Skip it and the card just
  starts with the title instead.
- `url` — the external link. Leave it blank and no button shows at all.

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

The domain overview page shows this as a card: a couple of quick
pointers, a chevron toggle that reveals a bit more, then a button through
to the full resources page. On that full page, this file's own body is
just the intro copy at the top — the actual resources (books, videos,
papers) are the `content/resource-items/` cards below it, documented
above.

```
+++
title: Leadership resources
pointers: Start with 10 minutes, not 2 hours. | Track streaks, not perfection. | Share unfinished work on purpose.
teaser: A short plain-text paragraph revealed by the card's toggle — a bit more context, still not the full page.
+++

Intro copy for the resources page — framing, not the resources
themselves. Individual books/videos/papers are their own
content/resource-items/ entries, not part of this body.
```

- `pointers` — two or three short, plain-text pointers, **separated by
  `|`** rather than a comma (a comma would get confused with one inside
  an ordinary sentence). Always visible on the card.
- `teaser` — plain text, not Markdown, hidden behind the card's toggle
  until a visitor clicks it. Optional — if you skip it, the card just
  won't have a toggle at all, only the pointers and the button through to
  the full page.

### Sitewide pages

Also usually only edited by whoever maintains the site structure. All take
just a `title` and a Markdown body, same shape as everything else:

- `content/index.entry` — the hub's own intro copy (the text under the
  "YOMONSNI" heading).
- `content/mission.entry`, `content/coaching.entry` — the two pages the
  hub's nav links to. Their `title` field is reused as *both* the nav
  label and the page's own `<h1>` — change the title here, not in the
  template, if the nav text needs to change.
- `content/footer.entry` — the ethical code / mission statement shown in
  the footer on every page. Front matter can be empty here (just `+++`
  immediately followed by `+++`) — there's nothing this one needs beyond
  the body.
- `content/legal/terms.entry`, `content/legal/privacy.entry` — the
  top-level Terms and Privacy pages.
