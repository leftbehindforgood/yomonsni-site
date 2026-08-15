# Yomonsni Site — Improvement Plan (draft, for review)

This is a punch list, not a commitment — pick what's worth doing. Items are
grouped by theme and roughly ordered by effort-to-value within each group.
Everything here was found by reading `gen-site.php`, the `.skel`/`.con`
templates, and `myfunk.css`; nothing is implemented yet.

## 1. Generator/build cleanup (low risk, improves maintainability)

- **Remove dead heredocs in `gen-site.php`.** `$topnav`, `$subtopnav`, and
  every `$dirs[$which]->subnav` assignment (about/massage/sanctuary/
  development/lifestyle/reflections) are defined but never referenced
  anywhere else in the script — only `$footer`/`$subfooter` are actually
  substituted (via the `FOOTER` token). That's ~70+ lines of navigation
  markup that looks like it controls the site but doesn't. Either wire them
  up for real (see "Fix the templating contract" below) or delete them so
  the next edit doesn't waste time changing markup that has no effect.
- **Fix the dead `.con`-file regex.** `gen-site.php:379`:
  `case (preg_match("/(.*\.con)^/i",$f,$m)?!$f:$f):` — the `^` anchor mid-
  pattern can never match, so this always evaluates to `case $f:` and
  silently accepts any filename as a content page, not just `.con` files.
  Works today by coincidence (every content file happens to end in `.con`).
  Replace with an explicit `substr($f, -4) === '.con'` check (or similar)
  so a stray non-`.con` file in a content directory fails loudly instead of
  generating a bogus page.
- **Fix the duplicate "Massage" nav label.** `gen-site.php:109` (`$topnav`,
  currently dead per above, but will resurface if that block gets wired up
  or copy-pasted) links to `sanctuary/index.html` but the visible text
  still reads "Massage" — should read "Sanctuary" (the subpage nav variants
  already get this right).
- **Update or delete `page-variables`.** It documents `TOPMENU`, `LINK`,
  `SUBNAV`, and `HEAD` as substitution tokens; none of these are actually
  implemented in `gen-site.php` (only `TITLE`, `BGIMAGE`, `SUBHEAD`,
  `SUBDEAH`, `STUFFING`, `FOOTER` are). Whoever edits content next will
  waste time hunting for tokens that don't exist.
- **Clean up the malformed closing tags** sprinkled through the `.skel`/
  `.con` files (`</div subnav>`, `</div col-10>`, `</div great-row>`,
  `</div page container>`, `</div innerrow>`, etc.). Browsers tolerate the
  trailing junk text on closing tags today, but it makes the actual nesting
  contract (which div closes which) hard to read and easy to break by
  hand. Either write real matched `</div>` tags, or better:
- **Fix the templating contract that requires `.con` files to open divs
  they never close.** Every content page currently opens
  `<div class="col-12 col-md-10">` / `<div class="row">` itself and relies
  on `page.skel`'s trailing (malformed) closing tags to close them. This is
  fragile — a `.con` file that forgets to open that wrapper (or opens the
  wrong one) silently produces broken nesting with no error. Moving the
  wrapper open/close into `page.skel` itself (with `STUFFING` purely as
  inner content) would remove an entire class of copy-paste mistakes.
- **Remove the leftover debug step.** `gen-site.php`'s final line —
  `system("rsync -a $writeprefix \/tmp\/foo",$ret);` — mirrors every build
  to `/tmp/foo` on whatever machine runs it. No other code reads that path;
  it's dead debugging output that will confuse `push-site.php`'s clean
  deploy story on someone else's machine or in CI.
- **Delete the commented-out dead loop** right before the real one:
  `foreach ($noworkdir as $which) { //system(...) }`.

## 2. Content correctness

- **Decide the fate of `index.con` and `about/index.con`.** Neither is ever
  substituted into its corresponding `.skel` — `index.skel` and
  `about/index.skel` hardcode their own nav/masthead/content directly and
  have no `STUFFING`/`TITLE`/`BGIMAGE` tokens for those files to fill. Both
  `.con` files are pure dead weight (`index.con` in particular is a nearly
  complete duplicate of `index.skel`). Either delete them, or refactor
  `index.skel`/`about/index.skel` to actually consume them so the content
  team has one place to edit instead of two out-of-sync copies.
- **`about/products.con` looks like an orphan.** It generates
  `about/products.html`, but nothing in the nav or any other page links to
  it, and its markup (`footer text-faded text-center py-5`, `product-item`
  classes) looks copy-pasted from the original startbootstrap-business-
  casual theme rather than written for this site. Confirm whether it's
  intentional; if not, delete it.
- **Fill in the placeholder testimonial slots.** `testimonials.con`,
  `about/goals.con`, and `about/index.skel` all contain multiple empty
  `bg-faded2` boxes (empty `<span>`/`<p>` tags) clearly meant to be filled
  in later. They currently render as visible empty boxes on the live site.
- **Empty `<meta name="description">` and `<meta name="author">`** on
  every page (`page.skel`, `index.skel`, all directory `index.skel`
  files). Worth filling in per-page descriptions for SEO/social-preview
  purposes — currently every page shares the same blank meta tags.
- **Image `alt` text.** Most `<img>` tags in the `.con` files
  (`product-item-img` in `about/index.con`, `about/products.con`,
  `sanctuary/goldcoasthinterland.con`) have `alt=""`. Fine if decorative,
  but several are the primary visual content of the section and should
  describe the image for screen readers.

## 3. Mobile/responsive follow-ups

The outer sidebar/content split, footer link row, and `background-
attachment: fixed` iOS issue were already fixed in a prior pass. Worth
following up with:

- **Test on a real phone**, not just a simulated viewport — the automated
  check in this environment couldn't get a genuinely narrow browser
  viewport, so verification relied on an iframe trick. A real-device pass
  (or Chrome DevTools device toolbar) would catch anything that trick
  missed, especially the inner `col-sm-*` card grids inside each page's
  content.
- **Tap targets in the collapsed navbar** — worth a manual check that the
  hamburger menu items have enough vertical padding for comfortable
  tapping (Bootstrap 4 defaults are usually fine, but the custom
  `myfunk.css` overrides nav-link padding).
- **Long words/URLs in body text** — a few testimonial paragraphs run long
  without breaks; check they wrap properly on very narrow (320px) screens.

## 4. Performance

- **Unoptimized images.** The `img/` directory is full of full-resolution
  camera exports (many multi-MB JPEGs judging by filenames like
  `-raw32-`). None of the background images are resized/compressed for
  web, and there's no responsive `srcset`/`sizes` for the few `<img>`
  tags that do exist. This is likely the single biggest page-weight and
  load-time win available.
- **Four separate Google Fonts requests** (Varela Round, Nunito, Raleway,
  and again implicitly via weights) on every page, each a render-blocking
  `<link>`. Consider self-hosting a subset of weights actually used, or
  combining into fewer requests.
- **No cache-control/CDN in front of S3.** `push-site.php` syncs straight
  to `s3://yomonsni.com` with no CloudFront (or equivalent) in front of
  it, and no cache-control headers set on the sync. Every visit re-fetches
  everything from S3 with default (often no) caching, and there's no way
  to invalidate cached HTML quickly if a CDN gets added later without also
  adding that step to `push-site.php`.

## 5. Process/tooling

- **No verification step before deploy.** Nothing currently checks that
  every `BGIMAGE` referenced in a `.con` file actually exists in `img/`,
  that internal links resolve, or that generated HTML doesn't have
  duplicate `</html>` tags (the bug fixed this session was invisible
  precisely because nothing checked for it). A small post-generation
  script — even just grepping `fresh-content/working/` for missing image
  references and multiple `</html>` occurrences — would catch this class
  of bug before it reaches production.
- **No local preview/watch loop.** `startediting.sh` just opens files in
  emacs; there's no equivalent of `gulp serve`/browsersync pointed at
  `fresh-content/working/` for previewing changes before running
  `push-site.php`. (The `gulpfile.js` files under `fresh-content/content/`
  and `toolset/` include browsersync tasks, but they're not wired into
  this project's actual build — `gen-site.php` doesn't use gulp at all.)
- **`push-site.php` has no confirmation/dry-run.** It's one command from
  local state to production with no `--dry-run` or diff-before-push step.
  Worth adding an `aws s3 sync --dryrun` preview option, especially since
  `gen-site.php` does a hard `rm -rf` of the working directory before
  every build.

## Suggested order of attack

1. Generator cleanup (§1) — cheap, removes traps for future edits, no
   visual/behavioral risk.
2. Content correctness (§2) — mostly deletions/decisions, low risk.
3. Image optimization (§4, images specifically) — likely the biggest
   real-world improvement for visitors, but needs new image assets
   (resized/compressed originals) so it's more effort than the above.
4. Everything else as time allows.
