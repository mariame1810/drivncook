#!/bin/bash

# Script de déploiement DrivnCook avec Docker
# Usage: ./deploy.sh [production|development|ssl-setup]

set -e

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction d'affichage des messages
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Variables par défaut
MODE=${1:-development}
DOMAIN=${2:-drivncook.online}
EMAIL=${3:-admin@drivncook.online}

# Vérification des prérequis
check_requirements() {
    log_info "Vérification des prérequis..."
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker n'est pas installé. Veuillez l'installer avant de continuer."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        log_error "Docker Compose n'est pas disponible. Veuillez l'installer avant de continuer."
        exit 1
    fi
    
    log_success "Prérequis vérifiés"
}

# Configuration de l'environnement
setup_environment() {
    log_info "Configuration de l'environnement ($MODE)..."
    
    # Copier le fichier d'environnement si il n'existe pas
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            cp .env.example .env
            log_info "Fichier .env créé depuis .env.example"
        fi
    fi
    
    # Configurer les permissions pour le dossier uploads
    if [ -d "uploads" ]; then
        chmod -R 755 uploads
        log_info "Permissions configurées pour le dossier uploads"
    fi
    
    log_success "Environnement configuré"
}

# Construction et démarrage des conteneurs
deploy_containers() {
    log_info "Construction et démarrage des conteneurs..."
    
    # Arrêter les conteneurs existants
    if docker-compose ps -q | grep -q .; then
        log_info "Arrêt des conteneurs existants..."
        docker-compose down
    fi
    
    # Construction des images
    log_info "Construction des images Docker..."
    docker-compose build --no-cache
    
    # Démarrage des services
    log_info "Démarrage des services..."
    docker-compose up -d
    
    # Attendre que les services soient prêts
    log_info "Attente de la disponibilité des services..."
    sleep 30
    
    # Vérifier l'état des conteneurs
    if docker-compose ps | grep -q "Up"; then
        log_success "Conteneurs démarrés avec succès"
    else
        log_error "Erreur lors du démarrage des conteneurs"
        docker-compose logs
        exit 1
    fi
}

# Configuration SSL avec Let's Encrypt
setup_ssl() {
    if [ "$MODE" != "production" ]; then
        log_warning "SSL configuré uniquement en mode production"
        return
    fi
    
    if [ "$DOMAIN" = "localhost" ]; then
        log_warning "Domaine localhost détecté. SSL non configuré."
        return
    fi
    
    log_info "Configuration SSL pour le domaine: $DOMAIN"
    
    # Générer le certificat SSL
    docker-compose run --rm certbot certonly \
        --webroot \
        --webroot-path=/var/www/certbot \
        --email $EMAIL \
        --agree-tos \
        --no-eff-email \
        -d $DOMAIN
    
    if [ $? -eq 0 ]; then
        log_success "Certificat SSL généré avec succès"
        
        # Mettre à jour la configuration Nginx pour utiliser SSL
        sed -i.bak 's|# return 301 https|return 301 https|g' docker/nginx/conf.d/default.conf
        sed -i.bak "s|yourdomain.com|$DOMAIN|g" docker/nginx/conf.d/default.conf
        
        # Redémarrer Nginx
        docker-compose restart webserver
        log_success "Configuration SSL activée"
    else
        log_error "Erreur lors de la génération du certificat SSL"
    fi
}

# Initialisation de la base de données
init_database() {
    log_info "Initialisation de la base de données..."
    
    # Attendre que MySQL soit prêt
    log_info "Attente de la disponibilité de MySQL..."
    until docker-compose exec db mysql -u root -p${DB_ROOT_PASS:-root_password} -e "SELECT 1" &> /dev/null; do
        sleep 2
    done
    
    log_success "Base de données initialisée"
}

# Affichage des informations de déploiement
show_deployment_info() {
    log_success "========================================"
    log_success "Déploiement terminé avec succès!"
    log_success "========================================"
    echo
    log_info "Services disponibles:"
    echo "  - Application web: http://drivncook.online"
    if [ "$MODE" = "production" ] && [ "$DOMAIN" != "localhost" ]; then
        echo "  - HTTPS: https://$DOMAIN"
    fi
    echo "  - phpMyAdmin: http://localhost:8080"
    echo
    log_info "Informations de connexion MySQL:"
    echo "  - Host: drivncook.online:3306"
    echo "  - Database: ${DB_NAME:-drivncook}"
    echo "  - User: ${DB_USER:-drivncook_user}"
    echo "  - Password: ${DB_PASS:-drivncook_password}"
    echo
    log_info "Commandes utiles:"
    echo "  - Voir les logs: docker-compose logs -f"
    echo "  - Arrêter: docker-compose down"
    echo "  - Redémarrer: docker-compose restart"
    echo "  - Mettre à jour: ./deploy.sh $MODE"
    echo
}

# Fonction principale
main() {
    log_info "========================================"
    log_info "Script de déploiement DrivnCook"
    log_info "Mode: $MODE"
    log_info "Domaine: $DOMAIN"
    log_info "========================================"
    echo
    
    check_requirements
    setup_environment
    deploy_containers
    init_database
    
    if [ "$MODE" = "production" ] || [ "$1" = "ssl-setup" ]; then
        setup_ssl
    fi
    
    show_deployment_info
}

# Gestion des arguments
case "$1" in
    "production")
        MODE="production"
        main
        ;;
    "development")
        MODE="development"
        main
        ;;
    "ssl-setup")
        if [ -z "$2" ]; then
            log_error "Usage: ./deploy.sh ssl-setup DOMAIN EMAIL"
            exit 1
        fi
        DOMAIN="$2"
        EMAIL="$3"
        setup_ssl
        ;;
    "stop")
        log_info "Arrêt des conteneurs..."
        docker-compose down
        log_success "Conteneurs arrêtés"
        ;;
    "restart")
        log_info "Redémarrage des conteneurs..."
        docker-compose restart
        log_success "Conteneurs redémarrés"
        ;;
    "logs")
        docker-compose logs -f
        ;;
    "clean")
        log_warning "Suppression de tous les conteneurs et volumes..."
        read -p "Êtes-vous sûr? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            docker-compose down -v
            docker system prune -f
            log_success "Nettoyage terminé"
        fi
        ;;
    *)
        echo "Usage: $0 {production|development|ssl-setup|stop|restart|logs|clean}"
        echo
        echo "Options:"
        echo "  production    - Déploiement en mode production avec SSL"
        echo "  development   - Déploiement en mode développement"
        echo "  ssl-setup     - Configuration SSL uniquement"
        echo "  stop          - Arrêter tous les conteneurs"
        echo "  restart       - Redémarrer tous les conteneurs"
        echo "  logs          - Afficher les logs en temps réel"
        echo "  clean         - Supprimer tous les conteneurs et volumes"
        echo
        echo "Exemples:"
        echo "  $0 development"
        echo "  $0 production yourdomain.com admin@yourdomain.com"
        echo "  $0 ssl-setup yourdomain.com admin@yourdomain.com"
        exit 1
        ;;
esac
