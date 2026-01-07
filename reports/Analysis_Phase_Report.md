# Analysis Phase Report: Parent-Student Tracer System

## 1. Problem Statement
The primary challenge in modern educational management is the fragmentation of data across multiple institutions and the lack of real-time visibility for parents. Traditional systems often operate in silos, making it difficult for parents to track their children's progress across different academic years or schools. Furthermore, educational administrators face the hurdle of "Multi-Tenancy" management—ensuring that data remains strictly isolated between schools within a single shared platform. The "Tracer" problem specifically refers to the need for a continuous, event-driven timeline that captures attendance, grades, and teacher remarks, transforming static data into an actionable educational journey.

## 2. Use Case Descriptions

### UC-01: Add Tracer Activity (Actor: Teacher)
- **Objective**: To record a significant academic or behavioral event for a student.
- **Precondition**: The Teacher must be authenticated and assigned to the same `school_id` as the student.
- **Main Flow**:
    1. The Teacher selects a student from their class roster.
    2. The Teacher inputs activity details (Type: Grade, Attendance, or Remark).
    3. The system validates the input against domain rules (e.g., score ranges).
    4. The system persists the event in the `tracer_activities` repository.
- **Postcondition**: The activity is visible on the Parent's dashboard in real-time.

### UC-02: View Progress Timeline (Actor: Parent)
- **Objective**: To monitor the child's academic and behavioral history via a chronological timeline.
- **Precondition**: The Parent must be linked to the Student's `parent_id` and share the same `school_id`.
- **Main Flow**:
    1. The Parent logs into the portal.
    2. The system retrieves all students linked to the parent account.
    3. Upon selecting a student, the system fetches chronological events from the `TracerService`.
    4. The Parent applies filters (e.g., "Attendance Only") to refine the view.
- **Postcondition**: The Parent gains a comprehensive overview of the student's status without administrative delays.

### UC-03: Manage School Branding (Actor: Admin)
- **Objective**: To customize the portal’s visual aesthetics and academic settings per school.
- **Precondition**: The Actor must have the 'admin' role.
- **Main Flow**:
    1. The Admin navigates to the School Settings module.
    2. The Admin updates branding elements (Logo, Primary Color, Footer Text).
    3. The system applies these changes globally for all users under that specific `school_id`.
- **Postcondition**: The portal reflects the unique identity of the institution, ensuring a "Premium" experience.

## 3. Business Process (Activity Flow)
The data orchestration flow from recording to visualization follows a strict lifecycle:
1. **Trigger**: A Teacher records a final grade score in the grading module.
2. **Persistence**: The system saves the record to the `grades` table.
3. **Trace Generation**: The Business Logic layer (specifically the `TracerService`) detects the update and generates a corresponding entry in the `tracer_activities` table with an associated "Severity" (e.g., 'Success' for high scores).
4. **Notification/Availability**: The entry is tagged with the student’s index and the school’s isolation ID.
5. **Consumption**: When the Parent dashboard refreshes, the AJAX controller fetches this specific activity, rendering it as a Bento-box component on the vertical timeline.
