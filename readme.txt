=== Zero Results Fixer ===
Contributors: yodzira
Tags: search, woocommerce, synonyms, 404, typos
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fix empty search results: request→posts mappings, synonyms, keyboard-layout and translit tolerance. Works with the native search, on top of any theme.

== Description ==

"айфон" shows nothing while "iPhone 15" sits in your catalog — that is lost money. Zero Results Fixer repairs empty search results instead of replacing your search engine:

* mapping rules: «телефон» → show exactly these posts/products
* synonyms: «смартфон = phone» — both match
* keyboard layout tolerance: «ghbdtn» finds «привет»
* transliteration: «айфон» also matches latin product names
* zero-results fallback: show latest posts of a chosen category instead of a dead end
* hit counters per rule — see what actually helps

Works with the native WordPress/WooCommerce search. Does not fight your search plugin: results are widened, not replaced.

== Installation ==

1. Install and activate.
2. Open "Search Fixer" → add a rule: query + post IDs (or a synonym word).
3. Search like your customers do.

== Frequently Asked Questions ==

= Does it replace my search plugin? =
No. It widens results of the native search and coexists with search plugins.

= Will it slow the site? =
No. Rules live in one small table, the active set is cached, and one extra OR-clause is the only cost.

== Changelog ==

= 0.1.0 =
* First release: mappings, synonyms, layout/translit variants, zero-results fallback, hit counters, clean uninstall.
