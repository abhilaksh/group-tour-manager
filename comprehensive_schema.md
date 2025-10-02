Comprehensive Database Schema for Tour Operations SaaS
This document provides a deeply expanded MySQL schema that accounts for all features discussed. It is designed to be used with Laravel and the archtechx/tenancy package.

Module 1: Core Tenancy, Users & Roles
-- Each SaaS customer is a 'company'
CREATE TABLE companies (
  id CHAR(36) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users are managed by Laravel/Sanctum but linked to a company tenant
CREATE TABLE users (
  id CHAR(36) PRIMARY KEY,
  company_id CHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'planner', -- e.g., 'admin', 'planner', 'tour_director'
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

Module 2: Tour Planning & Lifecycle
-- Master blueprints for repeatable tours
CREATE TABLE tour_templates (
  id CHAR(36) PRIMARY KEY,
  company_id CHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  base_itinerary_data JSON, -- Stores the master component list and structure
  base_pricing_rules JSON, -- Stores default costs and markup rules
  is_active BOOLEAN DEFAULT true,
  version INT DEFAULT 1,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Flexible "what-if" planning space
CREATE TABLE scenarios (
  id CHAR(36) PRIMARY KEY,
  company_id CHAR(36) NOT NULL,
  parent_scenario_id CHAR(36) NULL, -- For easy duplication/versioning
  name VARCHAR(255) NOT NULL,
  version INT DEFAULT 1,
  scenario_data JSON,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_scenario_id) REFERENCES scenarios(id) ON DELETE SET NULL
);

-- Confirmed tours with structured data, often instantiated from a template
CREATE TABLE tours (
  id CHAR(36) PRIMARY KEY,
  company_id CHAR(36) NOT NULL,
  tour_template_id CHAR(36) NULL, -- Links a tour to its master template
  name VARCHAR(255) NOT NULL,
  status VARCHAR(50) DEFAULT 'planning', -- 'planning', 'confirmed', 'active', 'completed', 'cancelled'
  start_date DATE,
  end_date DATE,
  base_currency CHAR(3) NOT NULL DEFAULT 'USD',
  selling_currency CHAR(3) NOT NULL DEFAULT 'USD',
  locked_exchange_rate DECIMAL(10, 6),
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  FOREIGN KEY (tour_template_id) REFERENCES tour_templates(id) ON DELETE SET NULL
);

-- The detailed, day-by-day plan for a confirmed tour
CREATE TABLE itinerary_items (
  id CHAR(36) PRIMARY KEY,
  tour_id CHAR(36) NOT NULL,
  day_number INT NOT NULL,
  start_time TIME,
  end_time TIME,
  type VARCHAR(50), -- 'Accommodation', 'Transport', 'Activity', 'Meal', 'Flight'
  description TEXT,
  supplier_id CHAR(36) NULL,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- Reusable building blocks for the Scenario Planner and Templates
CREATE TABLE components (
  id CHAR(36) PRIMARY KEY,
  company_id CHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL, -- 'Accommodation', 'Transport', 'Activity', 'Meal'
  description TEXT,
  default_duration_minutes INT,
  default_cost DECIMAL(10, 2),
  currency CHAR(3),
  supplier_id CHAR(36) NULL,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

Module 3: Financial Hub
... (No changes to this module's schema)

-- Granular cost items linked to an itinerary item
CREATE TABLE cost_items (
  id CHAR(36) PRIMARY KEY,
  itinerary_item_id CHAR(36) NOT NULL,
  description VARCHAR(255),
  amount DECIMAL(10, 2) NOT NULL,
  currency CHAR(3) NOT NULL,
  amount_in_base_currency DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (itinerary_item_id) REFERENCES itinerary_items(id) ON DELETE CASCADE
);

-- Tracking payments OUT to suppliers
CREATE TABLE supplier_payments (
  id CHAR(36) PRIMARY KEY,
  tour_id CHAR(36) NOT NULL,
  supplier_id CHAR(36) NOT NULL,
  description VARCHAR(255),
  due_date DATE NOT NULL,
  paid_date DATE NULL,
  amount DECIMAL(10, 2) NOT NULL,
  currency CHAR(3) NOT NULL,
  status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'paid', 'overdue'
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
);

-- Defines the installment plan for a tour
CREATE TABLE payment_plans (
  id CHAR(36) PRIMARY KEY,
  tour_id CHAR(36) NOT NULL,
  name VARCHAR(255),
  total_amount DECIMAL(10, 2) NOT NULL,
  currency CHAR(3) NOT NULL,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);

-- Defines the individual installments within a plan
CREATE TABLE payment_plan_installments (
  id CHAR(36) PRIMARY KEY,
  payment_plan_id CHAR(36) NOT NULL,
  description VARCHAR(255),
  due_date DATE NOT NULL,
  amount DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (payment_plan_id) REFERENCES payment_plans(id) ON DELETE CASCADE
);

-- Tracking payments IN from participants
CREATE TABLE participant_payments (
  id CHAR(36) PRIMARY KEY,
  participant_id CHAR(36) NOT NULL,
  installment_id CHAR(36) NOT NULL,
  amount_paid DECIMAL(10, 2) NOT NULL,
  payment_date DATETIME NOT NULL,
  transaction_id VARCHAR(255),
  status VARCHAR(50) DEFAULT 'completed', -- 'completed', 'refunded'
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
  FOREIGN KEY (installment_id) REFERENCES payment_plan_installments(id) ON DELETE CASCADE
);

Module 4: Participants, Tasks & Documents
... (No changes to this module's schema)

CREATE TABLE participants (
  id CHAR(36) PRIMARY KEY,
  tour_id CHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  type VARCHAR(50) DEFAULT 'paying', -- 'paying', 'non-paying' (chaperone), 'staff'
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);

CREATE TABLE visa_workflows ( id CHAR(36) PRIMARY KEY, country_code VARCHAR(3), name VARCHAR(255), company_id CHAR(36) );
CREATE TABLE visa_workflow_steps ( id CHAR(36) PRIMARY KEY, workflow_id CHAR(36), step_order INT, title VARCHAR(255), description TEXT );

CREATE TABLE participant_tasks (
  id CHAR(36) PRIMARY KEY,
  participant_id CHAR(36) NOT NULL,
  title VARCHAR(255),
  due_date DATE,
  status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'completed', 'overdue'
  step_id CHAR(36) NULL,
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
  FOREIGN KEY (step_id) REFERENCES visa_workflow_steps(id) ON DELETE SET NULL
);

CREATE TABLE documents (
  id CHAR(36) PRIMARY KEY,
  company_id CHAR(36) NOT NULL,
  documentable_id CHAR(36) NOT NULL,
  documentable_type VARCHAR(255) NOT NULL,
  name VARCHAR(255),
  file_path TEXT NOT NULL,
  version INT DEFAULT 1,
  uploaded_by_user_id CHAR(36) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id)
);

Module 5: Supplier CRM & Communication
... (No changes to this module's schema)

CREATE TABLE suppliers (
  id CHAR(36) PRIMARY KEY,
  company_id CHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  contact_person VARCHAR(255),
  email VARCHAR(255),
  phone VARCHAR(50),
  notes TEXT,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE communication_channels (
  id CHAR(36) PRIMARY KEY,
  tour_id CHAR(36) NOT NULL,
  name VARCHAR(255) NOT NULL,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);

CREATE TABLE messages (
  id CHAR(36) PRIMARY KEY,
  channel_id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  content TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (channel_id) REFERENCES communication_channels(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

Module 6: Incident Management
... (No changes to this module's schema)

-- Log of incidents that occur on a tour
CREATE TABLE incidents (
  id CHAR(36) PRIMARY KEY,
  tour_id CHAR(36) NOT NULL,
  reported_by_user_id CHAR(36) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  category VARCHAR(50) NOT NULL, -- 'medical', 'logistical', 'behavioral', 'safety'
  status VARCHAR(50) DEFAULT 'open', -- 'open', 'resolved', 'monitoring'
  incident_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
  FOREIGN KEY (reported_by_user_id) REFERENCES users(id)
);

-- A running log of updates for a specific incident
CREATE TABLE incident_updates (
  id CHAR(36) PRIMARY KEY,
  incident_id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  update_text TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

Module 7: Business Intelligence & Analytics
-- Stores periodic snapshots of key metrics for reporting
CREATE TABLE analytics_snapshots (
  id CHAR(36) PRIMARY KEY,
  company_id CHAR(36) NOT NULL,
  tour_id CHAR(36) NOT NULL,
  snapshot_date DATE NOT NULL,
  total_revenue DECIMAL(12, 2),
  total_cost DECIMAL(12, 2),
  profit_margin DECIMAL(5, 2),
  participant_count INT,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);
