Architecture Overview
This document outlines the high-level technical architecture for the Tour Ops SaaS platform.

Core Philosophy
The system is built on a "best-of-breed" philosophy, combining a powerful open-source core (Laravel, MySQL, React) with industry-standard managed services for critical infrastructure (AWS, Google Maps) to achieve a balance of control, scalability, and development speed.

System Components
Frontend: A Single Page Application (SPA) built with React (Vite) and TypeScript, styled with Tailwind CSS.

Backend: A monolithic API built with Laravel (PHP). It handles all business logic, data persistence, and authentication.

Database: A MySQL database.

Multi-Tenancy: Handled automatically at the database level using the archtechx/tenancy Laravel package.

Tour Product & BI Hub: A new core module that introduces TourTemplates as master blueprints. A BI system aggregates data from completed tours into analytics_snapshots for high-level reporting.

Key Third-Party Integrations
Authentication: Handled by Laravel Sanctum.

File Storage: AWS S3 for all user-uploaded documents.

Email: AWS SES for all transactional emails.

Mapping: Google Maps Platform API for location data, distance calculation, and mapping visuals.

AI: Integrates with a powerful external LLM (e.g., GPT-4, Gemini) for specific intelligent features.