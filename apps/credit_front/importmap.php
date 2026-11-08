<?php

/**
 * Importmap d'AssetMapper : volontairement vide.
 *
 * Le JavaScript est compilé par esbuild (scripts/compile-ts.js) vers public/js/ et le CSS par Sass
 * (scripts/compile-scss.js) vers public/css/. AssetMapper ne sert ici que les logos partenaires
 * de packages/credit-core (config/packages/asset_mapper.yaml).
 *
 * @return array<string, array<string, mixed>>
 */
return [];
