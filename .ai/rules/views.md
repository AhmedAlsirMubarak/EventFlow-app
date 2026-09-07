---
paths:
  - 'resources/views/**'
---

# Views

## Blade directives need a non-word boundary before @
`@if`/`@endif` etc. are only recognized when the `@` is not immediately preceded by a word character (Blade's compiler regex requires `\B` there) — `word@if(...)` is left as literal text, but the matching `@endif` elsewhere is compiled unconditionally, producing an unbalanced `<?php endif; ?>` and a ParseError at render time (hit once in resources/views/reports/communication-evidence.blade.php: `...event@if($organization)...`). Always put a space (or newline) between a preceding word and `@directive`.
