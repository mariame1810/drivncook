# Guide de déploiement DrivnCook avec Docker

Ce guide explique comment déployer l'application DrivnCook en utilisant Docker avec SSL, Nginx et MySQL.

## Prérequis

- Docker et Docker Compose installés
- Un nom de domaine (pour le déploiement en production avec SSL)
- Ports 80, 443, 3306 et 8080 disponibles

## Architecture

L'infrastructure se compose de :
- **app** : Conteneur PHP-FPM avec l'application
- **webserver** : Nginx pour servir les fichiers et proxifier vers PHP
- **db** : Base de données MySQL 8.0
- **certbot** : Gestion automatique des certificats SSL Let's Encrypt
- **phpmyadmin** : Interface web pour gérer la base de données

## Installation rapide

### 1. Configuration

Copiez le fichier d'environnement :
```bash
cp .env.example .env
```

Modifiez les variables dans `.env` selon vos besoins :
```bash
# Configuration de base de données
DB_NAME=drivncook
DB_USER=drivncook_user
DB_PASS=votre_mot_de_passe_securise
DB_ROOT_PASS=mot_de_passe_root_securise

# Configuration du domaine (pour SSL)
DOMAIN=votre-domaine.com
EMAIL=votre-email@domaine.com
```

### 2. Déploiement en développement

```bash
./deploy.sh development
```

L'application sera accessible à :
- Application : http://localhost
- phpMyAdmin : http://localhost:8080

### 3. Déploiement en production

```bash
./deploy.sh production votre-domaine.com votre-email@domaine.com
```

L'application sera accessible à :
- HTTP : http://votre-domaine.com (redirigé vers HTTPS)
- HTTPS : https://votre-domaine.com
- phpMyAdmin : http://votre-domaine.com:8080

## Scripts disponibles

### Script de déploiement (`deploy.sh`)

```bash
# Déploiement en développement
./deploy.sh development

# Déploiement en production avec SSL
./deploy.sh production mon-domaine.com admin@mon-domaine.com

# Configuration SSL uniquement
./deploy.sh ssl-setup mon-domaine.com admin@mon-domaine.com

# Arrêter tous les conteneurs
./deploy.sh stop

# Redémarrer tous les conteneurs
./deploy.sh restart

# Voir les logs en temps réel
./deploy.sh logs

# Nettoyage complet (supprime tout)
./deploy.sh clean
```

### Script de monitoring (`monitor.sh`)

```bash
# Statut des conteneurs
./monitor.sh status

# Logs de tous les services
./monitor.sh logs

# Logs d'un service spécifique
./monitor.sh logs app

# Vérification de santé
./monitor.sh health

# Utilisation des ressources
./monitor.sh resources

# Sauvegarde de la base de données
./monitor.sh backup

# Toutes les informations
./monitor.sh all
```

## Configuration SSL

### Certificats Let's Encrypt

Le script configure automatiquement SSL en production :

1. Génère les certificats via Let's Encrypt
2. Configure Nginx pour HTTPS
3. Met en place la redirection HTTP → HTTPS
4. Configure le renouvellement automatique

### Configuration manuelle SSL

Si vous avez vos propres certificats :

1. Placez vos certificats dans `docker/certbot/conf/live/votre-domaine.com/`
2. Modifiez `docker/nginx/conf.d/default.conf` avec le bon chemin
3. Redémarrez Nginx : `docker-compose restart webserver`

## Base de données

### Connexion

Les paramètres de connexion par défaut :
- Host : `localhost:3306`
- Database : `drivncook`
- User : `drivncook_user`
- Password : `drivncook_password`

### phpMyAdmin

Accessible à http://localhost:8080 avec les mêmes identifiants.

### Sauvegarde

```bash
# Sauvegarde automatique
./monitor.sh backup

# Sauvegarde manuelle
docker-compose exec db mysqldump -u root -p drivncook > backup.sql
```

### Restauration

```bash
# Restaurer depuis une sauvegarde
docker-compose exec -T db mysql -u root -p drivncook < backup.sql
```

## Maintenance

### Mise à jour de l'application

```bash
# Arrêter les services
./deploy.sh stop

# Récupérer les dernières modifications
git pull

# Redéployer
./deploy.sh development  # ou production

# Vérifier le statut
./monitor.sh status
```

### Nettoyage

```bash
# Supprimer les conteneurs arrêtés
docker container prune

# Supprimer les images inutilisées
docker image prune

# Nettoyage complet (ATTENTION : supprime tout)
./deploy.sh clean
```

## Résolution de problèmes

### Les conteneurs ne démarrent pas

```bash
# Vérifier les logs
./monitor.sh logs

# Vérifier l'état
./monitor.sh status

# Reconstruire les images
docker-compose build --no-cache
```

### Problèmes de permissions

```bash
# Corriger les permissions du dossier uploads
chmod -R 755 uploads
chown -R www-data:www-data uploads
```

### Base de données inaccessible

```bash
# Vérifier l'état de MySQL
./monitor.sh health

# Redémarrer la base de données
docker-compose restart db

# Vérifier les logs MySQL
./monitor.sh logs db
```

### SSL ne fonctionne pas

```bash
# Vérifier les certificats
docker-compose exec certbot certbot certificates

# Renouveler les certificats
docker-compose exec certbot certbot renew

# Vérifier la configuration Nginx
docker-compose exec webserver nginx -t
```

## Sécurité

### Bonnes pratiques

1. **Mots de passe** : Utilisez des mots de passe forts dans `.env`
2. **Firewall** : Limitez l'accès aux ports (3306, 8080)
3. **SSL** : Utilisez toujours HTTPS en production
4. **Sauvegardes** : Sauvegardez régulièrement la base de données
5. **Mises à jour** : Maintenez Docker et les images à jour

### Variables d'environnement sensibles

Ne commitez jamais le fichier `.env` dans Git. Utilisez `.env.example` comme template.

## Performance

### Optimisations

1. **Cache** : Nginx met en cache les fichiers statiques
2. **Compression** : Gzip activé pour les ressources
3. **HTTP/2** : Activé avec SSL
4. **PHP-FPM** : Configuration optimisée pour les performances

### Monitoring

Utilisez `./monitor.sh resources` pour surveiller l'utilisation des ressources.

## Support

Pour toute question ou problème :
1. Vérifiez les logs : `./monitor.sh logs`
2. Consultez ce guide
3. Contactez l'équipe de développement
