-- Schema pour la base de données de gestion immobilière
-- À exécuter dans PostgreSQL

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS "user" (
    id SERIAL PRIMARY KEY,
    email VARCHAR(180) NOT NULL UNIQUE,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP(0) WITHOUT TIME ZONE
);

-- Table des propriétés
CREATE TABLE IF NOT EXISTS property (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    address VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    surface DOUBLE PRECISION NOT NULL,
    rooms INTEGER NOT NULL,
    bedrooms INTEGER NOT NULL,
    bathrooms INTEGER NOT NULL,
    rent_amount NUMERIC(10,2) NOT NULL,
    charges NUMERIC(10,2) NOT NULL,
    deposit NUMERIC(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'available',
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE,
    furnished BOOLEAN NOT NULL DEFAULT false,
    garage BOOLEAN NOT NULL DEFAULT false,
    balcony BOOLEAN NOT NULL DEFAULT false,
    elevator BOOLEAN NOT NULL DEFAULT false,
    energy_rating VARCHAR(100)
);

-- Table des locataires
CREATE TABLE IF NOT EXISTS tenant (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    birth_date DATE NOT NULL,
    address VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city VARCHAR(100) NOT NULL,
    profession VARCHAR(100),
    monthly_income NUMERIC(10,2),
    employer_name VARCHAR(100),
    employer_address VARCHAR(255),
    employer_phone VARCHAR(20),
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE,
    status VARCHAR(50) NOT NULL DEFAULT 'active'
);

-- Table des contrats de location
CREATE TABLE IF NOT EXISTS rental_contract (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL,
    tenant_id INTEGER NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    rent_amount NUMERIC(10,2) NOT NULL,
    charges NUMERIC(10,2) NOT NULL,
    deposit NUMERIC(10,2) NOT NULL,
    rent_due_day INTEGER NOT NULL DEFAULT 1,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    special_conditions TEXT,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE,
    contract_number VARCHAR(100) NOT NULL,
    CONSTRAINT FK_rental_contract_property FOREIGN KEY (property_id) REFERENCES property(id),
    CONSTRAINT FK_rental_contract_tenant FOREIGN KEY (tenant_id) REFERENCES tenant(id)
);

-- Table des paiements
CREATE TABLE IF NOT EXISTS payment (
    id SERIAL PRIMARY KEY,
    rental_contract_id INTEGER NOT NULL,
    amount NUMERIC(10,2) NOT NULL,
    payment_date DATE,
    due_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(100),
    reference VARCHAR(255),
    period VARCHAR(20) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE,
    late_fee NUMERIC(10,2),
    CONSTRAINT FK_payment_rental_contract FOREIGN KEY (rental_contract_id) REFERENCES rental_contract(id)
);

-- Table des maintenances
CREATE TABLE IF NOT EXISTS maintenance (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    priority VARCHAR(50) NOT NULL DEFAULT 'normal',
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    reported_date DATE NOT NULL,
    scheduled_date DATE,
    completed_date DATE,
    contractor_name VARCHAR(255),
    contractor_phone VARCHAR(20),
    contractor_email VARCHAR(255),
    estimated_cost NUMERIC(10,2),
    actual_cost NUMERIC(10,2),
    work_performed TEXT,
    notes TEXT,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE,
    reported_by VARCHAR(255),
    CONSTRAINT FK_maintenance_property FOREIGN KEY (property_id) REFERENCES property(id)
);

-- Index pour les performances
CREATE INDEX IF NOT EXISTS IDX_rental_contract_property ON rental_contract(property_id);
CREATE INDEX IF NOT EXISTS IDX_rental_contract_tenant ON rental_contract(tenant_id);
CREATE INDEX IF NOT EXISTS IDX_payment_rental_contract ON payment(rental_contract_id);
CREATE INDEX IF NOT EXISTS IDX_payment_period ON payment(period);
CREATE INDEX IF NOT EXISTS IDX_payment_status ON payment(status);
CREATE INDEX IF NOT EXISTS IDX_maintenance_property ON maintenance(property_id);
CREATE INDEX IF NOT EXISTS IDX_maintenance_status ON maintenance(status);
CREATE INDEX IF NOT EXISTS IDX_maintenance_priority ON maintenance(priority);

-- Données de démonstration

-- Utilisateurs de démonstration
INSERT INTO "user" (email, roles, password, first_name, last_name, phone, created_at) VALUES 
('admin@demo.com', '["ROLE_ADMIN"]', '$2y$13$6BbWjjX5cVm3EytQzxk5ru7DcLvXTDqE8g5qGjlxcgGJq8q1F2BtC', 'Admin', 'Système', '0123456789', NOW()),
('user@demo.com', '["ROLE_USER"]', '$2y$13$6BbWjjX5cVm3EytQzxk5ru7DcLvXTDqE8g5qGjlxcgGJq8q1F2BtC', 'Utilisateur', 'Demo', '0987654321', NOW())
ON CONFLICT (email) DO NOTHING;

-- Propriétés de démonstration
INSERT INTO property (title, description, address, postal_code, city, type, surface, rooms, bedrooms, bathrooms, rent_amount, charges, deposit, status, furnished, garage, balcony, elevator, energy_rating) VALUES 
('Appartement T3 Centre-Ville', 'Bel appartement de 65m² situé en plein centre-ville, proche de tous commerces et transports.', '15 Rue de la République', '75001', 'Paris', 'appartement', 65.0, 3, 2, 1, 1200.00, 150.00, 2400.00, 'available', false, false, true, true, 'C'),
('Maison avec Jardin', 'Charmante maison individuelle de 120m² avec jardin privatif de 200m².', '45 Avenue des Roses', '69007', 'Lyon', 'maison', 120.0, 5, 3, 2, 1500.00, 100.00, 3000.00, 'occupied', false, true, false, false, 'B'),
('Studio Moderne', 'Studio entièrement rénové et meublé, parfait pour étudiant ou jeune actif.', '8 Boulevard Saint-Michel', '33000', 'Bordeaux', 'studio', 25.0, 1, 0, 1, 650.00, 80.00, 1300.00, 'available', true, false, false, false, 'D'),
('Loft Industriel', 'Magnifique loft de 85m² dans un ancien bâtiment industriel rénové.', '12 Rue des Docks', '44000', 'Nantes', 'loft', 85.0, 2, 1, 1, 950.00, 120.00, 1900.00, 'available', false, false, true, true, 'A');

-- Locataires de démonstration
INSERT INTO tenant (first_name, last_name, email, phone, birth_date, address, postal_code, city, profession, monthly_income, employer_name, status) VALUES 
('Marie', 'Dupont', 'marie.dupont@email.com', '0123456789', '1985-03-15', '123 Rue de la Paix', '75008', 'Paris', 'Ingénieure', 3500.00, 'Tech Corp', 'active'),
('Jean', 'Martin', 'jean.martin@email.com', '0234567890', '1990-07-22', '456 Avenue de la Liberté', '69002', 'Lyon', 'Commercial', 2800.00, 'Vente Plus', 'active'),
('Sophie', 'Bernard', 'sophie.bernard@email.com', '0345678901', '1988-11-08', '789 Boulevard Victor Hugo', '33100', 'Bordeaux', 'Professeure', 2200.00, 'Lycée Jean Moulin', 'active');

-- Contrats de démonstration
INSERT INTO rental_contract (property_id, tenant_id, start_date, end_date, rent_amount, charges, deposit, rent_due_day, status, contract_number) VALUES 
(2, 1, '2024-01-01', '2025-12-31', 1500.00, 100.00, 3000.00, 1, 'active', 'CTR-2024-0001'),
(1, 2, '2024-03-15', '2025-03-14', 1200.00, 150.00, 2400.00, 5, 'terminated', 'CTR-2024-0002');

-- Paiements de démonstration
INSERT INTO payment (rental_contract_id, amount, payment_date, due_date, status, payment_method, period) VALUES 
(1, 1600.00, '2024-01-01', '2024-01-01', 'paid', 'virement', '2024-01'),
(1, 1600.00, '2024-02-01', '2024-02-01', 'paid', 'virement', '2024-02'),
(1, 1600.00, '2024-03-01', '2024-03-01', 'paid', 'virement', '2024-03'),
(1, 1600.00, '2024-04-05', '2024-04-01', 'paid', 'virement', '2024-04'),
(1, 1600.00, NULL, '2024-05-01', 'pending', NULL, '2024-05'),
(2, 1350.00, '2024-03-15', '2024-03-15', 'paid', 'cheque', '2024-03'),
(2, 1350.00, NULL, '2024-04-05', 'overdue', NULL, '2024-04');

-- Maintenances de démonstration
INSERT INTO maintenance (property_id, title, description, type, priority, status, reported_date, contractor_name, estimated_cost, reported_by) VALUES 
(1, 'Réparation chaudière', 'La chaudière ne chauffe plus correctement depuis hier matin.', 'reparation', 'urgent', 'pending', CURRENT_DATE - INTERVAL '2 days', NULL, 350.00, 'locataire'),
(2, 'Peinture salon', 'Rafraîchir la peinture du salon suite à des traces d humidité.', 'amelioration', 'normal', 'completed', CURRENT_DATE - INTERVAL '30 days', 'Peinture Pro', 450.00, 'proprietaire'),
(3, 'Entretien annuel', 'Entretien préventif annuel: vérification plomberie et électricité.', 'preventive', 'normal', 'in_progress', CURRENT_DATE - INTERVAL '7 days', 'Multi Services', 280.00, 'proprietaire');

-- Mise à jour du statut des propriétés
UPDATE property SET status = 'occupied' WHERE id IN (SELECT DISTINCT property_id FROM rental_contract WHERE status = 'active');

-- Mise à jour des séquences (important pour PostgreSQL)
SELECT setval('user_id_seq', (SELECT COALESCE(MAX(id), 1) FROM "user"));
SELECT setval('property_id_seq', (SELECT COALESCE(MAX(id), 1) FROM property));
SELECT setval('tenant_id_seq', (SELECT COALESCE(MAX(id), 1) FROM tenant));
SELECT setval('rental_contract_id_seq', (SELECT COALESCE(MAX(id), 1) FROM rental_contract));
SELECT setval('payment_id_seq', (SELECT COALESCE(MAX(id), 1) FROM payment));
SELECT setval('maintenance_id_seq', (SELECT COALESCE(MAX(id), 1) FROM maintenance));