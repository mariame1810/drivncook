-- Script d'initialisation de la base de données DrivnCook
-- Ce script crée les tables de base nécessaires au fonctionnement de l'application

CREATE DATABASE IF NOT EXISTS drivncook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE drivncook;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des commandes
CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'en_preparation', 'livree', 'annulee') DEFAULT 'en_attente',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_livraison DATETIME NULL,
    adresse_livraison TEXT,
    commentaires TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des produits
CREATE TABLE IF NOT EXISTS produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des lignes de commande
CREATE TABLE IF NOT EXISTS ligne_commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- Table des camions (pour la livraison)
CREATE TABLE IF NOT EXISTS camions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    immatriculation VARCHAR(20) UNIQUE NOT NULL,
    capacite INT DEFAULT 100,
    statut ENUM('disponible', 'en_service', 'maintenance') DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des entrepôts
CREATE TABLE IF NOT EXISTS entrepots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse TEXT NOT NULL,
    ville VARCHAR(100) NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    telephone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des franchises
CREATE TABLE IF NOT EXISTS franchises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    telephone VARCHAR(20),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des documents
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('cni', 'kbis', 'autre') NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(500) NOT NULL,
    statut ENUM('en_attente', 'valide', 'refuse') DEFAULT 'en_attente',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertion de données de test (optionnel)
INSERT IGNORE INTO users (email, password, nom, prenom, telephone) VALUES 
('admin@drivncook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System', '0123456789');

INSERT IGNORE INTO produits (nom, description, prix, stock) VALUES 
('Produit Test 1', 'Description du produit test 1', 19.99, 100),
('Produit Test 2', 'Description du produit test 2', 29.99, 50),
('Produit Test 3', 'Description du produit test 3', 39.99, 25);

INSERT IGNORE INTO camions (nom, immatriculation, capacite) VALUES 
('Camion 1', 'ABC-123-XY', 150),
('Camion 2', 'DEF-456-ZW', 200);

INSERT IGNORE INTO entrepots (nom, adresse, ville, code_postal, telephone) VALUES 
('Entrepôt Central', '123 Rue de l\'Entrepôt', 'Paris', '75001', '0123456789'),
('Entrepôt Sud', '456 Avenue du Sud', 'Lyon', '69000', '0123456788');
