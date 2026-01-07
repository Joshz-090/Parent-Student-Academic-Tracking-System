# Design Phase Report: Parent-Student Tracer System

## 1. Object-Oriented Mapping (ORM Strategy)
The system employs a "Class-Table" mapping strategy to maintain a high degree of **High Cohesion**. Each database entity is mirrored by a PHP class that encapsulates its state and behavior.

- **`tracer_activities` Table → `TracerService`**: While the table stores the raw data, the `TracerService` acts as the domain coordinator, providing the logic to fetch, filter, and transform these records into Activity Objects.
- **`users` / `students` Tables → `User` / `Student` Models**: This represents a **Generalization** relationship. The `Student` class inherits properties from `User` (Encapsulation), extending it with academic-specific attributes.
- **`schools` Table → `School` Model**: Encapsulates institutional metadata and branding tokens, serving as the root for all tenant-based queries.

## 2. Multi-School Isolation Strategy
Technical isolation is achieved through a **Shared Database / Shared Schema** multi-tenancy design.
- **Global Filter**: Every query executed through the `Database::secureQuery` method is intercepted. This ensures that a `school_id` filter is automatically injected into the SQL's `WHERE` clause.
- **Security Impact**: This architectural decision leads to **Low Coupling** between the data Layer and the authentication layer. Developers do not need to manually remember to filter by school in every query; the core infrastructure enforces "Mandatory Access Control."

## 3. State Transition Logic: TracerActivity Lifecycle
A `TracerActivity` transitions through several logical states during its existence:
- **Created**: The event is triggered by a database write (e.g., a grade is saved).
- **Validated**: The `TracerService` ensures the activity is correctly mapped to a student within the active school context.
- **Active / Visible**: The activity is rendered on the frontend bento-grid.
- **Archived**: When an academic year closes, activities are logically archived (via `deleted_at`) but remain available for historical reporting.

## 4. Package Diagram & Physical Design
The directory structure represents a formal separation of tiers:
- **`/src/` (The Logic Tier)**: Contains the core "Engine" (Controllers, Models, Services). This folder represents the brain of the system, following PSR-4 standards for efficient autoloading.
- **`/public/` (The Presentation Tier)**: The only web-accessible folder. It contains the entry points (`index.php`) and assets (CSS, JS). This isolation significantly reduces the attack surface of the application.
- **`/docs/` & `/reports/` (The Meta-Information Package)**: Contains the system's blueprints. This organization demonstrates professional project management, distinguishing between "Phase Deliverables" and "Live Code."

## 5. Architectural Benefits
- **Maintenance**: Due to the **Modular Design**, a change in the attendance logic only requires an update to the `TracerService`, leaving the UI and Database layers untouched.
- **Scalability**: The use of **Typed Properties** and **Dependency Injection** (passing the Database instance to the Service) ensures the system can be expanded to support hundreds of schools with minimal performance degradation.
