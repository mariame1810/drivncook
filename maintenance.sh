#!/bin/bash

# Script de maintenance pour DrivnCook
# Usage: ./maintenance.sh [update|backup|restore|migrate|clean]

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

# Variables
BACKUP_DIR="./backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Mise à jour de l'application
update_application() {
    log_info "Mise à jour de l'application DrivnCook..."
    
    # Vérifier que nous sommes dans un repo git
    if [ ! -d ".git" ]; then
        log_error "Ce n'est pas un repository Git"
        return 1
    fi
    
    # Sauvegarder avant la mise à jour
    log_info "Création d'une sauvegarde de sécurité..."
    backup_database
    
    # Arrêter les services
    log_info "Arrêt des services..."
    docker-compose down
    
    # Récupérer les dernières modifications
    log_info "Récupération des dernières modifications..."
    git stash push -m "Auto-stash before update $TIMESTAMP"
    git pull origin main
    
    # Mettre à jour les dépendances
    log_info "Mise à jour des dépendances..."
    docker-compose build --no-cache
    
    # Redémarrer les services
    log_info "Redémarrage des services..."
    docker-compose up -d
    
    # Attendre que les services soient prêts
    sleep 30
    
    # Vérifier que tout fonctionne
    if ./monitor.sh status > /dev/null 2>&1; then
        log_success "Mise à jour terminée avec succès"
    else
        log_error "Problème détecté après la mise à jour"
        log_warning "Consultez les logs avec: ./monitor.sh logs"
    fi
}

# Sauvegarde de la base de données
backup_database() {
    local backup_file="drivncook_backup_$TIMESTAMP.sql"
    
    # Créer le dossier de backup s'il n'existe pas
    mkdir -p "$BACKUP_DIR"
    
    log_info "Création d'une sauvegarde de la base de données..."
    
    # Vérifier que la base de données est accessible
    if ! docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} -e "SELECT 1" &> /dev/null; then
        log_error "Impossible de se connecter à la base de données"
        return 1
    fi
    
    # Créer la sauvegarde
    docker-compose exec -T db mysqldump \
        -u root \
        -p${DB_ROOT_PASS:-root_password} \
        --routines \
        --triggers \
        --single-transaction \
        ${DB_NAME:-drivncook} > "$BACKUP_DIR/$backup_file"
    
    if [ $? -eq 0 ]; then
        # Compresser la sauvegarde
        gzip "$BACKUP_DIR/$backup_file"
        log_success "Sauvegarde créée: $BACKUP_DIR/$backup_file.gz"
        
        # Nettoyer les anciennes sauvegardes (garder les 10 dernières)
        ls -t "$BACKUP_DIR"/drivncook_backup_*.sql.gz 2>/dev/null | tail -n +11 | xargs -r rm
        log_info "Anciennes sauvegardes nettoyées (gardé les 10 dernières)"
    else
        log_error "Erreur lors de la création de la sauvegarde"
        rm -f "$BACKUP_DIR/$backup_file"
        return 1
    fi
}

# Restauration de la base de données
restore_database() {
    local backup_file="$1"
    
    if [ -z "$backup_file" ]; then
        log_error "Fichier de sauvegarde non spécifié"
        echo "Usage: $0 restore fichier_de_sauvegarde.sql.gz"
        return 1
    fi
    
    if [ ! -f "$backup_file" ]; then
        log_error "Fichier de sauvegarde non trouvé: $backup_file"
        return 1
    fi
    
    log_warning "ATTENTION: Cette opération va remplacer toutes les données existantes!"
    read -p "Êtes-vous sûr de vouloir continuer? (y/N): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "Restauration annulée"
        return 0
    fi
    
    log_info "Restauration de la base de données depuis: $backup_file"
    
    # Décompresser si nécessaire
    if [[ "$backup_file" == *.gz ]]; then
        gunzip -c "$backup_file" | docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} ${DB_NAME:-drivncook}
    else
        docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} ${DB_NAME:-drivncook} < "$backup_file"
    fi
    
    if [ $? -eq 0 ]; then
        log_success "Restauration terminée avec succès"
    else
        log_error "Erreur lors de la restauration"
        return 1
    fi
}

# Migration de la base de données
migrate_database() {
    log_info "Exécution des migrations de base de données..."
    
    # Vérifier s'il y a des fichiers de migration
    if [ ! -d "docker/mysql/migrations" ]; then
        log_warning "Aucun dossier de migration trouvé"
        return 0
    fi
    
    # Créer la table de migrations si elle n'existe pas
    docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} ${DB_NAME:-drivncook} << 'EOF'
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
EOF
    
    # Exécuter les migrations non appliquées
    for migration_file in docker/mysql/migrations/*.sql; do
        if [ -f "$migration_file" ]; then
            filename=$(basename "$migration_file")
            
            # Vérifier si la migration a déjà été exécutée
            executed=$(docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} ${DB_NAME:-drivncook} -e "SELECT COUNT(*) FROM migrations WHERE filename='$filename';" | tail -1)
            
            if [ "$executed" -eq 0 ]; then
                log_info "Exécution de la migration: $filename"
                
                # Exécuter la migration
                docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} ${DB_NAME:-drivncook} < "$migration_file"
                
                if [ $? -eq 0 ]; then
                    # Marquer la migration comme exécutée
                    docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} ${DB_NAME:-drivncook} -e "INSERT INTO migrations (filename) VALUES ('$filename');"
                    log_success "Migration $filename exécutée avec succès"
                else
                    log_error "Erreur lors de l'exécution de la migration $filename"
                    return 1
                fi
            else
                log_info "Migration $filename déjà exécutée"
            fi
        fi
    done
    
    log_success "Toutes les migrations ont été exécutées"
}

# Nettoyage du système
clean_system() {
    log_warning "Nettoyage du système Docker..."
    
    read -p "Cela va supprimer les images, conteneurs et volumes inutilisés. Continuer? (y/N): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "Nettoyage annulé"
        return 0
    fi
    
    # Supprimer les conteneurs arrêtés
    log_info "Suppression des conteneurs arrêtés..."
    docker container prune -f
    
    # Supprimer les images inutilisées
    log_info "Suppression des images inutilisées..."
    docker image prune -f
    
    # Supprimer les volumes inutilisés
    log_info "Suppression des volumes inutilisés..."
    docker volume prune -f
    
    # Supprimer les réseaux inutilisés
    log_info "Suppression des réseaux inutilisés..."
    docker network prune -f
    
    log_success "Nettoyage terminé"
}

# Optimisation des performances
optimize_performance() {
    log_info "Optimisation des performances de la base de données..."
    
    # Analyser et optimiser les tables
    docker-compose exec -T db mysql -u root -p${DB_ROOT_PASS:-root_password} ${DB_NAME:-drivncook} << 'EOF'
-- Optimiser toutes les tables
SELECT CONCAT('OPTIMIZE TABLE ', table_name, ';') AS stmt 
FROM information_schema.tables 
WHERE table_schema = DATABASE();

-- Analyser les tables pour les statistiques
SELECT CONCAT('ANALYZE TABLE ', table_name, ';') AS stmt 
FROM information_schema.tables 
WHERE table_schema = DATABASE();
EOF
    
    log_success "Optimisation terminée"
}

# Fonction principale
main() {
    case "${1:-}" in
        "update")
            update_application
            ;;
        "backup")
            backup_database
            ;;
        "restore")
            restore_database "$2"
            ;;
        "migrate")
            migrate_database
            ;;
        "clean")
            clean_system
            ;;
        "optimize")
            optimize_performance
            ;;
        "full-maintenance")
            log_info "Maintenance complète du système..."
            backup_database
            migrate_database
            optimize_performance
            clean_system
            log_success "Maintenance complète terminée"
            ;;
        *)
            echo "Usage: $0 {update|backup|restore|migrate|clean|optimize|full-maintenance}"
            echo
            echo "Commandes:"
            echo "  update           - Mettre à jour l'application depuis Git"
            echo "  backup           - Créer une sauvegarde de la base de données"
            echo "  restore FILE     - Restaurer la base de données depuis un fichier"
            echo "  migrate          - Exécuter les migrations de base de données"
            echo "  clean            - Nettoyer le système Docker"
            echo "  optimize         - Optimiser les performances de la base de données"
            echo "  full-maintenance - Exécuter toutes les tâches de maintenance"
            echo
            echo "Exemples:"
            echo "  $0 backup"
            echo "  $0 restore backups/drivncook_backup_20241225_120000.sql.gz"
            echo "  $0 full-maintenance"
            exit 1
            ;;
    esac
}

# Vérifier les prérequis
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    log_error "Docker Compose n'est pas disponible"
    exit 1
fi

if [ ! -f "docker-compose.yml" ]; then
    log_error "Fichier docker-compose.yml non trouvé. Assurez-vous d'être dans le bon répertoire."
    exit 1
fi

main "$@"
