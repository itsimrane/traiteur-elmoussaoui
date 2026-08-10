# Traiteur EL MOUSSAOUI — Site Web

## Structure du projet

```
Traiteur_Elmoussaoui/
├── index.php              → Page d'accueil
├── pages/                 → Pages publiques (services, galerie, contact...)
├── admin/                 → Panneau d'administration
├── includes/              → Connexion à la base de données (config.php)
├── api/                   → Traitement des formulaires (à venir)
├── css/                   → Feuilles de style
├── js/                    → Scripts JavaScript
└── assets/
    ├── img/                → Images fixes du design (logo, photos statiques)
    └── uploads/            → Photos ajoutées dynamiquement (galerie, devis)
```

## Installation sur XAMPP

1. Copier tout le contenu de ce dossier dans :
   `C:\xampp\htdocs\Traiteur_Elmoussaoui\`

2. Démarrer Apache et MySQL depuis le panneau de contrôle XAMPP

3. Importer la base de données :
   - Ouvrir `http://localhost/phpmyadmin`
   - Créer une base nommée `db_traiteur_elmoussaoui`
   - Importer le fichier `db_traiteur_elmoussaoui.sql`

4. Vérifier le fichier `includes/config.php` :
   - `DB_USER` et `DB_PASS` doivent correspondre à ton MySQL (par défaut : `root` / vide)

5. Ouvrir dans le navigateur :
   - Site public : `http://localhost/Traiteur_Elmoussaoui/`
   - Espace admin : `http://localhost/Traiteur_Elmoussaoui/admin/login.php`

## Identifiants admin (démo)

- Email : `admin@traiteur-elmoussaoui.ma`
- Mot de passe : `Admin@2025`

⚠️ Pense à changer ce mot de passe avant la mise en ligne publique.

## Notes importantes

- Tous les fichiers sont en `.php` (même les pages sans logique base de données),
  pour rester cohérent et permettre d'ajouter facilement du PHP plus tard.
- Chaque fichier `.php` inclut automatiquement `includes/config.php` en première ligne,
  ce qui connecte la page à la base de données MySQL.
- Le dossier `api/` est prévu pour recevoir les scripts qui traitent les formulaires
  (réservation, contact) — actuellement vide, à remplir à la prochaine étape.
