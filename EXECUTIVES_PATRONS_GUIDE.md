# Executives & Patrons - System Guide

## Current Schema Analysis

### Two-Level Role System

The system has **TWO separate role fields**:

1. **`users.role`** - System access level (authentication)
   - Member
   - Executive
   - Patron
   - Admin

2. **`members.position`** - Organizational position (membership)
   - Member
   - Executive
   - Patron

### Additional Table

3. **`campus_executives`** - Specific executive positions
   - Links members to campuses
   - Stores specific position titles (e.g., "President", "Secretary")

---

## Recommended Treatment

### 1. **Regular Members**
**Who:** Students who are general members

**Characteristics:**
- `users.role` = "Member"
- `members.position` = "Member"
- Login with: Student ID
- Access: View profile, events, dues

**Academic Info Required:**
- ✅ Institution
- ✅ Campus
- ✅ Department
- ✅ Program
- ✅ Year
- ✅ Student ID

---

### 2. **Executives**
**Who:** Elected student leaders (President, Secretary, Treasurer, etc.)

**Characteristics:**
- `users.role` = "Executive" (system access)
- `members.position` = "Executive" (organizational role)
- Login with: Email OR Student ID
- Access: All member features + management features

**Academic Info:**
- ✅ Still students, so all academic fields required
- ✅ Have student ID
- ✅ Belong to institution/campus

**Additional Info:**
- Entry in `campus_executives` table with specific position
- Can manage members, events, payments, SMS

**Executive Positions:**
- President
- Vice President
- Secretary
- Treasurer
- Organizer
- Women's Organizer
- Communications Director
- etc.

---

### 3. **Patrons**
**Who:** Faculty advisors, alumni, or non-student supporters

**Characteristics:**
- `users.role` = "Patron" (system access)
- `members.position` = "Patron" (organizational role)
- Login with: Email (no student ID)
- Access: View and advisory features

**Academic Info:**
- ❌ NOT students
- ❌ No student ID
- ❌ No year/program
- ✅ May have institution affiliation (as staff/alumni)
- ✅ May have region/constituency

**Special Handling:**
- Student ID should be optional/nullable
- Year/Program should be optional
- Different form fields

---

## Recommended Implementation

### Option 1: Single Form with Conditional Fields (Current)

**Keep current `member_add.php` but:**

```php
// Show/hide fields based on position selection
<script>
document.getElementById('position').addEventListener('change', function() {
    const position = this.value;
    const academicFields = document.getElementById('academic-section');
    
    if (position === 'Patron') {
        // Make academic fields optional
        document.getElementById('student_id').required = false;
        document.getElementById('year').required = false;
        document.getElementById('program').required = false;
        
        // Show helper text
        academicFields.classList.add('optional-section');
    } else {
        // Make academic fields required
        document.getElementById('student_id').required = true;
        document.getElementById('year').required = true;
        document.getElementById('program').required = true;
    }
});
</script>
```

---

### Option 2: Separate Forms (Recommended)

**Create three different forms:**

1. **`member_add.php`** - For regular members
   - All academic fields required
   - Student ID required
   - Login with Student ID

2. **`executive_add.php`** - For executives
   - All academic fields required
   - Student ID required
   - Login with Email OR Student ID
   - Additional field: Executive Position
   - Creates entry in `campus_executives` table

3. **`patron_add.php`** - For patrons
   - Academic fields optional
   - No student ID
   - Login with Email only
   - Different field set

---

## Executive Position Management

### Campus Executives Table

```sql
campus_executives
├── campus_id (which campus they lead)
├── member_id (link to member)
├── position (President, Secretary, etc.)
└── appointed_at (when they took office)
```

### Usage

**When creating an Executive:**
1. Create user account (`users` table) with role="Executive"
2. Create member profile (`members` table) with position="Executive"
3. Create executive position (`campus_executives` table) with specific title

**Example:**
```php
// 1. Create user
$user->register($email, $password, 'Executive');

// 2. Create member
$member->create([
    'user_id' => $userId,
    'position' => 'Executive',
    // ... other fields
]);

// 3. Assign executive position
$query = "INSERT INTO campus_executives (campus_id, member_id, position) 
          VALUES (:campus_id, :member_id, :position)";
// position = "President", "Secretary", etc.
```

---

## Access Control Matrix

| Feature | Member | Executive | Patron | Admin |
|---------|--------|-----------|--------|-------|
| **View own profile** | ✅ | ✅ | ✅ | ✅ |
| **Edit own profile** | ✅ | ✅ | ✅ | ✅ |
| **View members list** | ❌ | ✅ | ✅ | ✅ |
| **Add members** | ❌ | ✅ | ❌ | ✅ |
| **Edit members** | ❌ | ✅ | ❌ | ✅ |
| **Delete members** | ❌ | ❌ | ❌ | ✅ |
| **Manage events** | ❌ | ✅ | ✅ | ✅ |
| **Send SMS** | ❌ | ✅ | ❌ | ✅ |
| **View payments** | Own | ✅ | ❌ | ✅ |
| **Record payments** | ❌ | ✅ | ❌ | ✅ |
| **View reports** | ❌ | ✅ | ✅ | ✅ |
| **Manage users** | ❌ | ❌ | ❌ | ✅ |
| **System settings** | ❌ | ❌ | ❌ | ✅ |

---

## Login Behavior

### Members
```
Login Field: Student ID
Example: UGCS12345
Password: ******
```

### Executives
```
Option 1 - Student ID: UGCS12345
Option 2 - Email: president@tescon.com
Password: ******
```

### Patrons
```
Login Field: Email
Example: patron@university.edu.gh
Password: ******
(No student ID)
```

### Admins
```
Login Field: Email
Example: admin@tescon.com
Password: ******
```

---

## Database Modifications Needed

### Make Student ID Nullable for Patrons

```sql
ALTER TABLE members 
MODIFY COLUMN student_id VARCHAR(50) NULL;

ALTER TABLE members 
MODIFY COLUMN year VARCHAR(20) NULL;

ALTER TABLE members 
MODIFY COLUMN program VARCHAR(100) NULL;
```

---

## Recommended Next Steps

### 1. **Immediate (Keep Current System)**
- Make student_id, year, program nullable in database
- Add conditional validation in member_add.php
- Show/hide fields based on position selection
- Update login to handle patrons (email only)

### 2. **Short Term (Better UX)**
- Create separate `executive_add.php`
- Create separate `patron_add.php`
- Add executive position management page
- Update sidebar navigation based on role

### 3. **Long Term (Full Features)**
- Executive dashboard with team management
- Patron dashboard with advisory features
- Executive position history/transitions
- Term limits and election management
- Executive performance tracking

---

## Example Scenarios

### Scenario 1: Adding a Campus President

```
1. Go to "Add Executive" (or member_add with position=Executive)
2. Fill in:
   - Email: john.president@gmail.com
   - Password: ******
   - Full Name: John Doe
   - Phone: 0241234567
   - Institution: University of Ghana
   - Campus: Main Campus - Legon
   - Student ID: UGCS12345
   - Position: Executive
   - Executive Position: President
3. System creates:
   - User account (role=Executive)
   - Member profile (position=Executive)
   - Campus executive entry (position=President)
```

### Scenario 2: Adding a Patron

```
1. Go to "Add Patron" (or member_add with position=Patron)
2. Fill in:
   - Email: dr.patron@ug.edu.gh
   - Password: ******
   - Full Name: Dr. Jane Smith
   - Phone: 0209876543
   - Institution: University of Ghana (as faculty)
   - Region: Greater Accra
   - Position: Patron
   - Skip: Student ID, Year, Program
3. System creates:
   - User account (role=Patron)
   - Member profile (position=Patron, student_id=NULL)
```

---

## Summary

### Current State
- ✅ Schema supports all three types
- ✅ Role-based access control in place
- ✅ Campus executives table exists
- ⚠️ Forms treat all as students

### Recommended Changes

**Priority 1: Database**
```sql
-- Make these nullable for patrons
ALTER TABLE members MODIFY student_id VARCHAR(50) NULL;
ALTER TABLE members MODIFY year VARCHAR(20) NULL;
ALTER TABLE members MODIFY program VARCHAR(100) NULL;
```

**Priority 2: Forms**
- Add conditional validation based on position
- Make academic fields optional for patrons
- Add executive position field for executives

**Priority 3: Features**
- Executive position management page
- Campus executive listing
- Patron-specific dashboard
- Executive-specific features

---

**Status:** Analysis Complete
**Recommendation:** Implement Priority 1 & 2 changes first
