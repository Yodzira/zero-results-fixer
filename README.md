# Zero Results Fixer

**[EN]** Fix empty search results instead of replacing your search: request→posts mappings, synonyms, keyboard-layout tolerance («ghbdtn» finds «привет») and transliteration («айфон» matches latin names). Works with the native WordPress/WooCommerce search.

**[RU]** Ремонт пустой поисковой выдачи вместо замены поисковика: правила «запрос → посты», синонимы, терпимость к раскладке клавиатуры («ghbdtn» находит «привет») и транслитерация («айфон» находит латинские названия). Работает с нативным поиском WordPress и WooCommerce.

🔗 [**Скачать бесплатно / Download free**](https://github.com/Yodzira/zero-results-fixer/releases/latest/download/zero-results-fixer.zip)

## Возможности

- **Mapping**: «гаджет» → показать конкретные посты/товары (ID через запятую)
- **Synonyms**: «смартфон = phone» — оба слова матчатся
- **Раскладка**: «ntrcn» → «текст», «ghbdtn» → «привет» (QWERTY↔ЙЦУКЕН)
- **Транслит**: «айфон» → «ayfon» — находит латинские названия
- **Fallback**: нулевая выдача показывает свежие посты выбранной категории вместо тупика
- **Счётчики попаданий** по каждому правилу — видно, что реально помогает

## Принципы

- Правила — одна маленькая таблица, активный набор лёгкий: цена — один OR-клауза в поиске
- Не заменяет поисковые плагины: расширяет выдачу любого поиска
- Чистый uninstall: таблица и опции стираются полностью

## Установка / Install

1. Скачайте [`zero-results-fixer.zip`](https://github.com/Yodzira/zero-results-fixer/releases/latest/download/zero-results-fixer.zip)
2. WP-админка → **Плагины → Добавить новый → Загрузить плагин** → zip → Активировать
3. Меню **Search Fixer** → добавьте правило: запрос + ID постов (или слово-синоним)

## Требования / Requirements

- WordPress 6.0+ (протестировано до 7.1), PHP 7.4+

## Качество / Quality

- PHPUnit (ядро): 8 тестов, 18 assertions ✅ (раскладка, транслит, резолвер правил)
- Интеграция на живом WP 7.1: 12/12 (синонимы, раскладка, mapping, счётчики, отключение) ✅
- Официальный Plugin Checker: 0 errors (release build) ✅
- Uninstall: таблица/опции стёрты ✅

## Лицензия / License

GPL-2.0-or-later (совместимо с WordPress).

💰 **[Купить Pro / Buy Pro — 2 990 ₽/год](https://yodsira.duckdns.org/buy/zero-results-fixer)** — лицензия на 1 сайт, 12 месяцев обновлений.
