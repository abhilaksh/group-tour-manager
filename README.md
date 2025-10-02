Tour Ops SaaS - The Operating System for Group Travel
Tour Ops SaaS is a full-stack, multi-tenant platform designed to be the central nervous system for student and group tour companies. It replaces the chaos of spreadsheets, email chains, and disconnected documents with a single, intelligent system for managing the entire tour lifecycle.

Core Features
Tour Product Management: Create, manage, and version master TourTemplates for your core product offerings.

Scenario Planner: A powerful "what-if" tool to rapidly build and price multiple trip options for clients.

End-to-End Tour Management: A five-stage workflow guiding you from proposal to post-tour reconciliation.

Integrated Financial Hub: Manage multi-currency costing, supplier payables, and client installment plans.

Automated Logistics: Streamline complex processes with visa workflow templates and compliance checklists.

Centralized Communication & Documents: A single source of truth for all stakeholders.

On-Tour Mobile App: Empower your tour directors with offline access to all necessary information.

Business Intelligence Dashboard: Gain strategic insights into profitability, supplier performance, and sales trends.

Technology Stack
The project's full technical stack and architecture are detailed in ARCHITECTURE.md.

Getting Started
Prerequisites
PHP / Composer

Node.js / npm

MySQL

Valet (macOS) or Docker (Windows/Linux)

Installation
Clone the repository:

git clone [https://github.com/your-username/tour-saas.git](https://github.com/your-username/tour-saas.git)
cd tour-saas

Backend Setup (/api directory):

cd api
composer install
cp .env.example .env
php artisan key:generate
# Configure your .env file with database and AWS credentials
php artisan migrate

Frontend Setup (/client directory):

cd ../client
npm install
cp .env.example .env.local
# Add your Google Maps API key to .env.local
npm run dev

Contributing
Please see CONTRIBUTING.md for details on how to contribute to this project.