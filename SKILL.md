---
name: sf-react-p04-credit-app
description: Architecture et conventions du monorepo Comparateur de crédit : package métier PHP `credit-core` (Credit\Core : catalogue JSON, scoring, calcul de prêt), package npm `credit-front-core` (types, API, composants partagés), API Symfony `apps/credit_api` (espace client, back-office, OTP, session JWT en cookie), site vitrine Symfony/Twig `apps/credit_front` (SEO, Sass + esbuild) et trois apps React 19 (`credit_tunnel`, `credit_client`, `credit_crm`). À utiliser systématiquement pour toute intervention sur le monorepo.
---

# Architecture & conventions du monorepo Comparateur de crédit

`CREDIT_COMPARATOR_SPEC.md` décrit le périmètre fonctionnel ; ce fichier décrit **ce qui est implémenté** et les règles à respecter pour le faire évoluer.

```text
.
├── package.json · .npmrc            # Workspaces npm (allow-git=root pour core-react / core-ts)
├── composer.json                    # Outils : PHPStan (phpstan.dist.neon + phpstan.front.neon), PHP-CS-Fixer
├── bin/setup.sh · bin/dev.sh        # Installation complète · lancement des 5 apps (Bash uniquement)
├── packages/
│   ├── credit-core/                 # Composer « credit/core » (Credit\Core\), bundle CreditCoreBundle
│   └── credit-front-core/           # npm « credit-front-core », consommé en source (main = src/index.ts)
└── apps/
    ├── credit_api/      :8000       # Symfony 8.1, seule app avec BDD (Doctrine, migrations MySQL)
    ├── credit_front/    :8001       # Symfony 8.1 + Twig, sans BDD ; SCSS/TS compilés par npm
    ├── credit_crm/      :5170       # React 19 + Vite : back-office conseillers
    ├── credit_client/   :5171       # React 19 + Vite : espace client
    └── credit_tunnel/   :5172       # React 19 + Vite : tunnel 8 étapes (+ 4 bis, 7 bis)
```

Versions : PHP 8.4, Symfony 8.1, Doctrine ORM 3, lexik/jwt 3, React 19, react-router 7, TypeScript 6, Vite 8, Sass 1.10x, esbuild 0.28, Node 24 / npm 12.

| Projet | Dépend de | Communique avec |
|---|---|---|
| `credit_api` | `credit-core`, `fagathe-dev/core-php` (>=1.1.4) | BDD, Mailer |
| `credit_front` | `credit-core` (PHP), `credit-front-core` (TS : calcul et formatage) | liens sortants vers `credit_tunnel` |
| `credit_tunnel` / `credit_client` / `credit_crm` | `credit-front-core`, `core-react` (+ `core-ts` pour le tunnel) | `credit_api` en HTTP ; tunnel → client par redirection d'URL |

---

## 0. Principes — SOLID, Open/Closed prioritaire

Ajouter un comportement = **ajouter une classe ou une entrée de registre**, jamais grossir un `switch` / `match` / chaîne de `if`. Les implémentations sont des services taggés découverts par `#[AutoconfigureTag]` + `#[AutowireIterator]`. Classes concrètes `final`, extension par composition. Contrôleurs sans logique métier.

Points d'extension en place :

| Pour ajouter… | Créer… | Découvert par |
|---|---|---|
| un partenaire | `config/data/<code>.json` + classe `extends AbstractJsonPartner` | `InMemoryProductCatalog` (tag `credit_core.partner`) |
| une règle de scoring | classe `implements ScoringRuleInterface` (ou `extends AbstractProductRule` / `AbstractAdmissionRule`) | `ScoringEngine` (tag `credit_core.scoring_rule`) |
| un critère du score /1000 | classe `implements ScoreCriterionInterface` | `ScoringEngine` (tag `credit_core.score_criterion`) |
| un public de connexion OTP | classe `implements OtpAudienceInterface` (credit_api) | `OtpLoginService` (tag `app.otp_audience`) |
| une règle de priorité d'appel (RG) | classe `implements CallbackPriorityRuleInterface` + cas de `CallbackPriorityEnum` (credit_api) | `CallbackPriorityResolver` (tag `app.callback_priority_rule`, ordre = `rank()`) |
| un e-mail de dossier | cas d'`EmailNotificationTypeEnum` + handler `extends AbstractDossierMailHandler` + gabarit `templates/emails/dossier/` | `DossierNotifier` (tag `app.dossier_mail_handler`) |
| une page projet du site | classe `implements ProjectPageInterface` (credit_front) + `templates/partials/icons/project/_<type>.svg.twig` | `ProjectPageRegistry` (tag `app.project_page`), route `/{slug}` de priorité −10, sitemap, navigation |
| une étape du tunnel | composant `StepN…tsx` + entrée dans `STEPS` (`ComparisonTunnel.tsx`) et dans `STEP_VALIDATIONS` (`tunnelForm.ts`) | même index |
| une décision de résultat | entrée dans `renderers` de `QuoteResultsView` | `Record<ScoringDecision, …>` |

---

## 1. Règles impératives

- `declare(strict_types=1);` partout en PHP. DTO et modèles `final readonly` (sauf entités Doctrine).
- **Aucun `float`** pour les montants (centimes, `int`) ni les taux (points de base, `int` : `380` = 3,80 %).
  - PHP : arithmétique exacte via **bcmath** (`Credit\Core\Util\Decimal`, `ext-bcmath` requis). Seule exception : la lecture des JSON du catalogue (`UnitConverter`, conversion via chaîne).
  - TS : entiers ; mensualité calculée en **BigInt virgule fixe** (`credit-front-core/src/utils/loanMath.ts`), résultat identique au centime près à `LoanMathCalculator` (vérifié). Formatage par `moneyFormatter.ts` (`formatCentsToEuros`, `formatBpsToPercent`, `parseEurosToCents`).
- Territoire : codes postaux `01000`–`95999` (regex `^(?:0[1-9]|[1-8]\d|9[0-5])\d{3}$`) ; âge 18 ans révolus – 80 ans (emprunteur et co-emprunteur).
- Partage : un élément n'entre dans un package que s'il sert à **au moins deux projets** (ex. `.toggle`, `.facts-list`, `.back-link` sont dans la base SCSS partagée car utilisés par deux apps). Les apps React ne s'importent jamais entre elles.
- `credit-core` : ni Doctrine, ni Mailer, ni sécurité. `credit-front-core` : types, services API, utilitaires et composants partagés ; ni pages ni routes.
- CSS : **pas de BEM**, classes courtes et sémantiques (`.card-offer`, `.offer-body`, états `.is-selected`, `.is-ready`). **Pas de `lucide-react`** : icônes SVG manuelles (`ProjectIconsSvg.tsx`, gabarits Twig). Aucun branding partenaire : un seul `<CardOffer/>`.
- Symfony : **pas de Symfony UX ni de Stimulus** (ni `assets/controllers/`, ni importmap applicative).
- Scripts : Bash uniquement (`bin/*.sh`), aucune commande Windows.
- **`.env.local` / `.env.develop` : ne jamais les créer, lire, modifier, copier ni citer.** Chaque app a un `.env` versionné contenant toutes ses variables, secrets vides. Toute nouvelle variable lue = ajoutée au `.env` dans la même modification. Les clés JWT `*.pem` ne sont jamais versionnées.

---

## 2. Package `packages/credit-core` (`Credit\Core\`)

```text
src/
├── CreditCoreBundle.php      # config credit_core.data_dir, credit_core.asset_base_uri ; param credit_core.logos_dir
├── Enum/                     # CreditProjectType, EmploymentStatus, ProfessionCategory, HousingStatus, MaritalStatus,
│                             # Civility, RiskGrade (fromScore, isAtLeast), ScoringDecision, RuleSeverity, RuleStage,
│                             # ClientStatus, DossierStatus, CallbackStatus, AdjustmentKind (+ label() en français)
├── DTO/Request/              # ComparisonRequestDto (#[Assert]), Incomes/Charges/CoBorrower/AcquisitionInputDto
├── DTO/Response/             # ComparisonResultDto, QuoteOfferDto, ScoringSummaryDto, ScoreContributionDto,
│                             # RuleExecutionDto, AdvisorFallbackDto, AdjustmentSuggestionDto, RejectionDto
├── Catalog/                  # PartnerInterface, AbstractJsonPartner (loadConfig + cache), 5 partenaires,
│                             # InMemoryProductCatalog, PartnerLogoUrlResolver, DTO (Partner/Offer/OfferCriteria)
├── Scoring/
│   ├── Calculator/           # LoanMathCalculator (bcmath), DebtRatioCalculator (locatif 70 %, reste à vivre)
│   ├── Rule/                 # ADMISSION : MetropolitanTerritory, LegalMajorityAge, MandatoryConsent (→ 422)
│   │                         # PRODUCT : AllowedEmploymentStatus, EmploymentStability, MinMonthlyIncome (STRUCTURAL)
│   │                         #   ProductAmountRange, ProductDurationRange, MaxDebtRatio, RemainderToLive,
│   │                         #   ProductMinRemainderToLive, MinRiskGrade (ADJUSTABLE)
│   ├── Score/                # EmploymentStatus, Seniority, Housing, ProjectedDebtRatio (base 500, borné 0..1000)
│   ├── Model/                # ApplicantProfile, LoanSchedule, OfferEvaluation, RuleContext, ScoreResult, AdjustmentSimulation
│   ├── ScoringEngine.php     # checkAdmission(), buildProfile(), score(), evaluateOffer()
│   ├── InstantQuoteMatcher.php · FinancingAdjustmentSimulator.php · QuoteFactory.php
│   └── CreditComparator.php  # orchestration : INSTANT_OFFERS → ADVISOR_CALLBACK_NEEDED → HARD_REJECT
└── Util/                     # Decimal, UnitConverter, FrenchFormatter (« 37,2 % », « 15 000,00 € »), DateCalculator
config/data/*.json            # 5 partenaires × 4 produits (valeurs de la spec 4.3)
assets/logos/*.svg            # logos statiques
tests/                        # PHPUnit : calcul, catalogue, 3 décisions, recevabilité, barème du score
```

**Décision** : chaque produit du projet est évalué au montant/durée demandés. Sinon, la *meilleure variante* (montant minimal, durée maximale, jamais plus que la demande) détermine la sévérité effective : un critère qui y échoue encore devient `STRUCTURAL`. Pistes (≤ 3) : durée seule (plus court allongement), montant seul (plus grand montant, pas de 500 €), compromis seulement si aucune des deux ne suffit ; triées par distance (baisse relative du montant + hausse relative de la durée, en bps).

**Hypothèses retenues** (à valider, voir spec « Notes ») : TAEG = valeur du catalogue (taux débiteur dérivé du TAEG) ; assurance = capital × taux / 12 ; coût total = intérêts + assurance ; revenu minimal comparé aux revenus pondérés du foyer ; reste à vivre = max(seuil foyer, `minRemainderToLive`) via deux règles ; grade minimal par partenaire (Altis B, Veloce B, CrediNova C sauf NOVA_EQUIP_MAISON B, SolvaBank D, FlashPact D) ; exemples représentatifs recalculés (corrige l'incohérence de l'exemple CrediNova) ; TAEG NOVA_EQUIP_MAISON = 5,40 % (tableau 4.3).

Tests : `cd packages/credit-core && vendor/bin/phpunit`.

---

## 3. API — `apps/credit_api` (`App\`)

- **Contrôleurs** (`final`, `AbstractApiController::respond()`) : `CompareQuoteController`, `CatalogController`, `AuthController`, `ClientSpaceController`, `CrmAuthController`, `CrmController`. Ils appellent un service qui renvoie l'objet `{data, status, headers, context}` de core-php (`App\Http\ApiResponseTrait` ajoute `sendUnprocessable` 422, `sendTooManyRequests` 429, `sendNotFound`).
- **Payloads** : `App\Http\PayloadReader` (désérialisation + validation) → erreurs toujours au format `{ message, code: "validation_failed", violations: { "chemin.pointé": message } }` en **422**. Les règles ADMISSION produisent le même format (`field` du `RuleExecutionDto`).
- **Services** : `Comparison\ComparisonSubmissionService`, `ClientSpace\ClientRegistrationService` (3 cas `CREATED` / `OTP_REQUIRED` / `AUTHENTICATED`), `ClientSpace\OtpService`, `ClientSpace\ClientSpaceService`, `ClientSpace\DossierPresenter`, `Crm\CrmService`, `Catalog\CatalogService`, `Security\Auth\OtpLoginService`.
- **Persistance** : entités `Client`, `Advisor`, `Dossier`, `OtpCode`, `EmailNotification` (UUID v7). Les services n'injectent **jamais** `EntityManagerInterface` : `AbstractRepository::save()/remove()/flush()`. Résultats figés en JSON (DTO de credit-core normalisés). Migrations `Version20261105000000` puis `Version20261106000000` (workflow, expiration, e-mails) en **MySQL 8 / MariaDB** (garde-fou sur la plateforme). Enums propres à l'API dans `App\Enum` (un seul projet les utilise côté PHP).
- **Sécurité** : pas de mot de passe. Session = JWT (lexik `JWTEncoderInterface`, claims `sub`, `aud`, `exp`) dans un cookie `HttpOnly`, `SameSite=Lax` : `client_session` (firewall `api`, `ROLE_CLIENT`) et `advisor_session` (firewall `crm`, `ROLE_ADVISOR`). `AbstractCookieAuthenticator` : cookie invalide = requête anonyme ; route protégée = 401 JSON `{code: "unauthenticated"}`. Un cookie client n'ouvre pas `/api/v1/crm/**` et inversement.
- **OTP** : 6 chiffres, HMAC-SHA256 (APP_SECRET), `OTP_TTL_MINUTES`, `OTP_MAX_ATTEMPTS` (429 ensuite), `OTP_RESEND_DELAY_SECONDS`, nouveau code = anciens invalidés, `otp/request` toujours 204. Le code de fin de tunnel est lié au dossier (`PENDING_VERIFICATION` → `ACTIVE` à la vérification) et part sans délai de renvoi.
- **E-mail** : `Mailer\OtpCodeMailHandler extends AbstractMailerHandler` (core-php), gabarit `templates/emails/otp_code.html.twig` (layout `@CorePhp`). `MAILER_DSN` vide → repli `null://null` (`config/packages/mailer.yaml`).
- **CORS** : `EventSubscriber\CorsSubscriber` (origines `CORS_ALLOW_ORIGIN`, credentials, préliminaires OPTIONS avant le firewall). Pas de NelmioCors.
- **Logos** : copiés par `bin/setup.sh` dans `public/assets/logos/` (ignoré par git) ; `partnerLogo` = `DEFAULT_URI` + chemin.
- **Console** : `app:advisor:create <email> <prénom> <nom>`, `app:dossiers:send-reminders` (cron horaire). `KnpPaginatorBundle` (dépendance transitive de core-php) est volontairement **retiré** de `bundles.php`.

### 3.1 Avancement des dossiers (`Dossier.workflowStatus`, `App\Enum\DossierWorkflowStatusEnum`)

Distinct de la décision de scoring (figée) et du statut de visibilité (`PENDING_VERIFICATION` / `ACTIVE`).

```text
INSTANT_OFFERS ──▶ QUOTES_GENERATED ──(client : POST /me/dossiers/{id}/subscribe)──▶ QUOTE_SUBSCRIBED ──(conseiller)──▶ APPROVED | REJECTED
ADVISOR_CALLBACK_NEEDED ──▶ NO_OFFER_ADVISOR_QUEUE          HARD_REJECT ──▶ REJECTED
```

- Offres valables `Dossier::QUOTE_VALIDITY_DAYS` = **7 jours** (`quotesExpireAt`) ; souscription refusée (422) si expirée, déjà faite ou offre absente du dossier.
- Transitions conseiller : `DossierWorkflowStatusEnum::advisorTransitions()` (seulement depuis `QUOTE_SUBSCRIBED`), appliquées via `PATCH /crm/dossiers/{id}/callback` (`workflowStatus` facultatif).

### 3.2 Priorisation des appels (RG-01 à RG-04, `App\Crm\Priority`)

Calculée **à la lecture** (dépend de l'heure) par `CallbackPriorityResolver`, à partir de `DossierMetrics` (résultat figé). Garde commune : dossier `ACTIVE`, **consentement de rappel obligatoire**, suivi d'appel non clos (`ADJUSTED`, `DECLINED`, `CLOSED` ⇒ hors priorisation). Première règle qui correspond, par rang :

| Priorité | Règle | Conditions (endettement = `debtRatioAfterBps` du résultat) |
|---|---|---|
| P1 | `HotAdjustableRule` (RG-01) | < 72 h ; `QUOTES_GENERATED` ou `NO_OFFER_ADVISOR_QUEUE` ; endettement **> 33 % et ≤ 40 %** (la tranche 33–35 % des dossiers avec offres est rattachée à P1, choix validé) ; reste à vivre ≥ 850 € ; revenus pondérés ≥ 1 500 € ; durée demandée < durée max du produit de référence |
| P1 bis | `LargeAmountHesitantRule` (RG-02) | `QUOTES_GENERATED`, ≤ 33 %, montant ≥ 15 000 €, de 2 h à 36 h |
| P2 | `StandardQuoteReminderRule` (RG-03) | `QUOTES_GENERATED`, ≤ 33 %, montant < 15 000 €, **de 24 h à 48 h** (fenêtre stricte, choix validé) |
| P3 | `LastReminderBeforeExpiryRule` (RG-04) | `QUOTES_GENERATED`, plus de 5 jours, offres non expirées |

`GET /crm/callbacks` : filtres `priority` (absent = file P1 à P3 ; `P1|P1_BIS|P2|P3|NONE` ; `ALL`), `decision`, `callbackStatus`, `workflowStatus` ; tri par rang puis ancienneté ; pagination en mémoire après calcul (lecture SQL limitée à 7 jours pour la file). Tests de bornes : `apps/credit_api/tests/Crm/Priority/CallbackPriorityResolverTest.php`.

### 3.3 E-mails transactionnels (`App\Notification`, table `email_notification`)

| Type | Quand | Handler |
|---|---|---|
| `REQUEST_CONFIRMATION` | dossier visible (fin de tunnel `CREATED`/`AUTHENTICATED`, ou vérification OTP) | `RequestConfirmationMailHandler` |
| `PARTNER_TRANSMISSION` | idem, sauf `HARD_REJECT` | `PartnerTransmissionMailHandler` |
| `REMINDER_D1` | offres non choisies, de 24 h à 5 jours | `QuoteReminderMailHandler` |
| `EXPIRY_REMINDER` (« relance J+7 », « Votre devis expire bientôt », RG-04) | offres non choisies, après 5 jours et avant expiration | `QuoteExpiryMailHandler` |

- `DossierNotifier::notify()` envoie et **trace chaque envoi** (`SENT` / `FAILED` + erreur) ; un type envoyé avec succès n'est jamais renvoyé, un échec est retenté au passage suivant. `QuoteReminderScheduler` (commande `app:dossiers:send-reminders`) est idempotent.
- Libellés monétaires formatés par `DossierMailFactory` (`FrenchFormatter`) : aucun calcul décimal dans les gabarits.
- `core_php.email.check_deliverability` est **désactivé en dev** (`config/packages/core_php.yaml`) : fixtures et tests utilisent des domaines `example.*`.

### 3.4 Jeu de données (`App\DataFixtures`, `doctrine:fixtures:load -n`)

- 3 conseillers (`alex.durand@`, `samia.benali@`, `julien.morel@example.com`), ~180 clients, **210 dossiers** : P1 30, P1 bis 25, P2 25, P3 20, `QUOTE_SUBSCRIBED` 20, `APPROVED` 15, souscription refusée 5, `HARD_REJECT` 25, à ajuster hors priorité 20, offres sans priorité 20, en attente de vérification 5.
- Méthode : `ApplicantRequestGenerator` (Faker `fr_FR`, graine fixe) produit une demande orientée par profil ; elle passe dans le **vrai** `CreditComparator` ; `DossierFactory` (partagé avec `ClientRegistrationService`) crée le dossier ; il est antidaté (`Dossier::backdate`) par rapport à l'heure du chargement, puis accepté seulement si le **vrai** `CallbackPriorityResolver` / l'état obtenu correspond au cas (`FixtureBucket`). L'endettement, le reste à vivre et les devis stockés sont donc exactement ceux du moteur ; codes postaux 01000–95999, âges 19–79 ans.
- Historique d'e-mails cohérent avec la chronologie (confirmation, transmission, J+1, J+7 si les offres attendaient encore un choix à ces dates).
- Les priorités dépendent de l'heure : les effectifs exacts ne valent qu'au moment du chargement (un dossier P1 bis sort de sa fenêtre à 36 h).
- Logs JSON core-php : `apps/credit_api/logs/` (ignoré).

| Route | Accès |
|---|---|
| `POST /api/v1/compare` | public (lit la session client) → `{clientStatus, dossierId}` |
| `GET /api/v1/catalog` | public |
| `POST /api/v1/auth/otp/request` · `otp/verify` · `logout` | public |
| `GET /api/v1/me` · `PATCH /me/consents` · `GET /me/dossiers` · `GET /me/dossiers/{id}` · `POST /me/dossiers/{id}/subscribe` | client |
| `POST /api/v1/crm/auth/otp/request` · `otp/verify` · `logout` | public |
| `GET /api/v1/crm/me` · `GET /crm/callbacks` (filtres `decision`, `callbackStatus`, `page`, `limit`) · `GET /crm/dossiers/{id}` · `PATCH /crm/dossiers/{id}/callback` | conseiller |

`PATCH /me/consents` s'applique à tous les dossiers actifs du client (premier consentement → `callbackStatus = TO_CALL`).

---

## 4. Site vitrine — `apps/credit_front`

- Sans BDD, mailer ni sécurité (paquets retirés). Contrôleurs : `HomeController`, `ProjectPageController` (`/{slug}`), `GuideController` (`/guide/credit-conso`), `LegalController`, `SitemapController` (`/sitemap.xml`, `/robots.txt`).
- **Pages légales** (`LegalController`, contenu en dur, `noindex, follow`) : `/mentions-legales` (`app_legal_mentions`), `/gestion-donnees-personnelles` (`app_legal_privacy`), `/mentions-comparateurs` (`app_legal_comparator`), `/cgu` (`app_legal_cgu`). Gabarits `templates/legal/*.html.twig` sur `legal/_layout.html.twig` (fil d'Ariane, rappel « projet de démonstration », `.legal-container` / `.legal-header` / `.legal-content`, styles `_legal.scss`). Liens : `partials/_legal_links.html.twig` (pied de page, colonne « Informations légales ») et bandeau du tunnel (`LegalBanner`, `LEGAL_LINKS` de `config.ts` sur `VITE_FRONT_URL`, nouvel onglet). Seuls les noms des partenaires et le nombre de produits viennent du catalogue.
- `Service\TunnelLinkBuilder` : `{TUNNEL_URL}/comparer?project=…&amount=<euros>&duration=…&step=2` + recopie `utm_source|medium|campaign`. `Service\ProjectPricingService` : TAEG « à partir de », bornes et exemple représentatif recalculé.
- SEO : `Seo\SeoPage` + `Seo\JsonLdFactory` (Organization, WebSite, BreadcrumbList, FAQPage, LoanOrCredit) rendus par `base.html.twig`. Mention légale obligatoire via `partials/_legal_notice.html.twig`.
- Twig : `App\Twig\CreditExtension` (`|cents`, `|bps`, `tunnel_url()`, `simulator_url()`, `partner_logo()`, `project_pages()`) ; globals `app_name`, `DEFAULT_URI`, `design_system_url`.
- **Design-system** (`sf-p05-design-system`, `DESIGN_SYSTEM_URL`) : `base.html.twig` charge `{{ design_system_url }}/assets/ds-icons.css`, `/css/ds-ui-kit.css` (avant `app.css`) et `/js/main.js` ; thème clair par défaut, sans sélecteur : `data-ui-theme="light"` sur `<html>` et script en ligne qui enregistre `ds-theme = light` (`localStorage`) si absent, sinon le service de thème du kit suivrait `prefers-color-scheme`. Composants du kit utilisés tels quels : `.btn` (`btn-primary`, `btn-outline-primary`, `btn-sm`), `.container`, typographie (`h1`–`h3`, `.lead`, `.small`), `.breadcrumb` / `.breadcrumb-item` (partiel `partials/_breadcrumb.html.twig`). Les styles propres au site (`assets/scss`) lisent les jetons `--ds-*` via les alias de `_tokens.scss` (des `var()` : `color-mix()` plutôt que les fonctions de couleur Sass) ; ne pas redéfinir une classe du kit. En-tête de page du site : `.page-hero` (le `.hero` du kit est un bandeau centré).
- **Assets (modèle `sf-p05-design-system`)** : `package.json` propre + `scripts/compile-scss.js` (Dart Sass, entrées `assets/scss/*.scss` hors partiels → `public/css/`) et `scripts/compile-ts.js` (esbuild, `assets/ts/main.ts` + `assets/ts/pages/*.ts` → `public/js/`, chunks partagés). Commandes : `npm run compile|build|dev --workspace credit_front`. Gabarits : `asset('css/app.css')`, `asset('js/main.js')`, `asset('js/pages/project.js')`. `public/css`, `public/js` ignorés par git.
- TS : `components/faqAccordion.ts`, `components/monthlyEstimator.ts` (estimateur de mensualité des pages projet, amélioration progressive, réutilise `credit-front-core/src/utils/loanMath` et `moneyFormatter` par import de fichier ciblé), `components/quickSimulator.ts` + `pages/simulator.ts`.
- **Simulateur** `/simulateur` (`SimulatorController`, barème de `SimulatorDataProvider` en JSON dans `data-simulator-offers`) : formulaire GET vers `{TUNNEL_URL}/comparer` (`step=2`, UTM en champs cachés). CTA « Comparer les offres personnalisées » dans `.sticky-cta` (fixé en bas d'écran sous 768px, aussi sur l'estimateur des pages projet).
- AssetMapper ne sert **que** les logos de `packages/credit-core/assets/logos` (préfixe `logos/`) ; `importmap.php` est vide.

---

## 5. Front React — `credit-front-core` et les 3 apps

**`packages/credit-front-core/src`** :

| Dossier | Contenu |
|---|---|
| `types/` | `enums.ts` (objets `as const` + unions + libellés : **pas d'`enum` TS**, `erasableSyntaxOnly`), `credit.types.ts`, `client.types.ts`, `crm.types.ts` |
| `api/` | `httpClient.ts` (`fetch`, `credentials: 'include'`, erreurs = `ApiError` de **core-ts** ; `isUnauthorized`, `isValidationError`, `isTooManyRequests`, `getViolations`, `getErrorMessage`), `creditApi.ts`, `clientApi.ts`, `crmApi.ts` (fabriques `create*Api(http)`) |
| `utils/` | `moneyFormatter.ts`, `loanMath.ts`, `dateFormatter.ts` |
| `components/` | `icons/ProjectIconsSvg.tsx`, `auth/EmailForm` · `OtpForm` (`useCountdown` de **core-react**), `dossier/DossierRecap`, `results/QuoteResultsView` · `CardOffer` · `ScoringBadgePanel` · `AdvisorFallbackCard` · `NotFinanceableCard` |
| `styles/` | `_tokens.scss`, `_base.scss`, `_components.scss`, `index.scss` (importé par chaque `main.tsx`) ; les apps font `@use 'credit-front-core/src/styles/tokens' as *;` |

**Apps** (structure commune `src/main.tsx`, `App.tsx`, `config.ts`, `vite-env.d.ts`, `components/`, `pages/`, `styles/`) :

- `credit_tunnel` : `/comparer` seul (`ComparisonTunnel`, brouillon et préremplissage en `sessionStorage` via `StorageService` de core-ts, redirection `utils/clientRedirect.ts`). Le simulateur rapide est sur le site vitrine (`/simulateur`), qui ouvre le tunnel à l'étape 2. Champs du formulaire en chaînes (euros, `AAAA-MM`, `'yes' | 'no' | ''`) convertis par `toPayload()`.
  - **Sortie protégée** (`utils/leaveGuard.ts`) : `useLeaveGuard(active)` dès qu'une saisie est commencée → `beforeunload` (fermeture, rechargement : message générique du navigateur, non personnalisable). Le logo (`components/layout/BrandLink`) mène à `VITE_FRONT_URL` et ouvre d'abord `ui/ConfirmDialog` (`<dialog>` natif modal) « Êtes-vous sûr de vouloir quitter le comparateur ? » ; `leaveWithoutPrompt(url)` quitte sans seconde confirmation (aussi utilisé après l'envoi).
  - **Saisies** : choix Oui / Non et civilité (M. / Mme / Autre) en `ChoiceChips` ; dates en listes déroulantes (`tunnel/dateFields.tsx` : `MonthYearField` → `AAAA-MM`, `DateSelectField` → `AAAA-MM-JJ`, bornes `PAST_YEARS` / `BIRTH_YEARS` de `stepProps.ts`) ; « Période d'essai terminée ? » aux étapes 4 et 4 bis pour CDI, CDD, fonctionnaire (`hasTrialPeriod`) → `trialPeriodCompleted` (enregistré et affiché, **sans effet sur le scoring**) ; « Nom de naissance » aux étapes 7 et 7 bis pour une femme mariée (Mme + Marié(e), `needsBirthName` ↔ `ComparisonRequestDto::requiresBirthName`, obligatoire, contrôlé aussi par l'API via `Assert\Callback`) → `birthName` (le champ « Nom » devient alors « Nom d'usage »). Étapes 5 et 6 en chips : revenus annexes ; charges (loyer si locataire, mensualité immobilière si propriétaire avec crédit, puis crédits en cours, pensions versées, autres).
  - **Étapes** identifiées (`STEP_IDS` dans `tunnelForm.ts`) : 1 projet, 2 famille, 3 logement, 4 emploi, **4 bis** emploi du co-emprunteur, 5 revenus (dont salaire du co-emprunteur), 6 charges, 7 identité, **7 bis** identité du co-emprunteur, 8 contact. `STEP_RULES[id]` porte validation, `apiPaths`, `bisOf` (numérotation « n bis ») et `isVisible` (étapes bis seulement si « Oui » à l'étape 2) ; `STEP_VIEWS` (ComparisonTunnel) porte titre, libellé court et composant. Ajouter une étape = un identifiant + ses règles + sa vue. `?step=N` vise les étapes principales (`MAIN_STEP_IDS`). Erreurs 422 : rattachées à l'étape du **préfixe d'API le plus long**, puis au champ (`API_FIELD_MAP`).
  - **Composants d'interface** (`src/components/ui/`, d'après le design-system `sf-p05-design-system`) : `Stepper` (pastilles numérotées reliées, courante en rôle primary, validées cochées et cliquables, bis réduites ; résumé « Étape n » sous 768px), `ChoiceChips` (choix unique en chips `.chip-choice` de 48px : `role="radiogroup"`, chips `role="radio"` + `aria-checked`, flèches / Début / Fin, tabulation itinérante), `Chip` (bascule `aria-pressed` + `.is-selected` : étapes 5 et 6), `ConfirmDialog`. Styles `_stepper.scss`, `_chip.scss` (dont `.date-field`, `.date-selects`), `_dialog.scss` structurés comme le design-system : Component Tokens en variables CSS, puis boîte, typographie et couleurs, motion, états.
  - **Mise en page** : `.field-row` (2 colonnes, `align-items: start`) + `.field` (`align-content: start`) + hauteur de contrôle unique (48px) : libellés et contrôles voisins restent alignés quand l'un a une aide ou une erreur.
- **Boutons** (base partagée, vocabulaire du design-system) : `.btn-primary` (plein), `.btn-outline` (contour : actions secondaires visibles, ex. « Précédent »), `.btn-ghost` (transparent sans bordure : actions discrètes, ex. « Déconnexion »).
- `credit_client` : `/connexion`, `/code` (aussi cible `OTP_REQUIRED` : `?email=&dossierId=`), `/`, `/dossiers/:id`. Garde `ClientSpaceLayout` (401 → `/connexion`), contexte via `clientSpaceContext.ts` (`useClientSpace`). « Choisir cette offre » appelle `clientApi.subscribe()` (confirmation, puis badge d'avancement et offre mise en avant) ; validité des offres affichée.
- `credit_crm` : `/connexion`, `/code`, `/` (`CallbackTable` : priorité + motif, filtres priorité / avancement / décision / suivi dans l'URL, pagination), `/dossiers/:id` (carte de priorité, `QuoteResultsView` sans `onSelectOffer`, détail du score, historique des e-mails, `CallbackFollowUpForm` avec validation ou refus de l'offre choisie). Garde `CrmLayout`, contexte `crmContext.ts` (`useCrm`).
- Conventions : hooks de contexte dans un fichier `*Context.ts` séparé (règle `react-refresh/only-export-components`) ; pas de `setState` synchrone dans un `useEffect` (règle `react-hooks/set-state-in-effect`) ; `tsconfig.app.json` en `strict`.
- Ports fixes `strictPort` : crm **5170**, client **5171**, tunnel **5172** (les trois tournent en même temps).

---

## 6. Environnement et outillage

| App | Variables du `.env` versionné |
|---|---|
| `credit_api` | `APP_ENV`, `APP_SECRET`¹, `APP_SHARE_DIR`, `DEFAULT_URI` (:8000), `DATABASE_URL`¹, `MAILER_DSN`¹, `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE`¹, `JWT_TTL`, `AUTH_COOKIE_SECURE`, `AUTH_COOKIE_DOMAIN`, `CORS_ALLOW_ORIGIN` (5172, 5171, 5170), `OTP_TTL_MINUTES`, `OTP_MAX_ATTEMPTS`, `OTP_RESEND_DELAY_SECONDS`, `FRONTEND_URL` (:5171), `APP_NAME`, `DEFAULT_EMAIL_SENDER`, `SUPPORT_EMAIL`, `LOGO_PATH` (`assets/logo.svg`, relatif à `DEFAULT_URI`), `CRM_URL` (:5170) |
| `credit_front` | `APP_ENV`, `APP_SECRET`¹, `APP_SHARE_DIR`, `DEFAULT_URI`, `SITE_URL` (:8001), `TUNNEL_URL` (:5172), `APP_NAME` |
| `credit_tunnel` | `VITE_API_URL`, `VITE_FRONT_URL` (site vitrine : logo et pages légales, défaut :8001), `VITE_CLIENT_URL` (:5171), `VITE_APP_NAME` |
| `credit_client` | `VITE_API_URL`, `VITE_TUNNEL_URL` (:5172), `VITE_APP_NAME` |
| `credit_crm` | `VITE_API_URL`, `VITE_APP_NAME` |

¹ secret : vide dans `.env`, à définir dans `.env.local`.

- `bin/setup.sh` : Composer (core, api, front, racine), `npm install`, copie des logos, `lexik:jwt:generate-keypair --skip-if-exists` (définit `OPENSSL_CONF` sous Git Bash Windows), `npm run compile` du site, tests du domaine.
- `bin/dev.sh` : serveurs PHP intégrés (8000, 8001), watch des assets du site, 3 serveurs Vite.
- Qualité : `npm run build|typecheck|lint` (racine, tous les workspaces), `composer phpstan` (niveau 6, deux configurations car deux autoloaders), `composer cs:check`, PHPUnit dans `packages/credit-core` et `apps/credit_api`.
- Pièges connus : `composer` est un `.bat` sous Windows → ne jamais passer `>=` dans un argument via PowerShell (redirection cmd) ; PowerShell 5.1 écrit un BOM qui casse `composer.json` ; npm 12 bloque les dépendances git sans `.npmrc` (`allow-git=root`) ; après un `composer remove`, vérifier `public/index.php` (déjà supprimé une fois par la désinstallation d'une recette).
