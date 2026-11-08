# Spécification technique : Comparateur de crédit avec espace client (Symfony API + React)

> Fichier de contexte à fournir à l'agent IA (Claude Code, Copilot en mode Agent, Cursor).
> Monorepo sans pnpm : 5 applications dans `apps/` (2 Symfony, 3 React) et 2 packages dans `packages/` (1 Composer, 1 npm).

---

## 1. Rôle et contexte

Tu es un Architecte Logiciel Senior spécialisé en **PHP 8.3+ / Symfony 7+** et **TypeScript 5+ / React 19 (Vite)**.

Ta mission : générer le code d'une plateforme de comparaison de crédit à la consommation avec un **moteur de scoring financier temps réel**, un **catalogue d'offres partenaires en JSON**, un tunnel de comparaison, un espace client, un back-office pour les conseillers et un site vitrine SEO.

Le projet est un **monorepo sans pnpm** (outillage en 3.2) : 5 applications dans `apps/` et 2 packages dans `packages/`.

| Emplacement | Technologie | Rôle |
| --- | --- | --- |
| `apps/credit_api` | Symfony 7 | API JSON. Reçoit le payload des 8 étapes du tunnel, applique les règles métier (majorité, territoire), exécute le scoring (endettement, reste à vivre, pondération locative à 70 %, grade de risque A à E), filtre les 20 produits des 5 partenaires et retourne l'une des trois issues décrites en 4.4 : offres immédiates, dossier à ajuster avec un conseiller, ou dossier non financable. Elle enregistre le dossier, gère l'espace client (compte, connexion par code OTP envoyé par email) et expose les endpoints du back-office conseiller. Seule application qui accède à la base de données et exécute les migrations. |
| `apps/credit_front` | Symfony 7, Twig, rendu serveur | Site vitrine et acquisition de trafic : pages SEO par projet, guide du crédit à la consommation, barèmes indicatifs lus dans le catalogue. Ses boutons renvoient vers `credit_tunnel` avec le projet, le montant, la durée et les paramètres UTM. Sans base de données. |
| `apps/credit_tunnel` | React 19 | Simulateur rapide et tunnel à 8 étapes. En fin de parcours, redirige vers `credit_client`. |
| `apps/credit_client` | React 19 | Espace client : connexion par code OTP, profil, consentements, dossiers, récapitulatif et devis. |
| `apps/credit_crm` | React 19 | Back-office des conseillers : file de rappels, détail du dossier, suivi des appels. |
| `packages/credit-core` | Package Composer | Domaine métier PHP partagé : enums, catalogue JSON, scoring, calcul de prêt, DTO. Utilisé par `credit_api` et `credit_front`. |
| `packages/credit-front-core` | Package npm | Types, services API et composants partagés par au moins deux applications React. |

**Vocabulaire.** L'`Applicant` est le demandeur (l'emprunteur qui remplit le tunnel, éventuellement accompagné d'un co-emprunteur). Un dossier à ajuster est rappelé par un conseiller, qui cherche avec l'`Applicant` un montant plus bas ou une durée plus longue.


---

## 2. Règles d'ingénierie (impératives)

1. **Architecture**
   - Monorepo sans pnpm (3.2). Chaque application a son propre fichier `.env`, versionné (règle 7).
   - Seule `credit_api` accède à la base de données (Doctrine ORM) et exécute les migrations. `credit_front` et `credit-core` n'y touchent pas.
   - Les 5 partenaires et leurs 20 produits sont décrits dans 5 fichiers JSON (un par partenaire, `packages/credit-core/config/data/`), chargés au démarrage et gardés en cache mémoire par `InMemoryProductCatalog.php` (format en 4.5).
2. **Scripts**
   - Bash natif (`.sh`) pour l'installation et le lancement. Aucune commande Windows (`cmd`, PowerShell).
3. **CSS et icônes**
   - Pas de méthodologie BEM (pas de `.block__element--modifier`). Classes courtes, sémantiques et scopées.
   - Pas de `lucide-react`. Toutes les icônes sont des composants SVG manuels, dans `packages/credit-front-core/src/components/icons/` quand plusieurs applications les utilisent, sinon dans l'application.
   - Les logos des 5 partenaires sont des fichiers SVG statiques dans `packages/credit-core/assets/logos/`. `credit_api` les sert sous `/assets/logos/` et renvoie dans `partnerLogo` l'URL absolue (`DEFAULT_URI` + champ `logo` du JSON du partenaire).
   - Aucun branding par partenaire : ni slogan, ni couleur d'accent. Toutes les offres sont rendues par un seul composant `<CardOffer/>` au style de la plateforme ; seuls le nom, le logo et les métriques financières changent.
4. **Rigueur financière (PHP et TS)**
   - `declare(strict_types=1);` en tête de chaque fichier PHP.
   - Aucun `float` pour les montants ou les taux.
     - Montants en **centimes** (`int` / `number`) : `1500000` = 15 000,00 €.
     - Taux (TAEG, taux débiteur, endettement) en **points de base** (`int` / `number`) : `10000` = 100,00 %, `380` = 3,80 %.
   - Tous les DTO et modèles PHP sont des classes `final readonly`, sauf les entités Doctrine de l'espace client.
5. **Périmètre géographique et légal**
   - Seules la **France métropolitaine et la Corse** sont acceptées : codes postaux `01000` à `95999`, hors Monaco (`98000`) et hors DROM-COM (`97xxx`, `98xxx`).
   - L'emprunteur, et l'éventuel co-emprunteur, a **entre 18 ans révolus et 80 ans**.
6. **Règles de partage entre projets**
   - Un élément n'entre dans un package que s'il est utilisé par au moins deux projets. Sinon, il reste dans l'application.
   - `credit-core` ne dépend ni de Doctrine, ni de Symfony Mailer, ni du composant de sécurité. Il ne contient que du domaine : enums, catalogue, scoring, calcul de prêt, DTO.
   - `credit-front-core` ne contient que des types, des services API et des composants partagés. Pas de pages, pas de routes, pas de logique propre à une application.
   - Les applications React ne communiquent entre elles que par redirection d'URL ; elles ne s'importent jamais.
7. **Fichiers d'environnement (/!\ impératif)**
   - **Ne jamais toucher aux fichiers `.env.local` ni `.env.develop`** (dans aucune application) : ne pas les créer, les modifier, les écraser, les supprimer, ni les copier ou les citer dans un script, un commit ou une réponse. Ils appartiennent au développeur et contiennent ses valeurs réelles. `bin/setup.sh` et `bin/dev.sh` ne les créent pas non plus.
   - Chaque application a un fichier **`.env`** versionné, qui contient **toutes** les variables qu'elle lit, avec des valeurs par défaut de développement **sans aucun secret**. Une variable secrète (mot de passe, clé, DSN avec identifiants, `APP_SECRET`, passphrase JWT) y figure avec une valeur vide et un commentaire indiquant qu'elle se définit dans `.env.local`.
   - Toute nouvelle variable lue par le code est ajoutée dans le `.env` de l'application dans la même modification. Il n'y a plus de fichier `.env.example`.
   - Les fichiers `.env.local` et `.env.develop` sont déjà ignorés par git ; l'agent ne modifie pas les `.gitignore` pour eux.
   - Les clés JWT (`*.pem`) ne sont jamais versionnées ; seuls leurs chemins le sont.

---

## 3. Monorepo : arborescence et outillage

### 3.1 Arborescence

```text
credit-platform/
├── package.json                                # Workspaces npm : apps React + credit-front-core
├── composer.json                               # Outils d'analyse statique à la racine (PHPStan, PHP-CS-Fixer)
├── bin/
│   ├── setup.sh                                # Installation complète : composer, npm, logos (ne touche à aucun .env)
│   └── dev.sh                                  # Lance les 5 applications en développement
│
├── packages/
│   ├── credit-core/                            # PACKAGE COMPOSER : domaine métier PHP partagé
│   │   ├── composer.json
│   │   ├── assets/
│   │   │   └── logos/                          # 5 logos SVG des partenaires (ex. credinova.svg)
│   │   ├── config/
│   │   │   └── data/                           # Un JSON par partenaire, nommé d'après son code (ex. credit_nova.json)
│   │   └── src/
│   │       ├── CreditCoreBundle.php            # Enregistre les services et la config du package
│   │       ├── Enum/
│   │       │   ├── CreditProjectTypeEnum.php   # GREEN_LOAN, AUTO, HOME_IMPROVEMENT, PERSONAL_GOODS, CASH_TREASURY
│   │       │   ├── EmploymentStatusEnum.php    # CDI, CIVIL_SERVANT, CDD, INDEPENDENT, RETIRED, STUDENT, UNEMPLOYED
│   │       │   ├── ProfessionCategoryEnum.php  # EXECUTIVE, EMPLOYEE, WORKER, INTERMEDIATE, SELF_EMPLOYED, INACTIVE
│   │       │   ├── HousingStatusEnum.php       # TENANT, OWNER_WITH_MORTGAGE, OWNER_WITHOUT_MORTGAGE, HOSTED_FREE, COMPANY_HOUSING
│   │       │   ├── MaritalStatusEnum.php       # SINGLE, MARRIED, PACS, DIVORCED, WIDOWED
│   │       │   ├── RiskGradeEnum.php           # GRADE_A, GRADE_B, GRADE_C, GRADE_D, GRADE_E
│   │       │   ├── ScoringDecisionEnum.php     # INSTANT_OFFERS, ADVISOR_CALLBACK_NEEDED, HARD_REJECT
│   │       │   ├── RuleSeverityEnum.php        # STRUCTURAL (non ajustable), ADJUSTABLE (levable en changeant montant ou durée)
│   │       │   ├── ClientStatusEnum.php        # CREATED, OTP_REQUIRED, AUTHENTICATED
│   │       │   ├── DossierStatusEnum.php       # PENDING_VERIFICATION, ACTIVE
│   │       │   └── CallbackStatusEnum.php      # TO_CALL, CALLED, CLOSED
│   │       ├── DTO/
│   │       │   ├── Request/
│   │       │   │   ├── ComparisonRequestDto.php    # Payload des 8 étapes, consentements et acquisition, validé par #[Assert\...]
│   │       │   │   ├── IncomesInputDto.php         # Salaire, co-emprunteur, CAF, pensions, locatif, autres
│   │       │   │   └── ChargesInputDto.php         # Loyer, prêt immo, crédits conso, pensions versées, autres
│   │       │   └── Response/
│   │       │       ├── ComparisonResultDto.php     # Décision + ScoringSummary + Quotes + diagnostic conseiller
│   │       │       ├── QuoteOfferDto.php           # Offre chiffrée d'un partenaire
│   │       │       ├── ScoringSummaryDto.php       # Score /1000, grade A..E, endettement avant/après, reste à vivre
│   │       │       ├── RuleExecutionDto.php        # Détail de chaque règle : passée, échouée, sévérité (STRUCTURAL ou ADJUSTABLE)
│   │       │       ├── AdvisorFallbackDto.php      # Dossier à ajuster : raisons du blocage + pistes de rattrapage
│   │       │       ├── AdjustmentSuggestionDto.php # Une piste chiffrée : produit, montant, durée, mensualité, TAEG
│   │       │       └── RejectionDto.php            # Dossier non financable : motifs structurels
│   │       ├── Catalog/                            # Catalogue lu depuis les JSON de config/data/
│   │       │   ├── DTO/
│   │       │   │   ├── PartnerConfigDto.php        # Code, nom, logo, liste d'offres
│   │       │   │   └── OfferConfigDto.php          # Offre et critères d'éligibilité (voir 4.5)
│   │       │   ├── Partner/
│   │       │   │   └── AbstractJsonPartner.php     # Charge, valide et met en cache le JSON d'un partenaire (loadConfig)
│   │       │   └── InMemoryProductCatalog.php      # Agrège les 5 partenaires, 20 produits
│   │       └── Scoring/                            # Moteur de scoring et de calcul financier
│   │           ├── Calculator/
│   │           │   ├── DebtRatioCalculator.php     # Pondération locative 70 %, endettement, reste à vivre
│   │           │   └── LoanMathCalculator.php      # Mensualité constante, coût total, TAEG en bps
│   │           ├── Rule/
│   │           │   ├── ScoringRuleInterface.php        # evaluate() + severity() : STRUCTURAL ou ADJUSTABLE
│   │           │   ├── MetropolitanTerritoryRule.php   # CP 01000..95999 (métropole et Corse)
│   │           │   ├── LegalMajorityAgeRule.php        # 18 <= âge <= 80 ans
│   │           │   ├── MandatoryConsentRule.php        # Transmission partenaires (toujours) et création d'espace client (nouveau client)
│   │           │   ├── MaxDebtRatioRule.php            # Plafond d'endettement
│   │           │   ├── RemainderToLiveRule.php         # Reste à vivre selon adultes et enfants
│   │           │   └── EmploymentStabilityRule.php     # Contrat et ancienneté professionnelle
│   │           ├── ScoringEngine.php               # Exécute les règles, calcule le score /1000 et le RiskGrade
│   │           ├── InstantQuoteMatcher.php         # Croise le profil scoré avec les 20 produits (montant et durée demandés)
│   │           └── FinancingAdjustmentSimulator.php # Cherche un montant plus bas ou une durée plus longue qui rend le dossier financable
│   │
│   └── credit-front-core/                      # PACKAGE NPM : types, services API, composants partagés uniquement
│       ├── package.json
│       ├── tsconfig.json
│       └── src/
│           ├── index.ts
│           ├── types/
│           │   ├── enums.ts                    # Miroir TS des enums PHP
│           │   ├── credit.types.ts             # Étapes du tunnel, QuoteOfferDto, ScoringSummaryDto, AdvisorFallbackDto, RejectionDto
│           │   ├── client.types.ts             # Profil, dossiers, statut client, DTO de l'espace client
│           │   └── crm.types.ts                # DTO du back-office conseiller
│           ├── api/
│           │   ├── httpClient.ts               # Fetch typé, credentials: 'include', gestion des erreurs 401, 422, 429
│           │   ├── creditApi.ts                # POST /api/v1/compare, GET /api/v1/catalog
│           │   ├── clientApi.ts                # /api/v1/auth et /api/v1/me
│           │   └── crmApi.ts                   # /api/v1/crm
│           ├── utils/
│           │   └── moneyFormatter.ts           # Centimes <-> euros, bps <-> %
│           ├── styles/
│           │   └── _tokens.scss                # Couleurs, espacements, rayons, typographie communs aux 3 apps
│           └── components/
│               ├── icons/
│               │   └── ProjectIconsSvg.tsx     # Icônes SVG des 5 types de projet et des statuts
│               ├── auth/
│               │   ├── EmailForm.tsx           # Saisie de l'email, demande du code
│               │   └── OtpForm.tsx             # Saisie du code à 6 chiffres, renvoi
│               ├── dossier/
│               │   └── DossierRecap.tsx        # Récapitulatif des données déclarées d'un dossier
│               └── results/
│                   ├── QuoteResultsView.tsx    # Résumé scoring + cartes d'offres, rattrapage ou refus selon la décision
│                   ├── CardOffer.tsx           # Carte d'offre générique (identité du partenaire, mensualité, TAEG, coût, CTA)
│                   ├── ScoringBadgePanel.tsx   # Jauge d'endettement avant/après, reste à vivre, grade A..E
│                   ├── AdvisorFallbackCard.tsx # Dossier à ajuster : pistes montant/durée, rappel selon le consentement
│                   └── NotFinanceableCard.tsx  # Dossier non financable : motifs clairs, sans promesse de rappel
│
└── apps/
    ├── credit_api/                             # Symfony 7 : API JSON, seule app avec accès à la base de données
    │   ├── composer.json                       # Dépend de credit-core et de fagathe-dev/core-php (section 6)
    │   ├── .env                        # Variables d'environnement versionnées, sans secret (6.3)
    │   ├── migrations/                         # Migrations Doctrine
    │   ├── config/
    │   │   ├── bundles.php
    │   │   ├── routes.yaml
    │   │   └── services.yaml
    │   ├── public/
    │   │   └── assets/logos/                   # Logos servis aux front (copiés ou liés depuis credit-core par bin/setup.sh)
    │   └── src/
    │       ├── Controller/
    │       │   ├── CompareQuoteController.php  # POST /api/v1/compare (scoring, enregistrement du dossier)
    │       │   ├── CatalogController.php       # GET  /api/v1/catalog (partenaires et taux d'appel)
    │       │   ├── AuthController.php          # OTP client : demande, vérification, déconnexion (7.7)
    │       │   ├── ClientSpaceController.php   # /api/v1/me : profil, consentements, dossiers (7.7)
    │       │   ├── CrmAuthController.php       # OTP conseiller (8.3)
    │       │   └── CrmController.php           # /api/v1/crm : file de rappels, dossier, suivi d'appel (8.4)
    │       ├── DTO/Response/
    │       │   ├── ComparisonSubmissionDto.php # Réponse de POST /compare : clientStatus + dossierId
    │       │   ├── ClientProfileDto.php        # Profil et consentements du client connecté
    │       │   ├── DossierSummaryDto.php       # Ligne de la liste des dossiers
    │       │   ├── DossierDetailDto.php        # Date du résultat, récap, décision, devis ou diagnostic
    │       │   ├── CrmCallbackItemDto.php      # Ligne de la file de rappels
    │       │   └── CrmDossierDetailDto.php     # DossierDetailDto + coordonnées du client + suivi d'appel
    │       ├── Entity/                         # Doctrine
    │       │   ├── Client.php
    │       │   ├── Advisor.php
    │       │   ├── Dossier.php
    │       │   └── OtpCode.php
    │       ├── Repository/                     # ClientRepository, AdvisorRepository, DossierRepository, OtpCodeRepository
    │       ├── ClientSpace/
    │       │   ├── ClientRegistrationService.php   # Reconnaît ou crée le client, enregistre le dossier
    │       │   └── OtpService.php                  # Génère, envoie par email et vérifie les codes (clients et conseillers)
    │       └── Security/                       # Authentification par cookie JWT : firewalls client et conseiller
    │
    ├── credit_front/                           # Symfony 7, Twig : site vitrine, SEO, acquisition
    │   ├── composer.json                       # Dépend de credit-core
    │   ├── .env
    │   ├── config/
    │   ├── templates/
    │   │   ├── base.html.twig                  # Gabarit, balises meta, JSON-LD
    │   │   ├── home.html.twig
    │   │   ├── project/show.html.twig          # Page d'un projet (5 pages)
    │   │   ├── guide/credit-conso.html.twig
    │   │   └── legal/mentions.html.twig
    │   ├── assets/                             # SCSS sans BEM
    │   ├── public/
    │   └── src/
    │       ├── Controller/                     # HomeController, ProjectPageController, GuideController, LegalController, SitemapController
    │       ├── Seo/                            # Titres, meta descriptions, JSON-LD
    │       └── Service/
    │           └── TunnelLinkBuilder.php       # URL de credit_tunnel avec projet, montant, durée et UTM
    │
    ├── credit_tunnel/                          # React 19 + Vite + TypeScript + SCSS
    │   ├── package.json
    │   ├── tsconfig.json
    │   ├── vite.config.ts
    │   ├── .env
    │   └── src/
    │       ├── main.tsx
    │       ├── App.tsx                         # Routes /simulateur et /comparer
    │       ├── styles/                         # SCSS modulaire, sans BEM : main.scss, simulator.scss, tunnel.scss
    │       ├── utils/
    │       │   ├── validators.ts               # Regex CP métropole/Corse, âge >= 18 ans, mobile 06/07
    │       │   ├── prefillStorage.ts           # Query params URL + sessionStorage (simulateur -> tunnel, UTM)
    │       │   └── clientRedirect.ts           # Construit l'URL de redirection vers credit_client
    │       ├── components/
    │       │   ├── simulator/
    │       │   │   └── QuickSimulatorCard.tsx  # Simulateur 4 entrées + CTA "Comparer les offres"
    │       │   └── tunnel/
    │       │       ├── ComparisonTunnel.tsx    # Orchestrateur des 8 étapes + barre de progression
    │       │       ├── Step1Project.tsx
    │       │       ├── Step2Family.tsx
    │       │       ├── Step3HousingLocation.tsx
    │       │       ├── Step4Employment.tsx
    │       │       ├── Step5Incomes.tsx
    │       │       ├── Step6Charges.tsx
    │       │       ├── Step7Identity.tsx
    │       │       └── Step8ContactConsent.tsx
    │       └── pages/
    │           ├── QuickSimulatorPage.tsx
    │           └── TunnelPage.tsx              # Tunnel à 8 étapes, puis redirection vers credit_client
    │
    ├── credit_client/                          # React 19 + Vite + TypeScript + SCSS : espace client
    │   ├── package.json
    │   ├── tsconfig.json
    │   ├── vite.config.ts
    │   ├── .env
    │   └── src/
    │       ├── main.tsx
    │       ├── App.tsx                         # Routes /connexion, /code, /, /dossiers/:id
    │       ├── styles/
    │       ├── components/
    │       │   ├── ClientSpaceLayout.tsx       # Gabarit et garde de route (401 -> connexion)
    │       │   ├── ProfileCard.tsx             # Informations du client
    │       │   ├── ConsentSettings.tsx         # État des consentements, bascule du rappel conseiller
    │       │   └── DossierList.tsx             # Dossiers triés par date de résultat
    │       └── pages/
    │           ├── ClientLoginPage.tsx         # /connexion
    │           ├── ClientOtpPage.tsx           # /code (aussi cible du cas OTP_REQUIRED venu du tunnel)
    │           ├── ClientDashboardPage.tsx     # / : profil, consentements, dossiers
    │           └── ClientDossierPage.tsx       # /dossiers/:id : date, récap, devis
    │
    └── credit_crm/                             # React 19 + Vite + TypeScript + SCSS : back-office conseillers
        ├── package.json
        ├── tsconfig.json
        ├── vite.config.ts
        ├── .env
        └── src/
            ├── main.tsx
            ├── App.tsx                         # Routes /connexion, /code, /, /dossiers/:id
            ├── styles/
            ├── components/
            │   ├── CrmLayout.tsx               # Gabarit et garde de route
            │   ├── CallbackTable.tsx           # File de rappels avec filtres
            │   └── CallbackFollowUpForm.tsx    # Statut d'appel et note du conseiller
            └── pages/
                ├── CrmLoginPage.tsx            # /connexion
                ├── CrmOtpPage.tsx              # /code
                ├── CallbackQueuePage.tsx       # / : file de rappels
                └── CrmDossierPage.tsx          # /dossiers/:id : récap, scoring, pistes, devis, suivi
```

### 3.2 Outillage du monorepo

- **Pas de pnpm.** Le `package.json` racine déclare des workspaces npm : `credit_tunnel`, `credit_client`, `credit_crm` et `credit-front-core`. Un seul `npm install` à la racine installe tout. Les applications dépendent de `credit-front-core` par son nom de workspace.
- **`credit-front-core` est consommé en source**, sans étape de build : son `package.json` pointe `main` et `types` vers `src/index.ts`, et Vite le compile avec l'application.
- **Composer** : `credit_api` et `credit_front` déclarent `packages/credit-core` comme dépôt de type `path` avec `symlink: true`. Pas de workspaces Composer.
- **Scripts** : `bin/setup.sh` installe les dépendances PHP et JS et copie les logos. Les fichiers `.env` sont versionnés, donc rien à copier ; les scripts ne créent ni ne modifient `.env.local` ni `.env.develop` (règle 7). `bin/dev.sh` lance les 5 applications. Ports de développement proposés : `credit_api` 8000, `credit_front` 8001, `credit_tunnel` 5173, `credit_client` 5174, `credit_crm` 5175.

### 3.3 Qui dépend de quoi

| Projet | Dépend de | Communique avec |
| --- | --- | --- |
| `credit_api` | `credit-core` | Base de données, envoi d'emails |
| `credit_front` | `credit-core` (enums, catalogue, calcul de prêt indicatif) | Liens sortants vers `credit_tunnel` |
| `credit_tunnel`, `credit_client`, `credit_crm` | `credit-front-core` | `credit_api` en HTTP. `credit_tunnel` redirige vers `credit_client`. |
| `credit-core` | Rien d'applicatif : ni Doctrine, ni Mailer, ni sécurité | |
| `credit-front-core` | React, `core-react`, `core-ts` | |


---

## 4. Moteur de scoring et catalogue (`packages/credit-core`)

### 4.1 Calcul financier (`packages/credit-core/src/Scoring/`)

**Pondération des revenus (`DebtRatioCalculator`)**

| Champ | Pondération |
| --- | --- |
| `netMonthlySalaryCents` (salaire net ou retraite) | 100 % (10000 bps) |
| `coBorrowerNetSalaryCents` (si `hasCoBorrower === true`) | 100 % (10000 bps) |
| `familyAllowancesCents` (CAF, APL) | 100 % (10000 bps) |
| `pensionsReceivedCents` | 100 % (10000 bps) |
| `rentalIncomeGrossCents` (revenus locatifs bruts) | **70 %** (7000 bps), abattement bancaire forfaitaire de 30 % |
| `otherMonthlyIncomeCents` | 100 % (10000 bps) |

**Agrégation des charges (`DebtRatioCalculator`)**

Les charges mensuelles existantes sont le cumul de `rentMonthlyCents`, `mortgageMonthlyCents`, `consumerLoansMonthlyCents`, `alimonyPaidMonthlyCents` et `otherChargesMonthlyCents`. La nouvelle mensualité s'y ajoute pour obtenir l'endettement projeté.

**Seuil minimal de reste à vivre**

| Situation | Seuil |
| --- | --- |
| Personne seule | 75 000 centimes (750 €) |
| Avec co-emprunteur | 110 000 centimes (1 100 €) |
| Par enfant à charge (`dependentChildrenCount`) | + 25 000 centimes (+ 250 €) |

### 4.2 Score sur 1000 et grade (`ScoringEngine`)

Le score part de **500 points**.

| Critère | Valeur | Points |
| --- | --- | --- |
| Contrat professionnel | CDI, CIVIL_SERVANT | +200 |
| | RETIRED | +150 |
| | INDEPENDENT | +80 |
| | CDD | +20 |
| | STUDENT | -50 |
| | UNEMPLOYED | -400 |
| Ancienneté professionnelle | 36 mois et plus | +100 |
| | 12 à 35 mois | +50 |
| | moins de 6 mois | -50 |
| Logement | OWNER_WITHOUT_MORTGAGE | +120 |
| | OWNER_WITH_MORTGAGE | +80 |
| | TENANT | +20 |
| | HOSTED_FREE | 0 |
| Endettement projeté | 25 % et moins | +100 |
| | 25 % à 30 % | +50 |
| | 30 % à 33 % | 0 |
| | 33 % à 35 % | -100 |
| | plus de 35 % | -300 |

| Score | Grade |
| --- | --- |
| 800 et plus | `GRADE_A` |
| 650 à 799 | `GRADE_B` |
| 500 à 649 | `GRADE_C` |
| 350 à 499 | `GRADE_D` |
| moins de 350 | `GRADE_E` |

### 4.3 Catalogue : 5 partenaires, 20 produits (`InMemoryProductCatalog.php`)

Chaque établissement propose 4 produits. Les tableaux ci-dessous donnent les valeurs de référence pour remplir les 5 fichiers JSON décrits en 4.5. Un partenaire n'a ni slogan ni couleur : seulement un code, un nom et un logo.

**Partenaires**

| # | Partenaire | Fichier JSON | Logo |
| --- | --- | --- | --- |
| 1 | Altis Finance | `altis_finance.json` | `/assets/logos/altis.svg` |
| 2 | CrediNova | `credit_nova.json` | `/assets/logos/credinova.svg` |
| 3 | FlashPact | `flash_pact.json` | `/assets/logos/flashpact.svg` |
| 4 | Veloce Crédit | `veloce_credit.json` | `/assets/logos/veloce.svg` |
| 5 | SolvaBank | `solva_bank.json` | `/assets/logos/solvabank.svg` |

> Seuls `credit_nova.json` et `/assets/logos/credinova.svg` sont donnés tels quels. Les autres noms de fichiers suivent la même logique (code du partenaire en snake_case) et sont à confirmer.

**Produits**

| Partenaire | Code produit | Projet | Montant (€) | Durée (mois) | TAEG (bps) | Revenu min (€) | Endettement max (bps) | Ancienneté min | Contrats acceptés |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Altis Finance | ALTIS_GREEN_HORIZON | GREEN_LOAN | 3 000 – 75 000 | 12 – 84 | 290 (2,90 %) | 2 200 | 3000 (30 %) | 12 mois | CDI, CIVIL_SERVANT, RETIRED |
| Altis Finance | ALTIS_AUTO_PREMIUM | AUTO | 5 000 – 50 000 | 12 – 72 | 380 (3,80 %) | 2 100 | 3000 (30 %) | 12 mois | CDI, CIVIL_SERVANT, RETIRED |
| Altis Finance | ALTIS_TRAVAUX_HABITAT | HOME_IMPROVEMENT | 5 000 – 75 000 | 24 – 84 | 410 (4,10 %) | 2 400 | 3200 (32 %) | 12 mois | CDI, CIVIL_SERVANT, RETIRED |
| Altis Finance | ALTIS_TRESO_PRIVILEGE | CASH_TREASURY | 3 000 – 30 000 | 12 – 60 | 460 (4,60 %) | 2 500 | 3000 (30 %) | 18 mois | CDI, CIVIL_SERVANT |
| CrediNova | NOVA_EQUIP_MAISON | PERSONAL_GOODS | 1 000 – 15 000 | 12 – 48 | 540 (5,40 %) | 1 400 | 3300 (33 %) | 6 mois | CDI, CDD, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| CrediNova | NOVA_AUTO_OCCASION | AUTO | 2 000 – 40 000 | 12 – 72 | 550 (5,50 %) | 1 500 | 3300 (33 %) | 6 mois | CDI, CDD, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| CrediNova | NOVA_TRAVAUX_CONFORT | HOME_IMPROVEMENT | 3 000 – 50 000 | 12 – 84 | 520 (5,20 %) | 1 600 | 3300 (33 %) | 6 mois | CDI, CDD, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| CrediNova | NOVA_TRESO_LIBRE | CASH_TREASURY | 1 500 – 25 000 | 12 – 60 | 650 (6,50 %) | 1 450 | 3300 (33 %) | 6 mois | CDI, CDD, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| FlashPact | FLASH_TECH_MOBILIER | PERSONAL_GOODS | 300 – 5 000 | 6 – 36 | 690 (6,90 %) | 1 100 | 3400 (34 %) | 1 mois | Tous sauf UNEMPLOYED |
| FlashPact | FLASH_GREEN_MOBILITY | GREEN_LOAN | 500 – 8 000 | 6 – 36 | 450 (4,50 %) | 1 100 | 3400 (34 %) | 1 mois | Tous sauf UNEMPLOYED |
| FlashPact | FLASH_CASH_URGENCE | CASH_TREASURY | 500 – 6 000 | 6 – 24 | 890 (8,90 %) | 1 150 | 3400 (34 %) | 2 mois | CDI, CDD, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| FlashPact | FLASH_AUTO_JEUNE | AUTO | 1 500 – 12 000 | 12 – 48 | 720 (7,20 %) | 1 200 | 3300 (33 %) | 3 mois | Tous sauf UNEMPLOYED |
| Veloce Crédit | VELOCE_SOLAIRE_PAC | GREEN_LOAN | 4 000 – 60 000 | 24 – 84 | 340 (3,40 %) | 1 800 | 3300 (33 %) | 6 mois | CDI, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| Veloce Crédit | VELOCE_EV_ELECTRIQUE | GREEN_LOAN | 5 000 – 65 000 | 12 – 84 | 320 (3,20 %) | 1 850 | 3300 (33 %) | 6 mois | CDI, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| Veloce Crédit | VELOCE_AUTO_ROUTE | AUTO | 3 000 – 45 000 | 12 – 72 | 485 (4,85 %) | 1 700 | 3200 (32 %) | 9 mois | CDI, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| Veloce Crédit | VELOCE_TRAVAUX_DECO | HOME_IMPROVEMENT | 2 500 – 40 000 | 12 – 72 | 495 (4,95 %) | 1 700 | 3200 (32 %) | 6 mois | CDI, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| SolvaBank | SOLVA_EQUIP_SOUPLE | PERSONAL_GOODS | 1 000 – 12 000 | 12 – 60 | 740 (7,40 %) | 1 300 | 3500 (35 %) | 3 mois | CDI, CDD, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| SolvaBank | SOLVA_TRAVAUX_MAX | HOME_IMPROVEMENT | 3 000 – 60 000 | 24 – 96 | 660 (6,60 %) | 1 500 | 3500 (35 %) | 6 mois | CDI, CDD, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| SolvaBank | SOLVA_TRESO_OXYGENE | CASH_TREASURY | 1 500 – 20 000 | 12 – 72 | 790 (7,90 %) | 1 350 | 3500 (35 %) | 3 mois | CDI, CDD, CIVIL_SERVANT, INDEPENDENT, RETIRED |
| SolvaBank | SOLVA_AUTO_FLEX | AUTO | 2 000 – 30 000 | 12 – 72 | 680 (6,80 %) | 1 350 | 3500 (35 %) | 3 mois | CDI, CDD, CIVIL_SERVANT, INDEPENDENT, RETIRED |

> Dans le code, les montants sont en centimes (`3 000 €` = `300000`) et les revenus minimaux aussi (`2 200 €` = `220000`).

### 4.4 Décision : trois issues

`POST /api/v1/compare` répond toujours en HTTP 200 avec une `decision` parmi trois valeurs. Seules les erreurs de validation (territoire, âge, consentement de transmission aux partenaires, consentement de création d'espace client pour un nouveau client, payload invalide) renvoient une erreur HTTP 422, avant tout calcul. Le `ScoringSummaryDto` est renvoyé dans les trois cas.

| `decision` | Situation de l'`Applicant` | Réponse |
| --- | --- | --- |
| `INSTANT_OFFERS` | Au moins un produit est éligible avec le montant et la durée demandés. | `quotes` triées par TAEG croissant. |
| `ADVISOR_CALLBACK_NEEDED` | Aucun produit n'est éligible tel quel, mais le dossier devient financable avec un montant plus bas ou une durée plus longue. | `quotes = []` et un `AdvisorFallbackDto` : si l'`Applicant` a consenti au rappel, un conseiller l'appelle pour en discuter avec lui. |
| `HARD_REJECT` | Aucun montant ni aucune durée ne rend le dossier financable. | `quotes = []` et un `RejectionDto` : motifs structurels, aucun rappel promis. |

#### Algorithme de décision

1. `InstantQuoteMatcher` ne garde que les produits du `projectType` demandé et évalue chacun avec le montant et la durée demandés. Au moins une offre éligible : `INSTANT_OFFERS`.
2. Sinon, `FinancingAdjustmentSimulator` explore pour chaque produit les variantes suivantes :
   - montant : du montant demandé jusqu'au minimum du produit, par pas de 500 € ;
   - durée : de la durée demandée jusqu'au maximum du produit.
3. Au moins une variante éligible : `ADVISOR_CALLBACK_NEEDED`. Aucune : `HARD_REJECT`.

Seules les variantes qui réduisent le montant ou allongent la durée sont explorées : le simulateur ne propose jamais d'emprunter plus.

#### Sévérité des critères

| Critère | Dépend du montant ou de la durée ? | Sévérité |
| --- | --- | --- |
| Contrat accepté par le produit | Non | `STRUCTURAL` |
| Ancienneté professionnelle minimale | Non | `STRUCTURAL` |
| Revenu minimal du produit | Non | `STRUCTURAL` |
| Montant min/max du produit | Oui | `ADJUSTABLE` (`STRUCTURAL` si le montant demandé est sous le minimum de tous les produits du projet) |
| Durée min/max du produit | Oui | `ADJUSTABLE` |
| Plafond d'endettement projeté | Oui, via la mensualité | `ADJUSTABLE` |
| Reste à vivre après mensualité | Oui, via la mensualité | `ADJUSTABLE` |
| Reste à vivre minimal du produit (`minRemainderToLive`) | Oui, via la mensualité | `ADJUSTABLE` |
| Grade de risque minimal du produit (`minRiskGrade`) | Oui, via l'endettement projeté qui entre dans le score | `ADJUSTABLE` |

Un critère `ADJUSTABLE` devient bloquant définitif (`STRUCTURAL`) quand il échoue encore dans la meilleure variante possible du produit, c'est-à-dire au montant minimal et à la durée maximale. Exemple : si l'endettement avant projet atteint déjà le plafond du produit, aucune mensualité ne passe.

Un dossier est `HARD_REJECT` quand chaque produit du projet échoue sur au moins un critère `STRUCTURAL`. Il est `ADVISOR_CALLBACK_NEEDED` dès qu'un seul produit n'échoue que sur des critères `ADJUSTABLE`, levables par une variante.

#### Cas `ADVISOR_CALLBACK_NEEDED` : `AdvisorFallbackDto`

- Les raisons du blocage, chiffrées (ex. : « Votre taux d'endettement projeté est de 37,2 % alors que le plafond automatique est fixé à 35,0 % »).
- Jusqu'à 3 `AdjustmentSuggestionDto`, classées de la moins à la plus éloignée de la demande. Chacune indique le partenaire, le produit, le montant, la durée, la mensualité, le TAEG et l'endettement projeté :
  1. **Durée plus longue**, montant inchangé : l'allongement le plus court qui passe.
  2. **Montant plus bas**, durée inchangée : le montant maximal qui passe.
  3. **Compromis** : une combinaison des deux quand ni l'une ni l'autre ne suffit seule.
- Si `consentAdvisorCallback` est donné : la confirmation de prise en charge « Un conseiller spécialisé va vous rappeler au [phone] pour étudier avec vous un montant plus bas ou une durée plus longue. »
- Sinon : les pistes sont affichées sans promesse de rappel, avec une invitation à demander à être rappelé (bascule du consentement dans l'espace client, section 7.3).

Les pistes sont indicatives : c'est le conseiller qui valide avec l'`Applicant` ce qu'il accepte de modifier.

#### Cas `HARD_REJECT` : `RejectionDto`

- Les motifs structurels en langage clair (ex. : « Aucun de nos partenaires n'accepte votre type de contrat pour ce projet », « Votre ancienneté professionnelle est inférieure au minimum exigé »).
- Aucune suggestion chiffrée et aucune promesse de rappel.

### 4.5 Format JSON des partenaires et DTO

Chaque partenaire a un fichier `packages/credit-core/config/data/<code>.json`. Il ne contient que l'identité stricte (`code`, `name`, `logo`) et ses offres (`offers`). Les propriétés de branding (`slogan`, `primaryColor`, `accentColor`) n'existent plus.

```json
{
  "code": "credit_nova",
  "name": "CrediNova",
  "logo": "/assets/logos/credinova.svg",
  "offers": [
    {
      "offerCode": "NOVA_EQUIP_MAISON",
      "productName": "Prêt Perso Équipement & Mobilier",
      "projectType": "personal_goods",
      "nominalRate": 4.23,
      "apr": 5.05,
      "insuranceRate": 0.55,
      "referenceAmount": 3500,
      "referenceDurationMonths": 24,
      "monthlyInstallment": 200,
      "totalFinancingCost": 3930,
      "criteria": {
        "minAmount": 1000,
        "maxAmount": 15000,
        "minDurationMonths": 12,
        "maxDurationMonths": 48,
        "minMonthlyIncome": 1400,
        "maxDebtRatioPercent": 33.0,
        "minRemainderToLive": 800,
        "minSeniorityMonths": 6,
        "minRiskGrade": "GRADE_B",
        "allowedEmploymentStatuses": ["CDI", "CDD", "CIVIL_SERVANT", "INDEPENDENT", "RETIRED"]
      }
    }
  ]
}
```

| Champ | Rôle |
| --- | --- |
| `projectType` | Valeur de `CreditProjectTypeEnum` (minuscules : `personal_goods`, `green_loan`, etc.). |
| `nominalRate`, `apr`, `insuranceRate` | Taux débiteur, TAEG fixe et taux d'assurance, en pourcentage. |
| `referenceAmount`, `referenceDurationMonths`, `monthlyInstallment`, `totalFinancingCost` | Exemple représentatif affiché pour le produit. Jamais utilisé pour le calcul des offres : mensualité et coûts viennent de `LoanMathCalculator` à partir du montant et de la durée de la demande. |
| `criteria` | Critères d'éligibilité du produit. Ils alimentent `InstantQuoteMatcher` et `FinancingAdjustmentSimulator`. |

**Logos.** Le champ `logo` est un chemin (`/assets/logos/credinova.svg`). Le fichier SVG est dans `packages/credit-core/assets/logos/`. `credit_api` le sert et renvoie une URL absolue dans `QuoteOfferDto::partnerLogo`.

**Unités.** Le JSON est écrit en unités humaines (euros, pourcentages) pour rester lisible et éditable. Le chargement (`loadConfig()`) convertit tout en centimes et en points de base dès la lecture, et aucun `float` ne circule ensuite dans le code métier. Lire du JSON est la seule exception tolérée à la règle « pas de `float` ».

**Catalogue.** Les valeurs des 20 produits sont celles de 4.3 (montants, durées, revenus minimaux, plafonds d'endettement, ancienneté, contrats). Chaque offre ajoute `minRemainderToLive` et `minRiskGrade`.

#### `packages/credit-core/src/Catalog/DTO/PartnerConfigDto.php`

```php
<?php

declare(strict_types=1);

namespace Credit\Core\Catalog\DTO;

final readonly class PartnerConfigDto
{
    /**
     * @param list<OfferConfigDto> $offers
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $logo,
        public array $offers,
    ) {}
}
```

#### Fin de `loadConfig()` dans `packages/credit-core/src/Catalog/Partner/AbstractJsonPartner.php`

```php
return $this->cachedConfig = new PartnerConfigDto(
    code: (string) $raw['code'],
    name: (string) $raw['name'],
    logo: (string) $raw['logo'],
    offers: $offers,
);
```

#### `packages/credit-core/src/DTO/Response/QuoteOfferDto.php`

Le DTO renvoyé au front dans le détail d'un dossier (`GET /api/v1/me/dossiers/{id}`, section 7.7) expose uniquement les données nécessaires à `<CardOffer/>`.

```php
<?php

declare(strict_types=1);

namespace Credit\Core\DTO\Response;

use Credit\Core\Enum\CreditProjectTypeEnum;

final readonly class QuoteOfferDto
{
    public function __construct(
        public string $partnerCode,
        public string $partnerName,
        public string $partnerLogo,
        public string $offerCode,
        public string $productName,
        public CreditProjectTypeEnum $projectType,
        public int $amountCents,
        public int $durationMonths,
        public int $nominalRateBps,
        public int $aprBps,
        public int $monthlyInstallmentCents,
        public int $monthlyInsuranceCents,
        public int $totalInterestCents,
        public int $totalFinancingCostCents,
        public int $debtRatioAfterBps,
        public bool $isBestRate = false,
    ) {}
}
```

---

## 5. Applications front

### 5.1 Site vitrine (`credit_front`) et simulateur rapide

`credit_front` est rendu côté serveur (Twig) pour le référencement. Il n'a ni base de données ni état : il lit le catalogue via `credit-core` et renvoie le trafic vers `credit_tunnel`.

| Route | Contenu |
| --- | --- |
| `/` | Accueil : fonctionnement du crédit à la consommation, les 5 projets, partenaires. |
| `/credit-vert`, `/pret-auto`, `/pret-travaux`, `/pret-personnel`, `/tresorerie-express` | Une page par projet : balises `title` et `description`, titre H1, explication, TAEG « à partir de » tiré du catalogue (plus petit `aprBps` du projet), exemple de mensualité, questions fréquentes, données structurées JSON-LD, mention légale. |
| `/guide/credit-conso` | Guide : comment fonctionne un crédit à la consommation. |
| `/mentions-legales`, `/sitemap.xml`, `/robots.txt` | Pages légales et fichiers d'indexation. |

- Mention légale sur les pages de crédit : « Un crédit vous engage et doit être remboursé. Vérifiez vos capacités de remboursement avant de vous engager. »
- **Acquisition.** `TunnelLinkBuilder` construit les liens vers `credit_tunnel`, par exemple `{TUNNEL_URL}/comparer?project=green_loan&amount=15000&duration=60&step=2`, et y recopie les paramètres `utm_source`, `utm_medium` et `utm_campaign` reçus par la page.
- **Simulateur rapide.** `QuickSimulatorCard` vit dans `credit_tunnel`, page `/simulateur`, pas dans `credit_front`. Il teste en temps réel projet, montant, durée et revenus mensuels nets, et affiche la mensualité et un TAEG approximatif d'après `GET /api/v1/catalog`. Le bouton « Comparer les offres personnalisées » enregistre ces 4 valeurs dans `sessionStorage` et dans les query params, puis ouvre le tunnel à l'étape 2.

### 5.2 Tunnel à 8 étapes (`credit_tunnel`, `ComparisonTunnel.tsx`)

Le tunnel lit les paramètres `project`, `amount`, `duration`, `step` et `utm_*` de l'URL, les garde dans `sessionStorage` et envoie les paramètres UTM dans le champ `acquisition` de `POST /api/v1/compare`. En fin de parcours, il redirige vers `credit_client` selon le `clientStatus` reçu (section 7.2).

| # | Composant | Contenu |
| --- | --- | --- |
| 1 | `Step1Project` | Type de projet (5 cartes avec icônes SVG), montant (`requestedAmountCents`), durée (`durationMonths`). Pré-rempli depuis le simulateur. |
| 2 | `Step2Family` | Situation familiale (`maritalStatus`), switch « Emprunter à deux » (`hasCoBorrower`), enfants à charge (`dependentChildrenCount`). |
| 3 | `Step3HousingLocation` | Statut du logement (`housingStatus`), date d'entrée, code postal et ville. Validation synchrone bloquante : `/^(?:0[1-9]\|[1-8]\d\|9[0-5])\d{3}$/` (métropole et Corse uniquement). |
| 4 | `Step4Employment` | Statut professionnel (`employmentStatus`), catégorie socio-professionnelle, date de début de contrat. Mêmes champs pour le co-emprunteur si `hasCoBorrower === true`. |
| 5 | `Step5Incomes` | Salaire net mensuel (`netMonthlySalaryCents`, pré-rempli depuis le simulateur), revenu du co-emprunteur (`coBorrowerNetSalaryCents`, si présent). Lignes activables par boutons « + Ajouter » : allocations (`familyAllowancesCents`), pensions perçues (`pensionsReceivedCents`), revenus locatifs bruts (`rentalIncomeGrossCents`, avec mention de la prise en compte à 70 %), autres revenus (`otherMonthlyIncomeCents`). |
| 6 | `Step6Charges` | Loyer (si `TENANT`) ou prêt immobilier (si `OWNER_WITH_MORTGAGE`), crédits en cours, pensions versées, autres charges. |
| 7 | `Step7Identity` | Civilité, nom, prénom, date de naissance avec contrôle de majorité (18 ans révolus, 80 ans maximum). |
| 8 | `Step8ContactConsent` | Email, mobile français 06/07, consentement de transmission aux partenaires (obligatoire), consentement de rappel par un conseiller (optionnel), mention de création d'espace client sous le bouton d'envoi (section 7.3). Le submit appelle `POST /api/v1/compare`, puis redirige vers `credit_client` (section 7.2). |

### 5.3 Écran de résultats

Ces composants vivent dans `packages/credit-front-core/src/components/results/` : `credit_client` et `credit_crm` affichent tous deux le résultat d'un dossier.

- **`QuoteResultsView`** : affiche le résumé de scoring puis, selon la `decision`, les cartes d'offres (`INSTANT_OFFERS`), l'écran de rattrapage (`ADVISOR_CALLBACK_NEEDED`) ou l'écran de refus (`HARD_REJECT`). Il n'est jamais affiché dans le tunnel : il est monté par `ClientDossierPage` (`credit_client`, section 7.8) et par `CrmDossierPage` (`credit_crm`, section 8.5).
- **`CardOffer`** : carte d'offre unique pour tous les partenaires (code en 5.4).
- **`ScoringBadgePanel`** : jauge d'endettement avant/après, reste à vivre, grade A à E.
- **`AdvisorFallbackCard`** : écran rassurant « Un conseiller va vous rappeler ». Il explique pourquoi la demande ne passe pas telle quelle, liste les `AdjustmentSuggestionDto` (montant plus bas, durée plus longue, compromis) et confirme le numéro de rappel.
- **`NotFinanceableCard`** : écran sobre qui expose les motifs de `RejectionDto`, sans promettre de rappel ni de solution.

### 5.4 Composant générique `<CardOffer/>` (`packages/credit-front-core/src/components/results/CardOffer.tsx`)

Le composant applique uniformément le style de la plateforme, sans style inline dynamique lié au partenaire.

```tsx
import React from 'react';
import { QuoteOfferDto } from '../../types/credit.types';
import { formatCentsToEuros, formatBpsToPercent } from '../../utils/moneyFormatter';

interface CardOfferProps {
  offer: QuoteOfferDto;
  /** Optionnel : sans lui (vue conseiller dans credit_crm), le bouton n'est pas affiché. */
  onSelectOffer?: (offer: QuoteOfferDto) => void;
}

export const CardOffer: React.FC<CardOfferProps> = ({ offer, onSelectOffer }) => {
  return (
    <article className="card-offer">
      {offer.isBestRate && (
        <div className="offer-ribbon">
          <span>Meilleur taux pour votre profil</span>
        </div>
      )}

      <div className="offer-body">
        {/* Colonne 1 : logo et nom du partenaire */}
        <div className="partner-identity">
          <div className="logo-wrapper">
            <img src={offer.partnerLogo} alt={offer.partnerName} loading="lazy" />
          </div>
          <div className="partner-meta">
            <h3 className="partner-name">{offer.partnerName}</h3>
            <p className="product-name">{offer.productName}</p>
          </div>
        </div>

        {/* Colonne 2 : mensualité et TAEG */}
        <div className="financial-metrics">
          <div className="metric-primary">
            <span className="metric-label">Mensualité hors assurance</span>
            <strong className="installment-value">
              {formatCentsToEuros(offer.monthlyInstallmentCents)}
              <small>/mois</small>
            </strong>
          </div>

          <div className="metric-grid">
            <div className="metric-item">
              <span className="metric-label">TAEG fixe</span>
              <strong className="metric-value">{formatBpsToPercent(offer.aprBps)}</strong>
            </div>
            <div className="metric-item">
              <span className="metric-label">Taux débiteur</span>
              <span className="metric-value">{formatBpsToPercent(offer.nominalRateBps)}</span>
            </div>
            <div className="metric-item">
              <span className="metric-label">Coût total du crédit</span>
              <span className="metric-value">{formatCentsToEuros(offer.totalFinancingCostCents)}</span>
            </div>
            <div className="metric-item">
              <span className="metric-label">Dont intérêts</span>
              <span className="metric-value">{formatCentsToEuros(offer.totalInterestCents)}</span>
            </div>
          </div>
        </div>

        {/* Colonne 3 : appel à l'action */}
        <div className="offer-actions">
          {onSelectOffer && (
            <button
              type="button"
              className="btn-select-offer"
              onClick={() => onSelectOffer(offer)}
            >
              Choisir cette offre
            </button>
          )}
          <span className="insurance-hint">
            Assurance facultative : +{formatCentsToEuros(offer.monthlyInsuranceCents)}/mois
          </span>
        </div>
      </div>
    </article>
  );
};
```

---

## 6. Dépendances et configuration

Ces libs viennent de dépôts GitHub `fagathe-dev`. L'agent les utilise quand elles couvrent un besoin (utilitaires, composants, services) plutôt que de réécrire l'équivalent. Les règles de la section 2 restent prioritaires : pas de `lucide-react`, pas de BEM, montants en centimes et taux en points de base.

### 6.1 Front (`packages/credit-front-core/package.json`)

```jsonc
"dependencies": {
  "core-react": "git+https://github.com/fagathe-dev/core-react.git#master",
  "core-ts": "git+https://github.com/fagathe-dev/core-ts.git#master"
}
```

Une application React qui importe directement ces libs les déclare aussi dans son propre `package.json`. Les trois applications dépendent de `credit-front-core` par son nom de workspace.

### 6.2 API (`apps/credit_api/composer.json`)

```jsonc
"require": {
  "credit/core": "*",
  "fagathe-dev/core-php": ">=1.1.4"
},
"repositories": {
  "credit-core": {
    "type": "path",
    "url": "../../packages/credit-core",
    "options": { "symlink": true }
  },
  "core-php": {
    "type": "vcs",
    "url": "https://github.com/fagathe-dev/core-php.git"
  }
}
```

`apps/credit_front/composer.json` déclare le même dépôt `credit-core` et la dépendance `credit/core`. Il n'ajoute `fagathe-dev/core-php` que s'il en a besoin.

### 6.3 Fichiers `.env` versionnés (sans secret)

Chaque application a son `.env`, versionné et sans secret (règle 7 de la section 2). Les valeurs réelles et les secrets se mettent dans `.env.local` ou `.env.develop`, que l'agent ne touche jamais. Les valeurs ci-dessous sont des défauts de développement local (ports de 3.2). Les noms marqués « proposé » sont des ajouts de ma part, les autres viennent de votre liste.

#### `apps/credit_api/.env`

```dotenv
###> symfony/framework-bundle ###
APP_ENV=dev
# Secret : à définir dans .env.local
APP_SECRET=
###< symfony/framework-bundle ###

# URI publique de credit_api (URL hors requête HTTP, URL absolue des logos)
DEFAULT_URI=http://localhost:8000

###> doctrine/doctrine-bundle ###
# Secret : DSN complet à définir dans .env.local (moteur à choisir)
DATABASE_URL=
###< doctrine/doctrine-bundle ###

###> symfony/mailer ###
# Secret : à définir dans .env.local
MAILER_DSN=
###< symfony/mailer ###

###> lexik/jwt-authentication-bundle ###
# Chemins seulement : les fichiers .pem ne sont jamais versionnés
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
# Secret : à définir dans .env.local
JWT_PASSPHRASE=
# Durée de la session en secondes (proposé, à fixer, 7.6)
JWT_TTL=3600
###< lexik/jwt-authentication-bundle ###

###> session et cookies (proposé) ###
# Drapeau Secure du cookie : false en local HTTP, true en production
AUTH_COOKIE_SECURE=false
# Domaine parent partagé par les 3 apps React et l'API ; vide en local (localhost, ports différents)
AUTH_COOKIE_DOMAIN=
# Origines autorisées avec cookies (credit_tunnel, credit_client, credit_crm), séparées par des virgules
CORS_ALLOW_ORIGIN=http://localhost:5173,http://localhost:5174,http://localhost:5175
###< session et cookies ###

###> code OTP (proposé, valeurs par défaut de 7.5) ###
OTP_TTL_MINUTES=10
OTP_MAX_ATTEMPTS=5
OTP_RESEND_DELAY_SECONDS=60
###< code OTP ###

###> fagathe/gather-core ###
# Base des liens envoyés par e-mail aux clients : URL de credit_client (à défaut : origine de la requête)
FRONTEND_URL=http://localhost:5174
APP_NAME="Comparateur de crédit"
DEFAULT_EMAIL_SENDER=no-reply@localhost
SUPPORT_EMAIL=support@localhost
# Chemin du logo des emails (proposé : un des logos de la plateforme dans public/)
LOGO_PATH=public/assets/logo.svg
###< fagathe/gather-core ###

###> back-office (proposé) ###
# URL de credit_crm, base des liens des emails envoyés aux conseillers
CRM_URL=http://localhost:5175
###< back-office ###
```

#### `apps/credit_front/.env`

```dotenv
###> symfony/framework-bundle ###
APP_ENV=dev
# Secret : à définir dans .env.local
APP_SECRET=
###< symfony/framework-bundle ###

DEFAULT_URI=http://localhost:8001

###> site vitrine (proposé) ###
# URL publique du site : URL canoniques, sitemap.xml, JSON-LD
SITE_URL=http://localhost:8001
# URL de credit_tunnel : base des boutons d'appel à l'action (TunnelLinkBuilder)
TUNNEL_URL=http://localhost:5173
APP_NAME="Comparateur de crédit"
###< site vitrine ###
```

`credit_front` n'a ni base de données, ni mailer, ni JWT : aucune de ces variables n'y figure.

#### Applications React (variables lues par Vite, toutes préfixées `VITE_`, donc publiques)

Une variable `VITE_*` est incluse dans le code envoyé au navigateur : elle ne contient jamais de secret.

`apps/credit_tunnel/.env`

```dotenv
# URL publique de credit_api
VITE_API_URL=http://localhost:8000
# URL de credit_client : cible de la redirection de fin de tunnel
VITE_CLIENT_URL=http://localhost:5174
VITE_APP_NAME="Comparateur de crédit"
```

`apps/credit_client/.env`

```dotenv
VITE_API_URL=http://localhost:8000
# URL de credit_tunnel : lien « Nouvelle demande »
VITE_TUNNEL_URL=http://localhost:5173
VITE_APP_NAME="Comparateur de crédit"
```

`apps/credit_crm/.env`

```dotenv
VITE_API_URL=http://localhost:8000
VITE_APP_NAME="Comparateur de crédit"
```

#### Rôle des variables de `credit_api`

| Variable | Rôle |
| --- | --- |
| `APP_ENV`, `APP_SECRET` | Environnement Symfony et secret du framework (vide, défini dans `.env.local`). |
| `DEFAULT_URI` | URI publique de `credit_api`, pour les URL hors requête HTTP et l'URL absolue des logos (`partnerLogo`). |
| `DATABASE_URL` | Base de données de l'espace client (section 7). Moteur à choisir. Secret. |
| `MAILER_DSN` | Transport Symfony Mailer, pour envoyer les codes OTP. Secret. |
| `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE` | Clés de `lexik/jwt-authentication-bundle`, qui signe la session. Les deux chemins sont versionnés, la passphrase est secrète. |
| `JWT_TTL` | Durée de la session, en secondes. |
| `AUTH_COOKIE_SECURE` | Drapeau `Secure` des cookies de session. |
| `AUTH_COOKIE_DOMAIN` | Domaine parent des cookies de session (ex. `.exemple.fr`). Voir 7.6. |
| `CORS_ALLOW_ORIGIN` | Origines des applications React autorisées avec cookies. |
| `OTP_TTL_MINUTES`, `OTP_MAX_ATTEMPTS`, `OTP_RESEND_DELAY_SECONDS` | Paramètres du code OTP (7.5). |
| `FRONTEND_URL` | Base des liens des emails envoyés aux clients : URL de `credit_client`. |
| `CRM_URL` | Base des liens des emails envoyés aux conseillers : URL de `credit_crm`. |
| `APP_NAME`, `DEFAULT_EMAIL_SENDER`, `SUPPORT_EMAIL`, `LOGO_PATH` | Nom, expéditeur, support et logo des emails. |

En production, les valeurs de ce fichier sont remplacées par les variables d'environnement du serveur ; `AUTH_COOKIE_SECURE` passe à `true`.

---

## 7. Espace client

### 7.1 Principe

Chaque `Applicant` qui termine le tunnel dispose d'un espace client, identifié par son email. Il y retrouve :

- ses informations (identité, contact, consentements) ;
- ses dossiers : un par passage dans le tunnel, avec la date du résultat ;
- pour chaque dossier, le récapitulatif de sa demande et le résultat (devis, pistes d'ajustement ou motifs de refus).

Il n'y a pas de mot de passe : toute connexion se fait par un code OTP envoyé par email. Le co-emprunteur n'a pas de compte ; ses informations sont dans le dossier.

### 7.2 Fin du tunnel

À l'étape 8, `credit_tunnel` appelle `POST /api/v1/compare`. L'API valide la demande (erreur 422 sinon), calcule la décision, puis cherche un client par son email.

1. **Email inconnu.** L'API crée le client en enregistrant le consentement de création d'espace client, enregistre le dossier avec son résultat, pose le cookie de session et répond `clientStatus = "CREATED"` avec le `dossierId`. Le tunnel redirige vers `{VITE_CLIENT_URL}/dossiers/{dossierId}`.
2. **Email connu, sans session valide.** L'API enregistre le dossier au statut `PENDING_VERIFICATION` (invisible pour tous), envoie un code OTP lié à ce dossier et répond `clientStatus = "OTP_REQUIRED"` avec le `dossierId`. Le tunnel redirige vers `{VITE_CLIENT_URL}/code?email={email}&dossierId={dossierId}`, où `credit_client` affiche la saisie du code. Après `POST /api/v1/auth/otp/verify`, la session est ouverte, le dossier passe `ACTIVE` et `credit_client` redirige vers `/dossiers/{dossierId}`.
3. **Email connu, session valide pour ce même client.** Pas d'OTP : le dossier est directement `ACTIVE`, la réponse porte `clientStatus = "AUTHENTICATED"` et le tunnel redirige vers `{VITE_CLIENT_URL}/dossiers/{dossierId}`.

La réponse de `POST /api/v1/compare` ne contient ni devis ni diagnostic : `ComparisonSubmissionDto` porte seulement `clientStatus` et `dossierId`. Le résultat est lu ensuite dans l'espace client.

### 7.3 Consentements

Le tunnel porte trois consentements. Chacun est enregistré avec sa date, jamais en simple booléen.

| Champ | Obligation | Texte | Effet |
| --- | --- | --- | --- |
| `consentPartnerTransmission` | Obligatoire | « J'accepte que mes données soient transmises aux établissements financiers partenaires afin d'obtenir mes offres de crédit personnalisées. » | Sans lui, erreur 422. |
| `consentAdvisorCallback` | Optionnel, décoché par défaut | « J'accepte d'être rappelé par un conseiller, pour ajuster mon dossier si c'est possible ou pour me proposer d'autres solutions de financement. » (libellé proposé) | Un dossier `ADVISOR_CALLBACK_NEEDED` n'est rappelé que si ce consentement est donné. Le client peut le donner ou le retirer depuis son espace. |
| `consentClientSpaceCreation` | Obligatoire pour un nouveau client | « En soumettant ce formulaire, j'accepte la création de mon espace client […] » (texte complet à fournir) | Enregistré seulement à la création du compte, ignoré pour un client existant. Erreur 422 pour un nouveau client si la valeur n'est pas `true`. |

Le troisième consentement est une mention affichée sous le bouton d'envoi, pas une case à cocher. Le front envoie `consentClientSpaceCreation = true` à la soumission. Il ne sait pas si l'email est connu avant l'envoi : la mention s'affiche donc pour tous, et l'API décide.

Ces trois consentements remplacent ceux des versions précédentes (rappel téléphonique obligatoire, offres promotionnelles).

### 7.4 Données persistées

Ces entités Doctrine, définies dans `credit_api`, sont les seules persistées (quatre avec `Advisor`, section 8.2). Montants en centimes et taux en points de base partout, y compris dans les JSON.

| Entité | Champs |
| --- | --- |
| `Client` | `id` (UUIDv7), `email` (unique), `civility`, `firstName`, `lastName`, `birthDate`, `phone`, `consentClientSpaceCreatedAt`, `createdAt`, `lastLoginAt` |
| `Dossier` | `id` (UUIDv7), `client`, `status` (`PENDING_VERIFICATION`, `ACTIVE`), `submittedAt` (date du résultat), `decision`, `requestSnapshot` (JSON : les 8 étapes sans les consentements), `scoringSummary` (JSON), `quotes` (JSON : liste de `QuoteOfferDto`), `advisorFallback` (JSON, nullable), `rejection` (JSON, nullable), `consentPartnerTransmissionAt`, `consentAdvisorCallbackAt` (nullable), `acquisition` (JSON nullable : `utm_source`, `utm_medium`, `utm_campaign`, `referrer`), `callbackStatus`, `advisorNote`, `callbackUpdatedAt`, `callbackAdvisor` (ces quatre derniers : section 8.2) |
| `OtpCode` | `id`, `client` (nullable), `advisor` (nullable), `dossier` (nullable), `codeHash`, `expiresAt`, `attempts`, `consumedAt`, `createdAt` |
| `Advisor` | Voir 8.2. |

Les devis sont figés dans le dossier au moment du résultat : une évolution du catalogue JSON ne change pas un dossier déjà enregistré.

### 7.5 Code OTP

Valeurs proposées par défaut :

- 6 chiffres, valable 10 minutes, usage unique.
- Stocké haché, jamais en clair.
- 5 essais maximum, puis le code est invalidé.
- Un nouveau code invalide les précédents. Un renvoi par minute au maximum.
- `POST /api/v1/auth/otp/request` répond toujours 204, que l'email existe ou non.
- Un OTP lié à un dossier ne rattache que ce dossier. Un OTP de connexion simple n'en rattache aucun.

L'email part avec Symfony Mailer (`MAILER_DSN`) : expéditeur `DEFAULT_EMAIL_SENDER`, nom `APP_NAME`, logo `LOGO_PATH`, contact `SUPPORT_EMAIL`, lien vers `FRONTEND_URL/code` pour un client, `CRM_URL/code` pour un conseiller. Objet : « Votre code de connexion {APP_NAME} ».

### 7.6 Session

- Session par JWT (`lexik/jwt-authentication-bundle`) dans le cookie `client_session`, `HttpOnly`, `SameSite=Lax`, drapeau `Secure` selon `AUTH_COOKIE_SECURE`, posé sur le domaine parent `AUTH_COOKIE_DOMAIN`.
- Les routes `/api/v1/me/**` exigent un client authentifié (401 sinon).
- `credit_tunnel`, `credit_client` et `credit_api` doivent être servis sous un même domaine parent (ex. `tunnel.exemple.fr`, `client.exemple.fr`, `api.exemple.fr`). Sans cela, le cookie posé par l'appel de fin de tunnel n'est pas envoyé par `credit_client`.
- Tous les appels de `credit-front-core` utilisent `credentials: 'include'`, et `CORS_ALLOW_ORIGIN` autorise les origines des applications React avec leurs cookies.
- Durée de vie de la session à fixer.

### 7.7 Endpoints

| Méthode et route | Accès | Rôle |
| --- | --- | --- |
| `POST /api/v1/compare` | Public | Valide, calcule, enregistre le dossier. Répond `ComparisonSubmissionDto` (`clientStatus` : `CREATED`, `OTP_REQUIRED` ou `AUTHENTICATED`, et `dossierId`). |
| `POST /api/v1/auth/otp/request` | Public | Corps `{ email }`. Envoie un code si le compte existe. Répond toujours 204. |
| `POST /api/v1/auth/otp/verify` | Public | Corps `{ email, code }`. 200 avec cookie de session et `dossierId` éventuel ; 401 si code invalide ou expiré ; 429 après trop d'essais. |
| `POST /api/v1/auth/logout` | Client | Supprime le cookie de session. |
| `GET /api/v1/me` | Client | `ClientProfileDto` : informations et état des consentements. |
| `PATCH /api/v1/me/consents` | Client | Corps `{ consentAdvisorCallback }`. Donne ou retire le consentement de rappel. |
| `GET /api/v1/me/dossiers` | Client | Liste de `DossierSummaryDto` : `id`, `submittedAt`, type de projet, montant, durée, décision, nombre de devis. Seuls les dossiers `ACTIVE`. |
| `GET /api/v1/me/dossiers/{id}` | Client | `DossierDetailDto` : `submittedAt`, récapitulatif, `scoringSummary`, `decision` et, selon la décision, `quotes`, `advisorFallback` ou `rejection`. |
| `GET /api/v1/catalog` | Public | Inchangé. |

### 7.8 Front (`credit_client`)

| Route | Page | Contenu |
| --- | --- | --- |
| `/connexion` | `ClientLoginPage` | `EmailForm` : saisie de l'email, demande du code. |
| `/code` | `ClientOtpPage` | `OtpForm` : saisie du code. Reçoit aussi le cas `OTP_REQUIRED` venu du tunnel, avec `email` et `dossierId` en query params. |
| `/` | `ClientDashboardPage` | `ProfileCard`, `ConsentSettings` (dont la bascule du rappel conseiller) et `DossierList`, du plus récent au plus ancien. |
| `/dossiers/:id` | `ClientDossierPage` | « Résultat du {date} », `DossierRecap`, puis `QuoteResultsView` (`CardOffer`, `AdvisorFallbackCard` ou `NotFinanceableCard`). |

Toutes les routes sauf `/connexion` et `/code` sont protégées par `ClientSpaceLayout` : une réponse 401 redirige vers la connexion. Le tableau de bord propose un lien « Nouvelle demande » vers `credit_tunnel` (`VITE_TUNNEL_URL`). Quand le dossier est `ADVISOR_CALLBACK_NEEDED` sans consentement de rappel, `AdvisorFallbackCard` affiche les pistes et un bouton pour demander à être rappelé (`PATCH /api/v1/me/consents`).

---

## 8. Back-office conseillers (`credit_crm`)

> Section proposée par moi : la demande mentionnait l'application `credit_crm` sans en détailler le contenu. Le périmètre ci-dessous est le minimum cohérent avec les rappels de la section 7 ; à ajuster.

### 8.1 Rôle

`credit_crm` permet aux conseillers de traiter les dossiers dont le client a donné `consentAdvisorCallbackAt` et dont le statut est `ACTIVE`. La file présente d'abord les dossiers `ADVISOR_CALLBACK_NEEDED` (ajuster montant ou durée avec l'`Applicant`), puis les autres décisions (prospection).

### 8.2 Données (dans `credit_api`)

| Entité / champ | Contenu |
| --- | --- |
| `Advisor` | `id` (UUIDv7), `email` (unique), `firstName`, `lastName`, `isActive`, `createdAt`, `lastLoginAt`. Créé par une commande console (`app:advisor:create`), pas d'inscription publique. |
| `Dossier.callbackStatus` | `CallbackStatusEnum` : `TO_CALL` (valeur initiale quand le consentement est donné), `NO_ANSWER`, `CALLBACK_SCHEDULED`, `ADJUSTED`, `DECLINED`, `CLOSED`. |
| `Dossier.advisorNote` | Texte libre du conseiller. |
| `Dossier.callbackUpdatedAt` | Date de la dernière mise à jour du suivi. |
| `Dossier.callbackAdvisor` | `Advisor` ayant traité le dossier en dernier (nullable). |

### 8.3 Authentification des conseillers

Même mécanisme que le client (7.5 et 7.6), sur des routes et un firewall séparés :

- `POST /api/v1/crm/auth/otp/request` répond toujours 204. `POST /api/v1/crm/auth/otp/verify` ouvre la session. `POST /api/v1/crm/auth/logout` la ferme.
- Seuls les `Advisor` actifs reçoivent un code. Le lien de l'email pointe vers `CRM_URL/code`.
- Cookie `advisor_session`, rôle `ROLE_ADVISOR`. Un cookie client ne donne aucun accès aux routes `/api/v1/crm/**`, et inversement.

### 8.4 Endpoints

| Route | Accès | Rôle |
| --- | --- | --- |
| `GET /api/v1/crm/me` | Conseiller | Profil du conseiller connecté. |
| `GET /api/v1/crm/callbacks` | Conseiller | Liste de `CrmCallbackItemDto`. Filtres : `decision`, `callbackStatus`. Tri : `ADVISOR_CALLBACK_NEEDED` d'abord, puis les plus anciens d'abord. Paginée. |
| `GET /api/v1/crm/dossiers/{id}` | Conseiller | `CrmDossierDetailDto` : coordonnées (nom, email, mobile), récapitulatif, `scoringSummary`, décision, devis ou pistes d'ajustement, suivi d'appel, `acquisition`. |
| `PATCH /api/v1/crm/dossiers/{id}/callback` | Conseiller | Corps `{ callbackStatus, advisorNote }`. Met à jour le suivi, `callbackUpdatedAt` et `callbackAdvisor`. |

Seuls les dossiers `ACTIVE` avec consentement de rappel sont accessibles : 404 sinon.

### 8.5 Front (`credit_crm`)

| Route | Page | Contenu |
| --- | --- | --- |
| `/connexion` | `CrmLoginPage` | `EmailForm` de `credit-front-core`. |
| `/code` | `CrmOtpPage` | `OtpForm` de `credit-front-core`. |
| `/` | `CallbackQueuePage` | `CallbackTable` : file de rappels, filtres par décision et statut. |
| `/dossiers/:id` | `CrmDossierPage` | Récapitulatif (`DossierRecap`), score (`ScoringBadgePanel`), devis via `CardOffer` sans `onSelectOffer`, et `CallbackFollowUpForm` (statut et note). |

Toutes les routes sauf `/connexion` et `/code` sont protégées par `CrmLayout` : une réponse 401 redirige vers la connexion.

---

## Notes de remise au propre

Le fichier d'origine était tronqué au milieu de l'étape 5 du tunnel. Les étapes 6 à 8 et la section 5.3 sont reconstruites à partir des commentaires de l'arborescence et de la section 4.4 : à relire. Le détail des champs de ces étapes (par exemple les consentements) reprend la spec précédente du tunnel à 8 étapes, non celle-ci.

La distinction entre dossier à ajuster et dossier non financable (section 4.4) a été ajoutée ensuite. La section 7 (espace client) a été ajoutée ensuite. Elle remplace la spec « sans base de données » : la demande de rappel est désormais enregistrée dans le dossier, et l'outil côté conseiller qui les traite est proposé en section 8.

Le passage en monorepo a ensuite réorganisé le tout (sections 1, 3 et 5 à 8). Hypothèses à valider :
- **Outillage** : « sans pnpm » est interprété comme des workspaces npm à la racine, des dépôts Composer de type `path` avec lien symbolique, et deux scripts Bash (`bin/setup.sh`, `bin/dev.sh`). Je n'ai pas vu votre projet `cms` ; à aligner sur lui.
- **Nommage des packages** : `credit/core` (namespace `Credit\Core\`) pour le package Composer, et le nom du workspace npm de `credit-front-core` est à fixer.
- **`credit_crm`** : le contenu de l'application est ma proposition (section 8).
- **Cookies** : la session client partagée entre `credit_tunnel`, `credit_client` et `credit_api` impose un domaine parent commun (`AUTH_COOKIE_DOMAIN`, 6.3 et 7.6).
- **UTM** : leur stockage dans `Dossier.acquisition` est ma proposition.
- **Ports et noms de variables** de 6.3 : valeurs par défaut proposées.
- **Fichiers `.env`** : les `.env.example` sont remplacés par des `.env` versionnés sans secret (règle 7). Les variables marquées « proposé » en 6.3 (`JWT_TTL`, `OTP_*`, `AUTH_COOKIE_DOMAIN`, `CORS_ALLOW_ORIGIN`, `CRM_URL`, `SITE_URL`, `TUNNEL_URL`, `VITE_*`) sont des ajouts de ma part. Le moteur de `DATABASE_URL` reste à choisir. Je n'ai pas vu vos `.env.local` ni `.env.develop` : les valeurs par défaut sont indépendantes de leur contenu.
- **Maquettes** : le document Docs et l'aperçu de design produits plus tôt reflètent l'ancienne version (8 étapes, sans monorepo) et ne sont pas à jour. Ce fichier markdown fait foi.

Points à trancher sur l'espace client :
- Texte complet du consentement de création d'espace client : seul « En soumettant ce formulaire, j'accepte la création de mon espace client » est connu, la suite est à fournir. Le libellé du consentement de rappel (7.3) est ma proposition.
- À la création, l'email n'est pas vérifié et la session s'ouvre tout de suite, comme demandé. Seules les données saisies par la personne sont exposées, mais quelqu'un peut créer un compte avec l'email d'un autre. Les connexions suivantes passent toutes par un code OTP.
- Pour un client reconnu, le statut `OTP_REQUIRED` révèle qu'un compte existe pour cet email. C'est inhérent à ce flux ; la limitation du nombre de demandes (7.5) atténue le risque.
- Durées à fixer : validité de la session, conservation des dossiers et des données (RGPD), purge des dossiers `PENDING_VERIFICATION` jamais confirmés.
- Les valeurs du code OTP (6 chiffres, 10 minutes, 5 essais, renvoi 1 par minute) sont des valeurs par défaut proposées.
- L'action « Choisir cette offre » n'a toujours pas de suite définie (souscription, prise de contact, etc.).
- Le pré-remplissage du tunnel pour un client connecté et la modification du profil depuis l'espace client sont hors périmètre.

Points à trancher sur le format JSON des partenaires (4.5) :
- L'exemple CrediNova est incohérent : 200 €/mois sur 24 mois font 4 800 €, pas 3 930 €, et à 4,23 % un prêt de 3 500 € sur 24 mois coûte environ 152 €/mois. Comme ces champs ne servent qu'à l'affichage, ce n'est pas bloquant, mais il faut corriger les valeurs.
- Le TAEG de `NOVA_EQUIP_MAISON` est 5,05 % dans le JSON et 5,40 % dans le tableau de 4.3. Le JSON est la source de vérité à terme : à confirmer, puis à reporter dans le tableau.
- `minRemainderToLive` (800 € pour cette offre) s'ajoute au reste à vivre du foyer (750 € ou 1 100 €, plus 250 € par enfant). Hypothèse retenue : le seuil appliqué est le plus élevé des deux.
- La formule de `monthlyInsuranceCents` à partir de `insuranceRate` n'est pas donnée.
- Les logos ne sont plus des composants React mais des fichiers SVG servis par `credit_api` : `PartnerLogoSvg.tsx` est retiré de l'arborescence.

Je n'ai pas consulté le contenu des libs `core-react`, `core-ts` et `core-php`. Les rôles des variables fagathe/gather-core en 6.3 viennent de leur nom et du commentaire fourni, à corriger si besoin. Les variables JWT, `AUTH_COOKIE_SECURE`, `DATABASE_URL` et `MAILER_DSN` servent à l'espace client (section 7). Le comparateur lui-même reste public.
