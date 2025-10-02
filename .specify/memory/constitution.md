<!--
Sync Impact Report:
- Version change: 0.0.0 → 1.0.0
- List of modified principles: None (initial creation)
- Added sections: Core Principles, Security, Development Workflow, Governance
- Removed sections: None
- Templates requiring updates:
  - ✅ .specify/templates/plan-template.md
  - ✅ .specify/templates/spec-template.md
  - ✅ .specify/templates/tasks-template.md
- Follow-up TODOs:
  - TODO(RATIFICATION_DATE): Set the official date of adoption.
-->

# Tour Ops SaaS Constitution

## Core Principles

### I. Best-of-Breed Architecture
The system is built on a "best-of-breed" philosophy, combining a powerful open-source core (Laravel, MySQL, React) with industry-standard managed services for critical infrastructure (AWS, Google Maps) to achieve a balance of control, scalability, and development speed.

### II. Test-First (NON-NEGOTIABLE)
TDD is mandatory. The Red-Green-Refactor cycle is strictly enforced. All new features or bug fixes must be accompanied by tests.

### III. Tour Lifecycle Workflow
The application is built around five operational stages: Scenario Planner, Proposal & Booking, Pre-Departure Logistics, Live Tour Operations, and Post-Tour Reconciliation. This structure must be reflected in the UI and backend logic.

### IV. Structured & Comprehensive Schema
The database schema, as defined in `comprehensive_schema.md`, is the single source of truth. All data structures must adhere to this schema.

### V. Focused AI Integration
AI will be integrated for specific, high-value features such as itinerary generation, risk assessment, and rooming optimization. AI features must be robust and handle API failures gracefully.

## Security

We are committed to ensuring the security of our application. All development must follow the security policy outlined in `SECURITY.md`. Vulnerabilities must be reported privately.

## Development Workflow

All contributions must adhere to the development workflow outlined in `CONTRIBUTING.md`. This includes using the Conventional Commits specification and ensuring all tests pass before submitting a pull request.

## Governance

This constitution supersedes all other practices. All PRs and reviews must verify compliance with these principles. The project is governed by the Contributor Covenant Code of Conduct.

**Version**: 1.0.0 | **Ratified**: TODO(RATIFICATION_DATE): Set the official date of adoption. | **Last Amended**: 2025-10-02
