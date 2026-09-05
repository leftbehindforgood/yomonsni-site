# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

The source for the static site **yomonsni.com**, built by a hand-rolled PHP site
generator (`gen-site.php`) rather than any standard static-site generator (no
Jekyll/Hugo/11ty/etc). There is no build tooling, package manager, linter, or
test suite in this repo — there is nothing to `npm install`/`build`/`test` at
the top level. The only "commands" are the PHP scripts described below.

## Commands

```bash
# Generate the site: reads content/*, writes working/*
php gen-site.php

# Push the generated site to production (S3)
php push-site.php
```

There is no watch mode or dev server. The dry-run *is* running `gen-site.php`
locally: after writing `working/`, it mirrors that output to `/tmp/foo` (see
"Site generation architecture" below) specifically so the full generated site
can be browsed in a browser before ever running `push-site.php`. There is no
separate preview command beyond that.

Vendored front-end assets (Bootstrap, jQuery, etc.) live under
`content/vendor` and are copied verbatim by the generator — they are not
rebuilt from `toolset/` (see "Dead code" below).

`startediting.sh` (a personal emacs-launch script for a fixed handful of
files) has been retired — it stopped making sense once content is no longer
a small fixed set of files one operator always edits.

## Site generation architecture (`gen-site.php`)

This is the part of the repo that matters most and is not obvious from file
names alone. The generator implements a tiny templating system over three file
types, all under `content/`:

- **`.skel` files** — HTML skeletons with placeholder tokens (`TITLE`,
  `BGIMAGE`, `SUBHEAD`, `SUBDEAH`, `STUFFING`, `FOOTER`) that get
  string-replaced. Each content directory can have two: `index.skel` (used
  for that directory's `index.html`) and `page.skel` (used for every other
  page in that directory — all non-index pages in a directory share one
  layout). See `page-variables` for the token reference (note: `TOPMENU`,
  `LINK`, `SUBNAV`, and `HEAD` are documented there but are **not** actually
  implemented as replaceable tokens in `gen-site.php` — the nav/footer/subnav
  markup is hardcoded PHP heredoc strings instead, see below).
- **`.con` files** — content files, one per output page. Lines matching
  `KEY=value` (`TITLE=`, `BGIMAGE=`, `SUBHEAD=`, `SUBDEAH=`) set that page's
  metadata; every other line is concatenated into `STUFFING` (the page body).
  A `.con` file named `foo.con` produces `foo.html`.
- Everything else (`css/`, `js/`, `scss/`, `vendor/`, `img/`, `doc/`) is
  copied as-is via `rsync`, not templated.

Top-level navigation, sub-navigation, and footer HTML are **not** stored in
`.skel`/`.con` files — they are hardcoded as PHP heredoc strings near the top
of `gen-site.php` (`$topnav`, `$subtopnav`, `$footer`, `$subfooter`, and
per-directory `$dirs[$which]->subnav`). Editing navigation therefore means
editing `gen-site.php` itself, not the content tree.

Site structure is a fixed, hardcoded list of top-level sections — driven by
the `$directories` array in `gen-site.php`:
`. (root), about, development, lifestyle, massage, reflections, sanctuary`.
Adding a new section means adding it to `$directories` (and the `$dirs[...]`
setup block just above) as well as to the nav heredocs — there is no
auto-discovery of directories.

`$dirs[$which]->level` (0 for root, 1 for subdirectories) controls whether a
page gets the root-relative footer/nav (`$footer`/`$topnav`, links like
`./terms.html`) or the subdirectory-relative one (`$subfooter`/`$subtopnav`,
links like `../terms.html`).

Generation flow, in order:
1. Wipe `working/*` and remove editor backup files (`*~`) from `content/`.
2. Walk each directory in `$directories`, loading its `index.skel`,
   `page.skel`, and every `.con` file into memory.
3. For each `.con` file, parse out `TITLE`/`BGIMAGE`/`SUBHEAD`/`SUBDEAH` and
   accumulate the remaining lines as `STUFFING`.
4. Pick the right skeleton (`index.skel` for `index.con`, `page.skel` for
   everything else) and do placeholder substitution, writing
   `working/<dir>/<page>.html`.
5. `rsync` the non-templated asset directories (`css`, `js`, `scss`, `vendor`,
   `img`, `doc`) from `content/` into `working/`.
6. Mirror the result to `/tmp/foo` — the local dry-run/preview step, not
   leftover debugging (see "Dead code" below).

`push-site.php` syncs `working/` to `s3://yomonsni.com` via `aws s3 sync` —
deployment is a direct-to-S3 static site push, no CDN invalidation step is
scripted.

## Repository layout and content provenance

- `content/` — **the live source of truth**, at the repo top level. This is
  what `gen-site.php` actually reads. (This used to live nested under
  `fresh-content/content/`, alongside a separate, stale, pre-`fresh-content`
  legacy `content/` at the repo root, plus an out-of-sync `fresh-content/
  skeleton/` reference copy and a stale `upload-ready/yomonsni.com/` output
  copy. All of that has been collapsed: the legacy trees were deleted and
  `fresh-content/content/` and `fresh-content/working/` were promoted to
  top-level `content/`/`working/`, so there is now exactly one content tree
  and one output tree, no parallel copies to accidentally edit the wrong
  one of.)
- `toolset/` — vendored, unmodified upstream sources for Bootstrap 4.3.1 and
  two Start Bootstrap themes (`business-casual`, `grayscale`). These are the
  themes `gen-site.php`'s header comment says were "hacked up," but nothing
  in this repo builds from `toolset/` — the actual customized/compiled assets
  live directly under `content/{css,js,scss,vendor}`. `toolset/`
  is reference material for understanding where the CSS/JS came from, not a
  build dependency.
- `Testimonials - Originals/` — source document for testimonial content
  (`.odt`), not consumed by the generator.

## Dead code / known problems

- **`page-variables` is out of date**: it documents `TOPMENU`, `LINK`,
  `SUBNAV`, and `HEAD` as template tokens, but `gen-site.php` never
  substitutes them — that markup is hardcoded PHP instead (see above). Only
  `TITLE`, `BGIMAGE`, `SUBHEAD`, `SUBDEAH`, `STUFFING`, and `FOOTER` are
  actually implemented.
- **The `.con` file-type switch in `gen-site.php` (~line 379) is dead/no-op
  logic**: `case (preg_match("/(.*\.con)^/i",$f,$m)?!$f:$f):` — the regex
  `(.*\.con)^` can never match (a `^` anchor after content mid-pattern), so
  this always evaluates to `case $f:`, i.e. it just falls through and treats
  every non-`.skel`/non-special filename as a content page regardless of
  extension. It happens to work today because every actual content file ends
  in `.con`, but the intent ("only if this looks like a `.con` file") is not
  what the code does.
- **Duplicate broken heredoc block**: `$dirs["massage"]->subnav` and the
  `$footer`/`$subfooter` heredocs contain malformed HTML (`</div subnav>`,
  `</div row>`, `</div great-row>`, `</div col-10>` — these are not real
  closing tags, just `</div ...>` with stray trailing text that browsers
  ignore but that make the markup non-conformant and confusing to edit).
- **Duplicate nav entry bug**: `$topnav` in `gen-site.php` has two `<a
  class="nav-link">` entries both labeled `"Massage"` (one linking to
  `./massage/index.html`, the other to `./sanctuary/index.html` but still
  reading "Massage") — the sanctuary link's visible text is wrong.
- **`system("rsync -a $writeprefix \/tmp\/foo",$ret);`** at the end of
  `gen-site.php` mirrors the generated output to `/tmp/foo` on the machine
  running the script. This is **not** leftover debugging — it's the actual
  local dry-run/preview step: running `gen-site.php` produces a full copy of
  the site at `/tmp/foo` to browse before ever running `push-site.php`. It's
  personal-machine-specific (hardcoded path, expects to run somewhere with
  write access to `/tmp/foo`) and not meant for CI, but it's intentional and
  should not be removed.
- **Secrets/credentials**: `push-site.php` shells out to `aws s3 sync`
  relying on ambient AWS CLI credentials (not present in this repo, which is
  correct) — there's no config here for which AWS profile/credentials to
  use, so it depends entirely on the operator's local `aws` setup.
- **A larger restructure is in progress** — see
  `docs/coaching-site-restructure-plan.md`. The site's mission is changing
  from a personal wellness practice to a multi-domain coaching platform,
  which will replace the `.con`/`.skel` content model, the current
  `$directories`/`$topnav`/`$subtopnav` navigation approach, and most of
  this file's architecture description above. Everything in this file is
  accurate for the *current* engine; check the restructure plan for what's
  changing and its current status before assuming this doc is still the
  full picture.
