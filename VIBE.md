Vibe Prompt: Build an Intelligent, Full-Stack Tour Operations SaaS
1. Project Vision
Your task is to build a full-stack, multi-tenant SaaS platform that serves as the strategic operating system for student and group tour companies. The system must be more flexible than a spreadsheet for initial planning and more powerful than any generic CRM for live operations. It will be architected around a best-practice operational workflow, guiding users from initial inquiry to post-tour analysis, with a special focus on the complexities of overseas travel.

2. The "Pragmatic Best-of-Breed" Technology Stack
Frontend: React (Vite) with Tailwind CSS.

Backend: Laravel for robust API, business logic, and queue management.

Database: MySQL.

Authentication: Laravel Sanctum.

Multi-Tenancy: archtechx/tenancy package for automatic, secure data isolation.

File Storage: AWS S3.

Email: AWS SES (Simple Email Service).

Queues: Database Queue Driver (Laravel's built-in).

Maps: Google Maps Platform API.

AI: Proprietary LLM (e.g., OpenAI GPT-4, Google Gemini) via API.

3. Core Workflow: The Tour Lifecycle
The application is built around five operational stages:

Scenario Planner: A flexible sandbox for rapid "what-if" tour costing.

Proposal & Booking: Converts a scenario into a formal, signed agreement.

Pre-Departure Logistics: Manages automated checklists for visas and health & safety.

Live Tour Operations: A mobile app for on-the-ground tour management.

Post-Tour Reconciliation: Analyzes financial performance and profitability.

4. Database Schema
The complete database schema is defined in the comprehensive_schema.md file. The schema is designed for MySQL and is fully compatible with the chosen technology stack.

5. Enhanced AI Integrations
Implement the following AI-powered features. The prompts should be structured and concise.

AI Itinerary Generation (POST /ai/generate-itinerary)
Role: Expert tour operations planner.

Task: Create a logical, day-by-day tour itinerary based on a user's natural language request (e.g., "10-day UK soccer tour") and a provided library of the company's tour Components.

Output: A JSON array of ItineraryItem objects.

AI Risk Assessment (POST /ai/assess-risk)
Role: Global security analyst.

Task: Scan official government travel advisories (e.g., US State Dept, UK FCDO) and recent news for the given destination and dates.

Output: A JSON object containing: riskLevel (enum: 'low', 'medium', 'high'), a summary (string), and sources (array of URLs).

AI Rooming Optimization (POST /ai/optimize-rooming)
Role: Logistics optimization engine.

Task: Assign participants to rooms to achieve the minimum possible total accommodation cost, respecting gender constraints (no mixed-gender sharing in multi-person rooms).

Input: JSON containing a list of participants (with gender) and a list of available room_types (with capacity and cost).

Output: A JSON object representing the optimized rooming list (e.g., { "room_type_id_1": [participant_id_1, participant_id_2] }).

6. Key Third-Party Integrations
This stack relies on robust, managed services to accelerate development and ensure scalability:

AWS: For S3 (File Storage) and SES (Email).

Google Cloud Platform: For the Google Maps API.

AI Provider (e.g., OpenAI): For generative AI features.