<?php
// Templates are plain PHP files under templates/, included with a set of
// variables in scope. This is the entire "templating engine" — using PHP
// itself as the template language, rather than inventing new placeholder
// syntax, gives real conditionals and loops for free (needed for the
// collection-loop pattern in the restructure plan §6) and matches this
// generator's existing hand-rolled-PHP style.

function render_template($__template, $__vars) {
    extract($__vars);
    ob_start();
    include $__template;
    return ob_get_clean();
}

// $prefix is "./" for root-level pages, "../" for everything one directory
// down (every domain page — overview, coach profile, resources, testimonials,
// whispers, events all live flat inside content/<domain>/, so
// they're all exactly one level down, same as the old $dirs[$which]->level
// distinction).
function asset_url($prefix, $path) {
    return $prefix . ltrim($path, '/');
}
