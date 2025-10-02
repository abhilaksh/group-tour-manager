Core System Logic and Functions (Expanded for Roles & Edge Cases)
This document outlines the complete business logic for the Tour Operations SaaS platform. It is structured around the core workflows of a tour operator and has been expanded to define user role permissions, primary flows, and critical edge cases for each function. The purpose of this document is to serve as the definitive blueprint for developers, ensuring the system is built to be robust, secure, and efficient.

Defined User Roles:
Internal Roles
Admin: (e.g., Brenda) Possesses full system access. Manages company settings, users, finances, and performs final approvals.

Planner: (e.g., Alex) The core operational user. Manages templates, scenarios, and confirmed tours.

Tour Director: (e.g., Carlos) The on-the-ground user with access to live tour data via a mobile interface.

Finance: Possesses read-only or specific permissions to view financial data, run reports, and manage reconciliation.

External Roles (Portal & App Access)
Client/Organizer: (e.g., a school administrator) The primary contact for a tour group. Can view and accept proposals, monitor overall group progress (payments, forms), and communicate with the Planner.

Traveler: (e.g., a student) A participant on the tour. Can view their personal itinerary, submit required documents and forms (e.g., passport scan), and receive on-tour notifications.

Parent/Guardian: The individual legally and financially responsible for a Traveler. Can make payments, sign consent forms, update medical information, and view tour information.

Chaperone: A non-paying tour participant with elevated on-tour responsibilities. Has access to a simplified manifest for their assigned group of Travelers and can assist the Tour Director.

Module 1: Product & Scenario Logic (The Strategic Planning Engine)
Principle: Empower operators to design repeatable products and rapidly model "what-if" scenarios for clients.

(This module is primarily for internal roles. External roles do not interact here.)

1.1. Tour Template Management
createTourTemplate(details)
Roles: Admin, Planner

Primary Flow:

Validates that the provided template details meet all requirements (e.g., unique name).

Creates a new tour_templates record in the database.

Assigns an initial version number of 1.

Logs the creation event in an audit trail.

Edge Cases:

Duplicate Name: The system must return a validation error if a template with the same name already exists to prevent confusion.

Incomplete Data: The system must validate that all required fields are present before attempting to save the record.

versionTourTemplate(templateId)
Roles: Admin, Planner

Primary Flow:

Locates the existing tour_templates record by its templateId.

Creates a new tour_templates record, performing a deep copy of all data from the original.

The new record's version number is incremented from the original's version.

The original record's status may be optionally set to 'archived' to hide it from default views.

Edge Cases:

Invalid templateId: The system must return a "Not Found" error if the specified templateId does not exist.

1.2. Scenario Creation & Pricing (The "Spreadsheet Killer")
createScenario(details, fromTemplateId)
Roles: Planner

Primary Flow:

Creates a new scenarios record with the provided details.

If fromTemplateId is provided, it populates the scenario_data JSON by copying the itinerary and costing information from the specified TourTemplate.

Edge Cases:

Invalid fromTemplateId: The system should still create the blank scenario but return a non-blocking warning to the user interface that the template could not be loaded.

calculateScenarioPrice(scenarioId)
Roles: Planner

Primary Flow: This is the core live budget engine, executed whenever a financially relevant parameter changes.

Fetches the current state of the scenario_data JSON for the given scenarioId.

Aggregates all active cost items, grouping them by their "buy" currency.

For each currency group, it converts the total cost to the company's base currency using a live API call or a user-locked exchange rate.

It calculates costs subject to tiered pricing (e.g., transportation) by calling getTieredCost() with the current participant count.

It calculates the total cost for all non-paying participants (Chaperones, Staff) and redistributes this cost evenly among the paying participants by calling calculateAbsorbedCost().

It applies any company-defined contingency percentages and markup rules to the total net cost.

It returns a complete pricing object, including per-person cost, total revenue, total cost, gross profit, and profit margin.

Edge Cases:

Zero Paying Participants: If the number of paying participants is zero, the calculateAbsorbedCost function must handle this gracefully to avoid a division-by-zero error. The price per person should be returned as "Not Applicable" or zero.

Missing Exchange Rate: If the currency conversion service is unavailable, the system must use the last known cached rate and clearly flag the price in the user interface as "provisional".

Incomplete Cost Data: If a scenario item is missing a cost, it must be calculated as zero and visually flagged in the scenario planner for manual review.

Misconfigured Tiers: If supplier cost tiers are misconfigured (e.g., overlapping participant numbers), the system should use the first valid tier it finds and raise a high-priority warning for an Admin to correct the data.

1.3. Component Management (The Building Blocks)
Principle: Create a library of reusable, pre-costed tour elements to accelerate scenario planning.

createComponent(details)
Roles: Admin, Planner

Primary Flow:

Validates the incoming details to ensure all required fields (e.g., name, type, default cost, currency) are present.

Checks that the provided supplier_id exists and is valid.

Creates a new record in the components table.

Logs the creation event in an audit trail for accountability.

Edge Cases:

Duplicate Name: The system should prevent the creation of a component with the exact same name to avoid confusion in the component library. A validation error should be returned.

Invalid Supplier: If the supplier_id provided in the details does not correspond to an existing record in the suppliers table, the operation must fail with an error.

Incomplete Costing: The system must require a default_cost and currency to be set. A component cannot be created without a price, even if that price is zero.

updateComponent(componentId, details)
Roles: Admin, Planner

Primary Flow:

Finds the existing component by its componentId.

Validates the incoming details for correctness.

Updates the record in the components table with the new information.

Logs the update event, ideally noting which fields were changed for a clear audit history.

Edge Cases:

Invalid componentId: The system must return a "Not Found" error if the component does not exist.

No Impact on Existing Scenarios: It is critical to establish that updating a master component in the library does not retroactively change the data in existing scenarios or templates. When a component is added to a scenario, its data is copied (denormalized) into the scenario_data JSON. This prevents historical data from being altered unintentionally.

archiveComponent(componentId)
Roles: Admin, Planner

Primary Flow:

Finds the existing component by its componentId.

Updates its status to 'archived'. The record is not deleted from the database.

Archived components will no longer appear in the library for selection when building new scenarios.

Edge Cases:

Archiving an In-Use Component: This action is permitted. Archiving a component simply prevents it from being added to new scenarios. Existing scenarios that already contain a copy of this component's data are unaffected and will continue to function correctly.

Module 2: Tour Lifecycle & Workflow Automation
Principle: Automate the administrative burden that begins once a tour is confirmed.

2.1. Lifecycle Transitions
convertScenarioToTour(scenarioId)
Roles: Planner

Primary Flow:

Runs a comprehensive validation check on the scenario to ensure all critical data is present (e.g., client contact, dates, participant counts).

Within a single database transaction, it performs the following:
a. Creates a new tours record.
b. Populates all related structured tables (itinerary_items, cost_items, participants, etc.) by parsing the scenario_data JSON.
c. Generates the initial portal access account for the designated Client/Organizer.

Upon successful transaction, it archives the scenario and sends a notification to the Admin and Finance roles.

Edge Cases:

Incomplete Scenario: The function must fail before starting the transaction if a Client/Organizer contact has not been assigned to the scenario. It will return a detailed list of all missing information to the user.

Concurrency: The use of a database transaction is mandatory to ensure that if any part of the conversion fails (e.g., an error while creating itinerary_items), the entire operation is rolled back, preventing orphaned or incomplete tour records.

updateTourStatus(tourId, newStatus)
Roles: Planner, Admin (can override)

Primary Flow:

Validates that the requested status transition is valid according to the defined state machine.

Updates the status field on the tours table.

If newStatus is 'confirmed', it triggers the creation of portal invitation jobs for all Travelers and Parents/Guardians to begin their onboarding process.

Edge Cases:

Invalid State Transition: The logic must enforce a strict state machine (e.g., a tour cannot move from planning directly to completed). Any attempt to do so must be rejected with a clear error message.

Cancellation: If newStatus is 'cancelled', this function must not proceed directly. Instead, it must trigger the dedicated cancelTour() workflow.

cancelTour(tourId, reason)
Roles: Admin

Primary Flow:

Updates the tour status to 'cancelled' and records the reason.

Freezes all financial operations for the tour.

Triggers a workflow to calculate any applicable cancellation fees for both suppliers and clients based on contracts and terms.

Sends a formal cancellation notice to the Client/Organizer, Parents/Guardians, and Travelers.

Creates tasks for the Finance role to process refunds and for the Planner role to cancel supplier services.

Edge Cases:

Cancellation Fees Calculation: The logic must accurately retrieve the cancellation policy for each supplier and for the tour itself to calculate fees. If a policy is not defined, it must be flagged for manual review.

2.2. Automated Workflow Engine
triggerWorkflow(tourId, workflowType)
Roles: System-triggered, Planner (can manually trigger)

Primary Flow:

Kicks off a series of automated tasks based on the tour's properties.

This includes assigning specific tasks directly to external roles, for example: "Submit Passport Scan" is assigned to the Traveler, while "Sign Medical Consent Form" is assigned to the Parent/Guardian.

Edge Cases:

No Matching Template: If no visa_workflow template exists for the tour's destination country, the system must assign a default, generic "Document Collection" task to all travelers and send a notification to an Admin to create a country-specific template for future use.

Module 3: Financial Management Logic
Principle: Provide absolute clarity and control over every dollar coming in and going out.

3.1. Payables & Receivables
generatePayablesSchedule(tourId)
Roles: Finance, Admin

Primary Flow:

Iterates through all cost_items associated with the tour.

For each item, it retrieves the payment terms from its linked supplier.

It creates a schedule of supplier_payments with due dates and amounts.

Edge Cases:

Missing Supplier Terms: If a supplier in the itinerary does not have default payment terms defined, the system must create a single payment record for the full amount, due on the tour's start date, and flag it prominently in the user interface for manual review and adjustment.

sendPaymentReminders()
Roles: System (automated scheduled job)

Primary Flow:

This job runs on a recurring schedule (e.g., daily).

It scans for all payment_plan_installments that are due within a predefined window (e.g., in 7 days).

For each upcoming payment, it queues a templated reminder email to be sent to the responsible Parent/Guardian or adult Traveler.

Edge Cases:

Bounced Emails: The system must track the delivery status of all emails. If an email bounces, a task must be automatically created and assigned to an Admin for manual follow-up.

Time Zones: Reminders must be queued to be sent during reasonable hours (e.g., 10:00 AM) in the client's local time zone, which is stored on their profile.

3.2. Financial Reconciliation
reconcileTour(tourId)
Roles: Finance, Admin

Primary Flow:

Fetches all budgeted cost_items for the tour.

Fetches all actual logged expenses for the tour.

Calculates the variance (the difference between budget and actual) for each line item.

Generates a final Profit and Loss summary report.

Edge Cases:

Uncategorized Expenses: Any logged expenses that are not linked to a budgeted item must be flagged and displayed in a separate "Uncategorized" section in the final reconciliation report for review.

Premature Reconciliation: The system must prevent this function from being executed on any tour that is not in the completed status to ensure all final costs have been logged.

Module 4: Live Operations & Communication
Principle: Empower the on-the-ground team with the information and tools they need to succeed.

4.1. Data Sync & Incident Management
syncForOffline(userId)
Roles: Tour Director, Chaperone

Primary Flow:

Identifies the user's role and their assigned active tours.

Packages all necessary data into a downloadable format for the mobile application. The data payload is tailored to the user's role (e.g., a Chaperone only receives data for their assigned travelers, not the entire group or financial information).

Edge Cases:

Partial Sync: If the network connection is lost mid-sync, the mobile application must be designed to discard the partial data packet and revert to the last known successful sync to prevent data corruption.

Delta Syncing: For efficiency, the function should have logic to only send data that has changed since the last successful sync, reducing data usage.

logIncident(tourId, details)
Roles: Tour Director, Planner, Admin. A Chaperone can log minor incidents for their assigned travelers, which are flagged for review by the Tour Director.

Primary Flow:

Creates a new incident record with a timestamp, category, and detailed description.

Triggers real-time notifications to relevant internal staff based on the incident's severity level.

Edge Cases:

Offline Logging: The mobile application MUST support offline incident logging. Reports created while offline are queued securely on the local device and are automatically synced to the server once a network connection is re-established.

Module 5: Business Intelligence & AI Services
Principle: Transform operational data into strategic business insights.

(This module is primarily for internal roles. External roles do not interact here.)

5.1. Analytics
getBusinessDashboardData()
Roles: Admin, Finance

Primary Flow:

Aggregates data from the analytics_snapshots table.

Calculates Key Performance Indicators (KPIs) such as Year-over-Year booking growth, average profit margin per TourTemplate, and top-performing suppliers.

Edge Cases:

No Data: If a new company has no completed tours, the dashboard must render a clean "empty state" with guidance on how data will appear once tours are completed, rather than showing errors or blank charts.

5.2. AI Service Integrations
Universal Edge Cases: All AI service calls must be wrapped in robust, multi-layered error handling.

API Failure: If an AI API call fails or times out, the system must gracefully fall back without crashing. For summarizeFinancials, it would simply display the raw data table. For generateItinerary, it would inform the user to try again later.

Nonsensical Response: If the AI returns a malformed (e.g., invalid JSON) or irrelevant response, the system must discard the response, log an error for internal review, and inform the user that the feature is temporarily unavailable.

Input Sanitization: All user-provided text sent to an AI model must be rigorously sanitized to prevent prompt injection attacks or the submission of malicious code.

Rate Limiting & Cost Tracking: The integration must respect API rate limits and include logging to track the cost of all AI API calls for budgeting purposes.