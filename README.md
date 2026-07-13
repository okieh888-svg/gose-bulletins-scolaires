# GOSE — Module de génération des bulletins scolaires

> **Prototype de démonstration technique** réalisé dans le cadre d'une
> **manifestation d'intérêt** relative à la plateforme GOSE (Gestion des
> Établissements Scolaires) du Ministère de l'Éducation Nationale et de la
> Formation Professionnelle (MENFOP) de la République de Djibouti.
>
> ⚠️ **Toutes les données de ce dépôt sont 100% FICTIVES.** Établissements,
> personnels, élèves, notes, matricules : aucun ne correspond à une personne
> réelle. **Aucune donnée réelle d'élève (mineur) n'est utilisée**, ni ici ni
> dans aucun jeu de fixtures livré avec ce prototype.

## 1. Contexte et périmètre

Ce dépôt répond à la partie du TDR portant sur le **module « Bulletins
scolaires »** de GOSE : calcul des moyennes, classement des élèves,
génération de bulletins PDF bilingues, et workflow de validation
enseignant → chef d'établissement → publication.

Il intègre également, dès ce prototype, la **couche d'authentification et de
gestion des rôles (RBAC)** qui structurera l'ensemble de la plateforme GOSE
(quatre profils : Administrateur, Proviseur, Enseignant, Élève), avec un
soin particulier porté au **cloisonnement par établissement** et à la
**protection des données des élèves mineurs**.

Le support des noms en écriture arabe (élèves, matières, appréciations) et
la génération d'un bulletin PDF intégralement bilingue (français / arabe,
RTL) préfigurent le **Lot 8 (arabisation)** du TDR.

## 2. Stack technique

- PHP 8.2, Symfony 6.4 (LTS)
- MySQL 8
- Doctrine ORM (attributs PHP 8, pas d'annotations)
- Twig
- dompdf (génération PDF à partir de gabarits Twig/HTML)
- Symfony Security (form login, sessions, Voters)
- Docker (php-fpm + nginx + mysql + adminer)

Aucun framework CSS/JS externe : l'interface est en CSS "sobre et
professionnel" auto-hébergé (`public/css/app.css`), sans dépendance à un CDN
(le conteneur peut ainsi fonctionner sans accès Internet après le build).

## 3. Prérequis

- Docker et Docker Compose (`docker-compose up` doit suffire à tout démarrer)
- Ports libres sur la machine hôte : `8080` (application), `8081` (Adminer),
  `3307` (MySQL, exposé pour un client SQL externe)

Aucune installation locale de PHP/Composer/MySQL n'est nécessaire : tout
s'exécute dans les conteneurs.

## 4. Démarrage

```bash
# 1. Cloner le dépôt puis se placer dedans
cd gose-bulletins-scolaires

# 2. Construire et démarrer la stack
docker-compose up -d --build

# 3. Créer le schéma de base de données
#    (prototype : schema:create direct, plutôt que des migrations —
#    voir la note en fin de section)
docker-compose exec php php bin/console doctrine:schema:create

# 4. Charger le jeu de données fictif de démonstration
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

# 5. Ouvrir l'application
#    http://localhost:8080
```

Un `Makefile` regroupe ces commandes (`make up`, `make schema`, `make
fixtures`, `make test`, `make sh`, ...) pour qui préfère `make` à `docker-compose
exec` répété.

Adminer (interface d'administration MySQL) est disponible sur
`http://localhost:8081` (serveur : `mysql`, utilisateur : `gose`, mot de
passe : `gose`, base : `gose_bulletins`).

> **Note migrations vs schema:create.** Ce prototype crée le schéma
> directement via `doctrine:schema:create` pour simplifier la prise en main.
> `doctrine/doctrine-migrations-bundle` est néanmoins déjà présent dans
> `composer.json` : le passage à des migrations versionnées
> (`doctrine:migrations:diff` puis `doctrine:migrations:migrate`) est
> immédiat le jour où ce module entrerait en développement continu.

## 5. Comptes de démonstration

Tous les comptes utilisent le mot de passe **`Demo123!`** — volontairement
simple, clairement identifié comme réservé à la démonstration.

| Rôle | Établissement | Email | Mot de passe |
|---|---|---|---|
| Administrateur | (aucun — vue multi-établissements) | `admin@gose.dj` | `Demo123!` |
| Proviseur | Lycée de Balbala | `proviseur.balbala@gose.dj` | `Demo123!` |
| Proviseur | Collège d'Arta *(établissement témoin cloisonnement)* | `proviseur.arta@gose.dj` | `Demo123!` |
| Enseignant | Lycée de Balbala | `ens.mahamoud@gose.dj` | `Demo123!` |
| Enseignant | Lycée de Balbala | `ens.amina@gose.dj` | `Demo123!` |
| Élève | Lycée de Balbala | `eleve.hibo@gose.dj` | `Demo123!` |
| Élève (nom en écriture arabe) | Lycée de Balbala | `eleve.yasin@gose.dj` | `Demo123!` |
| Élève (nom en écriture arabe) | Lycée de Balbala | `eleve.zahra@gose.dj` | `Demo123!` |

La liste complète des ~18 comptes élèves/enseignants générés se trouve dans
`src/DataFixtures/AppFixtures.php` (tous les emails y sont explicites et
statiques, donc reproductibles d'un chargement de fixtures à l'autre).

## 6. Jeu de données de démonstration

- **2 établissements** : Lycée de Balbala (établissement principal de la
  démonstration) et Collège d'Arta (établissement témoin, utilisé
  uniquement pour prouver le cloisonnement inter-établissements).
- **2 classes** (6ème A / 6ème B) au Lycée de Balbala, **15 élèves** au
  total, dont plusieurs avec un **nom en écriture arabe** (support UTF-8 /
  RTL démontré dès ce prototype).
- **8 matières à coefficients** (Français, Mathématiques, SVT,
  Physique-Chimie, Histoire-Géographie, Anglais, Arabe, Éducation
  Islamique) — Arabe et Éducation Islamique disposent en plus d'un libellé
  en arabe, utilisé par le bulletin bilingue.
- **3 trimestres**, avec un état volontairement différent pour dérouler
  une démonstration complète (voir section 8) :
  - **Trimestre 1** : notes saisies, bulletins générés, validés et
    **publiés** (déjà visibles côté élève).
  - **Trimestre 2** : notes saisies, bulletins générés et **validés par
    l'enseignant**, en attente de publication par le proviseur.
  - **Trimestre 3** : uniquement des notes saisies, **aucun bulletin
    généré** — permet de rejouer le parcours complet de bout en bout.

## 7. Authentification et rôles (RBAC)

Quatre rôles, quatre périmètres, appliqués par des **Voters Symfony**
(`src/Security/Voter/`) — pas par des `if` dispersés dans les contrôleurs :

| Rôle | Périmètre |
|---|---|
| `ROLE_ADMIN` | Gère tous les établissements et tous les comptes. |
| `ROLE_PROVISEUR` | Voit tout **son** établissement ; valide/publie les bulletins ; consulte les statistiques ; gère les comptes de son établissement. **Ne peut rien voir d'un autre établissement.** |
| `ROLE_ENSEIGNANT` | Voit uniquement **ses** classes/matières affectées ; saisit ses notes ; génère un bulletin en brouillon et le valide. **Ne peut jamais publier.** |
| `ROLE_ELEVE` | Consulte **uniquement son propre** bulletin, et seulement s'il est **publié** ; consulte ses propres notes. Toute tentative d'accès aux données d'un autre élève renvoie un **403**. |

Deux invariants sont garantis par le code ET couverts par des tests
automatisés (`tests/Security/`) :

1. **Un élève n'accède qu'à ses propres données** (`EleveVoter`,
   `BulletinVoter`) — vérifié en comparant l'identité du compte connecté à
   celle du propriétaire de la donnée, jamais la classe ou l'établissement
   seuls.
2. **Cloisonnement par établissement** pour le personnel (`ClasseVoter`,
   `EleveVoter`, `BulletinVoter`, `UserVoter`) — un proviseur ou un
   enseignant d'un établissement n'a **aucun accès** aux données d'un
   autre établissement (démontré avec le Collège d'Arta).

L'interface Twig masque les actions non autorisées (`is_granted()`), et
**chaque contrôleur revérifie systématiquement côté serveur** via
`denyAccessUnlessGranted()` — l'UI n'est jamais la seule barrière.

Toute tentative d'accès refusée (403) est journalisée (voir section 9),
tout comme les connexions et les transitions de workflow.

La session expire automatiquement après **30 minutes d'inactivité**
(`gose.duree_inactivite_max`, voir `config/packages/gose.yaml` et
`framework.yaml: session.gc_maxlifetime`) — adapté à un usage en
établissement sur poste partagé.

## 8. Parcours de démonstration pas à pas

Séquence recommandée pour dérouler la démonstration de bout en bout, en
s'appuyant sur le **Trimestre 3** (seul trimestre sans bulletin préexistant) :

### 8.1 Connexion proviseur — vue d'ensemble

1. Se connecter avec `proviseur.balbala@gose.dj` / `Demo123!`.
2. Observer le tableau de bord établissement, la file des bulletins en
   attente (issue du Trimestre 2) et les statistiques.
3. Se déconnecter.

### 8.2 Connexion enseignant — saisie de notes et génération

1. Se connecter avec `ens.mahamoud@gose.dj` / `Demo123!`.
2. Ouvrir « Mes classes » → 6ème A, sélectionner le Trimestre 3.
3. Ouvrir une matière (ex. Français), saisir une note pour un ou
   plusieurs élèves, enregistrer.
4. Revenir sur la classe, cliquer sur **Générer** pour un élève :
   un bulletin **brouillon** apparaît avec moyenne, rang et appréciation
   calculés automatiquement.
5. Cliquer sur **Valider** : le bulletin passe à l'état **« Validé par
   l'enseignant »**. Constater qu'aucun bouton « Publier » n'est
   disponible pour ce rôle (et qu'une tentative directe sur l'URL de
   publication renverrait un 403 — voir `tests/Security/BulletinVoterTest.php`).
6. Se déconnecter.

### 8.3 Connexion proviseur — publication

1. Se reconnecter avec `proviseur.balbala@gose.dj` / `Demo123!`.
2. Ouvrir « Bulletins à publier » : le bulletin généré à l'étape
   précédente apparaît dans la file.
3. Cliquer sur **Aperçu** pour vérifier le contenu, puis sur **Publier**.
4. Le bulletin passe à l'état **« Publié »** — il devient visible par
   l'élève concerné.
5. Se déconnecter.

### 8.4 Connexion élève — consultation en lecture seule

1. Se connecter avec le compte élève correspondant (ex. `eleve.hibo@gose.dj`
   / `Demo123!` s'il s'agit de Hibo Ahmed Waberi).
2. Ouvrir « Mes bulletins » : le bulletin du Trimestre 3 est désormais
   visible, en plus de ceux déjà publiés au Trimestre 1.
3. Ouvrir « Générer le PDF » (français) puis la variante arabe (RTL) pour
   constater le bilinguisme du document.
4. Tenter de modifier l'URL pour accéder à l'identifiant d'un autre élève
   (`/bulletin/{id}` ou `/eleve/...`) : accès refusé (403), journalisé.

### 8.5 (Optionnel) Démonstration du cloisonnement inter-établissements

1. Se connecter avec `proviseur.arta@gose.dj` / `Demo123!`.
2. Constater que seul le Collège d'Arta est visible : aucune classe, aucun
   élève et aucun bulletin du Lycée de Balbala n'apparaît, ni n'est
   accessible en forçant une URL directe.

## 9. Journalisation

Chaque connexion, chaque transition de workflow (validation, publication),
chaque consultation/téléchargement de bulletin et chaque **accès refusé**
sont journalisés dans la table `journal_acces` (qui / quoi / quand / depuis
quelle IP) — voir `App\Service\JournalisationService` et
`App\EventSubscriber\JournalisationAccesRefuseSubscriber`. Le journal est
consultable depuis le tableau de bord Administrateur.

## 10. Tests automatisés

```bash
docker-compose exec php php vendor/bin/phpunit
# ou : make test
```

Couverture :

- `tests/Service/MoyenneCalculatorTest.php` — moyenne par matière, moyenne
  générale pondérée par coefficient, cas limites (coefficient nul, liste
  vide).
- `tests/Service/RangCalculatorTest.php` — classement, gestion des
  ex-aequo (rang partagé, rang suivant qui "saute").
- `tests/Service/AppreciationServiceTest.php` — barème paramétrable
  (seuils français ET arabe).
- `tests/Security/EleveVoterTest.php` et `BulletinVoterTest.php` — les
  **deux invariants RBAC** (propriété des données élève, cloisonnement par
  établissement) et la règle « l'enseignant ne publie jamais », testés
  directement sur les Voters sans base de données.

## 11. Génération d'un bulletin PDF — exemple

Après avoir chargé les fixtures, un bulletin publié existe déjà (Trimestre
1) pour chaque élève. Pour l'obtenir en PDF :

```bash
# Se connecter en tant qu'élève sur http://localhost:8080, puis
# "Mes bulletins" -> "Générer le PDF" (ou l'URL directe une fois connecté) :
# http://localhost:8080/bulletin/{id}/pdf         (français)
# http://localhost:8080/bulletin/{id}/pdf/arabe   (arabe, RTL)
```

Le PDF est généré à la volée par `App\Service\PdfGenerator` à partir des
gabarits `templates/bulletin/pdf.html.twig` (français) et
`templates/bulletin/pdf_ar.html.twig` (arabe, `dir="rtl"`), rendus par
dompdf. Aucun fichier PDF n'est pré-généré dans ce dépôt : il est produit
à la demande, ce qui est la preuve technique demandée.

> **Limite connue (documentée, non bloquante).** dompdf utilise par défaut
> la police "DejaVu Sans", qui couvre l'arabe de base mais sans la
> typographie optimale d'une police arabe dédiée. Pour une qualité de
> production, embarquer une police telle que *Amiri* ou *Noto Naskh
> Arabic* via `Options::setFontDir()` / `setFontCache()` de dompdf.

## 12. Vérification d'authenticité (anti-falsification)

Un bulletin imprimé ou en PDF doit pouvoir être authentifié par un tiers
(employeur, autre établissement...) qui n'a pas de compte GOSE. Chaque
bulletin **publié** reçoit automatiquement, au moment de la publication par
le proviseur, un **code de vérification unique** (32 caractères
hexadécimaux, 128 bits d'aléa — `App\Service\BulletinWorkflowService`) qui
n'existe jamais tant que le bulletin est en brouillon.

Ce code est imprimé sur le bulletin sous deux formes :
- un **QR code** (généré localement, sans aucun appel réseau, via
  `endroid/qr-code` — voir `App\Service\QrCodeGenerator`) ;
- le **code en clair**, pour une saisie manuelle si le QR code est
  illisible.

Scanner le QR code (ou saisir le code sur `http://localhost:8080/verification`)
ouvre une page **publique, sans authentification** (voir `PUBLIC_ACCESS` dans
`config/packages/security.yaml` et `App\Controller\VerificationController`)
qui confirme l'authenticité et affiche uniquement une **synthèse** (élève,
établissement, période, moyenne générale, rang, appréciation, date et auteur
de la publication) — **jamais le détail des notes par matière**, pour limiter
l'exposition de données sur une page accessible sans compte.

Une régénération du bulletin (par l'enseignant) invalide immédiatement son
ancien code : celui-ci ne redevient valide qu'après une nouvelle publication,
qui en génère un nouveau (`BulletinGenerator::genererPourEleve`).

```bash
# Exemple : récupérer le code d'un bulletin publié puis le vérifier publiquement
docker-compose exec mysql mysql -ugose -pgose gose_bulletins \
  -e "SELECT code_verification FROM bulletin WHERE statut='publie' LIMIT 1;"

# Puis, sans être connecté :
# http://localhost:8080/verification/<code>
```

## 13. Structure du projet

```
gose-bulletins-scolaires/
├── docker-compose.yml, docker/          # php-fpm, nginx, mysql, adminer
├── config/                              # configuration Symfony (routes, security, doctrine...)
├── src/
│   ├── Entity/                          # Etablissement, User, Enseignant, Eleve, Classe,
│   │                                     # Matiere, Affectation, Periode, Note, Bulletin,
│   │                                     # BulletinLigne, JournalAcces
│   ├── Enum/BulletinStatut.php          # workflow brouillon -> validé -> publié
│   ├── Repository/                      # un repository Doctrine par entité
│   ├── Security/
│   │   ├── AppAuthenticator.php         # form login custom (CSRF, journalisation connexion)
│   │   └── Voter/                       # ClasseVoter, EleveVoter, NoteVoter, BulletinVoter, UserVoter
│   ├── Service/
│   │   ├── MoyenneCalculator.php        # moteur de calcul (testable sans Doctrine)
│   │   ├── RangCalculator.php           # classement avec gestion des ex-aequo
│   │   ├── AppreciationService.php      # barème paramétrable (config/packages/gose.yaml)
│   │   ├── BulletinGenerator.php        # orchestration calcul + persistance du brouillon
│   │   ├── BulletinWorkflowService.php  # machine à états du workflow de validation
│   │   ├── PdfGenerator.php             # rendu Twig -> PDF (dompdf), variantes FR/AR
│   │   ├── QrCodeGenerator.php           # QR code local (endroid/qr-code), anti-falsification
│   │   └── JournalisationService.php    # journal d'accès
│   ├── Controller/                      # Security, Dashboard, Enseignant, Proviseur, Eleve,
│   │                                     # Bulletin, Admin, Verification (publique)
│   ├── Form/UserType.php                # création de comptes (proviseur/admin)
│   ├── EventSubscriber/                 # journalisation des accès refusés (403)
│   └── DataFixtures/AppFixtures.php     # jeu de données 100% fictif
├── templates/                           # Twig : base sobre + gabarits par rôle + bulletin (FR/AR)
├── public/css/app.css                   # CSS auto-hébergé, pas de CDN
├── public/images/logo-menfop.jpg        # logo établissement (embarqué en base64 dans les PDF)
├── tests/
│   ├── Service/                         # moteur de calcul
│   └── Security/                        # invariants RBAC (sans base de données)
├── screenshots/                         # captures à fournir (voir screenshots/README.md)
├── LICENSE
└── README.md
```

## 14. Conformité TDR — table de correspondance

| Exigence du TDR | Élément(s) du dépôt |
|---|---|
| Modèle de données (établissement, classe, élève, matière à coefficient, période, note, bulletin) | `src/Entity/*` |
| Calcul de la moyenne par matière et par période | `App\Service\MoyenneCalculator::moyenneMatiere()` + `tests/Service/MoyenneCalculatorTest.php` |
| Calcul de la moyenne générale pondérée par coefficient | `App\Service\MoyenneCalculator::moyenneGenerale()` |
| Rang de l'élève dans la classe | `App\Service\RangCalculator` (gestion des ex-aequo) + `tests/Service/RangCalculatorTest.php` |
| Appréciation automatique selon barème paramétrable | `App\Service\AppreciationService` + `config/packages/gose.yaml: gose.bareme_appreciation` |
| Génération du bulletin PDF avec en-tête/logo établissement | `App\Service\PdfGenerator` + `templates/bulletin/pdf.html.twig` |
| Bulletin bilingue français/arabe (RTL) — préparation Lot 8 | `templates/bulletin/pdf_ar.html.twig`, `Eleve::nomArabe`, `Matiere::nomArabe`, `AppreciationService::appreciationArabe()` |
| Workflow de validation (enseignant → proviseur → publication) | `App\Enum\BulletinStatut`, `App\Service\BulletinWorkflowService`, `BulletinVoter` |
| Archivage / anti-falsification d'un bulletin publié | Code de vérification unique généré à la publication + QR code, page de vérification publique sans compte (`App\Controller\VerificationController`) |
| Authentification (Security component, CSRF, sessions) | `App\Security\AppAuthenticator`, `config/packages/security.yaml`, `templates/security/login.html.twig` |
| RBAC à 4 rôles via Voters (pas de `if` dispersés) | `src/Security/Voter/*` |
| Invariant : un élève n'accède qu'à ses propres données | `EleveVoter`, `BulletinVoter` + `tests/Security/EleveVoterTest.php`, `BulletinVoterTest.php` |
| Invariant : cloisonnement par établissement | Idem, démontré avec 2 établissements dans les fixtures |
| Journalisation des accès (qui/quoi/quand) | `App\Entity\JournalAcces`, `App\Service\JournalisationService`, `JournalisationAccesRefuseSubscriber` |
| Protection des données élèves mineurs | Données 100% fictives ; élève en lecture seule strict ; bulletin visible uniquement une fois publié ; journalisation de tout accès |
| Support UTF-8 / écriture arabe | `Eleve::nomArabe`, `Matiere::nomArabe`, fixtures avec noms en arabe, gabarit PDF RTL dédié |
| Déconnexion automatique après inactivité | `config/packages/framework.yaml: session.gc_maxlifetime`, `config/packages/gose.yaml: gose.duree_inactivite_max` |
| Interface web (liste classes → élèves → aperçu → PDF) | `templates/enseignant/*`, `templates/bulletin/apercu.html.twig` |
| Tableaux de bord différenciés par rôle | `templates/proviseur/*`, `templates/enseignant/*`, `templates/eleve/*` |
| Démarrage reproductible (`docker-compose up`) | `docker-compose.yml`, `docker/php/Dockerfile`, `docker/nginx/default.conf` |
| Jeu de données de démonstration fictif | `src/DataFixtures/AppFixtures.php` (aucune donnée réelle, aucun mineur réel) |
