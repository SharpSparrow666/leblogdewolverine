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

### Lancer le serveur
```
symfony serve
```