# Projet Le Blog de Wolverine

### Cloner le projet

```
git clone https://github.com/SharpSparrow666/leblogdewolverine.git
```

### Déplacer le terminal dans le dossier cloné
```
cd leblogdewolverine
```

### Installer les vendors (pour recréer le dossier vendor)
```
composer install
```

### Création BDD
Configurer la connexion à la BDD dans le fichier .env (cours) puis commandes suivantes
```
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate

```

### Création des fixtures
```
symfony console doctrine:fixtures:load
```
Cette commande créera :
* Un compte admin (email: perso, password:perso)
* 10 comptes utilisateurs
* 50 articles

### Installation fichiers front-end des bundles (CKEditor)
```
symfony console assets:install public

```
### Lancer le serveur
```
symfony serve
```