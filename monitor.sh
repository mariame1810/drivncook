#!/bin/bash

# Script de monitoring pour DrivnCook
# Usage: ./monitor.sh [status|logs|health|resources]

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

# Fonction pour afficher le statut des conteneurs
show_status() {
    log_info "========================================"
    log_info "Statut des conteneurs DrivnCook"
    log_info "========================================"
    echo
    
    if ! docker-compose ps | grep -q "Up"; then
        log_error "Aucun conteneur en cours d'exécution"
        return 1
    fi
    
    echo "Conteneurs actifs:"
    docker-compose ps
    echo
    
    # Vérifier la connectivité des services
    log_info "Vérification de la connectivité des services..."
    
    # Test du serveur web
    if curl -s http://localhost > /dev/null; then
        log_success "✓ Serveur web accessible"
    else
        log_error "✗ Serveur web inaccessible"
    fi
    
    # Test de la base de données
    if docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} -e "SELECT 1" &> /dev/null; then
        log_success "✓ Base de données accessible"
    else
        log_error "✗ Base de données inaccessible"
    fi
    
    # Test de phpMyAdmin
    if curl -s http://localhost:8080 > /dev/null; then
        log_success "✓ phpMyAdmin accessible"
    else
        log_warning "⚠ phpMyAdmin inaccessible"
    fi
}

# Fonction pour afficher les logs
show_logs() {
    local service=${1:-}
    
    if [ -n "$service" ]; then
        log_info "Affichage des logs pour le service: $service"
        docker-compose logs -f "$service"
    else
        log_info "Affichage des logs de tous les services"
        docker-compose logs -f
    fi
}

# Fonction pour vérifier la santé des services
health_check() {
    log_info "========================================"
    log_info "Vérification de santé des services"
    log_info "========================================"
    echo
    
    # Variables de couleur pour les statuts
    local healthy="${GREEN}HEALTHY${NC}"
    local unhealthy="${RED}UNHEALTHY${NC}"
    local warning="${YELLOW}WARNING${NC}"
    
    # Vérification du conteneur app
    log_info "Application PHP-FPM:"
    if docker-compose exec -T app php -v > /dev/null 2>&1; then
        echo -e "  Status: $healthy"
        echo -e "  PHP Version: $(docker-compose exec -T app php -r 'echo PHP_VERSION;')"
    else
        echo -e "  Status: $unhealthy"
    fi
    echo
    
    # Vérification du conteneur webserver
    log_info "Serveur Web (Nginx):"
    if docker-compose exec -T webserver nginx -t > /dev/null 2>&1; then
        echo -e "  Status: $healthy"
        echo -e "  Config: Valid"
    else
        echo -e "  Status: $unhealthy"
        echo -e "  Config: Invalid"
    fi
    echo
    
    # Vérification de la base de données
    log_info "Base de données (MySQL):"
    if docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} -e "SHOW STATUS LIKE 'Uptime'" 2>/dev/null | grep -q "Uptime"; then
        echo -e "  Status: $healthy"
        local uptime=$(docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} -e "SHOW STATUS LIKE 'Uptime'" 2>/dev/null | tail -1 | awk '{print $2}')
        echo -e "  Uptime: ${uptime} seconds"
    else
        echo -e "  Status: $unhealthy"
    fi
    echo
    
    # Vérification de l'espace disque
    log_info "Espace disque des volumes:"
    docker system df
    echo
}

# Fonction pour afficher l'utilisation des ressources
show_resources() {
    log_info "========================================"
    log_info "Utilisation des ressources"
    log_info "========================================"
    echo
    
    # Statistiques des conteneurs
    log_info "Utilisation CPU/Mémoire par conteneur:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}\t{{.NetIO}}\t{{.BlockIO}}"
    echo
    
    # Espace disque utilisé par Docker
    log_info "Utilisation de l'espace disque Docker:"
    docker system df
    echo
    
    # Taille des volumes
    log_info "Taille des volumes:"
    docker volume ls -q | while read volume; do
        size=$(docker run --rm -v "$volume":/data alpine du -sh /data 2>/dev/null | cut -f1)
        echo "  $volume: $size"
    done
}

# Fonction de backup de la base de données
backup_database() {
    local backup_dir="./backups"
    local timestamp=$(date +"%Y%m%d_%H%M%S")
    local backup_file="drivncook_backup_$timestamp.sql"
    
    # Créer le dossier de backup s'il n'existe pas
    mkdir -p "$backup_dir"
    
    log_info "Création d'une sauvegarde de la base de données..."
    
    docker-compose exec -T db mysqldump \
        -u root \
        -p${DB_ROOT_PASS:-root_password} \
        ${DB_NAME:-drivncook} > "$backup_dir/$backup_file"
    
    if [ $? -eq 0 ]; then
        log_success "Sauvegarde créée: $backup_dir/$backup_file"
        
        # Compresser la sauvegarde
        gzip "$backup_dir/$backup_file"
        log_success "Sauvegarde compressée: $backup_dir/$backup_file.gz"
    else
        log_error "Erreur lors de la création de la sauvegarde"
        return 1
    fi
}

# Fonction principale
main() {
    case "${1:-status}" in
        "status")
            show_status
            ;;
        "logs")
            show_logs "$2"
            ;;
        "health")
            health_check
            ;;
        "resources")
            show_resources
            ;;
        "backup")
            backup_database
            ;;
        "all")
            show_status
            echo
            health_check
            echo
            show_resources
            ;;
        *)
            echo "Usage: $0 {status|logs|health|resources|backup|all}"
            echo
            echo "Commandes:"
            echo "  status     - Afficher le statut des conteneurs"
            echo "  logs       - Afficher les logs (optionnel: nom du service)"
            echo "  health     - Vérifier la santé des services"
            echo "  resources  - Afficher l'utilisation des ressources"
            echo "  backup     - Créer une sauvegarde de la base de données"
            echo "  all        - Afficher toutes les informations"
            echo
            echo "Exemples:"
            echo "  $0 status"
            echo "  $0 logs app"
            echo "  $0 health"
            exit 1
            ;;
    esac
}

# Vérifier que Docker Compose est disponible
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    log_error "Docker Compose n'est pas disponible"
    exit 1
fi

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "docker-compose.yml" ]; then
    log_error "Fichier docker-compose.yml non trouvé. Assurez-vous d'être dans le bon répertoire."
    exit 1
fi

main "$@"
