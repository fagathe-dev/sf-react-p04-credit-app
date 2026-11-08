# PROMPT AGENT : Création des pages légales statiques pour l'application Symfony `credit_front`

## 1. Contexte et Objectif
Tu interviens sur l'application `apps/credit_front` (Symfony 7, Twig).
Ta mission est de générer les pages légales obligatoires pour ce projet de démonstration (portfolio) de comparateur de crédit. 

Ces pages sont strictement statiques. Aucune donnée ne provient de la base de données. Le contenu doit être directement "en dur" dans les fichiers Twig.

## 2. Contraintes Techniques
*   **Contrôleur :** Créer un contrôleur unique `LegalController` pour gérer toutes les routes légales.
*   **Templates :** Utiliser le layout de base (`base.html.twig`) et une structure HTML sémantique avec des classes CSS simples (pas de BEM, utiliser par exemple `.legal-container`, `.legal-header`, `.legal-content`).
*   **Contexte Fictif :** Le contenu légal doit mentionner clairement qu'il s'agit d'un projet de démonstration/portfolio (éditeur : Frédérick AGATHE) et que les partenaires/offres sont fictifs.
*   **Obligations :** Mentionner l'immatriculation fictive à l'ORIAS (registre des intermédiaires) et intégrer l'avertissement légal : *"Un crédit vous engage et doit être remboursé. Vérifiez vos capacités de remboursement avant de vous engager."*

## 3. Liste des pages à générer

### 3.1. `LegalController.php` (dans `apps/credit_front/src/Controller/`)
Créer un contrôleur avec les 4 routes suivantes :
1.  `GET /mentions-legales` -> `legal/mentions.html.twig` (nom : `app_legal_mentions`)
2.  `GET /gestion-donnees-personnelles` -> `legal/privacy.html.twig` (nom : `app_legal_privacy`)
3.  `GET /mentions-comparateurs` -> `legal/comparator.html.twig` (nom : `app_legal_comparator`)
4.  `GET /cgu` -> `legal/cgu.html.twig` (nom : `app_legal_cgu`)

### 3.2. Les templates Twig (dans `apps/credit_front/templates/legal/`)

Génère les 4 fichiers suivants, en étendant `base.html.twig` et en définissant le block `title` et `body` :

**A. `mentions.html.twig` (Mentions légales)**
*   **Titre H1 :** Mentions légales.
*   **Contenu :**
    *   **Éditeur :** Frédérick AGATHE (Développeur Web Fullstack, email : agathefrederick@gmail.com).
    *   **Hébergement :** o2switch (Chemin des Pardiaux, 63000 Clermont-Ferrand, France, 04 44 44 60 40).
    *   **Statut ORIAS (Fictif) :** Mentionner que dans un cadre réel, l'inscription à l'ORIAS (www.orias.fr) est obligatoire pour les IOBSP. Préciser le numéro fictif : *00 000 000*.
    *   **Avertissement :** Projet de démonstration technique (portfolio) non commercial. Aucun crédit ne sera octroyé.

**B. `privacy.html.twig` (Politique de confidentialité - RGPD)**
*   **Titre H1 :** Politique de confidentialité et gestion des données.
*   **Contenu :**
    *   **Finalité :** Évaluation de la solvabilité, interrogation du catalogue partenaire, création de l'espace client.
    *   **Transmission :** Accord explicite pour transmettre les données aux partenaires (Altis Finance, CrediNova, FlashPact, Veloce Crédit, SolvaBank) lors du choix de l'offre.
    *   **Sécurité/Conservation :** Données traitées *in-memory* par l'API Stateless. Stockage de l'espace client sécurisé par authentification sans mot de passe (OTP).
    *   **Droits RGPD :** Droit d'accès, rectification, effacement. Contact : agathefrederick@gmail.com.

**C. `comparator.html.twig` (Mentions relatives au fonctionnement du comparateur)**
*   **Titre H1 :** Fonctionnement de notre comparateur.
*   **Contenu :**
    *   **Classement :** Les offres sont classées par défaut selon le Taux Annuel Effectif Global (TAEG) fixe, du plus bas au plus élevé.
    *   **Périmètre :** Panel restreint de 5 partenaires (fictifs) couvrant 20 produits. Non exhaustif du marché.
    *   **Actualisation/Rémunération :** Dans un cadre réel, le courtier perçoit une commission de l'établissement prêteur sans incidence sur le classement.

**D. `cgu.html.twig` (Conditions Générales d'Utilisation)**
*   **Titre H1 :** Conditions Générales d'Utilisation (CGU).
*   **Contenu :**
    *   **Objet :** Mise à disposition d'un simulateur de crédit et analyse de solvabilité.
    *   **Engagement de l'utilisateur :** Saisie de données financières sincères et exactes.
    *   **Responsabilité :** Propositions à titre indicatif. L'octroi du crédit dépend de l'analyse des pièces par le prêteur.
    *   **Avertissement légal final (très visible) :** *"Un crédit vous engage et doit être remboursé. Vérifiez vos capacités de remboursement avant de vous engager."*

## 4. Ordre d'exécution demandé

1.  Génère le fichier `apps/credit_front/src/Controller/LegalController.php`.
2.  Génère le fichier `apps/credit_front/templates/legal/mentions.html.twig`.
3.  Génère le fichier `apps/credit_front/templates/legal/privacy.html.twig`.
4.  Génère le fichier `apps/credit_front/templates/legal/comparator.html.twig`.
5.  Génère le fichier `apps/credit_front/templates/legal/cgu.html.twig`.

