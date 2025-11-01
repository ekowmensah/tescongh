# Voting Tables Structure

## Overview

The system now separates **geographical location data** from **voting/electoral data** to provide better data organization and accuracy.

## Table Structure

### 1. Geographical Tables (Campus Location)
These tables represent where institutions and campuses are physically located:

- **`regions`** - Ghana's 16 administrative regions (where campuses are located)
- **`constituencies`** - Parliamentary constituencies (where campuses are located)
- **`institutions`** - Universities/Polytechnics with their physical location
- **`campuses`** - Campus branches with their physical location

**Purpose:** Track where students study

### 2. Voting Tables (Electoral Information)
These tables represent where members are registered to vote:

- **`voting_regions`** - Electoral regions (where members vote)
- **`voting_constituencies`** - Electoral constituencies (where members vote)

**Purpose:** Track where students vote/are registered

## Key Differences

| Aspect | Geographical (Campus) | Voting (Electoral) |
|--------|----------------------|-------------------|
| **Purpose** | Where student studies | Where student votes |
| **Example** | Campus in Kumasi, Ashanti Region | Registered voter in Accra Central |
| **Tables** | `regions`, `constituencies` | `voting_regions`, `voting_constituencies` |
| **Member Field** | `campus_id` (links to campus location) | `voting_region_id`, `voting_constituency_id` |

## Members Table Structure

```sql
members
├── Campus Information (Where they study)
│   ├── institution_id → institutions → region_id, constituency_id
│   └── campus_id → campuses → region_id, constituency_id
│
└── Voting Information (Where they vote)
    ├── voting_region_id → voting_regions
    └── voting_constituency_id → voting_constituencies
```

## Example Scenario

**Student Profile:**
- **Name:** Kwame Mensah
- **Studies at:** KNUST Main Campus, Kumasi (Geographical: Ashanti Region, Kumasi Constituency)
- **Votes in:** Accra Central (Electoral: Greater Accra Region, Accra Central Constituency)

**Database Records:**
```sql
-- Campus location (geographical)
campus.region_id = 2 (Ashanti)
campus.constituency_id = 45 (Kumasi)

-- Member voting info (electoral)
member.voting_region_id = 1 (Greater Accra)
member.voting_constituency_id = 12 (Accra Central)
```

## Migration Steps

### Step 1: Run Migration SQL
```bash
Execute: migrations/create_voting_tables.sql
```

This will:
1. Create `voting_regions` table
2. Create `voting_constituencies` table
3. Add `voting_region_id` and `voting_constituency_id` to `members` table
4. Populate voting_regions with Ghana's 16 regions

### Step 2: Populate Voting Constituencies
You need to add constituencies to `voting_constituencies` table based on Electoral Commission data.

### Step 3: Update Member Forms
Update member registration/edit forms to include:
- Voting Region dropdown
- Voting Constituency dropdown (filtered by voting region)

### Step 4: Data Migration (Optional)
If you have existing data in text fields (`region`, `constituency`), you can migrate:

```sql
-- Migrate region data
UPDATE members m 
INNER JOIN voting_regions vr ON m.region = vr.name 
SET m.voting_region_id = vr.id 
WHERE m.region IS NOT NULL;

-- Migrate constituency data
UPDATE members m 
INNER JOIN voting_constituencies vc ON m.constituency = vc.name 
SET m.voting_constituency_id = vc.id 
WHERE m.constituency IS NOT NULL;
```

### Step 5: Clean Up Old Fields (Optional)
After confirming data migration:
```sql
ALTER TABLE members DROP COLUMN region;
ALTER TABLE members DROP COLUMN constituency;
ALTER TABLE members DROP COLUMN hails_from_region;
```

## Benefits

1. **Data Accuracy**
   - Clear separation between campus location and voting location
   - Prevents confusion between geographical and electoral data

2. **Better Reporting**
   - Can analyze members by campus location
   - Can analyze members by voting constituency
   - Can identify students voting outside their campus region

3. **Electoral Planning**
   - Identify TESCON members in each voting constituency
   - Plan campaign activities by voting regions
   - Track voter registration by constituency

4. **Data Integrity**
   - Foreign key constraints ensure valid regions/constituencies
   - Cascading deletes maintain referential integrity
   - Standardized region/constituency names

## API Usage

### Get All Voting Regions
```php
$votingRegion = new VotingRegion($db);
$regions = $votingRegion->getAll();
```

### Get Constituencies by Voting Region
```php
$votingConstituency = new VotingConstituency($db);
$constituencies = $votingConstituency->getByVotingRegion($regionId);
```

### Update Member Voting Info
```php
$member->update($memberId, [
    'voting_region_id' => 1,
    'voting_constituency_id' => 12
]);
```

## Frontend Integration

### Registration Form
```html
<select name="voting_region_id" id="votingRegion">
    <option value="">Select Voting Region</option>
    <?php foreach ($votingRegions as $vr): ?>
        <option value="<?= $vr['id'] ?>"><?= $vr['name'] ?></option>
    <?php endforeach; ?>
</select>

<select name="voting_constituency_id" id="votingConstituency">
    <option value="">Select Voting Constituency</option>
    <!-- Populated via AJAX based on voting region -->
</select>
```

### AJAX Constituency Loading
```javascript
$('#votingRegion').change(function() {
    const regionId = $(this).val();
    $.get('api/get_voting_constituencies.php?region_id=' + regionId, function(data) {
        $('#votingConstituency').html(data);
    });
});
```

## Summary

This structure provides:
- ✅ Clear separation of geographical vs electoral data
- ✅ Better data organization and integrity
- ✅ Improved reporting capabilities
- ✅ Support for electoral planning
- ✅ Scalable architecture for future features
